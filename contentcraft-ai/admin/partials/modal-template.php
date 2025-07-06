<?php
/**
 * Modal template for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="contentcraft-ai-modal" class="contentcraft-modal" style="display: none;">
    <div class="contentcraft-modal-content">
        <div class="contentcraft-modal-header">
            <h2><?php _e('ContentCraft AI', 'contentcraft-ai'); ?></h2>
            <span class="contentcraft-modal-close">&times;</span>
        </div>
        
        <div class="contentcraft-modal-body">
            <div class="contentcraft-tabs">
                <button class="contentcraft-tab-button active" data-tab="enhance">
                    <?php _e('Enhance Content', 'contentcraft-ai'); ?>
                </button>
                <button class="contentcraft-tab-button" data-tab="generate">
                    <?php _e('Generate New', 'contentcraft-ai'); ?>
                </button>
            </div>
            
            <div id="enhance-tab" class="contentcraft-tab-content active">
                <div class="contentcraft-preview">
                    <h3><?php _e('Current Content', 'contentcraft-ai'); ?></h3>
                    <div id="current-content-preview">
                        <p><?php _e('Loading current content...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>
                
                <div class="contentcraft-controls">
                    <button id="enhance-content-btn" class="button button-primary">
                        <?php _e('Enhance Content', 'contentcraft-ai'); ?>
                    </button>
                    <div id="enhance-loading" class="contentcraft-loading" style="display: none;">
                        <div class="contentcraft-spinner"></div>
                        <p><?php _e('Processing...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>
                
                <div id="enhanced-content-preview" class="contentcraft-result" style="display: none;">
                    <h3><?php _e('Enhanced Content', 'contentcraft-ai'); ?></h3>
                    <div id="enhanced-content"></div>
                    <div class="contentcraft-actions">
                        <button id="accept-enhanced-btn" class="button button-primary">
                            <?php _e('Accept Changes', 'contentcraft-ai'); ?>
                        </button>
                        <button id="reject-enhanced-btn" class="button button-secondary">
                            <?php _e('Reject', 'contentcraft-ai'); ?>
                        </button>
                        <button id="regenerate-enhanced-btn" class="button button-secondary">
                            <?php _e('Regenerate', 'contentcraft-ai'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="generate-tab" class="contentcraft-tab-content">
                <div class="contentcraft-generation-options">
                    <h3><?php _e('Content Generation Options', 'contentcraft-ai'); ?></h3>
                    <div class="contentcraft-field">
                        <label for="generation-title"><?php _e('Title:', 'contentcraft-ai'); ?></label>
                        <input type="text" id="generation-title" class="contentcraft-input" placeholder="<?php _e('Enter post title...', 'contentcraft-ai'); ?>">
                    </div>
                    <div class="contentcraft-field">
                        <label for="generation-tags"><?php _e('Tags (optional):', 'contentcraft-ai'); ?></label>
                        <input type="text" id="generation-tags" class="contentcraft-input" placeholder="<?php _e('Enter tags separated by commas...', 'contentcraft-ai'); ?>">
                    </div>
                    <div class="contentcraft-field">
                        <label for="generation-length"><?php _e('Content Length:', 'contentcraft-ai'); ?></label>
                        <select id="generation-length" class="contentcraft-select">
                            <option value="short"><?php _e('Short (2-3 paragraphs)', 'contentcraft-ai'); ?></option>
                            <option value="medium" selected><?php _e('Medium (4-6 paragraphs)', 'contentcraft-ai'); ?></option>
                            <option value="long"><?php _e('Long (7+ paragraphs)', 'contentcraft-ai'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="contentcraft-controls">
                    <button id="generate-content-btn" class="button button-primary">
                        <?php _e('Generate New Content', 'contentcraft-ai'); ?>
                    </button>
                    <div id="generate-loading" class="contentcraft-loading" style="display: none;">
                        <div class="contentcraft-spinner"></div>
                        <p><?php _e('Generating...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>
                
                <div id="generated-content-preview" class="contentcraft-result" style="display: none;">
                    <h3><?php _e('Generated Content', 'contentcraft-ai'); ?></h3>
                    <div id="generated-content"></div>
                    <div class="contentcraft-actions">
                        <button id="accept-generated-btn" class="button button-primary">
                            <?php _e('Use This Content', 'contentcraft-ai'); ?>
                        </button>
                        <button id="reject-generated-btn" class="button button-secondary">
                            <?php _e('Cancel', 'contentcraft-ai'); ?>
                        </button>
                        <button id="regenerate-content-btn" class="button button-secondary">
                            <?php _e('Regenerate', 'contentcraft-ai'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="contentcraft-modal-footer">
            <div class="contentcraft-usage-info">
                <small id="usage-info-text"><?php _e('Usage: Loading...', 'contentcraft-ai'); ?></small>
            </div>
        </div>
    </div>
</div>

<div id="contentcraft-ai-overlay" class="contentcraft-overlay" style="display: none;"></div>
