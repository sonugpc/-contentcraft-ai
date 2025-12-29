<?php
/**
 * OpenRouter API Handler class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/interface-api-handler.php';

class ContentCraft_AI_OpenRouter_Handler implements ContentCraft_AI_API_Handler_Interface {

    /**
     * OpenRouter API base URL
     */
    private $api_base_url = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Settings instance
     */
    private $settings;

    /**
     * Free models list
     */
    private $free_models = [
        'meta-llama/llama-3.2-3b-instruct:free',
        'meta-llama/llama-3.1-8b-instruct:free',
        'meta-llama/llama-3.2-1b-instruct:free',
        'microsoft/wizardlm-2-8x22b:free',
        'mistralai/mistral-7b-instruct:free',
        'huggingface/zephyr-7b-beta:free',
        'mistralai/devstral-2512:free'
    ];

    /**
     * Constructor
     */
    public function __construct() {
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
        // Validate content length
        if (str_word_count(strip_tags($content)) < 10) {
            return new WP_Error('content_too_short', __('Content must be at least 10 words long to enhance.', 'contentcraft-ai'));
        }

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
    public function generate_content($title, $tags = '', $length = 'medium', $prompt = '', $content_details = '') {
        // Prepare post data
        $post_data = array(
            'title' => $title,
            'content' => '',
            'tags' => $tags,
            'length' => $length,
            'content_details' => $content_details,
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
     * Handle a general query
     */
    public function general_query($prompt) {
        return $this->make_api_request($prompt, false);
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
     * Make API request to OpenRouter
     */
    private function make_api_request($prompt, $json_response = true) {
        // Get API key
        $api_key = $this->get_settings()->get_option('openrouter_api_key', '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('OpenRouter API key is not configured.', 'contentcraft-ai'));
        }

        // Get model
        $model = $this->get_settings()->get_option('openrouter_model', $this->free_models[0]);

        // Prepare request
        $request_data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $this->get_settings()->get_option('temperature', 0.7)
        );

        if ($json_response) {
            $request_data['response_format'] = array('type' => 'json_object');
        }

        // Make HTTP request
        $response = wp_remote_post($this->api_base_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => get_site_url(),
                'X-Title' => 'ContentCraft AI'
            ),
            'body' => wp_json_encode($request_data),
            'timeout' => 120,
            'sslverify' => true
        ));

        // Handle response
        if (is_wp_error($response)) {
            $this->log_error('API request failed: ' . $response->get_error_message());
            return new WP_Error('api_request_failed', __('Failed to connect to OpenRouter API.', 'contentcraft-ai'));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $this->log_error('API returned error code: ' . $response_code . ', Body: ' . $response_body);
            $error_message = sprintf(__('OpenRouter API returned error code: %d.', 'contentcraft-ai'), $response_code);
            $decoded_body = json_decode($response_body, true);
            if ($decoded_body && isset($decoded_body['error']['message'])) {
                $error_message .= ' ' . $decoded_body['error']['message'];
            } else {
                $error_message .= ' ' . $response_body;
            }
            return new WP_Error('api_error', $error_message);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Failed to parse API response: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Failed to parse OpenRouter API response.', 'contentcraft-ai'));
        }

        return $this->handle_api_response($data, $json_response);
    }

    /**
     * Handle API response
     */
    private function handle_api_response($data, $json_response = true) {
        if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
            $this->log_error('No choices in API response: ' . wp_json_encode($data));
            return new WP_Error('no_choices', __('No content generated by OpenRouter API.', 'contentcraft-ai'));
        }

        $choice = $data['choices'][0];

        if (!isset($choice['message']['content'])) {
            $this->log_error('No content in API response: ' . wp_json_encode($data));
            return new WP_Error('no_content', __('No content in OpenRouter API response.', 'contentcraft-ai'));
        }

        $content = $choice['message']['content'];

        if (!$json_response) {
            return ['text' => $content];
        }

        // Decode the JSON string
        $structured_data = json_decode($content, true);

        if ($json_response && json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Failed to parse structured JSON from API response: ' . json_last_error_msg() . '. Raw content: ' . substr($content, 0, 500));

            // Return the raw content with a fallback structure so frontend can handle it
            return [
                'enhanced_title' => '',
                'enhanced_content' => $content, // Return raw content as fallback
                'suggested_tags' => [],
                'meta_description' => '',
                'focus_keyword' => '',
                'raw_response' => $content, // Include raw response for debugging
                'parse_error' => json_last_error_msg()
            ];
        }

        // Sanitize the fields
        $sanitized_data = [
            'enhanced_title' => isset($structured_data['enhanced_title']) ? sanitize_text_field($structured_data['enhanced_title']) : '',
            'enhanced_content' => isset($structured_data['enhanced_content']) ? $this->sanitize_content($structured_data['enhanced_content']) : '',
            'suggested_tags' => isset($structured_data['suggested_tags']) ? array_map('sanitize_text_field', $structured_data['suggested_tags']) : [],
            'meta_description' => isset($structured_data['meta_description']) ? sanitize_text_field($structured_data['meta_description']) : '',
            'focus_keyword' => isset($structured_data['focus_keyword']) ? sanitize_text_field($structured_data['focus_keyword']) : '',
        ];

        // Log successful request
        $this->log_success('Content generated successfully via OpenRouter');

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
     * Log error
     */
    private function log_error($message) {
        if (!$this->get_settings()->get_option('enable_logging', true)) {
            return;
        }

        error_log('[ContentCraft AI - OpenRouter] ERROR: ' . $message);
    }

    /**
     * Log success
     */
    private function log_success($message) {
        if (!$this->get_settings()->get_option('enable_logging', true)) {
            return;
        }

        error_log('[ContentCraft AI - OpenRouter] SUCCESS: ' . $message);
    }

    /**
     * Get API usage statistics
     */
    public function get_usage_stats() {
        return array(
            'current_usage' => 'N/A',
            'rate_limit' => 'N/A',
            'remaining' => 'N/A'
        );
    }

    /**
     * Get free models list
     */
    public function get_free_models() {
        return $this->free_models;
    }
}
