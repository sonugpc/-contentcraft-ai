<?php
/**
 * Admin class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Admin {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new ContentCraft_AI_Settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_contentcraft_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_contentcraft_get_usage_stats', array($this, 'ajax_get_usage_stats'));
        add_action('wp_ajax_contentcraft_enhance_content', array($this, 'ajax_enhance_content'));
        add_action('wp_ajax_contentcraft_generate_content', array($this, 'ajax_generate_content'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_footer', array($this, 'simple_debug')); // Simple debug
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('ContentCraft AI Settings', 'contentcraft-ai'),
            __('ContentCraft AI', 'contentcraft-ai'),
            'manage_options',
            'contentcraft-ai-settings',
            array($this, 'settings_page_callback')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'contentcraft_ai_settings_group',
            'contentcraft_ai_settings',
            array($this, 'sanitize_settings')
        );
        
        // API Settings Section
        add_settings_section(
            'contentcraft_ai_api_section',
            __('API Configuration', 'contentcraft-ai'),
            array($this, 'api_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'api_key',
            __('Gemini API Key', 'contentcraft-ai'),
            array($this, 'api_key_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_section'
        );
        
        // Prompt Templates Section
        add_settings_section(
            'contentcraft_ai_prompts_section',
            __('Prompt Templates', 'contentcraft-ai'),
            array($this, 'prompts_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'enhancement_prompt',
            __('Enhancement Prompt', 'contentcraft-ai'),
            array($this, 'enhancement_prompt_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_prompts_section'
        );
        
        add_settings_field(
            'generation_prompt',
            __('Generation Prompt', 'contentcraft-ai'),
            array($this, 'generation_prompt_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_prompts_section'
        );
        
        // API Settings Section
        add_settings_section(
            'contentcraft_ai_api_settings_section',
            __('API Settings', 'contentcraft-ai'),
            array($this, 'api_settings_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'max_tokens',
            __('Max Tokens', 'contentcraft-ai'),
            array($this, 'max_tokens_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_settings_section'
        );
        
        add_settings_field(
            'temperature',
            __('Temperature', 'contentcraft-ai'),
            array($this, 'temperature_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_settings_section'
        );
        
        add_settings_field(
            'rate_limit',
            __('Rate Limit (per hour)', 'contentcraft-ai'),
            array($this, 'rate_limit_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_settings_section'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'contentcraft_ai_advanced_section',
            __('Advanced Settings', 'contentcraft-ai'),
            array($this, 'advanced_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'enable_logging',
            __('Enable Logging', 'contentcraft-ai'),
            array($this, 'enable_logging_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_advanced_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        // Simple validation without complex dependencies
        $validated = array();
        
        // Handle API key
        if (isset($settings['api_key']) && !empty(trim($settings['api_key']))) {
            // Store API key directly (simple approach)
            $validated['api_key'] = sanitize_text_field($settings['api_key']);
        } else {
            // Keep existing API key
            $existing = get_option('contentcraft_ai_settings', array());
            $validated['api_key'] = isset($existing['api_key']) ? $existing['api_key'] : '';
        }
        
        // Handle other settings
        $validated['enhancement_prompt'] = isset($settings['enhancement_prompt']) ? 
            sanitize_textarea_field($settings['enhancement_prompt']) : 
            'Enhance this content: {post_title} - {post_content}';
            
        $validated['generation_prompt'] = isset($settings['generation_prompt']) ? 
            sanitize_textarea_field($settings['generation_prompt']) : 
            'Generate content for: {post_title}';
            
        $validated['max_tokens'] = isset($settings['max_tokens']) ? 
            absint($settings['max_tokens']) : 2000;
            
        $validated['temperature'] = isset($settings['temperature']) ? 
            floatval($settings['temperature']) : 0.7;
            
        $validated['rate_limit'] = isset($settings['rate_limit']) ? 
            absint($settings['rate_limit']) : 10;
            
        $validated['enable_logging'] = isset($settings['enable_logging']) ? 
            (bool) $settings['enable_logging'] : true;
        
        return $validated;
    }
    
    /**
     * Settings page callback
     */
    public function settings_page_callback() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'contentcraft_ai_messages',
                'contentcraft_ai_message',
                __('Settings Saved', 'contentcraft-ai'),
                'updated'
            );
        }
        
        settings_errors('contentcraft_ai_messages');
        
        include CONTENTCRAFT_AI_PLUGIN_PATH . 'admin/partials/settings-page.php';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ('settings_page_contentcraft-ai-settings' === $hook) {
            wp_enqueue_script(
                'contentcraft-ai-admin-settings',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/admin-scripts.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );
            
            wp_localize_script('contentcraft-ai-admin-settings', 'contentcraft_ai_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentcraft_ai_nonce'),
                'strings' => array(
                    'testing' => __('Testing...', 'contentcraft-ai'),
                    'success' => __('Connection successful!', 'contentcraft-ai'),
                    'error' => __('Connection failed. Please check your API key.', 'contentcraft-ai'),
                )
            ));
        }
    }
    
    /**
     * AJAX test API connection
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $api_handler = new ContentCraft_AI_API_Handler();
        $result = $api_handler->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('API connection successful!', 'contentcraft-ai')));
    }
    
    /**
     * AJAX get usage statistics
     */
    public function ajax_get_usage_stats() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $api_handler = new ContentCraft_AI_API_Handler();
        $stats = $api_handler->get_usage_stats();
        
        wp_send_json_success($stats);
    }

    /**
     * AJAX enhance content
     */
    public function ajax_enhance_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';

        $api_handler = new ContentCraft_AI_API_Handler();
        $result = $api_handler->enhance_content($title, $content, $tags);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $length = isset($_POST['length']) ? sanitize_text_field($_POST['length']) : 'medium';

        $api_handler = new ContentCraft_AI_API_Handler();
        $result = $api_handler->generate_content($title, $tags, $length);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        if ($screen->id === 'settings_page_contentcraft-ai-settings') {
            $api_key = $this->settings->get_api_key();
            
            if (empty($api_key)) {
                echo '<div class="notice notice-warning"><p>';
                echo __('Please configure your Gemini API key to start using ContentCraft AI.', 'contentcraft-ai');
                echo '</p></div>';
            }
        }
    }
    
    // Section callbacks
    public function api_section_callback() {
        echo '<p>' . __('Configure your Gemini API connection settings.', 'contentcraft-ai') . '</p>';
    }
    
    public function prompts_section_callback() {
        echo '<p>' . __('Customize the prompts used for content enhancement and generation.', 'contentcraft-ai') . '</p>';
        echo '<p><strong>' . __('Available variables:', 'contentcraft-ai') . '</strong> {post_title}, {post_content}, {tags}, {categories}, {excerpt}, {author}, {date}</p>';
    }
    
    public function api_settings_section_callback() {
        echo '<p>' . __('Configure API behavior and limits.', 'contentcraft-ai') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced configuration options.', 'contentcraft-ai') . '</p>';
    }
    
    // Field callbacks
    public function api_key_callback() {
        $api_key = $this->settings->get_api_key();
        
        echo '<input type="text" name="contentcraft_ai_settings[api_key]" value="' . esc_attr($api_key) . '" size="50" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from Google AI Studio (https://makersuite.google.com/app/apikey)', 'contentcraft-ai') . '</p>';
        
        if (!empty($api_key)) {
            echo '<p class="description" style="color: green;">✓ ' . __('API key is configured', 'contentcraft-ai') . '</p>';
        } else {
            echo '<p class="description" style="color: orange;">⚠ ' . __('API key is required for the plugin to work', 'contentcraft-ai') . '</p>';
        }
    }
    
    public function enhancement_prompt_callback() {
        $prompt = $this->settings->get_option('enhancement_prompt', $this->settings->get_default_settings()['enhancement_prompt']);
        echo '<textarea name="contentcraft_ai_settings[enhancement_prompt]" rows="4" cols="70">' . esc_textarea($prompt) . '</textarea>';
        echo '<p class="description">' . __('Template for enhancing existing content.', 'contentcraft-ai') . '</p>';
    }
    
    public function generation_prompt_callback() {
        $prompt = $this->settings->get_option('generation_prompt', $this->settings->get_default_settings()['generation_prompt']);
        echo '<textarea name="contentcraft_ai_settings[generation_prompt]" rows="4" cols="70">' . esc_textarea($prompt) . '</textarea>';
        echo '<p class="description">' . __('Template for generating new content.', 'contentcraft-ai') . '</p>';
    }
    
    public function max_tokens_callback() {
        $max_tokens = $this->settings->get_option('max_tokens', 2000);
        echo '<input type="number" name="contentcraft_ai_settings[max_tokens]" value="' . esc_attr($max_tokens) . '" min="100" max="4000" />';
        echo '<p class="description">' . __('Maximum number of tokens to generate (100-4000).', 'contentcraft-ai') . '</p>';
    }
    
    public function temperature_callback() {
        $temperature = $this->settings->get_option('temperature', 0.7);
        echo '<input type="number" name="contentcraft_ai_settings[temperature]" value="' . esc_attr($temperature) . '" min="0" max="1" step="0.1" />';
        echo '<p class="description">' . __('Controls randomness in generation (0.0-1.0). Higher values make output more random.', 'contentcraft-ai') . '</p>';
    }
    
    public function rate_limit_callback() {
        $rate_limit = $this->settings->get_option('rate_limit', 10);
        echo '<input type="number" name="contentcraft_ai_settings[rate_limit]" value="' . esc_attr($rate_limit) . '" min="1" max="100" />';
        echo '<p class="description">' . __('Maximum number of API requests per hour (1-100).', 'contentcraft-ai') . '</p>';
    }
    
    public function enable_logging_callback() {
        $enable_logging = $this->settings->get_option('enable_logging', true);
        echo '<input type="checkbox" name="contentcraft_ai_settings[enable_logging]" value="1" ' . checked($enable_logging, true, false) . ' />';
        echo '<label>' . __('Enable error logging for debugging.', 'contentcraft-ai') . '</label>';
    }
    
    /**
     * Simple debug footer
     */
    public function simple_debug() {
        if (!function_exists('get_current_screen')) {
            echo '<script>console.log("ContentCraft AI: get_current_screen function not available");</script>';
            return;
        }
        
        $screen = get_current_screen();
        
        if ($screen) {
            $screen_base = isset($screen->base) ? $screen->base : 'unknown';
            $post_type = isset($screen->post_type) ? $screen->post_type : 'none';
            
            echo '<script>console.log("ContentCraft AI: Admin debug - Screen: ' . $screen_base . ', Post type: ' . $post_type . '");</script>';
            
            if (in_array($screen_base, array('post', 'settings_page_contentcraft-ai-settings'))) {
                echo '<script>console.log("ContentCraft AI: Scripts should be loaded on this screen");</script>';
            }
        } else {
            echo '<script>console.log("ContentCraft AI: No screen object available");</script>';
        }
    }
}
