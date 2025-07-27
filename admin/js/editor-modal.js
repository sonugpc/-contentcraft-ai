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
        },
        
        determineEditor: function() {
            if ($('body').hasClass('block-editor-page')) {
                this.currentEditor = 'gutenberg';
            } else {
                this.currentEditor = 'classic';
            }
        },
        
        loadInitialData: function() {
            this.currentContent = this.getCurrentContent();
            this.loadDefaultPrompts();
            this.loadCurrentContentPreview();
            this.loadUsageInfo();
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
            try {
                if (this.currentEditor === 'classic') {
                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        tinymce.get('content').setContent(content);
                    } else if ($('#content').length) {
                        $('#content').val(content).trigger('change');
                    } else {
                        throw new Error('Classic editor content area not found.');
                    }
                } else if (this.currentEditor === 'gutenberg') {
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').editPost({ content: content });
                    } else {
                        throw new Error('Gutenberg editor not available.');
                    }
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
                    if (response.success) {
                        self.showEnhancedContent(response.data);
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
                    length: length,
                    prompt: $('#generation-prompt').val(),
                    post_id: this.getPostId()
                },
                success: function(response) {
                    if (response.success) {
                        self.showGeneratedContent(response.data);
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
            this.lastEnhancedContent = data.enhanced_content; // Store for later retrieval
            var preview = this.createContentPreview(data.enhanced_content, 'Enhanced');
            $('#enhanced-content').html(preview);
            $('#enhanced-json-response').val(JSON.stringify(data, null, 2));
            $('#enhanced-content-preview').show();

            // Update other fields
            this.updateEditorFields(data);
        },
        
        showGeneratedContent: function(data) {
            this.lastGeneratedContent = data.enhanced_content; // Store for later retrieval
            var preview = this.createContentPreview(data.enhanced_content, 'Generated');
            $('#generated-content').html(preview);
            $('#generated-json-response').val(JSON.stringify(data, null, 2));
            $('#generated-content-preview').show();

            // Update other fields
            this.updateEditorFields(data);
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
            var list = $('<ul>');
            if (links.length) {
                links.forEach(function(link) {
                    list.append('<li><a href="' + link.url + '" target="_blank">' + link.title + '</a></li>');
                });
            } else {
                list.append('<li>' + 'No similar posts found.' + '</li>');
            }
            $('#internal-links-list').html(list);
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
                var jsonResponse = JSON.parse($('#enhanced-json-response').val());
                var content = jsonResponse.enhanced_content || '';
                console.log('ContentCraft AI: Accepting enhanced content (length: ' + content.length + ')');
                this.setContent(content);
                this.updateEditorFields(jsonResponse);
            } catch (e) {
                this.showError('Invalid JSON in the response textarea.');
            }
        },
        
        acceptGeneratedContent: function() {
            try {
                var jsonResponse = JSON.parse($('#generated-json-response').val());
                var content = jsonResponse.enhanced_content || '';
                console.log('ContentCraft AI: Accepting generated content (length: ' + content.length + ')');
                this.setContent(content);
                this.updateEditorFields(jsonResponse);
            } catch (e) {
                this.showError('Invalid JSON in the response textarea.');
            }
        },

        parseAndInsertJson: function() {
            try {
                var jsonResponse = JSON.parse($('#parse-json-textarea').val());
                var content = jsonResponse.enhanced_content || '';
                if (content) {
                    this.setContent(content);
                } else {
                    this.showError('The JSON does not contain an "enhanced_content" field.');
                }
            } catch (e) {
                this.showError('Invalid JSON provided.');
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
            if (typeof rankMathEditor !== 'undefined') {
                if (data.meta_description) {
                    rankMathEditor.updateData('description', data.meta_description);
                }
                if (data.focus_keyword) {
                    rankMathEditor.updateData('focusKeyword', data.focus_keyword);
                }
            }
        },

        loadDefaultPrompts: function() {
            var self = this;
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
            $('#enhanced-content, #generated-content').empty();
            $('#generation-title, #generation-tags').val('');
            $('#generation-length').val('medium');
            
            this.lastEnhancedContent = '';
            this.lastGeneratedContent = '';
            
            this.switchTab('enhance');
        },
        
        showError: function(message) {
            var errorHtml = '<div class="contentcraft-error notice notice-error is-dismissible"><p>' + this.escapeHtml(message) + '</p></div>';
            
            $('.contentcraft-meta-box-content').prepend(errorHtml);
            
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
    
    $(document).ready(function() {
        if ($('#contentcraft_ai_meta_box').length) {
            ContentCraftEditor.init();
        }
    });
    
    window.ContentCraftEditor = ContentEditor;
    
})(jQuery);
