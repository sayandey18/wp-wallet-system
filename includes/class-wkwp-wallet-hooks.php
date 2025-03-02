<?php
/**
 * Wallet Hooks.
 *
 * @package WKWP_WALLET
 *
 * @since 3.6
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWP_Wallet_Hooks' ) ) {
	/**
	 * Wallet hooks class.
	 */
	class WKWP_Wallet_Hooks {
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
			$function_handler = WKWP_Wallet_Functions::get_instance();

			add_action( 'add_meta_boxes', array( $function_handler, 'wkwp_wallet_add_product_metabox' ) );
			add_action( 'save_post', array( $function_handler, 'wkwp_wallet_save_product_metabox' ) );
			add_action( 'new_to_publish', array( $function_handler, 'wkwp_wallet_save_product_metabox' ) );

			add_action( 'woocommerce_product_after_variable_attributes', array( $function_handler, 'wkwp_wallet_variation_product_metabox' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $function_handler, 'wkwp_wallet_save_variation_product_metabox' ), 10, 2 );

			add_action( 'woocommerce_order_status_completed', array( $function_handler, 'wkwp_wallet_after_order_completed' ), 10, 1 );
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
