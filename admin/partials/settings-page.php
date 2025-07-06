<?php
/**
 * Settings page template for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('contentcraft_ai_settings_group');
        do_settings_sections('contentcraft-ai-settings');
        ?>
        
        <div class="contentcraft-ai-test-section">
            <h2><?php _e('Test API Connection', 'contentcraft-ai'); ?></h2>
            <p><?php _e('Test your API connection to ensure everything is working correctly.', 'contentcraft-ai'); ?></p>
            <p>
                <button type="button" id="test-api-connection" class="button button-secondary">
                    <?php _e('Test API Connection', 'contentcraft-ai'); ?>
                </button>
                <span id="api-test-result"></span>
            </p>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="contentcraft-ai-usage-stats">
        <h2><?php _e('Usage Statistics', 'contentcraft-ai'); ?></h2>
        <div id="usage-stats-container">
            <p><?php _e('Loading usage statistics...', 'contentcraft-ai'); ?></p>
        </div>
    </div>
    
    <div class="contentcraft-ai-help">
        <h2><?php _e('Help & Documentation', 'contentcraft-ai'); ?></h2>
        <div class="help-content">
            <h3><?php _e('Getting Started', 'contentcraft-ai'); ?></h3>
            <ol>
                <li><?php _e('Get your Gemini API key from Google AI Studio', 'contentcraft-ai'); ?></li>
                <li><?php _e('Enter your API key in the configuration above', 'contentcraft-ai'); ?></li>
                <li><?php _e('Test the connection to ensure it\'s working', 'contentcraft-ai'); ?></li>
                <li><?php _e('Customize the prompt templates to match your needs', 'contentcraft-ai'); ?></li>
                <li><?php _e('Start using the AI features in your post editor', 'contentcraft-ai'); ?></li>
            </ol>
            
            <h3><?php _e('Available Variables', 'contentcraft-ai'); ?></h3>
            <p><?php _e('You can use these variables in your prompt templates:', 'contentcraft-ai'); ?></p>
            <ul>
                <li><code>{post_title}</code> - <?php _e('The current post title', 'contentcraft-ai'); ?></li>
                <li><code>{post_content}</code> - <?php _e('The current post content', 'contentcraft-ai'); ?></li>
                <li><code>{tags}</code> - <?php _e('Post tags (comma-separated)', 'contentcraft-ai'); ?></li>
                <li><code>{categories}</code> - <?php _e('Post categories (comma-separated)', 'contentcraft-ai'); ?></li>
                <li><code>{excerpt}</code> - <?php _e('Post excerpt', 'contentcraft-ai'); ?></li>
                <li><code>{author}</code> - <?php _e('Post author name', 'contentcraft-ai'); ?></li>
                <li><code>{date}</code> - <?php _e('Post publication date', 'contentcraft-ai'); ?></li>
            </ul>
            
            <h3><?php _e('Features', 'contentcraft-ai'); ?></h3>
            <ul>
                <li><?php _e('Content Enhancement - Improve existing content', 'contentcraft-ai'); ?></li>
                <li><?php _e('Content Generation - Create new content from scratch', 'contentcraft-ai'); ?></li>
                <li><?php _e('Block Editor Integration - Works with Gutenberg', 'contentcraft-ai'); ?></li>
                <li><?php _e('Classic Editor Support - Works with Classic Editor', 'contentcraft-ai'); ?></li>
                <li><?php _e('Rate Limiting - Prevents API overuse', 'contentcraft-ai'); ?></li>
                <li><?php _e('Error Logging - Debug API issues', 'contentcraft-ai'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test API connection
    $('#test-api-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#api-test-result');
        
        $button.prop('disabled', true).text('<?php _e('Testing...', 'contentcraft-ai'); ?>');
        $result.removeClass('success error').text('');
        
        $.ajax({
            url: contentcraft_ai_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'contentcraft_test_api_connection',
                nonce: contentcraft_ai_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('success').text('✓ ' + response.data.message);
                } else {
                    $result.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                $result.addClass('error').text('✗ <?php _e('Connection failed', 'contentcraft-ai'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php _e('Test API Connection', 'contentcraft-ai'); ?>');
            }
        });
    });
    
    // Load usage statistics
    function loadUsageStats() {
        $.ajax({
            url: contentcraft_ai_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'contentcraft_get_usage_stats',
                nonce: contentcraft_ai_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    var html = '<div class="usage-stats">';
                    html += '<div class="stat-item"><strong><?php _e('Requests Used:', 'contentcraft-ai'); ?></strong> ' + stats.current_usage + '</div>';
                    html += '<div class="stat-item"><strong><?php _e('Rate Limit:', 'contentcraft-ai'); ?></strong> ' + stats.rate_limit + '</div>';
                    html += '<div class="stat-item"><strong><?php _e('Remaining:', 'contentcraft-ai'); ?></strong> ' + stats.remaining + '</div>';
                    html += '</div>';
                    
                    $('#usage-stats-container').html(html);
                }
            },
            error: function() {
                $('#usage-stats-container').html('<p><?php _e('Failed to load usage statistics.', 'contentcraft-ai'); ?></p>');
            }
        });
    }
    
    // Load stats on page load
    loadUsageStats();
});
</script>

<style>
.contentcraft-ai-test-section {
    background: #f9f9f9;
    padding: 20px;
    margin: 20px 0;
    border-radius: 5px;
}

.contentcraft-ai-usage-stats {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
    border-radius: 5px;
}

.contentcraft-ai-help {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
    border-radius: 5px;
}

.help-content ul, .help-content ol {
    padding-left: 20px;
}

.help-content code {
    background: #f0f0f0;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

#api-test-result {
    margin-left: 10px;
    font-weight: bold;
}

#api-test-result.success {
    color: #00a32a;
}

#api-test-result.error {
    color: #d63638;
}

.usage-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    background: #f0f0f0;
    padding: 10px;
    border-radius: 3px;
    min-width: 150px;
}
</style>