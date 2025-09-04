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
     * Chat instance
     */
    public $chat;
    
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
        
        require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-chat.php';
        
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
        // Initialize chat
        $this->chat = new ContentCraft_AI_Chat();

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
    
}
