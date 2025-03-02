<?php
/**
 * Front End Functions.
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Front_Filter_Functions' ) ) {
	/**
	 * Front functions class
	 */
	class WKWC_Wallet_Front_Filter_Functions extends WKWC_Wallet_Front_Functions {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Front Functions Construct
		 *
		 * @return void
		 */
		public function __construct() {
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

		/**
		 * Updating Wallet page title.
		 *
		 * @param string $title Page title.
		 *
		 * @hooked 'the_title' Action hook.
		 *
		 * @return string
		 */
		public function wkwc_wallet_update_title( $title ) {
			global $wp;

			if ( is_array( $wp->query_vars ) && in_the_loop() && ! is_admin() && is_main_query() && is_account_page() ) {
				if ( array_key_exists( 'wkwc_wallet', $wp->query_vars ) ) {
					$title = __( 'Wallet', 'wp-wallet-system' );
				}

				remove_filter( 'the_title', array( $this, 'wkwc_wallet_update_title' ) );
			}

			return $title;
		}

		/**
		 * Add wallet endpoints.
		 *
		 * @param array $items Menu Items.
		 *
		 * @hooked 'woocommerce_account_menu_items' Filter hook
		 *
		 * @return array
		 */
		public function wkwc_wallet_add_endpoint( $items ) {
			if ( isset( $items['customer-logout'] ) ) {
				// Remove the logout menu item.
				$logout = $items['customer-logout'];
				unset( $items['customer-logout'] );
			}

			$wallet_setting = get_option( 'woocommerce_wkwc_wallet_settings', array() );

			if ( ! empty( $wallet_setting['enabled'] ) && 'yes' === $wallet_setting['enabled'] ) {
				// Insert your custom endpoint 'Wallet'.
				$items['wkwc_wallet'] = __( 'My Wallet', 'wp-wallet-system' );
			}

			if ( ! empty( $logout ) ) {
				// Insert back the logout item.
				$items['customer-logout'] = $logout;
			}

			return $items;
		}
	}
}
