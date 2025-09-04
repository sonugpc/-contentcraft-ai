<?php
/**
 * Cloudflare AI Handler class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/interface-api-handler.php';

class ContentCraft_AI_Cloudflare_Handler implements ContentCraft_AI_API_Handler_Interface {
    
    /**
     * Cloudflare API base URL
     */
    private $api_base_url = 'https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/run/';
    
    /**
     * Settings instance
     */
    private $settings;
    
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
        // Not implemented for Cloudflare yet
        return new WP_Error('not_implemented', __('This feature is not available for Cloudflare AI yet.', 'contentcraft-ai'));
    }
    
    /**
     * Generate new content
     */
    public function generate_content($title, $tags = '', $length = 'medium', $prompt = '') {
        // Not implemented for Cloudflare yet
        return new WP_Error('not_implemented', __('This feature is not available for Cloudflare AI yet.', 'contentcraft-ai'));
    }

    /**
     * Handle a general query
     */
    public function general_query($prompt) {
        $model = '@cf/meta/llama-2-7b-chat-int8';
        return $this->make_api_request($prompt, $model);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $test_prompt = 'This is a test message. Please respond with "Connection successful".';
        $model = '@cf/meta/llama-2-7b-chat-int8';
        
        $result = $this->make_api_request($test_prompt, $model);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }

    /**
     * Get API usage statistics
     */
    public function get_usage_stats() {
        // Not available for Cloudflare
        return array(
            'current_usage' => 'N/A',
            'rate_limit' => 'N/A',
            'remaining' => 'N/A'
        );
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function make_api_request($prompt, $model) {
        $account_id = $this->get_settings()->get_option('cloudflare_account_id');
        $api_key = $this->get_settings()->get_option('cloudflare_api_key');

        if (empty($account_id) || empty($api_key)) {
            return new WP_Error('no_api_key', __('Cloudflare Account ID and API Key are not configured.', 'contentcraft-ai'));
        }

        $api_url = str_replace('{account_id}', $account_id, $this->api_base_url) . $model;

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'prompt' => $prompt
            )),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['success']) && $data['success']) {
            return ['text' => $data['result']['response']];
        } else {
            $error_message = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : 'Unknown error';
            return new WP_Error('api_error', $error_message);
        }
    }
}
