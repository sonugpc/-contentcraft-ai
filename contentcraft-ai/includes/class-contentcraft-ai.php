<?php
/**
 * Main plugin class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Editor instance
     */
    public $editor;
    
    /**
     * Settings instance
     */
    public $settings;
    
    /**
     * API handler instance
     */
    public $api_handler;
    
    /**
     * Content processor instance
     */
    public $content_processor;
    
    /**
     * Get plugin instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        if (!defined('CONTENTCRAFT_AI_ABSPATH')) {
            define('CONTENTCRAFT_AI_ABSPATH', dirname(CONTENTCRAFT_AI_PLUGIN_PATH) . '/');
        }
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-settings.php';
        require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-api-handler.php';
        require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-content-processor.php';
        
        if (is_admin()) {
            require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-admin.php';
            require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-editor.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_contentcraft_enhance_content', array($this, 'ajax_enhance_content'));
        add_action('wp_ajax_contentcraft_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_contentcraft_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_contentcraft_get_usage_stats', array($this, 'ajax_get_usage_stats'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize settings
        $this->settings = new ContentCraft_AI_Settings();
        
        // Initialize API handler
        $this->api_handler = new ContentCraft_AI_API_Handler();
        
        // Initialize content processor
        $this->content_processor = new ContentCraft_AI_Content_Processor();
        
        // Initialize admin and editor
        if (is_admin()) {
            $this->admin = new ContentCraft_AI_Admin();
            $this->editor = new ContentCraft_AI_Editor();
        }
    }
    
    /**
     * Check what type of request this is
     */
    private function is_request($type) {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
        }
        return false;
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_single() || is_page()) {
            wp_enqueue_script(
                'contentcraft-ai-frontend',
                CONTENTCRAFT_AI_PLUGIN_URL . 'assets/js/editor-scripts.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );
            
            wp_enqueue_style(
                'contentcraft-ai-frontend',
                CONTENTCRAFT_AI_PLUGIN_URL . 'assets/css/editor-styles.css',
                array(),
                CONTENTCRAFT_AI_VERSION
            );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Debug: Log what hook we're on
        error_log('ContentCraft AI: Hook = ' . $hook);
        
        // Simple approach: load on post editor pages and settings
        $is_editor_page = in_array($hook, array('post.php', 'post-new.php'));
        $is_settings_page = strpos($hook, 'contentcraft-ai') !== false;
        
        // Also check global variables as fallback
        global $pagenow, $typenow;
        $is_post_edit = in_array($pagenow, array('post.php', 'post-new.php')) && in_array($typenow, array('post', 'page'));
        
        error_log('ContentCraft AI: Editor page: ' . ($is_editor_page ? 'yes' : 'no') . ', Settings page: ' . ($is_settings_page ? 'yes' : 'no') . ', Post edit: ' . ($is_post_edit ? 'yes' : 'no'));
        
        if ($is_editor_page || $is_settings_page || $is_post_edit) {
            
            wp_enqueue_script(
                'contentcraft-ai-admin',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/admin-scripts.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );
            
            wp_enqueue_script(
                'contentcraft-ai-modal',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/simple-test.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );
            
            wp_enqueue_style(
                'contentcraft-ai-admin',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/css/admin-styles.css',
                array(),
                CONTENTCRAFT_AI_VERSION
            );
            
            // Localize script for both admin and modal scripts
            $localize_data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentcraft_ai_nonce'),
                'strings' => array(
                    'processing' => __('Processing...', 'contentcraft-ai'),
                    'error' => __('An error occurred. Please try again.', 'contentcraft-ai'),
                    'success' => __('Content processed successfully!', 'contentcraft-ai'),
                    'no_content' => __('No content to process.', 'contentcraft-ai'),
                    'api_error' => __('API connection failed.', 'contentcraft-ai'),
                )
            );
            
            wp_localize_script('contentcraft-ai-admin', 'contentcraft_ai_ajax', $localize_data);
            wp_localize_script('contentcraft-ai-modal', 'contentcraft_ai_ajax', $localize_data);
            
            error_log('ContentCraft AI: Scripts enqueued successfully');
        }
    }
    
    /**
     * AJAX handler for content enhancement
     */
    public function ajax_enhance_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $tags = sanitize_text_field($_POST['tags']);
        
        if (empty($content)) {
            wp_send_json_error(array('message' => __('No content to enhance.', 'contentcraft-ai')));
        }
        
        $result = $this->api_handler->enhance_content($title, $content, $tags);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('content' => $result));
    }
    
    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $title = sanitize_text_field($_POST['title']);
        $tags = sanitize_text_field($_POST['tags']);
        
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Post title is required.', 'contentcraft-ai')));
        }
        
        $result = $this->api_handler->generate_content($title, $tags);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('content' => $result));
    }
    
    /**
     * AJAX handler for API testing
     */
    public function ajax_test_api() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $result = $this->api_handler->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('API connection successful!', 'contentcraft-ai')));
    }
    
    /**
     * AJAX handler for getting usage stats
     */
    public function ajax_get_usage_stats() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $stats = $this->api_handler->get_usage_stats();
        
        wp_send_json_success($stats);
    }
}
