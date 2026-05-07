<?php
/**
 * Plugin Name: BotBuddy
 * Description: An AI-powered chatbot for mobile service websites that delivers instant, accurate answers using your own data. Improve customer support, automate responses, and provide 24/7 assistance—all directly from your WordPress site.
 * Version: 1.0.0
 * Author: ForaziTech
 * Author URI: https://forazitech.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: botbuddy
 */

// Exit if accessed directly
defined( 'ABSPATH' ) or die( 'Direct access is not allowed' );

class Bot_buddy{
    private static $instance = null;
    private const LOG_TRANSIENT = 'botbuddy_logs';
    private $initialized = false;
    private $settings = [];
    private $rest_admin = null;
    
    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        $this->init();
    }

    public function init(  ){
        if ( $this->initialized ) {
            return;
        }
        $this->define_constants();
        $this->initialized = true;
        $this->settings = $this->get_settings();
        $this->include_files();
        $this->init_services();
        $this->init_hooks();
    }

    private function define_constants() {
        if ( ! defined( 'BOT_BUDDY_VERSION' ) ) {
            define( 'BOT_BUDDY_VERSION', time() );
        }
        if ( ! defined( 'BOT_BUDDY_PLUGIN_DIR' ) ) {
            define( 'BOT_BUDDY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }
        if ( ! defined( 'BOT_BUDDY_PLUGIN_URL' ) ) {
            define( 'BOT_BUDDY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }
    }

    private function include_files() {
        // Include admin REST API endpoints and other includes
        if ( file_exists( BOT_BUDDY_PLUGIN_DIR . 'admin/rest-api-admin.php' ) ) {
            require_once BOT_BUDDY_PLUGIN_DIR . 'admin/rest-api-admin.php';
        }
    }

    private function init_services() {
        if ( class_exists( 'Bot_buddy_Admin_REST' ) && null === $this->rest_admin ) {
            $this->rest_admin = new Bot_buddy_Admin_REST( $this );
        }
    }

    private function init_hooks() {
        // Initialize hooks here
        add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 10, 0 );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 10, 0 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 999, 1 );
        add_action( 'wp_ajax_botbuddy_save_settings', [ $this, 'save_settings_ajax' ] );
        add_action( 'admin_post_botbuddy_clear_logs', [ $this, 'clear_logs' ] );
        add_filter( 'script_loader_tag', [ $this, 'filter_admin_script_module_tag' ], 10, 3 );
    }

    private function log( $line ) {
        $line = is_scalar( $line ) ? (string) $line : wp_json_encode( $line );
        $ts = date_i18n( 'Y-m-d H:i:s' );
        $prev = get_transient( self::LOG_TRANSIENT );
        $prev = $prev ? "\n" . $prev : '';
        $msg = '[' . $ts . '] ' . $line;

        set_transient( self::LOG_TRANSIENT, $msg . $prev, 12 * HOUR_IN_SECONDS );

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::log( $line );
        }
    }

    public function add_log( $line ) {
        $this->log( $line );
    }

    public function get_logs() {
        $logs = get_transient( self::LOG_TRANSIENT );

        return is_string( $logs ) ? $logs : '';
    }

    public function clear_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to clear logs.', 'botbuddy' ) );
        }

        check_admin_referer( 'botbuddy_clear_logs', 'botbuddy_clear_logs_nonce' );

        delete_transient( self::LOG_TRANSIENT );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'botbuddy-admin',
                ],
                admin_url( 'tools.php' )
            )
        );
        exit;
    }

    public function get_settings() {
        return [
            'bot_name' => get_option( 'bot_name', 'BotBuddy' ),
            'bot_avatar' => get_option( 'bot_avatar', BOT_BUDDY_PLUGIN_URL . 'assets/images/bot-avatar.avif' ),
            'doc_id' => get_option( 'bot_doc_id', '' ),
            'hugging_face_api_key' => get_option( 'bot_hugging_face_api_key', '' ),
            'pinecone_api_key' => get_option( 'bot_pinecone_api_key', '' ),
            'pinecone_host' => get_option( 'bot_pinecone_host', '' ),
            'system_prompt' => get_option( 'bot_system_prompt', "You are a helpful assistant.\n\nMemory:\n%s" ),
            'prompt_template' => get_option(
                'bot_prompt_template',
                "Answer the question using the provided context or our previous conversation.\nRespond in a natural, human-friendly sentence.\n\nIf the answer is not in the context, say:\n'I don't know based on the provided information.'\n\nContext:\n%s\n\nQuestion:\n%s"
            ),
        ];
    }

    private function sanitize_settings( $input ) {
        return [
            'bot_name' => isset( $input['bot_name'] ) ? sanitize_text_field( wp_unslash( $input['bot_name'] ) ) : '',
            'bot_avatar' => isset( $input['bot_avatar'] ) ? esc_url_raw( wp_unslash( $input['bot_avatar'] ) ) : '',
            'doc_id' => isset( $input['doc_id'] ) ? sanitize_text_field( wp_unslash( $input['doc_id'] ) ) : '',
            'hugging_face_api_key' => isset( $input['hugging_face_api_key'] ) ? sanitize_text_field( wp_unslash( $input['hugging_face_api_key'] ) ) : '',
            'pinecone_api_key' => isset( $input['pinecone_api_key'] ) ? sanitize_text_field( wp_unslash( $input['pinecone_api_key'] ) ) : '',
            'pinecone_host' => isset( $input['pinecone_host'] ) ? esc_url_raw( wp_unslash( $input['pinecone_host'] ) ) : '',
            'system_prompt' => isset( $input['system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $input['system_prompt'] ) ) : '',
            'prompt_template' => isset( $input['prompt_template'] ) ? sanitize_textarea_field( wp_unslash( $input['prompt_template'] ) ) : '',
        ];
    }

    public function save_settings_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'You do not have permission to manage these settings.', 'botbuddy' ),
                ],
                403
            );
        }

        check_ajax_referer( 'botbuddy_save_settings', 'botbuddy_nonce' );

        $input = isset( $_POST['botbuddy_settings'] ) ? (array) $_POST['botbuddy_settings'] : [];
        $settings = $this->sanitize_settings( $input );

        update_option( 'bot_name', $settings['bot_name'] );
        update_option( 'bot_avatar', $settings['bot_avatar'] );
        update_option( 'bot_doc_id', $settings['doc_id'] );
        update_option( 'bot_hugging_face_api_key', $settings['hugging_face_api_key'] );
        update_option( 'bot_pinecone_api_key', $settings['pinecone_api_key'] );
        update_option( 'bot_system_prompt', $settings['system_prompt'] );
        update_option( 'bot_prompt_template', $settings['prompt_template'] );
        update_option( 'bot_pinecone_host', $settings['pinecone_host'] );

        $this->log( 'BotBuddy settings were updated.' );
        $this->settings = $this->get_settings();

        wp_send_json_success(
            [
                'message' => esc_html__( 'BotBuddy settings updated successfully.', 'botbuddy' ),
                'logs' => $this->get_logs(),
            ]
        );
    }

    public function register_scripts() {
        // Register and enqueue scripts here
        wp_register_script( 'botbuddy-script', BOT_BUDDY_PLUGIN_URL . 'assets/js/botbuddy.js', [], BOT_BUDDY_VERSION, true );
        wp_register_style( 'botbuddy-style', BOT_BUDDY_PLUGIN_URL . 'assets/css/botbuddy.css', [], BOT_BUDDY_VERSION, 'all' );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'BotBuddy',
            'BotBuddy',
            'manage_options',
            'botbuddy-admin',
            [ $this, 'render_admin_page' ]
        );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'tools_page_botbuddy-admin' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style( 'botbuddy-admin-style', BOT_BUDDY_PLUGIN_URL . 'admin/assets/css/admin-page.css', [], BOT_BUDDY_VERSION, 'all' );
        wp_enqueue_script( 'botbuddy-admin-script', BOT_BUDDY_PLUGIN_URL . 'admin/assets/js/admin-page.js', [], BOT_BUDDY_VERSION, true );
        wp_localize_script(
            'botbuddy-admin-script',
            'BotBuddyAdmin',
            [
                'settings' => $this->get_settings(),
                'ajax' => [
                    'url' => admin_url( 'admin-ajax.php' ),
                    'saveAction' => 'botbuddy_save_settings',
                ],
                'restApi' => [
                    'chunkingUrl' => rest_url( 'botbuddy/v1/admin/chunking' ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                ],
            ]
        );
    }

    public function filter_admin_script_module_tag( $tag, $handle, $src ) {
        if ( 'botbuddy-admin-script' !== $handle ) {
            return $tag;
        }

        return '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }

    public function render_admin_page() {
        $settings = $this->get_settings();
        $logs = $this->get_logs();

        include BOT_BUDDY_PLUGIN_DIR . 'admin/admin-page.php';
    }

    public function send_request( string $endpoint, array $payload = [], string $method = 'POST', array $args = [] , string $log_prefix = 'API Request' ) {
        $method = strtoupper( $method );
        $defaults = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
        ];
        $request_args = wp_parse_args( $args, $defaults );

        if ( 'GET' === $method ) {
            if ( ! empty( $payload ) ) {
                $endpoint = add_query_arg( $payload, $endpoint );
            }
            $response = wp_remote_get( $endpoint, $request_args );
        } else {
            $request_args['body'] = wp_json_encode( $payload );
            $response = wp_remote_post( $endpoint, $request_args );
        }

        if ( is_wp_error( $response ) ) {
            if ( method_exists( $this, 'add_log' ) ) {
                $this->add_log( "{$log_prefix} request failed: " . $response->get_error_message() );
            }
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $headers = wp_remote_retrieve_headers( $response );

        if ( method_exists( $this, 'add_log' ) ) {
            $this->add_log( "{$log_prefix} {$method} {$endpoint} -> {$code}" );
        }

        $decoded = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'code' => $code,
                'body' => $body,
                'headers' => is_array( $headers ) ? $headers : (array) $headers,
            ];
        }

        return [
            'code' => $code,
            'body' => $decoded,
            'raw' => $body,
            'headers' => is_array( $headers ) ? $headers : (array) $headers,
        ];
    }
}

add_action( 'plugins_loaded', [ 'Bot_buddy', 'get_instance' ], 10, 0 );