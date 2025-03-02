<?php
/**
 * Front End Hooks
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Front_Filter_Hooks' ) ) {
	/**
	 * Front hooks class
	 */
	class WKWC_Wallet_Front_Filter_Hooks {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Front end hooks construct
		 */
		public function __construct() {
			$function_handler = WKWC_Wallet_Front_Filter_Functions::get_instance();

			add_filter( 'the_title', array( $function_handler, 'wkwc_wallet_update_title' ) );

			add_filter( 'woocommerce_account_menu_items', array( $function_handler, 'wkwc_wallet_add_endpoint' ) );
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
