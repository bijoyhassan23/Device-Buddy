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
    private $initialized = false;
    private $settings = [];
    
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
        // Include necessary files here
    }

    private function init_hooks() {
        // Initialize hooks here
        add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 10, 0 );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 10, 0 );
        add_action( 'admin_post_botbuddy_save_settings', [ $this, 'save_settings' ] );
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

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage these settings.', 'botbuddy' ) );
        }

        check_admin_referer( 'botbuddy_save_settings', 'botbuddy_nonce' );

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

        $this->settings = $this->get_settings();

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'botbuddy-admin',
                    'settings-updated' => '1',
                ],
                admin_url( 'tools.php' )
            )
        );
        exit;
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

    public function render_admin_page() {
        // Enqueue admin styles and scripts
        wp_enqueue_style( 'botbuddy-admin-style', BOT_BUDDY_PLUGIN_URL . 'admin/assets/css/admin-page.css', [], BOT_BUDDY_VERSION, 'all' );

        $settings = $this->get_settings();
        $settings_updated = isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'];

        include BOT_BUDDY_PLUGIN_DIR . 'admin/admin-page.php';
    }
}

add_action( 'plugins_loaded', [ 'Bot_buddy', 'get_instance' ], 10, 0 );