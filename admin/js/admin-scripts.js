/**
 * Admin Scripts for ContentCraft AI
 */

(function($) {
    'use strict';
    
    var ContentCraftAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },
        
        bindEvents: function() {
            // Test API connection
            $(document).on('click', '#test-api-connection', this.testApiConnection);
            
            // Load usage statistics
            this.loadUsageStats();
        },
        
        initializeComponents: function() {
            // Initialize any admin components here
            this.initializeTooltips();
        },
        
        initializeTooltips: function() {
            // Add tooltips to help elements
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');
                
                $element.attr('title', tooltip);
            });
        },
        
        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#api-test-result');
            
            $button.prop('disabled', true).text(contentcraft_ai_admin.strings.testing);
            $result.removeClass('success error').text('');
            
            $.ajax({
                url: contentcraft_ai_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'contentcraft_test_api',
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
                    $result.addClass('error').text('✗ ' + contentcraft_ai_admin.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test API Connection');
                }
            });
        },
        
        loadUsageStats: function() {
            var $container = $('#usage-stats-container');
            
            if (!$container.length) {
                return;
            }
            
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
                        html += '<div class="stat-item"><strong>Requests Used:</strong> ' + stats.current_usage + '</div>';
                        html += '<div class="stat-item"><strong>Rate Limit:</strong> ' + stats.rate_limit + '</div>';
                        html += '<div class="stat-item"><strong>Remaining:</strong> ' + stats.remaining + '</div>';
                        html += '</div>';
                        
                        $container.html(html);
                    }
                },
                error: function() {
                    $container.html('<p>Failed to load usage statistics.</p>');
                }
            });
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible">';
            noticeHtml += '<p>' + message + '</p>';
            noticeHtml += '</div>';
            
            $('.wrap h1').after(noticeHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.notice.is-dismissible').fadeOut();
            }, 5000);
        },
        
        validateSettings: function() {
            var isValid = true;
            var errors = [];
            
            // Validate API key
            var apiKey = $('input[name="contentcraft_ai_settings[api_key]"]').val();
            if (!apiKey || apiKey.trim() === '') {
                errors.push('API key is required');
                isValid = false;
            }
            
            // Validate max tokens
            var maxTokens = parseInt($('input[name="contentcraft_ai_settings[max_tokens]"]').val());
            if (isNaN(maxTokens) || maxTokens < 100 || maxTokens > 4000) {
                errors.push('Max tokens must be between 100 and 4000');
                isValid = false;
            }
            
            // Validate temperature
            var temperature = parseFloat($('input[name="contentcraft_ai_settings[temperature]"]').val());
            if (isNaN(temperature) || temperature < 0 || temperature > 1) {
                errors.push('Temperature must be between 0.0 and 1.0');
                isValid = false;
            }
            
            if (!isValid) {
                this.showNotice('Please fix the following errors: ' + errors.join(', '), 'error');
            }
            
            return isValid;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ContentCraftAdmin.init();
    });
    
    // Make ContentCraftAdmin available globally
    window.ContentCraftAdmin = ContentCraftAdmin;
    
})(jQuery);