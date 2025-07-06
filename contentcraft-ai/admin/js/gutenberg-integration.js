/**
 * Gutenberg Integration for ContentCraft AI
 */

(function() {
    'use strict';
    
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { Button, PanelBody } = wp.components;
    const { createElement: el } = wp.element;
    
    // ContentCraft AI Plugin for Gutenberg
    const ContentCraftAIPlugin = function() {
        return el(
            PluginDocumentSettingPanel,
            {
                name: 'contentcraft-ai-panel',
                title: 'ContentCraft AI',
                className: 'contentcraft-ai-gutenberg-panel'
            },
            el(
                PanelBody,
                {
                    title: 'AI Content Tools',
                    initialOpen: true
                },
                el(
                    Button,
                    {
                        className: 'contentcraft-ai-enhance-btn',
                        variant: 'primary',
                        onClick: function() {
                            if (window.ContentCraftModal) {
                                window.ContentCraftModal.open('gutenberg');
                            } else {
                                console.error('ContentCraft AI Modal not loaded');
                            }
                        }
                    },
                    'Enhance Content'
                ),
                el(
                    Button,
                    {
                        className: 'contentcraft-ai-generate-btn',
                        variant: 'secondary',
                        onClick: function() {
                            if (window.ContentCraftModal) {
                                window.ContentCraftModal.open('gutenberg');
                                setTimeout(function() {
                                    window.ContentCraftModal.switchTab('generate');
                                }, 100);
                            } else {
                                console.error('ContentCraft AI Modal not loaded');
                            }
                        },
                        style: { marginLeft: '10px' }
                    },
                    'Generate New'
                )
            )
        );
    };
    
    // Register the plugin
    registerPlugin('contentcraft-ai', {
        render: ContentCraftAIPlugin
    });
    
    // Add toolbar button
    const { registerFormatType } = wp.richText;
    const { RichTextToolbarButton } = wp.blockEditor;
    
    if (registerFormatType && RichTextToolbarButton) {
        registerFormatType('contentcraft-ai/toolbar-button', {
            title: 'ContentCraft AI',
            tagName: 'span',
            className: 'contentcraft-ai-toolbar',
            edit: function(props) {
                return el(
                    RichTextToolbarButton,
                    {
                        icon: 'admin-generic',
                        title: 'ContentCraft AI',
                        onClick: function() {
                            if (window.ContentCraftModal) {
                                window.ContentCraftModal.open('gutenberg');
                            }
                        }
                    }
                );
            }
        });
    }
    
    // Add floating action button
    function addFloatingButton() {
        if (document.getElementById('contentcraft-ai-floating-btn')) {
            return;
        }
        
        const floatingBtn = document.createElement('div');
        floatingBtn.id = 'contentcraft-ai-floating-btn';
        floatingBtn.className = 'contentcraft-ai-floating-button';
        floatingBtn.innerHTML = '<span class="dashicons dashicons-admin-generic"></span><span class="button-text">ContentCraft AI</span>';
        
        floatingBtn.addEventListener('click', function() {
            if (window.ContentCraftModal) {
                window.ContentCraftModal.open('gutenberg');
            }
        });
        
        document.body.appendChild(floatingBtn);
    }
    
    // Initialize floating button when editor is ready
    wp.domReady(function() {
        addFloatingButton();
    });
    
    // Add styles for Gutenberg integration
    const style = document.createElement('style');
    style.textContent = `
        .contentcraft-ai-gutenberg-panel {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .contentcraft-ai-enhance-btn,
        .contentcraft-ai-generate-btn {
            width: 100%;
            margin-bottom: 10px;
            justify-content: center;
        }
        
        .contentcraft-ai-floating-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contentcraft-ai-floating-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        
        .contentcraft-ai-floating-button .button-text {
            display: none;
        }
        
        .contentcraft-ai-floating-button:hover .button-text {
            display: inline;
        }
        
        .contentcraft-ai-floating-button .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 768px) {
            .contentcraft-ai-floating-button {
                bottom: 20px;
                right: 20px;
                padding: 12px 15px;
            }
        }
    `;
    
    document.head.appendChild(style);
    
})();