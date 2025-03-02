<?php
/**
 * Admmin End Ajax Hooks
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Admin_Ajax_Hooks' ) ) {
	/**
	 * Admin hooks class
	 */
	class WKWC_Wallet_Admin_Ajax_Hooks {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Admin end ajax hooks construct.
		 */
		public function __construct() {
			$function_handler = WKWC_Wallet_Admin_Ajax_Functions::get_instance();

			add_action( 'wp_ajax_wkwc_wallet_json_search_customers', array( $function_handler, 'wkwc_wallet_search_customers_json' ) );
			add_action( 'wp_ajax_nopriv_wkwc_wallet_json_search_customers', array( $function_handler, 'wkwc_wallet_search_customers_json' ) );

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
