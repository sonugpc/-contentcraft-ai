<?php
/**
 * Admin class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Admin {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new ContentCraft_AI_Settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_contentcraft_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_contentcraft_enhance_content', array($this, 'ajax_enhance_content'));
        add_action('wp_ajax_contentcraft_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_contentcraft_general_query', array($this, 'ajax_general_query'));
        add_action('wp_ajax_contentcraft_fetch_internal_links', array($this, 'ajax_fetch_internal_links'));
        add_action('wp_ajax_contentcraft_get_default_prompts', array($this, 'ajax_get_default_prompts'));
        add_action('wp_ajax_contentcraft_find_similar_posts', array($this, 'ajax_find_similar_posts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('ContentCraft AI Settings', 'contentcraft-ai'),
            __('ContentCraft AI', 'contentcraft-ai'),
            'manage_options',
            'contentcraft-ai-settings',
            array($this, 'settings_page_callback')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'contentcraft_ai_settings_group',
            'contentcraft_ai_settings',
            array($this, 'sanitize_settings')
        );
        
        // API Provider Section
        add_settings_section(
            'contentcraft_ai_api_provider_section',
            __('API Provider', 'contentcraft-ai'),
            array($this, 'api_provider_section_callback'),
            'contentcraft-ai-settings'
        );

        add_settings_field(
            'api_provider',
            __('Select Provider', 'contentcraft-ai'),
            array($this, 'api_provider_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_provider_section'
        );

        // Gemini API Settings Section
        add_settings_section(
            'contentcraft_ai_gemini_api_section',
            __('Gemini API Configuration', 'contentcraft-ai'),
            array($this, 'api_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'api_key',
            __('Gemini API Key', 'contentcraft-ai'),
            array($this, 'api_key_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_gemini_api_section'
        );

        add_settings_field(
            'gemini_model',
            __('Gemini Model', 'contentcraft-ai'),
            array($this, 'gemini_model_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_gemini_api_section'
        );

        // Cloudflare API Settings Section
        add_settings_section(
            'contentcraft_ai_cloudflare_api_section',
            __('Cloudflare AI Configuration', 'contentcraft-ai'),
            array($this, 'cloudflare_api_section_callback'),
            'contentcraft-ai-settings'
        );

        add_settings_field(
            'cloudflare_account_id',
            __('Cloudflare Account ID', 'contentcraft-ai'),
            array($this, 'cloudflare_account_id_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_cloudflare_api_section'
        );

        add_settings_field(
            'cloudflare_api_key',
            __('Cloudflare API Key', 'contentcraft-ai'),
            array($this, 'cloudflare_api_key_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_cloudflare_api_section'
        );

        // OpenRouter API Settings Section
        add_settings_section(
            'contentcraft_ai_openrouter_api_section',
            __('OpenRouter AI Configuration', 'contentcraft-ai'),
            array($this, 'openrouter_api_section_callback'),
            'contentcraft-ai-settings'
        );

        add_settings_field(
            'openrouter_api_key',
            __('OpenRouter API Key', 'contentcraft-ai'),
            array($this, 'openrouter_api_key_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_openrouter_api_section'
        );

        add_settings_field(
            'openrouter_model',
            __('OpenRouter Model', 'contentcraft-ai'),
            array($this, 'openrouter_model_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_openrouter_api_section'
        );
        
        // Prompt Templates Section
        add_settings_section(
            'contentcraft_ai_prompts_section',
            __('Prompt Templates', 'contentcraft-ai'),
            array($this, 'prompts_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'enhancement_prompt',
            __('Enhancement Prompt', 'contentcraft-ai'),
            array($this, 'enhancement_prompt_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_prompts_section'
        );
        
        add_settings_field(
            'generation_prompt',
            __('Generation Prompt', 'contentcraft-ai'),
            array($this, 'generation_prompt_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_prompts_section'
        );
        
        // API Settings Section
        add_settings_section(
            'contentcraft_ai_api_settings_section',
            __('API Settings', 'contentcraft-ai'),
            array($this, 'api_settings_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'temperature',
            __('Temperature', 'contentcraft-ai'),
            array($this, 'temperature_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_api_settings_section'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'contentcraft_ai_advanced_section',
            __('Advanced Settings', 'contentcraft-ai'),
            array($this, 'advanced_section_callback'),
            'contentcraft-ai-settings'
        );
        
        add_settings_field(
            'enable_logging',
            __('Enable Logging', 'contentcraft-ai'),
            array($this, 'enable_logging_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_advanced_section'
        );

        // Post Types Section
        add_settings_section(
            'contentcraft_ai_post_types_section',
            __('Enable on Post Types', 'contentcraft-ai'),
            array($this, 'post_types_section_callback'),
            'contentcraft-ai-settings'
        );

        add_settings_field(
            'enabled_post_types',
            __('Post Types', 'contentcraft-ai'),
            array($this, 'enabled_post_types_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_post_types_section'
        );

        // Schema Display Section
        add_settings_section(
            'contentcraft_ai_schema_section',
            __('Expected JSON Output Schema', 'contentcraft-ai'),
            array($this, 'schema_section_callback'),
            'contentcraft-ai-settings'
        );

        add_settings_field(
            'schema_display',
            __('Schema', 'contentcraft-ai'),
            array($this, 'schema_display_callback'),
            'contentcraft-ai-settings',
            'contentcraft_ai_schema_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        // Simple validation without complex dependencies
        $validated = array();
        
        // Handle API key
        if (isset($settings['api_key']) && !empty(trim($settings['api_key']))) {
            // Store API key directly (simple approach)
            $validated['api_key'] = sanitize_text_field($settings['api_key']);
        } else {
            // Keep existing API key
            $existing = get_option('contentcraft_ai_settings', array());
            $validated['api_key'] = isset($existing['api_key']) ? $existing['api_key'] : '';
        }
        
        // Handle other settings
        $validated['enhancement_prompt'] = isset($settings['enhancement_prompt']) ? 
            sanitize_textarea_field($settings['enhancement_prompt']) : 
            'Enhance this content: {post_title} - {post_content}';
            
        $validated['generation_prompt'] = isset($settings['generation_prompt']) ? 
            sanitize_textarea_field($settings['generation_prompt']) : 
            'Generate content for: {post_title}';
            
        $validated['temperature'] = isset($settings['temperature']) ? 
            floatval($settings['temperature']) : 0.7;
            
        $validated['enable_logging'] = isset($settings['enable_logging']) ? 
            (bool) $settings['enable_logging'] : true;

        $validated['enabled_post_types'] = isset($settings['enabled_post_types']) && is_array($settings['enabled_post_types']) ?
            array_map('sanitize_text_field', $settings['enabled_post_types']) :
            array_keys(get_post_types(['public' => true]));

        $validated['api_provider'] = isset($settings['api_provider']) ? sanitize_text_field($settings['api_provider']) : 'gemini';
        $validated['cloudflare_account_id'] = isset($settings['cloudflare_account_id']) ? sanitize_text_field($settings['cloudflare_account_id']) : '';
        $validated['cloudflare_api_key'] = isset($settings['cloudflare_api_key']) ? sanitize_text_field($settings['cloudflare_api_key']) : '';
        $validated['gemini_model'] = isset($settings['gemini_model']) ? sanitize_text_field($settings['gemini_model']) : 'gemini-2.5-pro';
        $validated['openrouter_api_key'] = isset($settings['openrouter_api_key']) ? sanitize_text_field($settings['openrouter_api_key']) : '';
        $validated['openrouter_model'] = isset($settings['openrouter_model']) ? sanitize_text_field($settings['openrouter_model']) : 'meta-llama/llama-3.2-3b-instruct:free';
        
        return $validated;
    }
    
    /**
     * Settings page callback
     */
    public function settings_page_callback() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'contentcraft_ai_messages',
                'contentcraft_ai_message',
                __('Settings Saved', 'contentcraft-ai'),
                'updated'
            );
        }
        
        settings_errors('contentcraft_ai_messages');
        
        include CONTENTCRAFT_AI_PLUGIN_PATH . 'admin/partials/settings-page.php';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Enqueue scripts for settings page
        if ('settings_page_contentcraft-ai-settings' === $hook) {
            wp_enqueue_script(
                'contentcraft-ai-admin-settings',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/admin-scripts.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );
            
            wp_localize_script('contentcraft-ai-admin-settings', 'contentcraft_ai_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentcraft_ai_nonce'),
                'strings' => array(
                    'testing' => __('Testing...', 'contentcraft-ai'),
                    'success' => __('Connection successful!', 'contentcraft-ai'),
                    'error' => __('Connection failed. Please check your API key.', 'contentcraft-ai'),
                )
            ));
        }

        // Enqueue scripts and styles for post editor
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_style(
                'contentcraft-ai-admin-styles',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/css/admin-styles.css',
                array(),
                CONTENTCRAFT_AI_VERSION
            );

            wp_enqueue_script(
                'contentcraft-ai-editor-modal',
                CONTENTCRAFT_AI_PLUGIN_URL . 'admin/js/editor-modal.js',
                array('jquery', 'wp-data', 'wp-dom-ready'),
                CONTENTCRAFT_AI_VERSION,
                true
            );

            wp_localize_script('contentcraft-ai-editor-modal', 'contentcraft_ai_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentcraft_ai_nonce')
            ));

            // Enqueue chat styles
            wp_enqueue_style(
                'contentcraft-ai-chat',
                CONTENTCRAFT_AI_PLUGIN_URL . 'assets/css/chat.css',
                array(),
                CONTENTCRAFT_AI_VERSION
            );

            // Enqueue chat scripts
            wp_enqueue_script(
                'contentcraft-ai-chat',
                CONTENTCRAFT_AI_PLUGIN_URL . 'assets/js/chat.js',
                array('jquery'),
                CONTENTCRAFT_AI_VERSION,
                true
            );

            // Localize chat script
            wp_localize_script('contentcraft-ai-chat', 'contentcraft_ai_chat_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentcraft_ai_chat_nonce'),
                'post_id' => get_the_ID()
            ));
        }
    }
    
    /**
     * AJAX test API connection
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }
        
        $api_handler = ContentCraft_AI_API_Handler_Factory::get_handler();
        $result = $api_handler->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('API connection successful!', 'contentcraft-ai')));
    }

    /**
     * AJAX enhance content
     */
    public function ajax_enhance_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');
    
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'contentcraft-ai')], 403);
        }
    
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
        $api_handler = ContentCraft_AI_API_Handler_Factory::get_handler();
        $result = $api_handler->enhance_content($title, $content, $tags, $prompt);
    
        if (is_wp_error($result)) {
            $error_code = $result->get_error_code();
            $error_message = $result->get_error_message();
            $status_code = 400;
    
            if ($error_code === 'rate_limit_exceeded') {
                $status_code = 429;
            } elseif ($error_code === 'no_api_key') {
                $status_code = 401;
            }
    
            wp_send_json_error(['message' => $error_message, 'code' => $error_code], $status_code);
        }
    
        if ($post_id > 0 && !empty($result['enhanced_content'])) {
            $content_processor = new ContentCraft_AI_Content_Processor();
            $result['enhanced_content'] = $content_processor->add_internal_links($result['enhanced_content'], $post_id);
        }
    
        wp_send_json_success($result);
    }

    /**
     * AJAX generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $content_details = isset($_POST['content_details']) ? sanitize_textarea_field($_POST['content_details']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $length = isset($_POST['length']) ? sanitize_text_field($_POST['length']) : 'medium';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        $api_handler = ContentCraft_AI_API_Handler_Factory::get_handler();
        $result = $api_handler->generate_content($content_details, $tags, $length, $prompt, $content_details);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        if ($post_id > 0 && !empty($result['enhanced_content'])) {
            $content_processor = new ContentCraft_AI_Content_Processor();
            $result['enhanced_content'] = $content_processor->add_internal_links($result['enhanced_content'], $post_id);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX general query
     */
    public function ajax_general_query() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        $api_handler = ContentCraft_AI_API_Handler_Factory::get_handler();
        $result = $api_handler->general_query($prompt);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX fetch internal links
     */
    public function ajax_fetch_internal_links() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'contentcraft-ai')]);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if (empty($post_id)) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'contentcraft-ai')]);
        }

        $settings = new ContentCraft_AI_Settings();
        $enabled_post_types = $settings->get_option('enabled_post_types', array('post', 'page'));

        $args = [
            'post_type' => $enabled_post_types,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'post__not_in' => [$post_id],
        ];

        if (!empty($title)) {
            $args['s'] = $title;
        }

        if (!empty($tags)) {
            $args['tag'] = $tags;
        }

        if (!empty($category)) {
            $args['category_name'] = $category;
        }

        $query = new WP_Query($args);
        $links = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $links[] = [
                    'url' => get_permalink(),
                    'title' => get_the_title(),
                ];
            }
        }

        wp_reset_postdata();

        wp_send_json_success($links);
    }

    /**
     * AJAX get default prompts
     */
    public function ajax_get_default_prompts() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $prompts = array(
            'enhancement' => $this->settings->get_option('enhancement_prompt', ''),
            'generation' => $this->settings->get_option('generation_prompt', '')
        );

        wp_send_json_success($prompts);
    }

    /**
     * AJAX find similar posts for internal linking
     */
    public function ajax_find_similar_posts() {
        check_ajax_referer('contentcraft_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'contentcraft-ai')));
        }

        $content_details = isset($_POST['content_details']) ? sanitize_textarea_field($_POST['content_details']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($content_details)) {
            wp_send_json_error(array('message' => __('Content details are required to find similar posts.', 'contentcraft-ai')));
        }

        $internal_links = $this->find_similar_posts($content_details, $tags, $post_id);

        wp_send_json_success(array('internal_links' => $internal_links));
    }

    /**
     * Find similar posts based on content analysis
     */
    private function find_similar_posts($content_details, $tags = '', $exclude_post_id = 0) {
        // Extract keywords from content details
        $keywords = $this->extract_keywords($content_details);

        // Add user-provided tags to keywords
        if (!empty($tags)) {
            $tag_array = array_map('trim', explode(',', $tags));
            $keywords = array_merge($keywords, $tag_array);
        }

        // Remove duplicates and empty values
        $keywords = array_unique(array_filter($keywords));

        // If no keywords found, try to extract some basic terms from the content
        if (empty($keywords)) {
            $content_lower = strtolower($content_details);
            // Extract words that are 3+ characters and appear multiple times
            preg_match_all('/\b[a-z]{3,}\b/', $content_lower, $matches);
            if (!empty($matches[0])) {
                $word_counts = array_count_values($matches[0]);
                arsort($word_counts);
                $keywords = array_slice(array_keys($word_counts), 0, 5);
            }
        }

        if (empty($keywords)) {
            return '';
        }

        $links = array();

        // Method 1: Search by post title and content (most inclusive)
        $search_query = implode(' ', array_slice($keywords, 0, 3));
        if (!empty($search_query)) {
            $args1 = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 8,
                'post__not_in' => array($exclude_post_id),
                's' => $search_query,
                'sentence' => true // Better search matching
            );

            $query1 = new WP_Query($args1);
            if ($query1->have_posts()) {
                while ($query1->have_posts()) {
                    $query1->the_post();
                    $post_id = get_the_ID();
                    $title = get_the_title();
                    $url = get_permalink();

                    // Calculate relevance score
                    $score = $this->calculate_relevance_score($title, $content_details, $keywords);

                    $links[$post_id] = array(
                        'title' => $title,
                        'url' => $url,
                        'score' => $score
                    );
                }
            }
            wp_reset_postdata();
        }

        // Method 2: Search by Yoast focus keywords (if available)
        if (count($links) < 5) {
            $args2 = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 5,
                'post__not_in' => array($exclude_post_id),
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_yoast_wpseo_focuskw',
                        'value' => $keywords,
                        'compare' => 'IN'
                    )
                )
            );

            $query2 = new WP_Query($args2);
            if ($query2->have_posts()) {
                while ($query2->have_posts()) {
                    $query2->the_post();
                    $post_id = get_the_ID();
                    $title = get_the_title();
                    $url = get_permalink();

                    // Calculate relevance score
                    $score = $this->calculate_relevance_score($title, $content_details, $keywords);

                    // Add bonus for Yoast focus keyword match
                    $score += 5;

                    $links[$post_id] = array(
                        'title' => $title,
                        'url' => $url,
                        'score' => $score
                    );
                }
            }
            wp_reset_postdata();
        }

        // Method 3: Search by tags if we have user-provided tags
        if (!empty($tags) && count($links) < 3) {
            $tag_array = array_map('trim', explode(',', $tags));
            $tag_slugs = array();

            foreach ($tag_array as $tag_name) {
                $tag = get_term_by('name', $tag_name, 'post_tag');
                if ($tag) {
                    $tag_slugs[] = $tag->slug;
                }
            }

            if (!empty($tag_slugs)) {
                $args3 = array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in' => array($exclude_post_id),
                    'tag' => implode(',', $tag_slugs)
                );

                $query3 = new WP_Query($args3);
                if ($query3->have_posts()) {
                    while ($query3->have_posts()) {
                        $query3->the_post();
                        $post_id = get_the_ID();
                        $title = get_the_title();
                        $url = get_permalink();

                        // Calculate relevance score
                        $score = $this->calculate_relevance_score($title, $content_details, $keywords);

                        $links[$post_id] = array(
                            'title' => $title,
                            'url' => $url,
                            'score' => $score
                        );
                    }
                }
                wp_reset_postdata();
            }
        }

        // Sort by relevance score and take top 3
        if (!empty($links)) {
            usort($links, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $top_links = array_slice($links, 0, 3);

            // Format for AI prompt - just the links without heading
            $formatted_links = "";
            foreach ($top_links as $index => $link) {
                $formatted_links .= ($index + 1) . ". Title: \"" . $link['title'] . "\"\n";
                $formatted_links .= "   URL: " . $link['url'] . "\n\n";
            }
            $formatted_links .= "Consider linking to these posts where relevant to improve internal linking and SEO.";

            return $formatted_links;
        }

        return '';
    }

    /**
     * Extract keywords from content
     */
    private function extract_keywords($content) {
        // Convert to lowercase and remove punctuation
        $content = strtolower($content);
        $content = preg_replace('/[^\w\s]/', ' ', $content);

        // Split into words
        $words = explode(' ', $content);
        $words = array_filter($words, function($word) {
            return strlen($word) > 3; // Only words longer than 3 characters
        });

        // Count word frequency
        $word_count = array_count_values($words);

        // Remove common stop words
        $stop_words = array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'has', 'let', 'put', 'say', 'she', 'too', 'use');
        $word_count = array_diff_key($word_count, array_flip($stop_words));

        // Sort by frequency and return top keywords
        arsort($word_count);
        return array_slice(array_keys($word_count), 0, 10);
    }

    /**
     * Calculate relevance score for a post
     */
    private function calculate_relevance_score($post_title, $content_details, $keywords) {
        $score = 0;

        // Convert to lowercase for comparison
        $post_title_lower = strtolower($post_title);
        $content_lower = strtolower($content_details);

        // Title matches (highest weight)
        foreach ($keywords as $keyword) {
            if (strpos($post_title_lower, strtolower($keyword)) !== false) {
                $score += 10;
            }
        }

        // Content matches (medium weight)
        foreach ($keywords as $keyword) {
            if (strpos($content_lower, strtolower($keyword)) !== false) {
                $score += 5;
            }
        }

        // Exact keyword matches in title get bonus
        foreach ($keywords as $keyword) {
            if (strtolower($post_title) === strtolower($keyword)) {
                $score += 20;
            }
        }

        return $score;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        if ($screen->id === 'settings_page_contentcraft-ai-settings') {
            $settings = get_option('contentcraft_ai_settings', []);
            $provider = isset($settings['api_provider']) ? $settings['api_provider'] : 'gemini';

            if ($provider === 'gemini') {
                $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
                if (empty($api_key)) {
                    echo '<div class="notice notice-warning"><p>';
                    echo __('Please configure your Gemini API key to start using ContentCraft AI.', 'contentcraft-ai');
                    echo '</p></div>';
                }
            } elseif ($provider === 'cloudflare') {
                $account_id = isset($settings['cloudflare_account_id']) ? $settings['cloudflare_account_id'] : '';
                $api_key = isset($settings['cloudflare_api_key']) ? $settings['cloudflare_api_key'] : '';
                if (empty($account_id) || empty($api_key)) {
                    echo '<div class="notice notice-warning"><p>';
                    echo __('Please configure your Cloudflare Account ID and API Key to start using ContentCraft AI.', 'contentcraft-ai');
                    echo '</p></div>';
                }
            } elseif ($provider === 'openrouter') {
                $api_key = isset($settings['openrouter_api_key']) ? $settings['openrouter_api_key'] : '';
                if (empty($api_key)) {
                    echo '<div class="notice notice-warning"><p>';
                    echo __('Please configure your OpenRouter API Key to start using ContentCraft AI.', 'contentcraft-ai');
                    echo '</p></div>';
                }
            }
        }
    }
    
    // Section callbacks
    public function api_provider_section_callback() {
        echo '<p>' . __('Select your preferred AI provider.', 'contentcraft-ai') . '</p>';
    }

    public function api_section_callback() {
        echo '<p>' . __('Configure your Gemini API connection settings.', 'contentcraft-ai') . '</p>';
    }

    public function cloudflare_api_section_callback() {
        echo '<p>' . __('Configure your Cloudflare AI connection settings.', 'contentcraft-ai') . '</p>';
    }
    
    public function prompts_section_callback() {
        echo '<p>' . __('Customize the prompts used for content enhancement and generation.', 'contentcraft-ai') . '</p>';
        echo '<p><strong>' . __('Available variables:', 'contentcraft-ai') . '</strong> {post_title}, {post_content}, {content_details}, {tags}, {categories}, {excerpt}, {author}, {date}</p>';
    }
    
    public function api_settings_section_callback() {
        echo '<p>' . __('Configure API behavior and limits.', 'contentcraft-ai') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced configuration options.', 'contentcraft-ai') . '</p>';
    }

    public function post_types_section_callback() {
        echo '<p>' . __('Select the post types where you want to enable ContentCraft AI.', 'contentcraft-ai') . '</p>';
    }

    public function schema_section_callback() {
        echo '<p>' . __('This is the expected JSON structure for the AI response. Use this as a reference when crafting your prompts.', 'contentcraft-ai') . '</p>';
    }
    
    // Field callbacks
    public function api_key_callback() {
        $api_key = $this->settings->get_api_key();
        
        echo '<input type="text" name="contentcraft_ai_settings[api_key]" value="' . esc_attr($api_key) . '" size="50" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from Google AI Studio (https://makersuite.google.com/app/apikey)', 'contentcraft-ai') . '</p>';
        
        if (!empty($api_key)) {
            echo '<p class="description" style="color: green;">✓ ' . __('API key is configured', 'contentcraft-ai') . '</p>';
        } else {
            echo '<p class="description" style="color: orange;">⚠ ' . __('API key is required for the plugin to work', 'contentcraft-ai') . '</p>';
        }
    }

    public function gemini_model_callback() {
        $model = $this->settings->get_option('gemini_model', 'gemini-2.5-pro');
        echo '<input type="text" name="contentcraft_ai_settings[gemini_model]" value="' . esc_attr($model) . '" size="50" class="regular-text" />';
        echo '<p class="description">' . __('Enter the Gemini model ID (e.g., gemini-2.5-pro, gemini-2.5-flash).', 'contentcraft-ai') . '</p>';
    }
    
    public function enhancement_prompt_callback() {
        $prompt = $this->settings->get_option('enhancement_prompt', $this->settings->get_default_settings()['enhancement_prompt']);
        echo '<textarea name="contentcraft_ai_settings[enhancement_prompt]" rows="4" cols="70">' . esc_textarea($prompt) . '</textarea>';
        echo '<p class="description">' . __('Template for enhancing existing content.', 'contentcraft-ai') . '</p>';
    }
    
    public function generation_prompt_callback() {
        $prompt = $this->settings->get_option('generation_prompt', $this->settings->get_default_settings()['generation_prompt']);
        echo '<textarea name="contentcraft_ai_settings[generation_prompt]" rows="4" cols="70">' . esc_textarea($prompt) . '</textarea>';
        echo '<p class="description">' . __('Template for generating new content.', 'contentcraft-ai') . '</p>';
    }
    
    public function temperature_callback() {
        $temperature = $this->settings->get_option('temperature', 0.7);
        echo '<input type="number" name="contentcraft_ai_settings[temperature]" value="' . esc_attr($temperature) . '" min="0" max="1" step="0.1" />';
        echo '<p class="description">' . __('Controls randomness in generation (0.0-1.0). Higher values make output more random.', 'contentcraft-ai') . '</p>';
    }
    
    public function enable_logging_callback() {
        $enable_logging = $this->settings->get_option('enable_logging', true);
        echo '<input type="checkbox" name="contentcraft_ai_settings[enable_logging]" value="1" ' . checked($enable_logging, true, false) . ' />';
        echo '<label>' . __('Enable error logging for debugging.', 'contentcraft-ai') . '</label>';
    }

    public function api_provider_callback() {
        $provider = $this->settings->get_option('api_provider', 'gemini');
        ?>
        <select name="contentcraft_ai_settings[api_provider]" id="api_provider">
            <option value="gemini" <?php selected($provider, 'gemini'); ?>><?php _e('Google Gemini', 'contentcraft-ai'); ?></option>
            <option value="cloudflare" <?php selected($provider, 'cloudflare'); ?>><?php _e('Cloudflare AI', 'contentcraft-ai'); ?></option>
            <option value="openrouter" <?php selected($provider, 'openrouter'); ?>><?php _e('OpenRouter', 'contentcraft-ai'); ?></option>
        </select>
        <?php
    }

    public function cloudflare_account_id_callback() {
        $account_id = $this->settings->get_option('cloudflare_account_id', '');
        echo '<input type="text" name="contentcraft_ai_settings[cloudflare_account_id]" value="' . esc_attr($account_id) . '" size="50" class="regular-text" />';
    }

    public function cloudflare_api_key_callback() {
        $api_key = $this->settings->get_option('cloudflare_api_key', '');
        echo '<input type="text" name="contentcraft_ai_settings[cloudflare_api_key]" value="' . esc_attr($api_key) . '" size="50" class="regular-text" />';
    }

    public function openrouter_api_section_callback() {
        echo '<p>' . __('Configure your OpenRouter AI connection settings.', 'contentcraft-ai') . '</p>';
    }

    public function openrouter_api_key_callback() {
        $api_key = $this->settings->get_option('openrouter_api_key', '');
        echo '<input type="text" name="contentcraft_ai_settings[openrouter_api_key]" value="' . esc_attr($api_key) . '" size="50" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from OpenRouter (https://openrouter.ai/keys)', 'contentcraft-ai') . '</p>';

        if (!empty($api_key)) {
            echo '<p class="description" style="color: green;">✓ ' . __('API key is configured', 'contentcraft-ai') . '</p>';
        } else {
            echo '<p class="description" style="color: orange;">⚠ ' . __('API key is required for OpenRouter to work', 'contentcraft-ai') . '</p>';
        }
    }

    public function openrouter_model_callback() {
        $model = $this->settings->get_option('openrouter_model', 'meta-llama/llama-3.2-3b-instruct:free');
        $handler = new ContentCraft_AI_OpenRouter_Handler();
        $free_models = $handler->get_free_models();

        echo '<select name="contentcraft_ai_settings[openrouter_model]" class="regular-text">';
        foreach ($free_models as $model_option) {
            echo '<option value="' . esc_attr($model_option) . '" ' . selected($model, $model_option, false) . '>' . esc_html($model_option) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select a free OpenRouter model.', 'contentcraft-ai') . '</p>';
    }

    public function enabled_post_types_callback() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $enabled_post_types = $this->settings->get_option('enabled_post_types', array('post', 'page'));

        foreach ($post_types as $post_type) {
            echo '<label style="margin-right: 15px;">';
            echo '<input type="checkbox" name="contentcraft_ai_settings[enabled_post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked(in_array($post_type->name, $enabled_post_types), true, false) . ' />';
            echo esc_html($post_type->labels->name);
            echo '</label>';
        }
    }

    public function schema_display_callback() {
        $schema = <<<JSON
{
  "enhanced_title": "Improved SEO-friendly title",
  "enhanced_content": "Enhanced content with proper HTML formatting including headings",
  "suggested_tags": ["tag1", "tag2", "tag3", "tag4", "tag5"],
  "meta_description": "SEO-optimized meta description (150-160 characters)",
  "focus_keyword": "primary keyword for this post"
}
JSON;
        echo '<pre style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; white-space: pre-wrap;">' . esc_html($schema) . '</pre>';
    }
}
