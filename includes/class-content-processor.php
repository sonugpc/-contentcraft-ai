<?php
/**
 * Content Processor class for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentCraft_AI_Content_Processor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize processor
    }
    
    /**
     * Parse Gutenberg blocks
     */
    public function parse_gutenberg_blocks($content) {
        if (function_exists('parse_blocks')) {
            return parse_blocks($content);
        }
        
        // Fallback for older WordPress versions
        return $this->parse_blocks_fallback($content);
    }
    
    /**
     * Fallback block parser
     */
    private function parse_blocks_fallback($content) {
        $blocks = array();
        
        // Simple regex to find block comments
        $pattern = '/<!--\s*wp:([a-zA-Z0-9\-\/]+)(?:\s+({[^}]*}))?\s*-->/';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $block_name = $match[1];
            $attributes = isset($match[2]) ? json_decode($match[2], true) : array();
            
            $blocks[] = array(
                'blockName' => $block_name,
                'attrs' => $attributes ? $attributes : array(),
                'innerHTML' => '',
                'innerContent' => array()
            );
        }
        
        return $blocks;
    }
    
    /**
     * Extract text content from blocks
     */
    public function extract_text_content($content) {
        // Check if it's block content
        if (has_blocks($content)) {
            $blocks = $this->parse_gutenberg_blocks($content);
            return $this->extract_text_from_blocks($blocks);
        }
        
        // For classic editor content
        return $this->extract_text_from_html($content);
    }
    
    /**
     * Extract text from blocks
     */
    private function extract_text_from_blocks($blocks) {
        $text_content = '';
        
        foreach ($blocks as $block) {
            if (isset($block['innerHTML'])) {
                $text_content .= $this->extract_text_from_html($block['innerHTML']) . "\n\n";
            }
            
            if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                $text_content .= $this->extract_text_from_blocks($block['innerBlocks']);
            }
        }
        
        return trim($text_content);
    }
    
    /**
     * Extract text from HTML
     */
    private function extract_text_from_html($html) {
        // Remove scripts and styles
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        
        // Strip HTML tags
        $text = wp_strip_all_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Preserve block structure while updating content
     */
    public function preserve_block_structure($original_content, $enhanced_content) {
        // Check if original content has blocks
        if (!has_blocks($original_content)) {
            return $enhanced_content;
        }
        
        $blocks = $this->parse_gutenberg_blocks($original_content);
        
        // Simple approach: replace paragraph blocks with enhanced content
        $enhanced_paragraphs = explode("\n\n", $enhanced_content);
        $paragraph_index = 0;
        
        foreach ($blocks as &$block) {
            if ($block['blockName'] === 'core/paragraph' && isset($enhanced_paragraphs[$paragraph_index])) {
                // Update the paragraph content
                $new_content = trim($enhanced_paragraphs[$paragraph_index]);
                $block['innerHTML'] = '<p>' . esc_html($new_content) . '</p>';
                $block['innerContent'] = array('<p>' . esc_html($new_content) . '</p>');
                $paragraph_index++;
            }
        }
        
        // Reconstruct the content
        return $this->reconstruct_blocks($blocks);
    }
    
    /**
     * Reconstruct blocks into content
     */
    private function reconstruct_blocks($blocks) {
        $content = '';
        
        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                // Plain text block
                $content .= $block['innerHTML'];
            } else {
                // Block with comments
                $content .= '<!-- wp:' . $block['blockName'];
                
                if (!empty($block['attrs'])) {
                    $content .= ' ' . wp_json_encode($block['attrs']);
                }
                
                $content .= ' -->';
                $content .= $block['innerHTML'];
                $content .= '<!-- /wp:' . $block['blockName'] . ' -->';
            }
            
            $content .= "\n\n";
        }
        
        return trim($content);
    }
    
    /**
     * Process variables in content
     */
    public function process_variables($template, $post_data) {
        $variables = array(
            '{post_title}' => isset($post_data['title']) ? $post_data['title'] : '',
            '{post_content}' => isset($post_data['content']) ? $post_data['content'] : '',
            '{tags}' => isset($post_data['tags']) ? $post_data['tags'] : '',
            '{categories}' => isset($post_data['categories']) ? $post_data['categories'] : '',
            '{excerpt}' => isset($post_data['excerpt']) ? $post_data['excerpt'] : '',
            '{author}' => isset($post_data['author']) ? $post_data['author'] : '',
            '{date}' => isset($post_data['date']) ? $post_data['date'] : date('Y-m-d')
        );
        
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
    
    /**
     * Sanitize content for safety while preserving block structure
     */
    public function sanitize_content($content) {
        // Check if this is block content
        if (strpos($content, '<!-- wp:') !== false) {
            // Block content - preserve structure but remove dangerous elements
            $content = trim($content);
            
            // Remove scripts and dangerous elements but keep block comments
            $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
            $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
            $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
            
            // Don't remove empty paragraphs in block content as they might be intentional
            
        } else {
            // Regular content - use standard sanitization
            $content = wp_kses_post($content);
            
            // Remove empty paragraphs for regular content
            $content = preg_replace('/<p[^>]*>[\s]*<\/p>/i', '', $content);
            
            // Clean up excessive whitespace for regular content
            $content = preg_replace('/\s+/', ' ', $content);
        }
        
        // Trim whitespace
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Convert plain text to blocks
     */
    public function convert_text_to_blocks($text) {
        $paragraphs = explode("\n\n", $text);
        $blocks = array();
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            
            if (empty($paragraph)) {
                continue;
            }
            
            // Check if it's a heading
            if (preg_match('/^(#{1,6})\s+(.+)$/', $paragraph, $matches)) {
                $level = strlen($matches[1]);
                $heading_text = $matches[2];
                
                $blocks[] = array(
                    'blockName' => 'core/heading',
                    'attrs' => array('level' => $level),
                    'innerHTML' => '<h' . $level . '>' . esc_html($heading_text) . '</h' . $level . '>',
                    'innerContent' => array('<h' . $level . '>' . esc_html($heading_text) . '</h' . $level . '>')
                );
            } else {
                // Regular paragraph
                $blocks[] = array(
                    'blockName' => 'core/paragraph',
                    'attrs' => array(),
                    'innerHTML' => '<p>' . esc_html($paragraph) . '</p>',
                    'innerContent' => array('<p>' . esc_html($paragraph) . '</p>')
                );
            }
        }
        
        return $this->reconstruct_blocks($blocks);
    }
    
    /**
     * Get content summary
     */
    public function get_content_summary($content) {
        $text = $this->extract_text_content($content);
        
        $word_count = str_word_count($text);
        $char_count = strlen($text);
        
        // Count paragraphs
        $paragraphs = explode("\n\n", $text);
        $paragraph_count = count(array_filter($paragraphs, 'trim'));
        
        return array(
            'word_count' => $word_count,
            'char_count' => $char_count,
            'paragraph_count' => $paragraph_count,
            'has_blocks' => has_blocks($content),
            'reading_time' => ceil($word_count / 200) // Assuming 200 words per minute
        );
    }
    
    /**
     * Validate content structure
     */
    public function validate_content_structure($content) {
        $errors = array();
        
        // Check for empty content
        if (empty(trim($content))) {
            $errors[] = __('Content is empty.', 'contentcraft-ai');
        }
        
        // Check for very short content
        $word_count = str_word_count($this->extract_text_content($content));
        if ($word_count < 10) {
            $errors[] = __('Content is too short.', 'contentcraft-ai');
        }
        
        // Check for malformed blocks
        if (has_blocks($content)) {
            $blocks = $this->parse_gutenberg_blocks($content);
            
            if (empty($blocks)) {
                $errors[] = __('No valid blocks found in content.', 'contentcraft-ai');
            }
        }
        
        return $errors;
    }
}