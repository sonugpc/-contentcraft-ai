/**
 * Simple test script for ContentCraft AI debugging
 */

console.log('ContentCraft AI: Simple test script loaded at:', new Date().toISOString());

jQuery(document).ready(function($) {
    console.log('ContentCraft AI: jQuery ready');
    console.log('ContentCraft AI: jQuery version:', $.fn.jquery);
    console.log('ContentCraft AI: AJAX object available:', typeof contentcraft_ai_ajax !== 'undefined');
    console.log('ContentCraft AI: Current URL:', window.location.href);
    
    if (typeof contentcraft_ai_ajax !== 'undefined') {
        console.log('ContentCraft AI: AJAX data:', contentcraft_ai_ajax);
    } else {
        console.log('ContentCraft AI: AJAX data not available - check localization');
    }
    
    // Test button clicks with more specific selectors
    $(document).on('click', '#enhance-content-btn', function() {
        console.log('ContentCraft AI: Enhance button clicked!');
        alert('Enhance button clicked - JavaScript is working!');
    });
    
    $(document).on('click', '#contentcraft-ai-classic-button', function(e) {
        e.preventDefault();
        console.log('ContentCraft AI: Classic editor button clicked!');
        alert('Classic editor button clicked - JavaScript is working!');
    });
    
    // Generic button test
    $(document).on('click', '[id*="contentcraft"]', function(e) {
        console.log('ContentCraft AI: Any ContentCraft button clicked:', this.id);
    });
    
    // Check if elements exist
    setTimeout(function() {
        console.log('ContentCraft AI: Modal element exists:', $('#contentcraft-ai-modal').length > 0);
        console.log('ContentCraft AI: Classic button exists:', $('#contentcraft-ai-classic-button').length > 0);
        console.log('ContentCraft AI: All buttons with contentcraft:', $('[id*="contentcraft"]').length);
        
        // List all buttons found
        $('[id*="contentcraft"]').each(function() {
            console.log('ContentCraft AI: Found button:', this.id);
        });
    }, 1000);
    
    // Test modal opening
    window.testModalOpen = function() {
        console.log('ContentCraft AI: Testing modal open...');
        $('#contentcraft-ai-modal').show();
        alert('Modal should be visible now');
    };
    
    // Test AJAX
    window.testAjax = function() {
        if (typeof contentcraft_ai_ajax !== 'undefined') {
            $.ajax({
                url: contentcraft_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'contentcraft_test_api',
                    nonce: contentcraft_ai_ajax.nonce
                },
                success: function(response) {
                    console.log('ContentCraft AI: AJAX test successful:', response);
                    alert('AJAX test successful!');
                },
                error: function(xhr, status, error) {
                    console.log('ContentCraft AI: AJAX test failed:', error);
                    alert('AJAX test failed: ' + error);
                }
            });
        } else {
            alert('AJAX data not available');
        }
    };
    
    console.log('ContentCraft AI: Test functions available:');
    console.log('- testModalOpen() - Test modal display');
    console.log('- testAjax() - Test AJAX connection');
});