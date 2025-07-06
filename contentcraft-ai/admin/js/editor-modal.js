/**
 * Editor Modal Scripts for ContentCraft AI
 */

(function($) {
    'use strict';
    
    var ContentCraftModal = {
        
        isOpen: false,
        currentEditor: null,
        currentContent: '',
        lastEnhancedContent: '',
        lastGeneratedContent: '',
        
        init: function() {
            console.log('ContentCraft AI: Initializing modal functionality');
            this.bindEvents();
            this.addEditorButtons();
            console.log('ContentCraft AI: Modal initialization complete');
        },
        
        bindEvents: function() {
            var self = this;
            
            // Modal controls
            $(document).on('click', '.contentcraft-modal-close, #contentcraft-ai-overlay', function() {
                self.close();
            });
            
            // Tab switching
            $(document).on('click', '.contentcraft-tab-button', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Enhancement controls
            $(document).on('click', '#enhance-content-btn', function() {
                console.log('ContentCraft AI: Enhance content button clicked');
                self.enhanceContent();
            });
            
            $(document).on('click', '#accept-enhanced-btn', function() {
                self.acceptEnhancedContent();
            });
            
            $(document).on('click', '#reject-enhanced-btn', function() {
                self.close();
            });
            
            $(document).on('click', '#regenerate-enhanced-btn', function() {
                self.enhanceContent();
            });
            
            // Generation controls
            $(document).on('click', '#generate-content-btn', function() {
                self.generateContent();
            });
            
            $(document).on('click', '#accept-generated-btn', function() {
                self.acceptGeneratedContent();
            });
            
            $(document).on('click', '#reject-generated-btn', function() {
                self.close();
            });
            
            $(document).on('click', '#regenerate-content-btn', function() {
                self.generateContent();
            });
            
            // Escape key to close
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.isOpen) {
                    self.close();
                }
            });
            
            // Prevent modal content click from closing modal
            $(document).on('click', '.contentcraft-modal-content', function(e) {
                e.stopPropagation();
            });
        },
        
        addEditorButtons: function() {
            var self = this;
            
            // Add button to Classic Editor
            $(document).on('click', '#contentcraft-ai-classic-button', function(e) {
                e.preventDefault();
                console.log('ContentCraft AI: Classic editor button clicked');
                self.open('classic');
            });
            
            // Add button to Gutenberg (handled by separate script)
            // Check if Gutenberg is available
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                this.addGutenbergButton();
            }
        },
        
        addGutenbergButton: function() {
            var self = this;
            
            // Add floating button for Gutenberg
            if ($('#contentcraft-ai-gutenberg-button').length === 0) {
                var buttonHtml = '<div id="contentcraft-ai-gutenberg-button" class="contentcraft-editor-button contentcraft-floating-button">';
                buttonHtml += '<span class="dashicons dashicons-admin-generic"></span>';
                buttonHtml += '<span class="button-text">ContentCraft AI</span>';
                buttonHtml += '</div>';
                
                $('body').append(buttonHtml);
                
                $('#contentcraft-ai-gutenberg-button').on('click', function() {
                    self.open('gutenberg');
                });
            }
        },
        
        open: function(editor) {
            this.currentEditor = editor;
            this.isOpen = true;
            
            // Get current content
            this.currentContent = this.getCurrentContent();
            
            // Show modal
            $('#contentcraft-ai-modal, #contentcraft-ai-overlay').show();
            $('body').addClass('contentcraft-modal-open');
            
            // Load current content preview
            this.loadCurrentContentPreview();
            
            // Load usage info
            this.loadUsageInfo();
            
            // Focus on modal
            $('#contentcraft-ai-modal').focus();
        },
        
        close: function() {
            this.isOpen = false;
            this.currentEditor = null;
            this.currentContent = '';
            
            $('#contentcraft-ai-modal, #contentcraft-ai-overlay').hide();
            $('body').removeClass('contentcraft-modal-open');
            
            // Reset modal state
            this.resetModal();
        },
        
        switchTab: function(tab) {
            $('.contentcraft-tab-button').removeClass('active');
            $('.contentcraft-tab-content').removeClass('active');
            
            $('[data-tab="' + tab + '"]').addClass('active');
            $('#' + tab + '-tab').addClass('active');
            
            // Load content for generation tab
            if (tab === 'generate') {
                this.loadGenerationOptions();
            }
        },
        
        getCurrentContent: function() {
            var content = '';
            
            if (this.currentEditor === 'classic') {
                // Classic Editor - get raw content with HTML
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    content = tinymce.get('content').getContent();
                } else if ($('#content').length) {
                    content = $('#content').val();
                }
            } else if (this.currentEditor === 'gutenberg') {
                // Gutenberg Editor - get raw content with block markup
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    content = wp.data.select('core/editor').getEditedPostContent();
                }
            }
            
            console.log('ContentCraft AI: Raw content retrieved (length: ' + content.length + ')');
            console.log('ContentCraft AI: Content preview:', content.substring(0, 200) + '...');
            
            return content;
        },
        
        getCurrentTitle: function() {
            if (this.currentEditor === 'classic') {
                return $('#title').val() || '';
            } else if (this.currentEditor === 'gutenberg') {
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    return wp.data.select('core/editor').getEditedPostAttribute('title') || '';
                }
            }
            
            return '';
        },
        
        getCurrentTags: function() {
            var tags = [];
            
            if (this.currentEditor === 'gutenberg') {
                // Gutenberg Editor - get tags from the editor data
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    var tagTerms = wp.data.select('core/editor').getEditedPostAttribute('tags');
                    if (tagTerms && tagTerms.length > 0) {
                        // Get tag names from IDs
                        tagTerms.forEach(function(tagId) {
                            var tag = wp.data.select('core').getEntityRecord('taxonomy', 'post_tag', tagId);
                            if (tag && tag.name) {
                                tags.push(tag.name);
                            }
                        });
                    }
                }
            } else {
                // Classic Editor - get tags from the tag box
                $('.tagchecklist span a').each(function() {
                    var tag = $(this).text().trim();
                    if (tag && tag !== 'X') {
                        tags.push(tag);
                    }
                });
                
                // Also check the tag input field
                var tagInput = $('#new-tag-post_tag').val();
                if (tagInput && tagInput.trim()) {
                    tags.push(tagInput.trim());
                }
            }
            
            return tags.join(', ');
        },
        
        setContent: function(content) {
            if (this.currentEditor === 'classic') {
                // Classic Editor
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').setContent(content);
                } else if ($('#content').length) {
                    $('#content').val(content);
                }
            } else if (this.currentEditor === 'gutenberg') {
                // Gutenberg Editor
                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                    wp.data.dispatch('core/editor').editPost({
                        content: content
                    });
                }
            }
        },
        
        loadCurrentContentPreview: function() {
            var content = this.currentContent;
            var preview = '';
            
            if (content) {
                // Show both raw structure info and text preview
                var textContent = this.stripHtml(content);
                var hasBlocks = content.includes('<!-- wp:');
                var hasHtml = content.includes('<') && content.includes('>');
                
                preview = '<div class="content-structure-info">';
                if (hasBlocks) {
                    preview += '<span class="structure-tag">Gutenberg Blocks</span> ';
                }
                if (hasHtml) {
                    preview += '<span class="structure-tag">HTML Content</span> ';
                }
                preview += '<span class="content-length">' + content.length + ' characters</span>';
                preview += '</div>';
                
                preview += '<div class="content-text-preview">' + this.escapeHtml(textContent.substring(0, 400)) + (textContent.length > 400 ? '...' : '') + '</div>';
                
                // Show a small sample of raw content for debugging
                preview += '<details class="raw-content-sample"><summary>Raw Content Sample</summary>';
                preview += '<pre>' + this.escapeHtml(content.substring(0, 200)) + (content.length > 200 ? '...' : '') + '</pre>';
                preview += '</details>';
            } else {
                preview = '<div class="no-content">No content available</div>';
            }
            
            $('#current-content-preview').html('<div class="content-preview">' + preview + '</div>');
        },
        
        loadGenerationOptions: function() {
            var title = this.getCurrentTitle();
            var tags = this.getCurrentTags();
            
            $('#generation-title').val(title);
            $('#generation-tags').val(tags);
        },
        
        enhanceContent: function() {
            var self = this;
            var title = this.getCurrentTitle();
            var content = this.currentContent;
            var tags = this.getCurrentTags();
            
            console.log('ContentCraft AI: Starting content enhancement');
            console.log('Title:', title);
            console.log('Content length:', content ? content.length : 0);
            console.log('Tags:', tags);
            
            if (!content || content.trim() === '') {
                this.showError('No content to enhance');
                return;
            }
            
            // Show loading
            $('#enhance-loading').show();
            $('#enhance-content-btn').prop('disabled', true);
            $('#enhanced-content-preview').hide();
            
            var ajaxData = {
                action: 'contentcraft_enhance_content',
                nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : '',
                title: title,
                content: content,
                tags: tags
            };
            
            console.log('ContentCraft AI: Making AJAX request');
            console.log('URL:', (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl);
            console.log('Data:', ajaxData);
            
            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('ContentCraft AI: AJAX response received', response);
                    if (response.success) {
                        self.showEnhancedContent(response.data.content);
                    } else {
                        self.showError(response.data.message || 'Enhancement failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ContentCraft AI: AJAX error', xhr, status, error);
                    self.showError('Request failed. Please try again. Error: ' + error);
                },
                complete: function() {
                    $('#enhance-loading').hide();
                    $('#enhance-content-btn').prop('disabled', false);
                }
            });
        },
        
        generateContent: function() {
            var self = this;
            var title = $('#generation-title').val();
            var tags = $('#generation-tags').val();
            var length = $('#generation-length').val();
            
            if (!title || title.trim() === '') {
                this.showError('Title is required for content generation');
                return;
            }
            
            // Show loading
            $('#generate-loading').show();
            $('#generate-content-btn').prop('disabled', true);
            $('#generated-content-preview').hide();
            
            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'contentcraft_generate_content',
                    nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : '',
                    title: title,
                    tags: tags,
                    length: length
                },
                success: function(response) {
                    if (response.success) {
                        self.showGeneratedContent(response.data.content);
                    } else {
                        self.showError(response.data.message || 'Generation failed');
                    }
                },
                error: function() {
                    self.showError('Request failed. Please try again.');
                },
                complete: function() {
                    $('#generate-loading').hide();
                    $('#generate-content-btn').prop('disabled', false);
                }
            });
        },
        
        showEnhancedContent: function(content) {
            this.lastEnhancedContent = content; // Store for later retrieval
            var preview = this.createContentPreview(content, 'Enhanced');
            $('#enhanced-content').html(preview);
            $('#enhanced-content-preview').show();
        },
        
        showGeneratedContent: function(content) {
            this.lastGeneratedContent = content; // Store for later retrieval
            var preview = this.createContentPreview(content, 'Generated');
            $('#generated-content').html(preview);
            $('#generated-content-preview').show();
        },
        
        createContentPreview: function(content, type) {
            var textContent = this.stripHtml(content);
            var hasBlocks = content.includes('<!-- wp:');
            var hasHtml = content.includes('<') && content.includes('>');
            
            var preview = '<div class="content-preview">';
            
            // Structure info
            preview += '<div class="content-structure-info">';
            preview += '<strong>' + type + ' Content:</strong> ';
            if (hasBlocks) {
                preview += '<span class="structure-tag">Gutenberg Blocks</span> ';
            }
            if (hasHtml) {
                preview += '<span class="structure-tag">HTML Content</span> ';
            }
            preview += '<span class="content-length">' + content.length + ' characters</span>';
            preview += '</div>';
            
            // Text preview
            preview += '<div class="content-text-preview">' + this.escapeHtml(textContent.substring(0, 500)) + (textContent.length > 500 ? '...' : '') + '</div>';
            
            // Raw content sample
            preview += '<details class="raw-content-sample"><summary>Full ' + type + ' Content (Raw)</summary>';
            preview += '<pre>' + this.escapeHtml(content) + '</pre>';
            preview += '</details>';
            
            preview += '</div>';
            
            return preview;
        },
        
        acceptEnhancedContent: function() {
            // Get the raw content from the details element
            var content = $('#enhanced-content .raw-content-sample pre').text();
            if (!content) {
                // Fallback to getting from stored variable if available
                content = this.lastEnhancedContent || '';
            }
            
            console.log('ContentCraft AI: Accepting enhanced content (length: ' + content.length + ')');
            this.setContent(content);
            this.close();
        },
        
        acceptGeneratedContent: function() {
            // Get the raw content from the details element
            var content = $('#generated-content .raw-content-sample pre').text();
            if (!content) {
                // Fallback to getting from stored variable if available
                content = this.lastGeneratedContent || '';
            }
            
            console.log('ContentCraft AI: Accepting generated content (length: ' + content.length + ')');
            this.setContent(content);
            this.close();
        },
        
        loadUsageInfo: function() {
            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'contentcraft_get_usage_stats',
                    nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success) {
                        var stats = response.data;
                        $('#usage-info-text').text('Usage: ' + stats.current_usage + '/' + stats.rate_limit + ' requests this hour');
                    }
                }
            });
        },
        
        resetModal: function() {
            $('.contentcraft-result').hide();
            $('.contentcraft-loading').hide();
            $('#enhanced-content, #generated-content').empty();
            $('#generation-title, #generation-tags').val('');
            $('#generation-length').val('medium');
            
            // Clear stored content
            this.lastEnhancedContent = '';
            this.lastGeneratedContent = '';
            
            // Reset to enhance tab
            this.switchTab('enhance');
        },
        
        showError: function(message) {
            var errorHtml = '<div class="contentcraft-error">';
            errorHtml += '<p><strong>Error:</strong> ' + this.escapeHtml(message) + '</p>';
            errorHtml += '</div>';
            
            $('.contentcraft-modal-body').prepend(errorHtml);
            
            // Auto-remove error after 5 seconds
            setTimeout(function() {
                $('.contentcraft-error').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        stripHtml: function(html) {
            var tmp = document.createElement('DIV');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        },
        
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ContentCraftModal.init();
    });
    
    // Make ContentCraftModal available globally
    window.ContentCraftModal = ContentCraftModal;
    
})(jQuery);