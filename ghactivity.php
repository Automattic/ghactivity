<?php
/**
 * Plugin Name: GHActivity
 * Plugin URI: http://jeremy.hu
 * Description: Build reports of all your GitHub activity.
 * Version: 1.4.2
 * Author: Jeremy Herve
 * Author URI: http://jeremy.hu
 * Text Domain: ghactivity
 * Domain Path: /languages/
 * License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'GHACTIVITY__VERSION',     '1.4.1' );
define( 'GHACTIVITY__PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );

class Jeherve_GHActivity {
	private static $instance;

	static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Jeherve_GHActivity;
		}
		return self::$instance;
	}

	private function __construct() {
		// Load translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		// Load plugin
		add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
		// Flush rewrite rewrite_rules
		add_action( 'add_option_ghactivity_event', array( $this, 'flush_rules_on_enable' ) );
		add_action( 'update_option_ghactivity_event', array( $this, 'flush_rules_on_enable' ) );

	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ghactivity', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function load_plugin() {
		// Load core functions.
		require_once( GHACTIVITY__PLUGIN_DIR . 'core.ghactivity.php' );
		require_once( GHACTIVITY__PLUGIN_DIR . 'cpt.ghactivity.php' );
		require_once( GHACTIVITY__PLUGIN_DIR . 'charts.ghactivity.php' );
		require_once( GHACTIVITY__PLUGIN_DIR . 'reports.ghactivity.php' );
		require_once( GHACTIVITY__PLUGIN_DIR . 'widget.ghactivity.php' );

		// Settings panel.
		if ( is_admin() ) {
			require_once( GHACTIVITY__PLUGIN_DIR . 'admin.ghactivity.php' );
		}

		// Load shortcode
		require_once( GHACTIVITY__PLUGIN_DIR . 'shortcode.ghactivity.php' );
	}

	public function flush_rules_on_enable() {
		flush_rewrite_rules();
	}
}
// And boom.
Jeherve_GHActivity::get_instance();
