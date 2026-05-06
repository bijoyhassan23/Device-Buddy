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
        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    private function define_constants() {
        define( 'BOT_BUDDY_VERSION', time() );
        define( 'BOT_BUDDY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'BOT_BUDDY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    private function include_files() {
        // Include necessary files here
    }

    private function init_hooks() {
        // Initialize hooks here
        add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 10, 0 );
    }

    public function register_scripts() {
        // Register and enqueue scripts here
        wp_register_script( 'botbuddy-script', BOT_BUDDY_PLUGIN_URL . 'assets/js/botbuddy.js', [], BOT_BUDDY_VERSION, true );
        wp_register_style( 'botbuddy-style', BOT_BUDDY_PLUGIN_URL . 'assets/css/botbuddy.css', [], BOT_BUDDY_VERSION, 'all' );
    }
}

add_action( "plugin_loaded", [Bot_buddy::get_instance(), 'init'], 10, 0 );