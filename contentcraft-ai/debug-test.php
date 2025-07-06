<?php
/**
 * Debug test file for ContentCraft AI
 * Add this to admin footer to debug
 */

// Only run on admin pages
if (!is_admin()) {
    return;
}

?>
<script>
console.log('ContentCraft AI Debug Test');
console.log('jQuery available:', typeof jQuery !== 'undefined');
console.log('WordPress admin:', typeof ajaxurl !== 'undefined');
console.log('ContentCraft AJAX data:', typeof contentcraft_ai_ajax !== 'undefined' ? contentcraft_ai_ajax : 'Not available');
console.log('Modal element exists:', jQuery('#contentcraft-ai-modal').length > 0);
console.log('Classic button exists:', jQuery('#contentcraft-ai-classic-button').length > 0);
console.log('ContentCraftModal available:', typeof ContentCraftModal !== 'undefined');

// Test modal opening
if (typeof ContentCraftModal !== 'undefined') {
    window.testModal = function() {
        console.log('Testing modal open...');
        ContentCraftModal.open('classic');
    };
    console.log('Run testModal() in console to test modal');
}
</script>
<?php