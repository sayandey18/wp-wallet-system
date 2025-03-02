<?php
/**
 * Admin End Hooks.
 *
 * @package WKWC_WALLET
 *
 * @since 3.6
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Admin_Hooks' ) ) {
	/**
	 * Admin hooks class.
	 */
	class WKWC_Wallet_Admin_Hooks {
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
			$function_handler = WKWC_Wallet_Admin_Functions::get_instance();
			add_filter( 'woocommerce_screen_ids', array( $function_handler, 'wkwc_wallet_set_screen_ids' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $function_handler, 'wkwc_wallet_admin_scripts' ) );
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
