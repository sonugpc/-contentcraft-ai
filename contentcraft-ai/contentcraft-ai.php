<?php
/**
 * Plugin Name: ContentCraft AI
 * Description: AI-powered content enhancement and generation for WordPress using Gemini API
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: contentcraft-ai
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CONTENTCRAFT_AI_VERSION', '1.0.0');
define('CONTENTCRAFT_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTENTCRAFT_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CONTENTCRAFT_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include the main plugin class
require_once CONTENTCRAFT_AI_PLUGIN_PATH . 'includes/class-contentcraft-ai.php';

/**
 * Main function to initialize the plugin
 */
function contentcraft_ai_init() {
    return ContentCraft_AI::instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'contentcraft_ai_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'contentcraft_ai_activate');

function contentcraft_ai_activate() {
    // Create default settings
    $default_settings = [
        'api_key' => '',
        'enhancement_prompt' => 'Enhance this content: Title: {post_title}, Content: {post_content}, Tags: {tags}',
        'generation_prompt' => 'Generate content for: Title: {post_title}, Tags: {tags}',
        'max_tokens' => 2000,
        'temperature' => 0.7,
        'enable_logging' => true
    ];
    
    add_option('contentcraft_ai_settings', $default_settings);
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'contentcraft_ai_deactivate');

function contentcraft_ai_deactivate() {
    // Clean up any temporary data
    delete_transient('contentcraft_ai_cache');
}