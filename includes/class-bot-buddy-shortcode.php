<?php
/**
 * BotBuddy frontend shortcode.
 */

defined( 'ABSPATH' ) or die( 'Direct access is not allowed' );

class Bot_buddy_Shortcode {
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
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 10, 0 );
        add_shortcode( 'botbuddy', [ $this, 'render_shortcode' ] );
    }

    public function register_assets() {
        wp_register_style( 'botbuddy-frontend-style', BOT_BUDDY_PLUGIN_URL . 'assets/css/botbuddy-frontend.css' , [] , BOT_BUDDY_VERSION , 'all' );
        wp_register_script( 'botbuddy-frontend-script', BOT_BUDDY_PLUGIN_URL . 'assets/js/botbuddy-frontend.js', [] , BOT_BUDDY_VERSION , true );
        wp_localize_script(
            'botbuddy-frontend-script',
            'BotBuddyFrontend',
            [
                'apiEndpoint' => [
                    'message' => rest_url( 'botbuddy/v1/public/message' ),
                    'message_stream' => rest_url( 'botbuddy/v1/public/message_stream' ),
                    'memory' => rest_url( 'botbuddy/v1/public/memory' ),
                ],
                'nonce' => wp_create_nonce( 'botbuddy_public_api' ),
                'botImageUrl' => $this->settings['bot_avatar'] ?? '',
                'botName' => $this->settings['bot_name'] ?? 'BotBuddy',
            ]
        );
    }

    public function render_shortcode( $atts = [] ) {
        wp_enqueue_style( 'botbuddy-frontend-style' );
        wp_enqueue_style( 'botbuddy-style' );
        wp_enqueue_script( 'botbuddy-frontend-script' );
        wp_enqueue_script( 'botbuddy-script' );
        ob_start();
        include BOT_BUDDY_PLUGIN_DIR . 'includes/shortcode-body.php';
        return ob_get_clean();
    }
}
