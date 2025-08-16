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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
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
        
        $settings = new ContentCraft_AI_Settings();
        $enabled_post_types = $settings->get_option('enabled_post_types', array('post', 'page'));

        if (!in_array($typenow, $enabled_post_types)) {
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
    public function add_meta_box() {
        $settings = new ContentCraft_AI_Settings();
        $enabled_post_types = $settings->get_option('enabled_post_types', array('post', 'page'));

        add_meta_box(
            'contentcraft_ai_meta_box',
            __('ContentCraft AI', 'contentcraft-ai'),
            array($this, 'render_meta_box_content'),
            $enabled_post_types,
            'normal',
            'high'
        );
    }

    /**
     * Render meta box content
     */
    public function render_meta_box_content() {
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
