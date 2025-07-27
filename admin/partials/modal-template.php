<?php
/**
 * Modal template for ContentCraft AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="contentcraft-meta-box-content">
            <div class="contentcraft-tabs">
                <button class="contentcraft-tab-button active" data-tab="enhance">
                    <?php _e('Enhance Content', 'contentcraft-ai'); ?>
                </button>
                <button class="contentcraft-tab-button" data-tab="generate">
                    <?php _e('Generate New', 'contentcraft-ai'); ?>
                </button>
                <button class="contentcraft-tab-button" data-tab="query">
                    <?php _e('General Query', 'contentcraft-ai'); ?>
                </button>
                <button class="contentcraft-tab-button" data-tab="internal-links">
                    <?php _e('Internal Links', 'contentcraft-ai'); ?>
                </button>
                <button class="contentcraft-tab-button" data-tab="parse-json">
                    <?php _e('Parse JSON', 'contentcraft-ai'); ?>
                </button>
            </div>
            
            <div id="enhance-tab" class="contentcraft-tab-content active">
                <div class="contentcraft-preview">
                    <h3><?php _e('Current Content', 'contentcraft-ai'); ?></h3>
                    <div id="current-content-preview">
                        <p><?php _e('Loading current content...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>

                <div class="contentcraft-field">
                    <label for="enhancement-prompt"><?php _e('Enhancement Prompt:', 'contentcraft-ai'); ?></label>
                    <textarea id="enhancement-prompt" class="contentcraft-textarea" rows="4"></textarea>
                    <p class="description"><?php _e('Available variables: {post_title}, {post_content}, {tags}', 'contentcraft-ai'); ?></p>
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
                    <div class="contentcraft-field">
                        <label for="enhanced-json-response"><?php _e('Raw JSON Response:', 'contentcraft-ai'); ?></label>
                        <textarea id="enhanced-json-response" class="contentcraft-textarea" rows="8"></textarea>
                    </div>
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
                    <div class="contentcraft-field">
                        <label for="generation-prompt"><?php _e('Generation Prompt:', 'contentcraft-ai'); ?></label>
                        <textarea id="generation-prompt" class="contentcraft-textarea" rows="4"></textarea>
                        <p class="description"><?php _e('Available variables: {post_title}, {tags}', 'contentcraft-ai'); ?></p>
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
                    <div class="contentcraft-field">
                        <label for="generated-json-response"><?php _e('Raw JSON Response:', 'contentcraft-ai'); ?></label>
                        <textarea id="generated-json-response" class="contentcraft-textarea" rows="8"></textarea>
                    </div>
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

            <div id="query-tab" class="contentcraft-tab-content">
                <h3><?php _e('General AI Query', 'contentcraft-ai'); ?></h3>
                <div class="contentcraft-field">
                    <label for="general-query-prompt"><?php _e('Your Question:', 'contentcraft-ai'); ?></label>
                    <textarea id="general-query-prompt" class="contentcraft-textarea" rows="4" placeholder="<?php _e('Ask anything...', 'contentcraft-ai'); ?>"></textarea>
                </div>
                <div class="contentcraft-controls">
                    <button id="general-query-btn" class="button button-primary">
                        <?php _e('Submit Query', 'contentcraft-ai'); ?>
                    </button>
                    <div id="query-loading" class="contentcraft-loading" style="display: none;">
                        <div class="contentcraft-spinner"></div>
                        <p><?php _e('Processing...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>
                <div id="query-result-preview" class="contentcraft-result" style="display: none;">
                    <h3><?php _e('AI Response', 'contentcraft-ai'); ?></h3>
                    <div id="query-result"></div>
                    <div class="contentcraft-actions">
                        <button id="insert-query-result-btn" class="button button-primary">
                            <?php _e('Insert into Editor', 'contentcraft-ai'); ?>
                        </button>
                        <button id="copy-query-result-btn" class="button button-secondary">
                            <?php _e('Copy to Clipboard', 'contentcraft-ai'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div id="internal-links-tab" class="contentcraft-tab-content">
                <h3><?php _e('Find Internal Links', 'contentcraft-ai'); ?></h3>
                <div class="contentcraft-field">
                    <label for="internal-links-title"><?php _e('Title:', 'contentcraft-ai'); ?></label>
                    <input type="text" id="internal-links-title" class="contentcraft-input" placeholder="<?php _e('Enter title keywords...', 'contentcraft-ai'); ?>">
                </div>
                <div class="contentcraft-field">
                    <label for="internal-links-tags"><?php _e('Tags:', 'contentcraft-ai'); ?></label>
                    <input type="text" id="internal-links-tags" class="contentcraft-input" placeholder="<?php _e('Enter tags separated by commas...', 'contentcraft-ai'); ?>">
                </div>
                <div class="contentcraft-field">
                    <label for="internal-links-category"><?php _e('Category:', 'contentcraft-ai'); ?></label>
                    <input type="text" id="internal-links-category" class="contentcraft-input" placeholder="<?php _e('Enter category name...', 'contentcraft-ai'); ?>">
                </div>
                <div class="contentcraft-controls">
                    <button id="fetch-internal-links-btn" class="button button-primary">
                        <?php _e('Fetch Similar Posts', 'contentcraft-ai'); ?>
                    </button>
                    <div id="internal-links-loading" class="contentcraft-loading" style="display: none;">
                        <div class="contentcraft-spinner"></div>
                        <p><?php _e('Fetching...', 'contentcraft-ai'); ?></p>
                    </div>
                </div>
                <div id="internal-links-result" class="contentcraft-result" style="display: none;">
                    <h3><?php _e('Suggested Internal Links', 'contentcraft-ai'); ?></h3>
                    <div id="internal-links-list"></div>
                </div>
            </div>

            <div id="parse-json-tab" class="contentcraft-tab-content">
                <h3><?php _e('Parse and Insert JSON', 'contentcraft-ai'); ?></h3>
                <div class="contentcraft-field">
                    <label for="parse-json-textarea"><?php _e('Paste JSON here:', 'contentcraft-ai'); ?></label>
                    <textarea id="parse-json-textarea" class="contentcraft-textarea" rows="10" placeholder="<?php _e('Paste the JSON response from the AI here...', 'contentcraft-ai'); ?>"></textarea>
                </div>
                <div class="contentcraft-controls">
                    <button id="parse-json-btn" class="button button-primary">
                        <?php _e('Parse and Insert Content', 'contentcraft-ai'); ?>
                    </button>
                </div>
            </div>
</div>
