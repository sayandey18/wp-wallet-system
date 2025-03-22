<?php
/**
 * Wallet Hooks.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Hooks' ) ) {
	/**
	 * Front hooks class.
	 */
	class WKWC_Wallet_Hooks {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Wallet Front end hooks construct.
		 */
		public function __construct() {
			$function_handler = WKWC_Wallet_Functions::get_instance();
			add_action( 'woocommerce_review_order_before_submit', array( $function_handler, 'wkwc_wallet_wallet_payment' ), 20 );

			add_action( 'woocommerce_cart_calculate_fees', array( $function_handler, 'wkwc_wallet_add_cart_fee' ), 999 );

			add_filter( 'woocommerce_available_payment_gateways', array( $function_handler, 'wkwc_wallet_payment_gateway_handler' ), 10, 1 );

			add_action( 'woocommerce_checkout_order_processed', array( $function_handler, 'wkwc_wallet_order_processing' ), 10, 1 );

			add_action( 'woocommerce_order_status_completed', array( $function_handler, 'wkwc_wallet_update_after_order_completed' ), 10, 1 );

			add_action( 'user_register', array( $function_handler, 'wkwp_wallet_user_register_bonus' ), 10 );

			// add_action( 'woocommerce_cart_updated', array( $function_handler, 'wkwc_wallet_update_on_cart_change' ), 20 );
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
