<?php
/**
 * BotBuddy public REST API endpoints.
 */

defined( 'ABSPATH' ) or die( 'Direct access is not allowed' );

class Bot_buddy_Public_API {
    /**
     * Reference to the main plugin instance.
     *
     * @var Bot_buddy
     */
    private $plugin;
    private $settings;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->settings = $plugin->get_settings();
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route(
            'botbuddy/v1',
            '/public/message',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'handle_message' ],
                'permission_callback' => [ $this, 'validate_request_nonce' ],
            ]
        );

        register_rest_route(
            'botbuddy/v1',
            '/public/memory',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'handle_memory_request' ],
                'permission_callback' => [ $this, 'validate_request_nonce' ],
            ]
        );

        register_rest_route(
            'botbuddy/v1',
            '/public/message_stream',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'handle_stream_message' ],
                'permission_callback' => [ $this, 'validate_request_nonce' ],
            ]
        );
    }

    public function validate_request_nonce( WP_REST_Request $request ) {
        $botbuddy_nonce = $request->get_header( 'x-botbuddy-nonce' );
        if ( empty( $botbuddy_nonce ) ) {
            $botbuddy_nonce = $request->get_param( 'nonce' );
        }

        $wp_rest_nonce = $request->get_header( 'x-wp-nonce' );
        if ( empty( $wp_rest_nonce ) ) {
            $wp_rest_nonce = $request->get_param( '_wpnonce' );
        }

        $has_valid_botbuddy_nonce = ! empty( $botbuddy_nonce ) && wp_verify_nonce( $botbuddy_nonce, 'botbuddy_public_api' );
        $has_valid_wp_rest_nonce = ! empty( $wp_rest_nonce ) && wp_verify_nonce( $wp_rest_nonce, 'wp_rest' );

        if ( ! $has_valid_botbuddy_nonce && ! $has_valid_wp_rest_nonce ) {
            return new WP_Error(
                'botbuddy_invalid_nonce',
                __( 'Invalid request nonce.', 'botbuddy' ),
                [
                    'status' => 403,
                ]
            );
        }
        return true;
    }

    public function handle_message( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        if(!isset($data['message'])){
            return new WP_Error(
                'botbuddy_missing_message',
                __( 'Missing message parameter.', 'botbuddy' ),
                [
                    'status' => 400,
                ]
            );
        }

        $message = sanitize_text_field($data['message']);
        $similar_chunks = $this->get_similar_chunks($message) ?? [];
        $payload = $this->message_payload($data, $similar_chunks);
        $get_response = $this->send_to_llm($payload);
        return rest_ensure_response(
            [
                'success' => true,
                'message' => 'Public endpoint structure is ready.',
                'received' => $get_response,
            ]
        );
    }

    public function handle_memory_request( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        try{
            $message = sprintf($this->settings['memory_prompt'], $data['memory'] ?? '', $data['message'] ?? '', $data['response'] ?? '');
        }catch(Exception $e){
            return new WP_Error(
                'botbuddy_memory_prompt_error',
                __( 'Error generating memory prompt: ' . $e->getMessage(), 'botbuddy' ),
                [
                    'status' => 500,
                ]
            );
        }
        $payload = [
            'messages' => [
                [
                    "role" => "system",
                    "content" => "You are an AI memory manager. Your job is to maintain concise long-term memory from conversations."
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
        ];
        $get_response = $this->send_to_llm($payload);

        return rest_ensure_response(
            [
                'success' => true,
                'message' => 'Memory request route is ready.',
                'received' => $get_response,
            ]
        );
    }

    public function handle_stream_message( WP_REST_Request $request ) {
        $data = $request->get_json_params();

        if ( ! isset( $data['message'] ) ) {
            return new WP_Error(
                'botbuddy_missing_message',
                __( 'Missing message parameter.', 'botbuddy' ),
                [
                    'status' => 400,
                ]
            );
        }

        $message = sanitize_text_field( $data['message'] );
        $similar_chunks = $this->get_similar_chunks( $message ) ?? [];
        $payload = $this->message_payload( $data, $similar_chunks );

        $llm_provider = $this->settings['llm_provider'] ?? 'hugging_face';
        $endpoint = '';
        $api_key = '';

        if ( 'chatgpt' === $llm_provider ) {
            $endpoint = 'https://api.openai.com/v1/chat/completions';
            $api_key = $this->settings['chatgpt_api_key'] ?? '';
            $payload['model'] = 'gpt-4.1-mini';
        } else {
            $endpoint = 'https://router.huggingface.co/v1/chat/completions';
            $api_key = $this->settings['hugging_face_api_key'] ?? '';
            $payload['model'] = 'deepseek-ai/DeepSeek-V4-Pro:novita';
        }

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'botbuddy_missing_api_key',
                __( 'Missing API key for selected LLM provider.', 'botbuddy' ),
                [
                    'status' => 400,
                ]
            );
        }

        $payload['stream'] = true;

        // Stream chunks as Server-Sent Events for incremental UI updates.
        if ( function_exists( 'status_header' ) ) {
            status_header( 200 );
        }
        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache, no-transform' );
        header( 'Connection: keep-alive' );
        header( 'X-Accel-Buffering: no' );

        while ( ob_get_level() > 0 ) {
            @ob_end_flush();
        }
        @ob_implicit_flush( true );

        echo "event: start\n";
        echo 'data: ' . wp_json_encode( [ 'success' => true ] ) . "\n\n";
        flush();

        $stream_buffer = '';
        $final_text = '';

        $ch = curl_init( $endpoint );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ] );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, wp_json_encode( $payload ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
        curl_setopt( $ch, CURLOPT_WRITEFUNCTION, static function ( $curl, $chunk ) use ( &$stream_buffer, &$final_text ) {
            $stream_buffer .= $chunk;

            while ( false !== ( $newline_pos = strpos( $stream_buffer, "\n" ) ) ) {
                $line = trim( substr( $stream_buffer, 0, $newline_pos ) );
                $stream_buffer = substr( $stream_buffer, $newline_pos + 1 );

                if ( '' === $line || 0 !== strpos( $line, 'data:' ) ) {
                    continue;
                }

                $json_line = trim( substr( $line, 5 ) );
                if ( '[DONE]' === $json_line ) {
                    continue;
                }

                $decoded = json_decode( $json_line, true );
                if ( ! is_array( $decoded ) ) {
                    continue;
                }

                $delta = $decoded['choices'][0]['delta']['content'] ?? '';
                if ( '' === $delta ) {
                    continue;
                }

                $final_text .= $delta;

                echo 'data: ' . wp_json_encode( [ 'token' => $delta ] ) . "\n\n";
                flush();
            }

            return strlen( $chunk );
        } );

        $exec_result = curl_exec( $ch );

        if ( false === $exec_result || curl_errno( $ch ) ) {
            $error_message = curl_error( $ch );
            echo "event: error\n";
            echo 'data: ' . wp_json_encode( [ 'message' => $error_message ] ) . "\n\n";
            flush();
        }

        curl_close( $ch );

        echo "event: done\n";
        echo 'data: ' . wp_json_encode( [ 'text' => $final_text ] ) . "\n\n";
        flush();

        exit;
    }

    private function get_similar_chunks( $message ) {
        $get_embeding = $this->plugin->get_vector($message);
        if( is_wp_error($get_embeding) ) {
            if ( method_exists( $this->plugin, 'add_log' ) ) {
                $this->plugin->add_log( 'Error getting vector: ' . $get_embeding->get_error_message() );
            }
            return new WP_Error( 'botbuddy_vector_error', __( 'Error getting vector for the message.', 'botbuddy' ) );
        }
        
        // Placeholder for Pinecone query - replace with actual query logic
        $endpoint = $this->settings['pinecone_host'] . '/query';
        $payload = [
            'vector' => $get_embeding,
            'namespace' => 'default',
            "topK"=> 2,
            "includeMetadata" => true,
            "namespace" => "default"
        ];

        $args = [
            'headers' => [
                'Api-Key' => $this->settings['pinecone_api_key'],
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args, 'Send request to Pinecone' );

        if ( is_wp_error( $response ) ) {
            $this->plugin->add_log( 'Pinecone query error: ' . $response->get_error_message() );
            return $response;
        }
        if ( ! isset( $response['body']['matches'] ) ) {
            return new WP_Error( 'botbuddy_pinecone_error', __( 'Error occurred while querying Pinecone.', 'botbuddy' ) );
        }
        return $response['body']['matches'] ?? [];
    }

    private function message_payload($data, $similar_chunks){
        $context = '';
        if(is_array($similar_chunks)){
            foreach($similar_chunks as $chunk){
                $context .= $chunk['metadata']['text'] . "\n\n";
            }
        }
        $prompt = sprintf($this->settings['prompt_template'], $context, $data['message'] ?? '');
        $system_prompt = sprintf($this->settings['system_prompt'], $data['memory'] ?? '');
        $payload = ["messages" => []];
        $payload["messages"][] = [
            "role" => "system",
            "content" => $system_prompt
        ];
        $payload["messages"] = array_merge($payload["messages"], $data['conversation'] ?? []);
        $payload["messages"][] = [
            "role" => "user",
            "content" => $prompt
        ];
        return $payload;
    }

    private function send_to_llm($payload){
        $llm_provider = $this->settings['llm_provider'] ?? 'hugging_face';
        if($llm_provider === 'hugging_face'){
            return $this->send_to_deepseek($payload);
        } elseif($llm_provider === 'chatgpt'){
            return $this->send_to_chatgpt($payload);
        }
        return new WP_Error( 'botbuddy_invalid_llm', __( 'Invalid LLM provider configured.', 'botbuddy' ) );
    }

    private function send_to_deepseek($payload){
        $endpoint = 'https://router.huggingface.co/v1/chat/completions';
        $payload = array_merge($payload, [
            'model' => 'deepseek-ai/DeepSeek-V4-Pro:novita',
            'stream' => false,
        ]);
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['hugging_face_api_key'],
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 50, 
        ];
        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args, 'Send request to deepseek' );
        if ( is_wp_error( $response ) ) {
            // transport error
            $this->plugin->add_log( 'HF request error: ' . $response->get_error_message() );
            return $response;
        }
        return $response['body']['choices'][0]['message']['content'] ?? '';
    }

    private function send_to_chatgpt($payload){
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $payload = array_merge($payload, [
            'model' => 'gpt-4.1-mini',
            'stream' => false,
        ]);
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['chatgpt_api_key'],
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 50, 
        ];
        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args, 'Send request to chatgpt' );
        if ( is_wp_error( $response ) ) {
            // transport error
            $this->plugin->add_log( 'ChatGPT request error: ' . $response->get_error_message() );
            return $response;
        }
        return $response['body']['choices'][0]['message']['content'] ?? '';
    }
}
