<?php
/**
 * Admin End Ajax Functions
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Admin_Ajax_Functions' ) ) {
	/**
	 * Admin ajax functions class
	 */
	class WKWC_Wallet_Admin_Ajax_Functions {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Construct function
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
		 * Verify OTP for Wallet transaction.
		 */
		public function wkwc_wallet_search_customers_json() {
			if ( ! check_ajax_referer( 'wkwc-wallet-nonce', 'security', false ) ) {
				die( 'Busted!' );
			}

			$result = array(
				'error'   => true,
				'message' => esc_html__( 'No customer found.', 'wp-wallet-system' ),
			);

			$title = empty( $_GET['term'] ) ? '' : (string) wc_clean( wp_unslash( sanitize_key( $_GET['term'] ) ) );

			if ( ! empty( $title ) ) {

				$customers = get_users(
					array(
						'role__not_in'   => 'administrator',
						'search'         => '*' . $title . '*',
						'fields'         => array( 'id', 'display_name' ),
						'search_columns' => array(
							'ID',
							'user_login',
							'user_email',
							'user_nicename',
							'display_name',
						),
					)
				);

				$wallet_users = array_combine( wp_list_pluck( $customers, 'ID' ), wp_list_pluck( $customers, 'display_name' ) );

				if ( ! empty( $wallet_users ) ) {
					$result = array(
						'error'   => false,
						'data'    => $wallet_users,
						'message' => '',
					);

				} else {
					$result = array(
						'data'    => 0,
						'message' => esc_html__( 'No user found!', 'wp-wallet-system' ),
					);
				}
			}

			wp_send_json( $result );
		}
	}
}
