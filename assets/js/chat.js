(function($) {
    'use strict';

    $(function() {
        var chatInput = $('#contentcraft-ai-chat-message');
        var sendButton = $('#contentcraft-ai-chat-send');
        var chatLog = $('#contentcraft-ai-chat-log');

        function sendMessage() {
            var message = chatInput.val().trim();
            if (message === '') {
                return;
            }

            // Disable input while processing
            chatInput.prop('disabled', true);
            sendButton.prop('disabled', true);

            // Escape HTML to prevent XSS
            var escapedMessage = $('<div>').text(message).html();
            var userMessage = '<div class="chat-message user-message"><p>' + escapedMessage + '</p></div>';
            chatLog.append(userMessage);
            chatInput.val('');

            // Auto-scroll to bottom
            chatLog.scrollTop(chatLog[0].scrollHeight);

            $.ajax({
                url: contentcraft_ai_chat_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'contentcraft_ai_chat',
                    nonce: contentcraft_ai_chat_ajax.nonce,
                    post_id: contentcraft_ai_chat_ajax.post_id,
                    message: message
                },
                timeout: 30000, // 30 second timeout
                beforeSend: function() {
                    chatLog.append('<div class="chat-message bot-message loading"><p>Thinking...</p></div>');
                    chatLog.scrollTop(chatLog[0].scrollHeight);
                },
                success: function(response) {
                    chatLog.find('.loading').remove();
                    
                    if (response && response.success) {
                        var responseText = response.data.text || response.data.response || 'No response received';
                        // Basic HTML sanitization while preserving some formatting
                        var sanitizedResponse = responseText.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                        var botMessage = '<div class="chat-message bot-message"><p>' + sanitizedResponse + '</p></div>';
                        chatLog.append(botMessage);
                    } else {
                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Unknown error occurred';
                        var errorMessage = '<div class="chat-message bot-message error"><p>Error: ' + $('<div>').text(errorMsg).html() + '</p></div>';
                        chatLog.append(errorMessage);
                    }
                    
                    chatLog.scrollTop(chatLog[0].scrollHeight);
                },
                error: function(xhr, status) {
                    chatLog.find('.loading').remove();
                    
                    var errorMsg = 'Connection error';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please try again.';
                    } else if (xhr.status === 403) {
                        errorMsg = 'Access denied. Please check your permissions.';
                    } else if (xhr.status >= 500) {
                        errorMsg = 'Server error. Please try again later.';
                    }
                    
                    var errorMessage = '<div class="chat-message bot-message error"><p>' + errorMsg + '</p></div>';
                    chatLog.append(errorMessage);
                    chatLog.scrollTop(chatLog[0].scrollHeight);
                },
                complete: function() {
                    // Re-enable input
                    chatInput.prop('disabled', false);
                    sendButton.prop('disabled', false);
                    chatInput.focus();
                }
            });
        }

        // Click handler for send button
        sendButton.on('click', sendMessage);

        // Enter key handler for textarea
        chatInput.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        chatInput.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Focus on input when chat tab is activated
        $(document).on('click', '[data-tab="chat"]', function() {
            setTimeout(function() {
                chatInput.focus();
            }, 100);
        });
    });

})(jQuery);
