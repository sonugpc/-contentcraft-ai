<?php
/**
 * API Handler Factory for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-gemini-handler.php';
require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-cloudflare-handler.php';

class ContentCraft_AI_API_Handler_Factory {
    
    /**
     * Get the API handler
     */
    public static function get_handler() {
        $settings = new ContentCraft_AI_Settings();
        $provider = $settings->get_option('api_provider', 'gemini');

        switch ($provider) {
            case 'cloudflare':
                return new ContentCraft_AI_Cloudflare_Handler();
            case 'gemini':
            default:
                return new ContentCraft_AI_Gemini_Handler();
        }
    }
}
