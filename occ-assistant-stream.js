jQuery(function($) {
  // Find every assistant container on the page
  $('div[id^="occ_assistant_"]').each(function() {
    const $container     = $(this);
    const unique_id      = $container.attr('id');

    // Skip the chat history and input-area wrappers
    if (unique_id.endsWith('_chat_history') || unique_id.endsWith('_input_area')) {
      return;
    }

    // Grab the localized data object you injected in PHP
    const data = window[unique_id + '_data'];
    if (!data || !data.assistant_id || !data.unique_id) {
      console.error('Assistant data not found for ID:', unique_id);
      return;
    }
    const assistant_id   = data.assistant_id;

    //
    // CACHE DOM NODES
    //
    const $questionInput = $('#' + unique_id + '_question');
    const $submitButton  = $('#' + unique_id + '_submit');
    const $spinner       = $('#' + unique_id + '_spinner');
    const $downloadButton= $('#' + unique_id + '_download');
    const $chatHistory   = $('#' + unique_id + '_chat_history');

    let firstChunkArrived = false;

    //
    // 1) DOWNLOAD TRANSCRIPT
    //
    function downloadConversation() {
      const lines = [];
      $chatHistory.find('.occ-assistant-message').each(function() {
        const $m   = $(this);
        const who  = $m.hasClass('occ-assistant-user-message') ? 'User' : 'Assistant';
        const text = $m.text().trim();
        lines.push(`${who}: ${text}`);
      });

      const blob = new Blob([lines.join('\n\n')], { type: 'text/plain' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');

      a.href       = url;
      a.download   = 'assistant-conversation.txt';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    //
    // 2) STREAMING LOGIC (SSE)
    //
    function streamAssistantResponse(question) {
      // Build the AJAX-SSE URL, with cache-buster
      const baseUrl = occ_assistant_ajax.ajax_url
        + '?action=occ_assistant_stream_query'
        + `&nonce=${encodeURIComponent(occ_assistant_ajax.nonce)}`
        + `&question=${encodeURIComponent(question)}`
        + `&assistant_id=${encodeURIComponent(assistant_id)}`;

      const url = baseUrl + `&_=${Date.now()}`;

      // Reset flag, show spinner, disable Send
      firstChunkArrived = false;
      $spinner.css('visibility', 'visible');
      $submitButton.prop('disabled', true);

      const es = new EventSource(url);

      // When the first data chunk arrives, hide spinner and re-enable Send
      es.addEventListener('message', e => {
        if (!firstChunkArrived) {
          firstChunkArrived = true;
          $spinner.css('visibility', 'hidden');
          $submitButton.prop('disabled', false);
        }
        appendStreamedText(e.data);
      });

      // Clean up on completion (in case of zero-data streams)
      es.addEventListener('complete', () => {
        es.close();
        if (!firstChunkArrived) {
          $spinner.css('visibility', 'hidden');
          $submitButton.prop('disabled', false);
        }
      });

      // Handle assistant-specific errors
      es.addEventListener('assistant_error', e => {
        displayError(e.data);
        es.close();
        $spinner.css('visibility', 'hidden');
        $submitButton.prop('disabled', false);
      });

      // Handle network / SSE failures
      es.onerror = () => {
        displayError('SSE connection failed');
        es.close();
        $spinner.css('visibility', 'hidden');
        $submitButton.prop('disabled', false);
      };
    }

    //
    // 3) UI UPDATE HELPERS
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
      const $last = $chatHistory.find('.occ-assistant-message:last');
      $last.html($last.html() + escapeHtml(text).replace(/\n/g, '<br>'));
      scrollChatToBottom();
    }

    function displayError(msg) {
      $chatHistory.find('.occ-assistant-message:last')
        .html(`<p>Error: ${escapeHtml(msg)}</p>`);
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
    // 4) EVENT BINDINGS
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