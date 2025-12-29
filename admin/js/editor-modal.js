/**
 * Editor Modal Scripts for ContentCraft AI
 */

(function($) {
    'use strict';
    
    var ContentCraftEditor = {
        
        currentEditor: null,
        currentContent: '',
        lastEnhancedContent: '',
        lastGeneratedContent: '',
        chatHistory: [],
        
        init: function() {
            this.determineEditor();
            this.bindEvents();
            this.loadInitialData();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Tab switching
            $(document).on('click', '.contentcraft-tab-button', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Content change detection for classic editor
            $(document).on('input keyup', '#content', function() {
                // Debounce content refresh
                clearTimeout(self.contentRefreshTimeout);
                self.contentRefreshTimeout = setTimeout(function() {
                    if ($('.contentcraft-tab-button[data-tab="enhance"]').hasClass('active')) {
                        self.loadCurrentContentPreview();
                    }
                }, 1000);
            });
            
            // Enhancement controls
            $(document).on('click', '#enhance-content-btn', function() {
                self.enhanceContent();
            });
            
            $(document).on('click', '#accept-enhanced-btn', function() {
                self.acceptEnhancedContent();
            });
            
            $(document).on('click', '#reject-enhanced-btn', function() {
                $('#enhanced-content-preview').hide();
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
                $('#generated-content-preview').hide();
            });
            
            $(document).on('click', '#regenerate-content-btn', function() {
                self.generateContent();
            });

            // Query controls
            $(document).on('click', '#general-query-btn', function() {
                self.submitGeneralQuery();
            });

            $(document).on('click', '#insert-query-result-btn', function() {
                self.insertQueryResult();
            });

            $(document).on('click', '#copy-query-result-btn', function() {
                self.copyQueryResult();
            });

            // Internal links controls
            $(document).on('click', '#fetch-internal-links-btn', function() {
                self.fetchInternalLinks();
            });

            // Parse JSON controls
            $(document).on('click', '#parse-json-btn', function() {
                self.parseAndInsertJson();
            });

            // Chat controls
            $(document).on('click', '#contentcraft-ai-chat-send', function() {
                self.sendChatMessage();
            });

            // Saved prompts controls
            $(document).on('click', '.prompt-item', function() {
                $('#contentcraft-ai-chat-message').val($(this).text());
            });

            $(document).on('click', '.delete-prompt', function(e) {
                e.stopPropagation();
                var promptText = $(this).parent().text();
                self.deleteSavedPrompt(promptText);
            });
        },
        
        determineEditor: function() {
            // Check for Gutenberg first
            if ($('body').hasClass('block-editor-page') || 
                (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor'))) {
                this.currentEditor = 'gutenberg';
                console.log('ContentCraft AI: Detected Gutenberg editor');
            } else if (typeof tinymce !== 'undefined' || $('#content').length) {
                this.currentEditor = 'classic';
                console.log('ContentCraft AI: Detected Classic editor');
            } else {
                // Fallback - assume classic
                this.currentEditor = 'classic';
                console.log('ContentCraft AI: Could not detect editor type, defaulting to classic');
            }
        },
        
        loadInitialData: function() {
            var self = this;
            
            // Load non-content dependent data immediately
            this.loadDefaultPrompts();
            this.loadUsageInfo();
            
            // Delay content loading to ensure editor is ready
            setTimeout(function() {
                self.currentContent = self.getCurrentContent();
                self.loadCurrentContentPreview();
            }, 1000);
            
            // Also set up a delayed retry in case the editor takes longer to load
            setTimeout(function() {
                var content = self.getCurrentContent();
                if (!content || content.trim() === '') {
                    console.log('ContentCraft AI: Retrying content detection...');
                    self.loadCurrentContentPreview();
                }
                
                // Set up TinyMCE change listener if available
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').on('change keyup', function() {
                        clearTimeout(self.contentRefreshTimeout);
                        self.contentRefreshTimeout = setTimeout(function() {
                            if ($('.contentcraft-tab-button[data-tab="enhance"]').hasClass('active')) {
                                self.loadCurrentContentPreview();
                            }
                        }, 1000);
                    });
                }
                
                // Set up Gutenberg change listener if available
                if (typeof wp !== 'undefined' && wp.data) {
                    var previousContent = '';
                    var checkGutenbergChanges = setInterval(function() {
                        if (wp.data.select('core/editor')) {
                            var currentContent = wp.data.select('core/editor').getEditedPostContent();
                            if (currentContent !== previousContent) {
                                previousContent = currentContent;
                                clearTimeout(self.contentRefreshTimeout);
                                self.contentRefreshTimeout = setTimeout(function() {
                                    if ($('.contentcraft-tab-button[data-tab="enhance"]').hasClass('active')) {
                                        self.loadCurrentContentPreview();
                                    }
                                }, 1000);
                            }
                        }
                    }, 2000);
                    
                    // Store interval ID for cleanup if needed
                    self.gutenbergWatcher = checkGutenbergChanges;
                }
            }, 3000);
        },
        
        switchTab: function(tab) {
            $('.contentcraft-tab-button').removeClass('active');
            $('.contentcraft-tab-content').removeClass('active');
            
            $('[data-tab="' + tab + '"]').addClass('active');
            $('#' + tab + '-tab').addClass('active');
            
            // Refresh content when switching to enhance tab
            if (tab === 'enhance') {
                this.currentContent = this.getCurrentContent();
                this.loadCurrentContentPreview();
            }
            
            // Load content for generation tab
            if (tab === 'generate') {
                this.loadGenerationOptions();
            }

            if (tab === 'chat') {
                this.loadSavedPrompts();
            }
        },
        
        getCurrentContent: function() {
            var content = '';
            
            console.log('ContentCraft AI: Getting content from editor type:', this.currentEditor);
            
            if (this.currentEditor === 'classic') {
                // Classic Editor - get raw content with HTML
                if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
                    content = tinymce.get('content').getContent();
                    console.log('ContentCraft AI: Got content from TinyMCE');
                } else if ($('#content').length) {
                    content = $('#content').val();
                    console.log('ContentCraft AI: Got content from textarea #content');
                } else {
                    console.log('ContentCraft AI: No TinyMCE or #content textarea found');
                }
            } else if (this.currentEditor === 'gutenberg') {
                // Gutenberg Editor - get raw content with block markup
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    content = wp.data.select('core/editor').getEditedPostContent();
                    console.log('ContentCraft AI: Got content from Gutenberg');
                } else {
                    console.log('ContentCraft AI: Gutenberg editor not available');
                }
            }
            
            console.log('ContentCraft AI: Raw content retrieved (length: ' + content.length + ')');
            if (content.length > 0) {
                console.log('ContentCraft AI: Content preview:', content.substring(0, 200) + '...');
            } else {
                console.log('ContentCraft AI: No content found!');
            }
            
            return content;
        },
        
        // Debug function to manually check content detection
        debugContentDetection: function() {
            console.log('=== ContentCraft AI Debug Info ===');
            console.log('Editor Type:', this.currentEditor);
            console.log('TinyMCE Available:', typeof tinymce !== 'undefined');
            console.log('TinyMCE Content Editor:', typeof tinymce !== 'undefined' && tinymce.get('content'));
            console.log('jQuery Content Textarea:', $('#content').length > 0);
            console.log('WordPress Data Available:', typeof wp !== 'undefined' && wp.data);
            console.log('Gutenberg Editor Available:', typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor'));
            
            var content = this.getCurrentContent();
            console.log('Current Content Length:', content.length);
            console.log('Current Content Preview:', content.substring(0, 100));
            console.log('=== End Debug Info ===');
            
            return {
                editorType: this.currentEditor,
                contentLength: content.length,
                content: content
            };
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
            var self = this;

            // Re-determine editor type in case it changed
            this.determineEditor();

            try {
                if (this.currentEditor === 'classic') {
                    // Try TinyMCE first
                    if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
                        tinymce.get('content').setContent(content);
                        // Also update the textarea
                        $('#content').val(content).trigger('change');
                    } else if ($('#content').length) {
                        $('#content').val(content).trigger('change');

                        // If TinyMCE becomes available later, update it too
                        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                            setTimeout(function() {
                                if (!tinymce.get('content').isHidden()) {
                                    tinymce.get('content').setContent(content);
                                }
                            }, 100);
                        }
                    } else {
                        throw new Error('Classic editor content area not found.');
                    }
                } else if (this.currentEditor === 'gutenberg') {
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').editPost({ content: content });

                        // Simple verification for Gutenberg
                        setTimeout(function() {
                            if (wp.data.select('core/editor')) {
                                var currentContent = wp.data.select('core/editor').getEditedPostContent();
                                if (!currentContent || currentContent.length === 0) {
                                    wp.data.dispatch('core/editor').editPost({ content: content });
                                }
                            }
                        }, 300);
                    } else {
                        throw new Error('Gutenberg editor not available.');
                    }
                } else {
                    throw new Error('Unknown editor type: ' + this.currentEditor);
                }
            } catch (e) {
                console.error('ContentCraft AI: Error setting content:', e);
                this.showError('Failed to insert content into the editor. Please try copying and pasting manually.');
                // As a fallback, offer the content in a textarea
                this.showFallbackContent(content);
            }
        },

        showFallbackContent: function(content) {
            var fallbackHtml = '<div class="contentcraft-fallback-content">';
            fallbackHtml += '<h3>' + 'Copy Manually' + '</h3>';
            fallbackHtml += '<p>' + 'Could not automatically insert content. Please copy and paste it from the textarea below.' + '</p>';
            fallbackHtml += '<textarea rows="10" style="width:100%;">' + this.escapeHtml(content) + '</textarea>';
            fallbackHtml += '</div>';
            $('.contentcraft-meta-box-content').append(fallbackHtml);
        },
        
        loadCurrentContentPreview: function() {
            var content = this.getCurrentContent(); // Get fresh content
            var preview = '';
            
            console.log('ContentCraft AI: Loading content preview, content length:', content ? content.length : 0);
            
            if (content && content.trim() !== '') {
                // Show both raw structure info and text preview
                var textContent = this.stripHtml(content);
                var hasBlocks = content.includes('<!-- wp:');
                var hasHtml = content.includes('<') && content.includes('>');
                
                preview = '<div class="content-refresh-controls">';
                preview += '<button type="button" class="button button-small" onclick="ContentCraftEditor.loadCurrentContentPreview()">Refresh Content</button>';
                preview += '</div>';
                
                preview += '<div class="content-structure-info">';
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
                preview = '<div class="content-refresh-controls">';
                preview += '<button type="button" class="button button-small" onclick="ContentCraftEditor.loadCurrentContentPreview()">Refresh Content</button>';
                preview += '</div>';
                preview += '<div class="no-content">';
                preview += '<p><strong>No content detected</strong></p>';
                preview += '<p>If you have content in the editor, try:</p>';
                preview += '<ul>';
                preview += '<li>Click the "Refresh Content" button above</li>';
                preview += '<li>Save your post as draft first</li>';
                preview += '<li>Switch between Visual/Text editor tabs (Classic Editor)</li>';
                preview += '<li>Open browser console and run: <code>ContentCraftEditor.debugContentDetection()</code></li>';
                preview += '</ul>';
                preview += '</div>';
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
            var content = this.getCurrentContent(); // Get fresh content from editor
            var tags = this.getCurrentTags();
            
            console.log('ContentCraft AI: Starting content enhancement');
            console.log('Title:', title);
            console.log('Content length:', content ? content.length : 0);
            console.log('Tags:', tags);
            
            if (!content || content.trim() === '') {
                this.showError('No content to enhance. Please add some content to the editor first.');
                return;
            }
            
            // Update the stored content
            this.currentContent = content;
            
            // Show loading
            $('#enhance-loading').show();
            $('#enhance-content-btn').prop('disabled', true);
            $('#enhanced-content-preview').hide();
            
            var ajaxData = {
                action: 'contentcraft_enhance_content',
                nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : '',
                title: title,
                content: content,
                tags: tags,
                prompt: $('#enhancement-prompt').val(),
                post_id: this.getPostId()
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
                    try {
                        if (response.success) {
                            self.showEnhancedContent(response.data);
                        } else {
                            var errorMessage = 'Enhancement failed. Please check the logs for details.';
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            }
                            self.showError(errorMessage);
                        }
                    } catch (e) {
                        console.error('ContentCraft AI: Error processing response:', e);
                        self.showError('Error processing response: ' + e.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ContentCraft AI: AJAX error', xhr, status, error);
                    var errorMessage = 'Request failed. Please try again. Error: ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    self.showError(errorMessage);
                },
                complete: function() {
                    $('#enhance-loading').hide();
                    $('#enhance-content-btn').prop('disabled', false);
                }
            });
        },
        
        generateContent: function() {
            var self = this;
            var details = $('#generation-details').val();
            var tags = $('#generation-tags').val();
            var length = $('#generation-length').val();

            if (!details || details.trim() === '') {
                this.showError('Content details are required for content generation');
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
                    content_details: details,
                    tags: tags,
                    length: length,
                    prompt: $('#generation-prompt').val(),
                    post_id: this.getPostId()
                },
                success: function(response) {
                    try {
                        if (response.success) {
                            self.showGeneratedContent(response.data);
                        } else {
                            self.showError(response.data.message || 'Generation failed');
                        }
                    } catch (e) {
                        console.error('ContentCraft AI: Error processing generated response:', e);
                        self.showError('Error processing response: ' + e.message);
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

        submitGeneralQuery: function() {
            var self = this;
            var prompt = $('#general-query-prompt').val();

            if (!prompt || prompt.trim() === '') {
                this.showError('Please enter a query.');
                return;
            }

            $('#query-loading').show();
            $('#general-query-btn').prop('disabled', true);
            $('#query-result-preview').hide();

            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'contentcraft_general_query',
                    nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : '',
                    prompt: prompt
                },
                success: function(response) {
                    if (response.success) {
                        self.showQueryResult(response.data.text);
                    } else {
                        self.showError(response.data.message || 'Query failed');
                    }
                },
                error: function() {
                    self.showError('Request failed. Please try again.');
                },
                complete: function() {
                    $('#query-loading').hide();
                    $('#general-query-btn').prop('disabled', false);
                }
            });
        },
        
        showEnhancedContent: function(data) {
            try {
                // Check if there was a JSON parsing error on the backend
                if (data.parse_error) {
                    this.showError('API returned malformed JSON: ' + data.parse_error + '. Using raw response as fallback.');
                    console.warn('ContentCraft AI: JSON parse error from API:', data.parse_error);
                    console.warn('ContentCraft AI: Raw response:', data.raw_response);
                }
                
                this.lastEnhancedContent = data.enhanced_content; // Store for later retrieval
                var preview = this.createContentPreview(data.enhanced_content, 'Enhanced');
                $('#enhanced-content').html(preview);
                $('#enhanced-json-response').val(JSON.stringify(data, null, 2));
                $('#enhanced-content-preview').show();

                // Update other fields
                this.updateEditorFields(data);
            } catch (e) {
                console.error('ContentCraft AI: Error showing enhanced content:', e);
                this.showError('Error displaying enhanced content: ' + e.message);
            }
        },
        
        showGeneratedContent: function(data) {
            try {
                // Check if there was a JSON parsing error on the backend
                if (data.parse_error) {
                    this.showError('API returned malformed JSON: ' + data.parse_error + '. Using raw response as fallback.');
                    console.warn('ContentCraft AI: JSON parse error from API:', data.parse_error);
                    console.warn('ContentCraft AI: Raw response:', data.raw_response);
                }
                
                this.lastGeneratedContent = data.enhanced_content; // Store for later retrieval
                var preview = this.createContentPreview(data.enhanced_content, 'Generated');
                $('#generated-content').html(preview);
                $('#generated-json-response').val(JSON.stringify(data, null, 2));
                $('#generated-content-preview').show();

                // Update other fields
                this.updateEditorFields(data);
            } catch (e) {
                console.error('ContentCraft AI: Error showing generated content:', e);
                this.showError('Error displaying generated content: ' + e.message);
            }
        },

        showQueryResult: function(result) {
            $('#query-result').html('<p>' + this.escapeHtml(result) + '</p>');
            $('#query-result-preview').show();
        },

        insertQueryResult: function() {
            var result = $('#query-result').text();
            this.setContent(this.currentContent + '\n\n' + result);
        },

        copyQueryResult: function() {
            var result = $('#query-result').text();
            navigator.clipboard.writeText(result).then(function() {
                alert('Copied to clipboard!');
            }, function() {
                alert('Failed to copy to clipboard.');
            });
        },

        fetchInternalLinks: function() {
            var self = this;
            var postId = this.getPostId();
            var title = $('#internal-links-title').val();
            var tags = $('#internal-links-tags').val();
            var category = $('#internal-links-category').val();

            if (!postId) {
                this.showError('Could not determine the current post ID.');
                return;
            }

            $('#internal-links-loading').show();
            $('#fetch-internal-links-btn').prop('disabled', true);
            $('#internal-links-result').hide();

            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'contentcraft_fetch_internal_links',
                    nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : '',
                    post_id: postId,
                    title: title,
                    tags: tags,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        self.showInternalLinks(response.data);
                    } else {
                        self.showError(response.data.message || 'Failed to fetch internal links.');
                    }
                },
                error: function() {
                    self.showError('Request failed. Please try again.');
                },
                complete: function() {
                    $('#internal-links-loading').hide();
                    $('#fetch-internal-links-btn').prop('disabled', false);
                }
            });
        },

        showInternalLinks: function(links) {
            var content = '';
            if (links.length) {
                content += '<div class="internal-links-text-format">';
                content += '<p><strong>Internal Links for AI (Text Format):</strong></p>';
                content += '<textarea class="contentcraft-textarea" rows="8" style="width: 100%;" readonly>';
                links.forEach(function(link, index) {
                    content += (index + 1) + '. Title: ' + link.title + '\n   URL: ' + link.url + '\n\n';
                });
                content += '</textarea>';
                content += '<p class="description">Copy this text format to include in your AI prompts for internal linking suggestions.</p>';
                content += '</div>';
            } else {
                content = '<p>No similar posts found.</p>';
            }
            $('#internal-links-list').html(content);
            $('#internal-links-result').show();
        },

        getPostId: function() {
            if (this.currentEditor === 'classic') {
                return $('#post_ID').val();
            } else if (this.currentEditor === 'gutenberg') {
                if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                    return wp.data.select('core/editor').getCurrentPostId();
                }
            }
            return 0;
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
            try {
                var jsonText = $('#enhanced-json-response').val();
                if (!jsonText || jsonText.trim() === '') {
                    this.showError('No JSON response to parse.');
                    return;
                }
                
                var jsonResponse = JSON.parse(jsonText);
                
                // Check if the response is an object and has the 'enhanced_content' property
                if (typeof jsonResponse === 'object' && jsonResponse !== null && typeof jsonResponse.enhanced_content !== 'undefined') {
                    var content = jsonResponse.enhanced_content;
                    
                    console.log('ContentCraft AI: Accepting enhanced content (length: ' + content.length + ')');
                    this.setContent(content);
                    this.updateEditorFields(jsonResponse);
                    $('#enhanced-content-preview').hide();
                } else {
                    // Handle cases where the response is not the expected object
                    // This prevents inserting the whole JSON object
                    console.error('ContentCraft AI: Response is not in the expected format.', jsonResponse);
                    this.showError('The AI response was not in the expected format. Could not insert content.');
                }
            } catch (e) {
                console.error('ContentCraft AI: JSON parse error:', e);
                this.showError('Invalid JSON in the response textarea. Please check the format: ' + e.message);
            }
        },
        
        acceptGeneratedContent: function() {
            try {
                var jsonText = $('#generated-json-response').val();
                if (!jsonText || jsonText.trim() === '') {
                    this.showError('No JSON response to parse.');
                    return;
                }

                var jsonResponse = JSON.parse(jsonText);

                if (typeof jsonResponse === 'object' && jsonResponse !== null && typeof jsonResponse.enhanced_content !== 'undefined') {
                    var content = jsonResponse.enhanced_content;

                    console.log('ContentCraft AI: Accepting generated content (length: ' + content.length + ')');
                    this.setContent(content);
                    this.updateEditorFields(jsonResponse);

                    // Keep the content visible for reference - don't hide anything
                    this.showError('Content successfully inserted! The generated content is still visible above for reference.', 'success');
                } else {
                    console.error('ContentCraft AI: Response is not in the expected format.', jsonResponse);
                    this.showError('The AI response was not in the expected format. Could not insert content.');
                }
            } catch (e) {
                console.error('ContentCraft AI: JSON parse error:', e);
                this.showError('Invalid JSON in the response textarea. Please check the format: ' + e.message);
            }
        },

        parseAndInsertJson: function() {
            try {
                var jsonText = $('#parse-json-textarea').val();
                if (!jsonText || jsonText.trim() === '') {
                    this.showError('Please paste JSON content first.');
                    return;
                }
                
                var jsonResponse = JSON.parse(jsonText);
                var content = jsonResponse.enhanced_content || '';
                
                if (!content || content.trim() === '') {
                    this.showError('The JSON does not contain a valid "enhanced_content" field.');
                    return;
                }
                
                console.log('ContentCraft AI: Parsing and inserting JSON content (length: ' + content.length + ')');
                this.setContent(content);
                this.updateEditorFields(jsonResponse);
                
                // Clear the textarea after successful insertion
                $('#parse-json-textarea').val('');
                this.showError('Content successfully inserted from JSON!', 'success');
            } catch (e) {
                console.error('ContentCraft AI: JSON parse error:', e);
                this.showError('Invalid JSON provided. Please check the format: ' + e.message);
            }
        },

        updateEditorFields: function(data) {
            // Update title
            if (data.enhanced_title) {
                if (this.currentEditor === 'classic') {
                    $('#title').val(data.enhanced_title);
                } else if (this.currentEditor === 'gutenberg') {
                    wp.data.dispatch('core/editor').editPost({ title: data.enhanced_title });
                }
            }

            // Update tags
            if (data.suggested_tags && data.suggested_tags.length > 0) {
                var tags = data.suggested_tags.join(',');
                if (this.currentEditor === 'classic') {
                    $('#new-tag-post_tag').val(tags);
                } else if (this.currentEditor === 'gutenberg') {
                    // This is more complex and requires creating/assigning terms.
                    // For now, we'll just log it.
                    console.log('Gutenberg tags to add:', tags);
                }
            }

            // Update Rank Math fields
            if (typeof rankMathEditor !== 'undefined' && typeof rankMathEditor.updateField === 'function') {
                if (data.meta_description) {
                    rankMathEditor.updateField('description', data.meta_description);
                }
                if (data.focus_keyword) {
                    rankMathEditor.updateField('focusKeyword', data.focus_keyword);
                }
            }
        },

        loadDefaultPrompts: function() {
            $.ajax({
                url: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'contentcraft_get_default_prompts',
                    nonce: (typeof contentcraft_ai_ajax !== 'undefined') ? contentcraft_ai_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success) {
                        $('#enhancement-prompt').val(response.data.enhancement);
                        $('#generation-prompt').val(response.data.generation);
                    }
                }
            });
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
        
        resetInterface: function() {
            $('.contentcraft-result').hide();
            $('.contentcraft-loading').hide();
            $('.contentcraft-message').remove(); // Clear any error/success messages
            $('#enhanced-content, #generated-content').empty();
            $('#generation-title, #generation-tags').val('');
            $('#generation-length').val('medium');
            
            this.lastEnhancedContent = '';
            this.lastGeneratedContent = '';
            
            this.switchTab('enhance');
        },
        
        showError: function(message, type) {
            type = type || 'error';
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var messageHtml = '<div class="contentcraft-message notice ' + noticeClass + ' is-dismissible"><p>' + this.escapeHtml(message) + '</p></div>';
            
            $('.contentcraft-meta-box-content').prepend(messageHtml);
            
            setTimeout(function() {
                $('.contentcraft-message').fadeOut(function() {
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
                '&': '&',
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        sendChatMessage: function() {
            var self = this;
            var message = $('#contentcraft-ai-chat-message').val();
            if (message.trim() === '') {
                return;
            }

            if ($('#contentcraft-ai-save-prompt-checkbox').is(':checked')) {
                this.savePrompt(message);
                $('#contentcraft-ai-save-prompt-checkbox').prop('checked', false);
            }

            var chatLog = $('#contentcraft-ai-chat-log');
            var userMessage = '<div class="chat-message user-message"><p>' + this.escapeHtml(message) + '</p></div>';
            chatLog.append(userMessage);
            $('#contentcraft-ai-chat-message').val('');

            this.chatHistory.push({role: 'user', content: message});

            $.ajax({
                url: contentcraft_ai_chat_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'contentcraft_ai_chat',
                    nonce: contentcraft_ai_chat_ajax.nonce,
                    post_id: contentcraft_ai_chat_ajax.post_id,
                    message: message,
                    history: JSON.stringify(self.chatHistory)
                },
                beforeSend: function() {
                    chatLog.append('<div class="chat-message bot-message loading"><p>...</p></div>');
                },
                success: function(response) {
                    chatLog.find('.loading').remove();
                    if (response.success) {
                        var botMessageText = response.data.text;
                        self.chatHistory.push({role: 'assistant', content: botMessageText});
                        var botMessage = '<div class="chat-message bot-message"><p>' + self.escapeHtml(botMessageText) + '</p></div>';
                        chatLog.append(botMessage);
                    } else {
                        var errorMessage = '<div class="chat-message bot-message error"><p>' + self.escapeHtml(response.data.message) + '</p></div>';
                        chatLog.append(errorMessage);
                    }
                    chatLog.scrollTop(chatLog[0].scrollHeight);
                },
                error: function() {
                    chatLog.find('.loading').remove();
                    var errorMessage = '<div class="chat-message bot-message error"><p>An error occurred.</p></div>';
                    chatLog.append(errorMessage);
                    chatLog.scrollTop(chatLog[0].scrollHeight);
                }
            });
        },

        getSavedPrompts: function() {
            return JSON.parse(localStorage.getItem('contentcraft_ai_saved_prompts')) || [];
        },

        savePrompt: function(prompt) {
            var prompts = this.getSavedPrompts();
            if (prompts.indexOf(prompt) === -1) {
                prompts.push(prompt);
                localStorage.setItem('contentcraft_ai_saved_prompts', JSON.stringify(prompts));
                this.loadSavedPrompts();
            }
        },

        deleteSavedPrompt: function(prompt) {
            var prompts = this.getSavedPrompts();
            var index = prompts.indexOf(prompt);
            if (index > -1) {
                prompts.splice(index, 1);
                localStorage.setItem('contentcraft_ai_saved_prompts', JSON.stringify(prompts));
                this.loadSavedPrompts();
            }
        },

        loadSavedPrompts: function() {
            var prompts = this.getSavedPrompts();
            var list = $('#contentcraft-ai-saved-prompts-list');
            list.empty();
            for (const prompt of prompts) {
                list.append('<div class="prompt-item">' + this.escapeHtml(prompt) + '<span class="delete-prompt"> 🗑️</span></div>');
            }
        }
    };
    
    wp.domReady(function() {
        if ($('#contentcraft_ai_meta_box').length) {
            ContentCraftEditor.init();
        }
    });
    
    window.ContentCraftEditor = ContentCraftEditor;
    
})(jQuery);
