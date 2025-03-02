<?php
/**
 * Front End Ajax Hooks
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Front_Ajax_Hooks' ) ) {
	/**
	 * Front hooks class
	 */
	class WKWC_Wallet_Front_Ajax_Hooks {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Admin end Ajax hooks construct.
		 */
		public function __construct() {
			$function_handler = WKWC_Wallet_Front_Ajax_Functions::get_instance();

			add_action( 'wp_ajax_wkwc_wallet_send_transfer_otp', array( $function_handler, 'wkwc_wallet_send_transfer_money_otp' ) );
			add_action( 'wp_ajax_wkwc_wallet_verify_otp', array( $function_handler, 'wkwc_wallet_ajax_verify_otp' ) );
			add_action( 'wp_ajax_wkwc_wallet_frontend_bulk_delete', array( $function_handler, 'wkwc_wallet_frontend_bulk_delete' ) );
			add_action( 'wp_ajax_wkwc_wallet_checkout_validate', array( $function_handler, 'wkwc_wallet_ajax_validate_wallet' ) );
			add_action( 'wp_ajax_wkwc_wallet_update_phone', array( $function_handler, 'wkwc_wallet_ajax_update_phone' ) );
			add_action( 'wp_ajax_wkwc_wallet_remove_wallet', array( $function_handler, 'wkwc_wallet_ajax_remove_remove_wallet' ) );
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
