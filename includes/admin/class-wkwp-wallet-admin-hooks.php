<?php
/**
 * Admin End Hooks.
 *
 * @package WKWP_WALLET
 *
 * @since 3.6
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWP_Wallet_Admin_Hooks' ) ) {
	/**
	 * Admin hooks class.
	 */
	class WKWP_Wallet_Admin_Hooks {
		/**
		 * Instance variable.
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Admin end hooks construct.
		 */
		public function __construct() {
			$function_handler = WKWP_Wallet_Admin_Functions::get_instance();
			add_filter( 'plugin_action_links_' . WKWP_WALLET_PLUGIN_BASENAME, array( $function_handler, 'add_plugin_setting_links' ) );
			add_action( 'admin_menu', array( $function_handler, 'wkwp_wallet_create_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $function_handler, 'wkwp_wallet_enqueue_admin_scripts' ) );
			add_action( 'admin_post_wkwp_wallet_update_cb_rule', array( $function_handler, 'wkwp_wallet_add_update_cb_rule' ) );
		}

		/**
		 * Ensures only one instance of this class is loaded or can be loaded.
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( ! static::$instance ) {
				static::$instance = new self();
			}
			return static::$instance;
		}
	}
}
