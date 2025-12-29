<?php
/**
 * API Handler Interface for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

interface ContentCraft_AI_API_Handler_Interface {
    
    /**
     * Enhance existing content
     */
    public function enhance_content($title, $content, $tags = '', $prompt = '');
    
    /**
     * Generate new content
     */
    public function generate_content($title, $tags = '', $length = 'medium', $prompt = '', $content_details = '');

    /**
     * Handle a general query
     */
    public function general_query($prompt);
    
    /**
     * Test API connection
     */
    public function test_connection();

    /**
     * Get API usage statistics
     */
    public function get_usage_stats();
}
