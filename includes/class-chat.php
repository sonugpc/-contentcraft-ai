<?php
/**
 * Chat class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Chat {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_contentcraft_ai_chat', array($this, 'ajax_chat'));
    }

    /**
     * AJAX chat handler
     */
    public function ajax_chat() {
        check_ajax_referer('contentcraft_ai_chat_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'contentcraft-ai')], 403);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : [];

        if (empty($message)) {
            wp_send_json_error(['message' => __('Message cannot be empty.', 'contentcraft-ai')], 400);
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Invalid post.', 'contentcraft-ai')], 400);
        }

        $post_content = $post->post_content;
        $post_title = $post->post_title;

        $prompt = "You are a helpful assistant. The user is asking questions about the following content:\n\nTitle: $post_title\n\nContent: $post_content\n\n";

        foreach ($history as $item) {
            $prompt .= $item['role'] . ": " . $item['content'] . "\n";
        }
        $prompt .= "user: " . $message;

        $api_handler = ContentCraft_AI_API_Handler_Factory::get_handler();
        $result = $api_handler->general_query($prompt);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success($result);
    }
}
