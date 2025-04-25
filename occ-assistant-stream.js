jQuery(function($) {
    /**
     * Initialize each assistant instance on the page
     */
    $('div[id^="occ_assistant_"]').each(function() {
        var $container = $(this);
        var unique_id = $container.attr('id');
        // Skip child elements with _chat_history or _input_area
        if (unique_id.endsWith('_chat_history') || unique_id.endsWith('_input_area')) {
            return;
        }
        var data = window[unique_id + '_data'];
        if (!data || !data.assistant_id || !data.unique_id) {
            console.error('Assistant data not found for ID: ' + unique_id);
            return;
        }

        var assistant_id = data.assistant_id;

        //
        // CACHE DOM NODES
        //
        var $questionInput = $('#' + unique_id + '_question');
        var $submitButton = $('#' + unique_id + '_submit');
        var $downloadButton = $('#' + unique_id + '_download');
        var $chatHistory = $('#' + unique_id + '_chat_history');

        /**
         * Build a plain-text transcript of the chat and download it as .txt
         */
        function downloadConversation() {
            const lines = [];
            $chatHistory.find('.occ-assistant-message').each(function() {
                const $m = $(this);
                const who = $m.hasClass('occ-assistant-user-message') ? 'User' : 'Assistant';
                const text = $m.text().trim();
                lines.push(`${who}: ${text}`);
            });

            const content = lines.join('\n\n');
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'assistant-conversation.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        //
        // STREAMING LOGIC
        //
        function streamAssistantResponse(question) {
            const url = [
                occ_assistant_ajax.ajax_url,
                '?action=occ_assistant_stream_query',
                `&nonce=${encodeURIComponent(occ_assistant_ajax.nonce)}`,
                `&question=${encodeURIComponent(question)}`,
                `&assistant_id=${encodeURIComponent(assistant_id)}`
            ].join('');

            const es = new EventSource(url);

            es.addEventListener('message', e => {
                appendStreamedText(e.data);
            });

            es.addEventListener('complete', () => {
                es.close();
            });

            es.addEventListener('ping', () => {
                /* no-op heartbeat */
            });

            es.addEventListener('assistant_error', e => {
                displayError(e.data);
                es.close();
            });

            es.onerror = () => {
                displayError('SSE connection failed');
                es.close();
            };
        }

        //
        // UI UPDATE HELPERS
        //
        function initChatBubble(question) {
            $chatHistory.append(
                `<div class="occ-assistant-message occ-assistant-user-message">${escapeHtml(question)}</div>`
            );
            $chatHistory.append(
                `<div class="occ-assistant-message occ-assistant-bot-message"></div>`
            );
            scrollChatToBottom();
        }

        function appendStreamedText(data) {
            let text;
            try {
                text = JSON.parse(data);
            } catch {
                text = data;
            }
            const $lastBubble = $chatHistory.find('.occ-assistant-message:last');
            $lastBubble.html($lastBubble.html() + escapeHtml(text).replace(/\n/g, '<br>'));
            scrollChatToBottom();
        }

        function displayError(errMsg) {
            $chatHistory.find('.occ-assistant-message:last')
                .html(`<p>Error: ${escapeHtml(errMsg)}</p>`);
            scrollChatToBottom();
        }

        function scrollChatToBottom() {
            $chatHistory.scrollTop($chatHistory[0].scrollHeight);
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        //
        // EVENT HANDLERS
        //
        $submitButton.on('click', () => {
            const question = $questionInput.val().trim();
            if (!question) return;

            initChatBubble(question);
            $questionInput.val('');
            streamAssistantResponse(question);
        });

        $questionInput.on('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $submitButton.click();
            }
        });

        $downloadButton.on('click', downloadConversation);
    });
});