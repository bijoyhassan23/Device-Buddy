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
    }

    public function validate_request_nonce( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'x-botbuddy-nonce' );
        if ( empty( $nonce ) ) {
            $nonce = $request->get_param( 'nonce' );
        }
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'botbuddy_public_api' ) ) {
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
        $get_response = $this->send_to_deepseek($payload);
        return rest_ensure_response($get_response);
        return rest_ensure_response(
            [
                'success' => true,
                'message' => 'Public endpoint structure is ready.',
                'received' => $data,
                'memory' => 'this is the memory data that will be used to generate the response in the future',
            ]
        );
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

        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args );

        if ( is_wp_error( $response ) ) {
            $this->plugin->add_log( 'Pinecone query error: ' . $response->get_error_message() );
            return $response;
        }
        if ( ! isset( $response['body']['matches'] ) ) {
            return new WP_Error( 'botbuddy_pinecone_error', __( 'Error occurred while querying Pinecone.', 'botbuddy' ) );
        }
        return $response['body']['matches'];
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
        $payload = [
            "messages" => [],
            "model" => "deepseek-ai/DeepSeek-V4-Pro:novita",
            "stream" => false
        ];
        $payload["messages"][] = [
            "role" => "system",
            "content" => $system_prompt
        ];
        $payload["messages"] = array_merge($payload["messages"], $data['conversation'] ?? []);
        return $payload;
    }

    private function send_to_deepseek($payload){
        $endpoint = 'https://router.huggingface.co/v1/chat/completions';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['hugging_face_api_key'],
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 50, 
        ];
        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args );
                if ( is_wp_error( $response ) ) {
            // transport error
            $this->plugin->add_log( 'HF request error: ' . $response->get_error_message() );
            return $response;
        }
        return $response['body']['choices'][0]['message']['content'] ?? '';
    }
}
