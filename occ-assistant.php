<?php
/**
 * Plugin Name:       Assistant Chatbot
 * Plugin URI:        https://example.com/plugins/assistant
 * Description:       An AI-powered assistant for activities using OpenAI (streaming).
 * Version:           1.6
 * Author:            James Wilson
 * Author URI:        https://oneclickcontent.com
 * License:           GPLv2 or later
 * Text Domain:       assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue frontend scripts and localize AJAX data.
 */
function assistant_enqueue_scripts() {
    wp_enqueue_script(
      'assistant-stream',
      plugin_dir_url(__FILE__) . 'occ-assistant-stream.js',
      [ 'jquery' ],
      filemtime( plugin_dir_path(__FILE__) . 'occ-assistant-stream.js' ),
      true
    );

    wp_localize_script(
        'assistant-stream',
        'occ_assistant_ajax',
        array(
            'ajax_url' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
            'nonce'    => wp_create_nonce( 'occ_assistant_nonce' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'assistant_enqueue_scripts' );

/**
 * Register settings page and fields.
 */
function assistant_register_settings() {
    add_options_page(
        esc_html__( 'Assistant Settings', 'assistant' ),
        esc_html__( 'DTA Settings', 'assistant' ),
        'manage_options',
        'occ-assistant-settings',
        'assistant_render_settings_page'
    );

    register_setting(
        'occ-assistant-settings-group',
        'occ_assistant_api_key',
        array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );
    register_setting(
        'occ-assistant-settings-group',
        'occ_assistant_assistant_id',
        array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    add_settings_section(
        'occ-assistant-settings-section',
        esc_html__( 'OpenAI Configuration', 'assistant' ),
        'assistant_settings_section_cb',
        'occ-assistant-settings'
    );

    add_settings_field(
        'occ-assistant-api-key',
        esc_html__( 'OpenAI API Key', 'assistant' ),
        'assistant_api_key_field_cb',
        'occ-assistant-settings',
        'occ-assistant-settings-section'
    );

    add_settings_field(
        'occ-assistant-assistant-id',
        esc_html__( 'Assistant ID', 'assistant' ),
        'assistant_assistant_id_field_cb',
        'occ-assistant-settings',
        'occ-assistant-settings-section'
    );

    add_filter(
        'pre_update_option_occ_assistant_api_key',
        'assistant_validate_api_key',
        10,
        2
    );
}
add_action( 'admin_menu', 'assistant_register_settings' );
add_action( 'admin_init', 'assistant_register_settings' );

/**
 * Section callback: description text.
 */
function assistant_settings_section_cb() {
    echo '<p>' . esc_html__( 'Enter your OpenAI API key and Assistant ID below.', 'assistant' ) . '</p>';
}

/**
 * Render the API key text input.
 */
function assistant_api_key_field_cb() {
    $value = get_option( 'occ_assistant_api_key', '' );
    printf(
        '<input type="text" name="occ_assistant_api_key" value="%s" class="regular-text" />',
        esc_attr( $value )
    );
}

/**
 * Render the Assistant ID text input.
 */
function assistant_assistant_id_field_cb() {
    $value = get_option( 'occ_assistant_assistant_id', '' );
    printf(
        '<input type="text" name="occ_assistant_assistant_id" value="%s" class="regular-text" />',
        esc_attr( $value )
    );
}

/**
 * Validate the OpenAI API key by making a test request.
 *
 * @param string $new_value New API key.
 * @param string $old_value Old API key.
 * @return string Either the validated new key or the old key on failure.
 */
function assistant_validate_api_key( $new_value, $old_value ) {
    if ( $new_value && $new_value !== $old_value ) {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . sanitize_text_field( $new_value ),
                    'Content-Type'  => 'application/json',
                ),
            )
        );

        $code = wp_remote_retrieve_response_code( $response );
        if ( is_wp_error( $response ) || 200 !== $code ) {
            add_settings_error(
                'occ_assistant_api_key',
                'invalid_api_key',
                esc_html__( 'Invalid OpenAI API key. Please verify and try again.', 'assistant' ),
                'error'
            );
            return $old_value;
        }
    }

    return $new_value;
}

/**
 * Render the settings page HTML.
 */
function assistant_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Assistant Settings', 'assistant' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'occ-assistant-settings-group' );
            do_settings_sections( 'occ-assistant-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Shortcode handler: outputs the assistant UI with support for assistant_id attribute.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML markup for the assistant.
 */
function assistant_render_assistant( $atts ) {
    // Define shortcode attributes
    $atts = shortcode_atts(
        array(
            'assistant_id' => get_option( 'occ_assistant_assistant_id', '' ),
        ),
        $atts,
        'occ_assistant'
    );

    // Generate a unique ID with underscores instead of hyphens
    $unique_id = 'occ_assistant_' . str_replace( '-', '_', wp_generate_uuid4() );

    // Localize the assistant ID for this specific instance
    wp_localize_script(
        'assistant-stream',
        $unique_id . '_data',
        array(
            'assistant_id' => sanitize_text_field( $atts['assistant_id'] ),
            'unique_id'    => $unique_id,
        )
    );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $unique_id ); ?>">
        <div id="<?php echo esc_attr( $unique_id ); ?>_chat_history"></div>
        <div id="<?php echo esc_attr( $unique_id ); ?>_input_area">
            <textarea id="<?php echo esc_attr( $unique_id ); ?>_question" rows="2" placeholder="<?php esc_attr_e( 'Ask your question…', 'assistant' ); ?>"></textarea>
            <button id="<?php echo esc_attr( $unique_id ); ?>_submit"><?php esc_html_e( 'Send', 'assistant' ); ?></button>
            <button id="<?php echo esc_attr( $unique_id ); ?>_download" type="button"><?php esc_html_e( 'Download Chat', 'assistant' ); ?></button>
            <span id="<?php echo esc_attr( $unique_id ); ?>_spinner" class="occ-assistant-spinner"></span>
        </div>
    </div>
    <style>
        .occ-assistant-spinner {
          display: inline-block;
          width: 16px;
          height: 16px;
          margin-left: 8px;
          vertical-align: middle;
          border: 2px solid rgba(0, 0, 0, 0.1);
          border-top-color: rgba(0, 0, 0, 0.6);
          border-radius: 50%;
          animation: occ-spin 0.8s linear infinite;
          visibility: hidden; /* hidden by default */
        }

        @keyframes occ-spin {
          to { transform: rotate(360deg); }
        }

        #<?php echo esc_attr( $unique_id ); ?> {
            display: flex;
            flex-direction: column;
            height: 400px;
            border: 1px solid #ccc;
            overflow: hidden;
        }
        #<?php echo esc_attr( $unique_id ); ?>_chat_history {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .occ-assistant-message {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 5px;
            clear: both;
        }
        .occ-assistant-user-message {
            background-color: #e0f2f7;
            float: right;
        }
        .occ-assistant-bot-message {
            background-color: #e8e8e8;
            float: left;
        }
        #<?php echo esc_attr( $unique_id ); ?>_input_area {
            padding: 10px;
            border-top: 1px solid #ccc;
            display: flex;
        }
        #<?php echo esc_attr( $unique_id ); ?>_input_area textarea {
            flex-grow: 1;
            resize: none;
        }
        #<?php echo esc_attr( $unique_id ); ?>_input_area button {
            margin-left: 10px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode( 'occ_assistant', 'assistant_render_assistant' );

/**
 * Handle the streaming AJAX request via SSE.
 */
function assistant_handle_query() {
    check_ajax_referer( 'occ_assistant_nonce', 'nonce' );

    @ini_set( 'output_buffering', 'off' );
    @ini_set( 'zlib.output_compression', false );
    @ini_set( 'max_execution_time', 120 );
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    header( 'Content-Type: text/event-stream' );
    header( 'Cache-Control: no-cache' );
    header( 'Connection: keep-alive' );
    header( 'X-Accel-Buffering: no' );

    $question = sanitize_text_field( wp_unslash( $_GET['question'] ?? '' ) );
    $assistant_id = sanitize_text_field( wp_unslash( $_GET['assistant_id'] ?? '' ) );

    if ( ! $question ) {
        echo "event: assistant_error\n";
        echo 'data: ["No question provided."]' . "\n\n";
        exit;
    }

    $api_key = get_option( 'occ_assistant_api_key', '' );

    if ( ! $api_key || ! $assistant_id ) {
        echo "event: assistant_error\n";
        echo 'data: ["API key or Assistant ID not configured."]' . "\n\n";
        exit;
    }

    $send_event = function( $event_name, $payload ) {
        $json = wp_json_encode( $payload );
        echo "event: {$event_name}\n";
        echo "data: {$json}\n\n";
        @ob_flush();
        @flush();
    };

    $send_event( 'ping', 'Connection established' );

    $thread_resp = wp_remote_post(
        'https://api.openai.com/v1/threads',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . esc_attr( $api_key ),
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ),
            'body'    => '{}',
        )
    );
    $thread_body = json_decode( wp_remote_retrieve_body( $thread_resp ), true );
    $thread_id   = $thread_body['id'] ?? '';

    if ( ! $thread_id ) {
        $send_event( 'assistant_error', 'Thread creation failed.' );
        exit;
    }

    wp_remote_post(
        "https://api.openai.com/v1/threads/{$thread_id}/messages",
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . esc_attr( $api_key ),
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ),
            'body'    => wp_json_encode(
                array(
                    'role'    => 'user',
                    'content' => $question,
                )
            ),
        )
    );

    $curl = curl_init( "https://api.openai.com/v1/threads/{$thread_id}/runs" );
    curl_setopt_array(
        $curl,
        array(
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . esc_attr( $api_key ),
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2',
            ),
            CURLOPT_POSTFIELDS     => wp_json_encode(
                array(
                    'assistant_id' => $assistant_id,
                    'stream'       => true,
                )
            ),
            CURLOPT_WRITEFUNCTION  => function( $ch, $chunk ) use ( $send_event ) {
                $lines        = explode( "\n", $chunk );
                $current_event = '';

                foreach ( $lines as $line ) {
                    if ( 0 === strpos( $line, 'event: ' ) ) {
                        $current_event = substr( $line, 7 );
                    }

                    if ( 0 === strpos( $line, 'data: ' ) ) {
                        $data = substr( $line, 6 );

                        if ( '[DONE]' === $data ) {
                            $send_event( 'complete', 'Stream completed' );
                            return strlen( $chunk );
                        }

                        $json = json_decode( $data, true );
                        if ( 'thread.message.delta' === $current_event ) {
                            $content = $json['delta']['content'][0]['text']['value'] ?? '';
                            if ( $content ) {
                                $send_event( 'message', $content );
                            }
                        } elseif ( 'thread.run.failed' === $current_event ) {
                            $error = $json['last_error']['message'] ?? 'Run failed';
                            $send_event( 'assistant_error', $error );
                        }
                    }
                }

                return strlen( $chunk );
            },
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_FOLLOWLOCATION => true,
        )
    );

    curl_exec( $curl );
    if ( curl_errno( $curl ) ) {
        $send_event( 'assistant_error', 'CURL error: ' . curl_error( $curl ) );
    }
    curl_close( $curl );

    exit;
}
add_action( 'wp_ajax_occ_assistant_stream_query', 'assistant_handle_query' );
add_action( 'wp_ajax_nopriv_occ_assistant_stream_query', 'assistant_handle_query' );