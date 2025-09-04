(function($) {
    'use strict';

    $(function() {
        $('#contentcraft-ai-chat-send').on('click', function() {
            var message = $('#contentcraft-ai-chat-message').val();
            if (message.trim() === '') {
                return;
            }

            var chatLog = $('#contentcraft-ai-chat-log');
            var userMessage = '<div class="chat-message user-message"><p>' + message + '</p></div>';
            chatLog.append(userMessage);
            $('#contentcraft-ai-chat-message').val('');

            $.ajax({
                url: contentcraft_ai_chat_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'contentcraft_ai_chat',
                    nonce: contentcraft_ai_chat_ajax.nonce,
                    post_id: contentcraft_ai_chat_ajax.post_id,
                    message: message
                },
                beforeSend: function() {
                    chatLog.append('<div class="chat-message bot-message loading"><p>...</p></div>');
                },
                success: function(response) {
                    chatLog.find('.loading').remove();
                    if (response.success) {
                        var botMessage = '<div class="chat-message bot-message"><p>' + (response.data.text || response.data.response || 'No response') + '</p></div>';
                        chatLog.append(botMessage);
                    } else {
                        var errorMessage = '<div class="chat-message bot-message error"><p>' + (response.data.message || 'Unknown error') + '</p></div>';
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
        });
    });

})(jQuery);
