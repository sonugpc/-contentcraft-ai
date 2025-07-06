<?php
/**
 * API Handler class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_API_Handler {
    
    /**
     * Gemini API base URL
     */
    private $api_base_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent';
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Use simpler settings approach
        $this->settings = null;
    }
    
    /**
     * Get settings instance
     */
    private function get_settings() {
        if (!$this->settings) {
            $this->settings = new ContentCraft_AI_Settings();
        }
        return $this->settings;
    }
    
    /**
     * Enhance existing content
     */
    public function enhance_content($title, $content, $tags = '', $prompt = '') {
        // Prepare post data
        $post_data = array(
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
            'categories' => '',
            'excerpt' => '',
            'author' => wp_get_current_user()->display_name,
            'date' => date('Y-m-d')
        );
        
        // Get and process prompt template
        if (empty($prompt)) {
            $prompt = $this->get_settings()->get_prompt_template('enhancement');
        }
        $prompt = $this->get_settings()->process_variables($prompt, $post_data);
        
        // Make API request
        return $this->make_api_request($prompt);
    }
    
    /**
     * Generate new content
     */
    public function generate_content($title, $tags = '', $length = 'medium', $prompt = '') {
        // Prepare post data
        $post_data = array(
            'title' => $title,
            'content' => '',
            'tags' => $tags,
            'length' => $length,
            'categories' => '',
            'excerpt' => '',
            'author' => wp_get_current_user()->display_name,
            'date' => date('Y-m-d')
        );
        
        // Get and process prompt template
        if (empty($prompt)) {
            $prompt = $this->get_settings()->get_prompt_template('generation');
        }
        $prompt = $this->get_settings()->process_variables($prompt, $post_data);
        
        // Make API request
        return $this->make_api_request($prompt);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $test_prompt = 'This is a test message. Please respond with "Connection successful".';
        
        $result = $this->make_api_request($test_prompt);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Make API request to Gemini
     */
    private function make_api_request($prompt) {
        // Get API key
        $api_key = $this->get_settings()->get_api_key();
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('API key is not configured.', 'contentcraft-ai'));
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', __('Rate limit exceeded. Please try again later.', 'contentcraft-ai'));
        }
        
        // Prepare request
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'response_mime_type' => 'application/json',
                'maxOutputTokens' => $this->get_settings()->get_option('max_tokens', 2000),
                'temperature' => $this->get_settings()->get_option('temperature', 0.7)
            )
        );
        
        // Make HTTP request
        $response = wp_remote_post($this->api_base_url . '?key=' . $api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($request_data),
            'timeout' => 120,
            'sslverify' => true
        ));
        
        // Update rate limit counter
        $this->update_rate_limit();
        
        // Handle response
        if (is_wp_error($response)) {
            $this->log_error('API request failed: ' . $response->get_error_message());
            return new WP_Error('api_request_failed', __('Failed to connect to API. Possible Timeout', 'contentcraft-ai'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $this->log_error('API returned error code: ' . $response_code . ', Body: ' . $response_body);
            return new WP_Error('api_error', sprintf(__('API returned error code: %d', 'contentcraft-ai'), $response_code));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Failed to parse API response: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Failed to parse API response.', 'contentcraft-ai'));
        }
        
        return $this->handle_api_response($data);
    }
    
    /**
     * Handle API response
     */
    private function handle_api_response($data) {
        if (!isset($data['candidates']) || !is_array($data['candidates']) || empty($data['candidates'])) {
            $this->log_error('No candidates in API response: ' . wp_json_encode($data));
            return new WP_Error('no_candidates', __('No content generated by API.', 'contentcraft-ai'));
        }
        
        $candidate = $data['candidates'][0];
        
        if (!isset($candidate['content']['parts']) || !is_array($candidate['content']['parts']) || empty($candidate['content']['parts'])) {
            $this->log_error('No content parts in API response: ' . wp_json_encode($data));
            return new WP_Error('no_content', __('No content in API response.', 'contentcraft-ai'));
        }
        
        $content = $candidate['content']['parts'][0]['text'];
        
        // Decode the JSON string
        $structured_data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Failed to parse structured JSON from API response: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Failed to parse structured JSON from API response.', 'contentcraft-ai'));
        }

        // Sanitize the fields
        $sanitized_data = [
            'enhanced_title' => isset($structured_data['enhanced_title']) ? sanitize_text_field($structured_data['enhanced_title']) : '',
            'enhanced_content' => isset($structured_data['enhanced_content']) ? wp_kses_post($structured_data['enhanced_content']) : '',
            'suggested_tags' => isset($structured_data['suggested_tags']) ? array_map('sanitize_text_field', $structured_data['suggested_tags']) : [],
            'meta_description' => isset($structured_data['meta_description']) ? sanitize_text_field($structured_data['meta_description']) : '',
            'focus_keyword' => isset($structured_data['focus_keyword']) ? sanitize_text_field($structured_data['focus_keyword']) : '',
        ];
        
        // Log successful request
        $this->log_success('Content generated successfully');
        
        return $sanitized_data;
    }
    
    /**
     * Sanitize content
     */
    private function sanitize_content($content) {
        // For block content, we need to be more careful about sanitization
        // to preserve Gutenberg block comments and structure
        
        // First check if this looks like block content
        if (strpos($content, '<!-- wp:') !== false) {
            // This is block content - use minimal sanitization to preserve blocks
            // Allow all post content including block comments
            $allowed_html = wp_kses_allowed_html('post');
            
            // Add block comment allowances (these aren't normally in wp_kses)
            // We'll do a more manual approach for block content
            $content = trim($content);
            
            // Only remove potentially dangerous scripts/styles but keep block structure
            $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
            $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
            
            // Remove any obvious malicious attributes but keep data attributes used by blocks
            $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
            
        } else {
            // Regular HTML content - use standard WordPress sanitization
            $content = wp_kses_post($content);
        }
        
        // Trim whitespace
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Check rate limit
     */
    private function check_rate_limit() {
        $rate_limit = $this->get_settings()->get_option('rate_limit', 10);
        $current_count = get_transient('contentcraft_ai_rate_limit_count');
        
        if (false === $current_count) {
            return true;
        }
        
        return intval($current_count) < $rate_limit;
    }
    
    /**
     * Update rate limit counter
     */
    private function update_rate_limit() {
        $current_count = get_transient('contentcraft_ai_rate_limit_count');
        
        if (false === $current_count) {
            $current_count = 0;
        }
        
        $current_count++;
        
        set_transient('contentcraft_ai_rate_limit_count', $current_count, HOUR_IN_SECONDS);
    }
    
    /**
     * Log error
     */
    private function log_error($message) {
        if (!$this->get_settings()->get_option('enable_logging', true)) {
            return;
        }
        
        error_log('[ContentCraft AI] ERROR: ' . $message);
    }
    
    /**
     * Log success
     */
    private function log_success($message) {
        if (!$this->get_settings()->get_option('enable_logging', true)) {
            return;
        }
        
        error_log('[ContentCraft AI] SUCCESS: ' . $message);
    }
    
    /**
     * Get API usage statistics
     */
    public function get_usage_stats() {
        $current_count = get_transient('contentcraft_ai_rate_limit_count');
        $rate_limit = $this->get_settings()->get_option('rate_limit', 10);
        
        return array(
            'current_usage' => $current_count ? intval($current_count) : 0,
            'rate_limit' => $rate_limit,
            'remaining' => $rate_limit - ($current_count ? intval($current_count) : 0)
        );
    }
}
