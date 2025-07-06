<?php
/**
 * Settings management class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Settings {
    
    /**
     * Settings option name
     */
    private $option_name = 'contentcraft_ai_settings';
    
    /**
     * Settings cache
     */
    private $settings_cache = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check memory usage for debugging
        if (function_exists('memory_get_usage')) {
            $memory_usage = memory_get_usage();
            if ($memory_usage > 200 * 1024 * 1024) { // 200MB
                error_log('ContentCraft AI: High memory usage detected: ' . number_format($memory_usage / 1024 / 1024, 2) . ' MB');
            }
        }
        
        // Load settings into cache
        $this->load_settings();
    }
    
    /**
     * Load settings into cache
     */
    private function load_settings() {
        static $loading = false;
        
        if ($loading) {
            return; // Prevent infinite recursion
        }
        
        if (null === $this->settings_cache) {
            $loading = true;
            try {
                $this->settings_cache = get_option($this->option_name, $this->get_default_settings());
            } catch (Exception $e) {
                error_log('ContentCraft AI: Error loading settings - ' . $e->getMessage());
                $this->settings_cache = $this->get_default_settings();
            }
            $loading = false;
        }
    }
    
    /**
     * Get option value
     */
    public function get_option($key, $default = null) {
        $this->load_settings();
        
        if (isset($this->settings_cache[$key])) {
            return $this->settings_cache[$key];
        }
        
        return $default;
    }
    
    /**
     * Update option value
     */
    public function update_option($key, $value) {
        $this->load_settings();
        
        $this->settings_cache[$key] = $value;
        
        return update_option($this->option_name, $this->settings_cache);
    }
    
    /**
     * Update multiple options
     */
    public function update_options($options) {
        $this->load_settings();
        
        foreach ($options as $key => $value) {
            $this->settings_cache[$key] = $value;
        }
        
        return update_option($this->option_name, $this->settings_cache);
    }
    
    /**
     * Get all settings
     */
    public function get_all_settings() {
        $this->load_settings();
        return $this->settings_cache;
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        static $defaults = null;
        
        if ($defaults === null) {
            $defaults = array(
                'api_key' => '',
                'enhancement_prompt' => 'Enhance this WordPress content while preserving its structure. Improve the text to be more engaging and clear.

Title: {post_title}
Content: {post_content}
Tags: {tags}

Keep all formatting and structure intact.',
                'generation_prompt' => 'Generate high-quality WordPress content for: {post_title}

Tags: {tags}

Create well-structured, engaging content.',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'enable_logging' => true,
                'cache_duration' => 3600,
                'rate_limit' => 10
            );
        }
        
        return $defaults;
    }
    
    /**
     * Get API key (simplified)
     */
    public function get_api_key() {
        // Simplified approach - return API key directly
        return $this->get_option('api_key', '');
    }
    
    /**
     * Set API key (encrypted)
     */
    public function set_api_key($key) {
        if (empty($key)) {
            return $this->update_option('api_key', '');
        }
        
        $encrypted_key = $this->encrypt_api_key($key);
        return $this->update_option('api_key', $encrypted_key);
    }
    
    /**
     * Encrypt API key
     */
    public function encrypt_api_key($key) {
        if (empty($key)) {
            return '';
        }
        
        // Prevent processing of extremely large keys
        if (strlen($key) > 1000) {
            error_log('ContentCraft AI: API key too large, truncating');
            $key = substr($key, 0, 1000);
        }
        
        // Simple encryption using WordPress salt
        $salt = wp_salt('auth');
        
        // Ensure salt is reasonable size
        if (strlen($salt) > 500) {
            $salt = substr($salt, 0, 500);
        }
        
        $encrypted = base64_encode($key . '|' . $salt);
        
        return $encrypted;
    }
    
    /**
     * Decrypt API key
     */
    private function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }
        
        $decrypted = base64_decode($encrypted_key);
        
        if (false === $decrypted) {
            return '';
        }
        
        $parts = explode('|', $decrypted);
        
        if (count($parts) !== 2) {
            return '';
        }
        
        $salt = wp_salt('auth');
        
        if ($parts[1] !== $salt) {
            return '';
        }
        
        return $parts[0];
    }
    
    /**
     * Get prompt template
     */
    public function get_prompt_template($type) {
        $templates = array(
            'enhancement' => $this->get_option('enhancement_prompt', $this->get_default_settings()['enhancement_prompt']),
            'generation' => $this->get_option('generation_prompt', $this->get_default_settings()['generation_prompt'])
        );
        
        return isset($templates[$type]) ? $templates[$type] : '';
    }
    
    /**
     * Process template variables
     */
    public function process_variables($template, $post_data) {
        $variables = array(
            '{post_title}' => isset($post_data['title']) ? $post_data['title'] : '',
            '{post_content}' => isset($post_data['content']) ? $post_data['content'] : '',
            '{tags}' => isset($post_data['tags']) ? $post_data['tags'] : '',
            '{categories}' => isset($post_data['categories']) ? $post_data['categories'] : '',
            '{excerpt}' => isset($post_data['excerpt']) ? $post_data['excerpt'] : '',
            '{author}' => isset($post_data['author']) ? $post_data['author'] : '',
            '{date}' => isset($post_data['date']) ? $post_data['date'] : date('Y-m-d')
        );
        
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($settings) {
        $validated = array();
        
        // Validate API key
        if (isset($settings['api_key'])) {
            $validated['api_key'] = sanitize_text_field($settings['api_key']);
        }
        
        // Validate prompts
        if (isset($settings['enhancement_prompt'])) {
            $validated['enhancement_prompt'] = sanitize_textarea_field($settings['enhancement_prompt']);
        }
        
        if (isset($settings['generation_prompt'])) {
            $validated['generation_prompt'] = sanitize_textarea_field($settings['generation_prompt']);
        }
        
        // Validate numeric values
        if (isset($settings['max_tokens'])) {
            $validated['max_tokens'] = absint($settings['max_tokens']);
            if ($validated['max_tokens'] < 100) {
                $validated['max_tokens'] = 100;
            }
            if ($validated['max_tokens'] > 4000) {
                $validated['max_tokens'] = 4000;
            }
        }
        
        if (isset($settings['temperature'])) {
            $validated['temperature'] = floatval($settings['temperature']);
            if ($validated['temperature'] < 0) {
                $validated['temperature'] = 0;
            }
            if ($validated['temperature'] > 1) {
                $validated['temperature'] = 1;
            }
        }
        
        // Validate boolean values
        if (isset($settings['enable_logging'])) {
            $validated['enable_logging'] = (bool) $settings['enable_logging'];
        }
        
        // Validate cache duration
        if (isset($settings['cache_duration'])) {
            $validated['cache_duration'] = absint($settings['cache_duration']);
            if ($validated['cache_duration'] < 300) {
                $validated['cache_duration'] = 300; // Minimum 5 minutes
            }
        }
        
        // Validate rate limit
        if (isset($settings['rate_limit'])) {
            $validated['rate_limit'] = absint($settings['rate_limit']);
            if ($validated['rate_limit'] < 1) {
                $validated['rate_limit'] = 1;
            }
            if ($validated['rate_limit'] > 100) {
                $validated['rate_limit'] = 100;
            }
        }
        
        return $validated;
    }
    
    /**
     * Reset settings to default
     */
    public function reset_to_default() {
        $this->settings_cache = $this->get_default_settings();
        return update_option($this->option_name, $this->settings_cache);
    }
}