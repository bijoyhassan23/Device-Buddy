<?php
/**
 * BotBuddy Admin REST API class
 *
 * Registers admin-scoped REST routes for future vectorization and Pinecone storage.
 */

defined( 'ABSPATH' ) or die( 'Direct access is not allowed' );
class Bot_buddy_Admin_REST {
    /**
     * Reference to main plugin instance
     * @var Bot_buddy
     */
    private $plugin;
    private $settings;
    private $chunks_count = 0;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->settings = $this->plugin->get_settings();
        $this->chunks_count = (int) get_option( 'chunks_count', '0' );
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route('botbuddy/v1', '/admin/chunking',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'vectorize_callback' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                }
            ]
        );
    }

    /**
     * Placeholder callback for vectorization endpoint.
     *
     * Currently returns the received payload so the route works. Real
     * vectorization and Pinecone storage will be added later.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function vectorize_callback( WP_REST_Request $request ) {
        $chunks = $request->get_json_params();
        if ( method_exists( $this->plugin, 'add_log' ) ) {
            $this->plugin->add_log( 'Chunking request received through the admin REST endpoint.' );
        }

        $chunksWithVectors = [];
        if(is_array($chunks)){
            foreach($chunks as $chunk){
                $vector = $this->plugin->get_vector($chunk['metadata']['text']);
                if( is_wp_error($vector) ) {
                    if ( method_exists( $this->plugin, 'add_log' ) ) {
                        $this->plugin->add_log( 'Error getting vector: ' . $vector->get_error_message() );
                    }
                    continue; // skip this chunk on error
                }
                $chunksWithVectors[] = array_merge($chunk, ['values' => $vector]);
            }
        }

        if(!empty($chunksWithVectors)){
            $upsertResponse = $this->upsert_vector($chunksWithVectors); // upsert the first chunk for testing
            if( is_wp_error($upsertResponse) ) {
                if ( method_exists( $this->plugin, 'add_log' ) ) {
                    $this->plugin->add_log( 'Error upserting vector: ' . $upsertResponse->get_error_message() );
                }
                return new WP_REST_Response( [
                    'success' => false,
                    'data' => [
                        'message' => 'Error upserting vectors to Pinecone.',
                        'logs' => method_exists( $this->plugin, 'get_logs' ) ? $this->plugin->get_logs() : '',
                    ],
                ], 500 );
            }else{
                $extra_chunks_count = $this->chunks_count - count($chunksWithVectors);
                if($extra_chunks_count > 0){
                    $extra_chunks_ids = [];
                    for($i = count($chunksWithVectors) + 1; $i <= $this->chunks_count; $i++){
                        $extra_chunks_ids[] = "chunk-{$i}";
                    }
                    if(!empty($extra_chunks_ids)){
                        $deleteResponse = $this->delete_chunks( $extra_chunks_ids ); // delete any extra chunks from previous runs
                        if( is_wp_error($deleteResponse) ) {
                            if ( method_exists( $this->plugin, 'add_log' ) ) {
                                $this->plugin->add_log( 'Error deleting extra chunks: ' . $deleteResponse->get_error_message() );
                            }
                        }else{
                            if ( method_exists( $this->plugin, 'add_log' ) ) {
                                $this->plugin->add_log( 'Deleted extra chunks: ' . implode(', ', $extra_chunks_ids) );
                            }
                        }
                    }
                }
                // update chunks count option
                update_option( 'chunks_count', count($chunksWithVectors) );
                $this->chunks_count = count($chunksWithVectors);
            }
        }
        
        return new WP_REST_Response( [
            'success' => true,
            'data' => [
                'message' => 'Chunking endpoint is working! Payload received.',
                'logs' => method_exists( $this->plugin, 'get_logs' ) ? $this->plugin->get_logs() : '',
                'upsertResponse' => $upsertResponse ?? null,
            ],
        ], 200 );
    }

    private function upsert_vector(array $chunkWithVector = []){
        $endpoint = $this->settings['pinecone_host'] . '/vectors/upsert';
        $payload = [
            'vectors' => $chunkWithVector,
            'namespace' => 'default',
        ];

        // return $payload; // for testing
        $args = [
            'headers' => [
                'Api-Key' => $this->settings['pinecone_api_key'],
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        $response = $this->plugin->send_request( $endpoint, $payload, 'POST', $args );

        if ( is_wp_error( $response ) ) {
            $this->plugin->add_log( 'Pinecone upsert error: ' . $response->get_error_message() );
            return $response;
        }
        return $response['body'];

    }

    private function delete_chunks(array $ids = []){
        $endpoint = $this->settings['pinecone_host'] . '/vectors/delete';
        $payload = [
            'ids' => $ids,
            'namespace' => 'default',
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
            $this->plugin->add_log( 'Pinecone delete error: ' . $response->get_error_message() );
            return $response;
        }
        return $response['body'];

    }
}
