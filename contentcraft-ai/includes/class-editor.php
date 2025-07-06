<?php
/**
 * Editor Integration class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Editor {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize editor hooks
     */
    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_gutenberg_assets'));
        add_action('media_buttons', array($this, 'add_classic_editor_button'));
        add_action('admin_footer', array($this, 'render_modal_template'));
        add_action('wp_footer', array($this, 'render_modal_template'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'contentcraft-ai-modal-styles',
            CONTENTCRAFT_AI_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            CONTENTCRAFT_AI_VERSION
        );

        wp_enqueue_script(
            'contentcraft-ai-editor-modal',
            CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/editor-modal.js',
            array('jquery'),
            CONTENTCRAFT_AI_VERSION,
            true
        );

        wp_localize_script('contentcraft-ai-editor-modal', 'contentcraft_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentcraft_ai_nonce')
        ));
    }
    
    /**
     * Enqueue Gutenberg editor assets
     */
    public function enqueue_gutenberg_assets() {
        wp_enqueue_script(
            'contentcraft-ai-gutenberg',
            CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/gutenberg-integration.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
            CONTENTCRAFT_AI_VERSION,
            true
        );
        
        wp_localize_script('contentcraft-ai-gutenberg', 'contentcraft_ai_gutenberg', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentcraft_ai_nonce'),
            'strings' => array(
                'button_title' => __('ContentCraft AI', 'contentcraft-ai'),
                'button_description' => __('Enhance or generate content with AI', 'contentcraft-ai'),
                'processing' => __('Processing...', 'contentcraft-ai'),
                'error' => __('An error occurred. Please try again.', 'contentcraft-ai'),
            )
        ));
    }
    
    /**
     * Add button to Classic editor
     */
    public function add_classic_editor_button() {
        global $typenow;
        
        if (!in_array($typenow, array('post', 'page'))) {
            return;
        }
        
        echo '<button type="button" id="contentcraft-ai-classic-button" class="button contentcraft-editor-button">';
        echo '<span class="dashicons dashicons-admin-generic"></span> ';
        echo __('ContentCraft AI', 'contentcraft-ai');
        echo '</button>';
    }
    
    /**
     * Render modal template
     */
    public function render_modal_template() {
        // Check if we're in admin and on a post editing page
        if (!is_admin()) {
            return;
        }
        
        // Check if get_current_screen is available
        if (!function_exists('get_current_screen')) {
            // Fallback: check global variables
            global $pagenow, $typenow;
            if (!in_array($pagenow, array('post.php', 'post-new.php')) || !in_array($typenow, array('post', 'page'))) {
                return;
            }
        } else {
            $screen = get_current_screen();
            
            // Only render on post edit screens
            if (!$screen || !isset($screen->base) || $screen->base !== 'post') {
                return;
            }
            
            // Make sure we're on a post type that supports the editor
            if (!isset($screen->post_type) || !in_array($screen->post_type, array('post', 'page'))) {
                return;
            }
        }
        
        include CONTENTCRAFT_AI_PLUGIN_PATH . 'admin/partials/modal-template.php';
    }
    
    /**
     * Get current post data for JavaScript
     */
    public function get_post_data() {
        global $post;
        
        if (!$post) {
            return array();
        }
        
        $tags = get_the_tags($post->ID);
        $tag_names = array();
        
        if ($tags) {
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
        }
        
        $categories = get_the_category($post->ID);
        $category_names = array();
        
        if ($categories) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }
        
        return array(
            'id' => $post->ID,
            'title' => get_the_title($post->ID),
            'content' => $post->post_content,
            'excerpt' => get_the_excerpt($post->ID),
            'tags' => implode(', ', $tag_names),
            'categories' => implode(', ', $category_names),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'date' => get_the_date('Y-m-d', $post->ID)
        );
    }
}
