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
        },
        
        initializeComponents: function() {
            // Initialize any admin components here
            this.initializeTooltips();
            this.initializeProviderSettings();
        },

        initializeProviderSettings: function() {
            var providerSelect = $('#api_provider');
            var geminiSettings = $('input[name="contentcraft_ai_settings[api_key]"]').closest('tr');
            var cloudflareAccountIdSettings = $('input[name="contentcraft_ai_settings[cloudflare_account_id]"]').closest('tr');
            var cloudflareApiKeySettings = $('input[name="contentcraft_ai_settings[cloudflare_api_key]"]').closest('tr');

            function toggleSettings() {
                var provider = providerSelect.val();
                if (provider === 'gemini') {
                    geminiSettings.show();
                    cloudflareAccountIdSettings.hide();
                    cloudflareApiKeySettings.hide();
                } else if (provider === 'cloudflare') {
                    geminiSettings.hide();
                    cloudflareAccountIdSettings.show();
                    cloudflareApiKeySettings.show();
                }
            }

            toggleSettings();
            providerSelect.on('change', toggleSettings);
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
