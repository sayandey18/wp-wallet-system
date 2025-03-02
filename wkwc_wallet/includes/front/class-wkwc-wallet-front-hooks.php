<?php
/**
 * Front End Hooks
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Front_Hooks' ) ) {
	/**
	 * Front hooks class
	 */
	class WKWC_Wallet_Front_Hooks {
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
			$function_handler = WKWC_Wallet_Front_Functions::get_instance();
			add_action( 'wp_enqueue_scripts', array( $function_handler, 'wkwc_wallet_public_scripts' ) );
			add_action( 'init', array( $function_handler, 'wkwc_wallet_create_wallet_wc_endpoints' ) );
			add_action( 'wp_footer', array( $function_handler, 'wkwc_wallet_front_footer_info' ) );
			add_action( 'template_redirect', array( $function_handler, 'wkwc_wallet_template_redirect' ) );

			$wallet_setting = get_option( 'woocommerce_wkwc_wallet_settings', array() );

			if ( ! empty( $wallet_setting['enabled'] ) && 'yes' === $wallet_setting['enabled'] ) {
				add_action( 'woocommerce_account_wkwc_wallet_endpoint', array( $function_handler, 'wkwc_wallet_endpoint_content' ) );
			}

			add_action( 'woocommerce_before_calculate_totals', array( $function_handler, 'wkwc_wallet_update_cart_price' ) );
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
