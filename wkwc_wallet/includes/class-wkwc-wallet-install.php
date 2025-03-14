<?php
/**
 * Schema create on Activation
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WKWC_Wallet_Install' ) ) {
	/**
	 * Install Wallet
	 */
	class WKWC_Wallet_Install {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Global database.
		 *
		 * @var $wpdb
		 */
		protected $wpdb;

		/**
		 * Functions Construct
		 *
		 * @return void
		 */
		public function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
			add_action( 'admin_init', array( $this, 'init_schema' ) );
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
		 * Function initialization.
		 */
		public function init_schema() {
			$get_db_version = get_option( '_wkwc_wallet_db_version', '0.0.0' );

			if ( version_compare( WKWC_WALLET_DB_VERSION, $get_db_version, '>' ) ) {
				$this->create_update_migrate_schema_and_data();
			}
		}

		/**
		 * Create, update and migrate data and Schema.
		 */
		public function create_update_migrate_schema_and_data() {
			$wpdb_obj        = $this->wpdb;
			$charset_collate = $wpdb_obj->get_charset_collate();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// Code verification table.
			$old_verification       = $wpdb_obj->prefix . 'wallet_verification_code';
			$new_verification       = $wpdb_obj->prefix . 'wkwc_wallet_verification_code';
			$verification_check     = $wpdb_obj->get_var( "SHOW TABLES LIKE '$old_verification'" );
			$new_verification_check = $wpdb_obj->get_var( "SHOW TABLES LIKE '$new_verification'" );

			if ( $verification_check !== $old_verification && $new_verification_check !== $new_verification ) {
				$verification_sql = "CREATE TABLE IF NOT EXISTS $new_verification (
					id int(250) NOT NULL AUTO_INCREMENT,
					phone_number varchar(50) DEFAULT NULL,
					verification_code int(11) DEFAULT NULL,
					expiry int(250) NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate;";

				dbDelta( $verification_sql );
			} elseif ( $verification_check === $old_verification ) {
				$rename_verification_sql = "RENAME TABLE $old_verification to $new_verification";
				$wpdb_obj->query( $rename_verification_sql );
			}

			// Transactions table.
			$old_transactions       = $wpdb_obj->prefix . 'wallet_transactions';
			$new_transactions       = $wpdb_obj->prefix . 'wkwc_wallet_transactions';
			$transactions_check     = $wpdb_obj->get_var( "SHOW TABLES LIKE '$old_transactions'" );
			$new_transactions_check = $wpdb_obj->get_var( "SHOW TABLES LIKE '$new_transactions'" );

			if ( $transactions_check !== $old_transactions && $new_transactions_check !== $new_transactions ) {
				$transactions_sql = "CREATE TABLE IF NOT EXISTS $new_transactions (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					order_id varchar(250),
					reference varchar(100) NOT NULL,
					sender int(10) NOT NULL,
					customer int(10) NOT NULL,
					amount varchar(50) NOT NULL,
					transaction_type varchar(10) NOT NULL,
					transaction_date datetime NOT NULL,
					transaction_status varchar(25) DEFAULT 'completed',
					transaction_note varchar(250),
					PRIMARY KEY (`id`)
				) $charset_collate;";

				dbDelta( $transactions_sql );
			} elseif ( $transactions_check === $old_transactions ) {
				$rename_transactions_sql = "RENAME TABLE $old_transactions to $new_transactions";
				$wpdb_obj->query( $rename_transactions_sql );
			}

			$this->migrate_wallet_settings_and_data();

			update_option( '_wkwc_wallet_db_version', WKWC_WALLET_DB_VERSION, true );
		}

		/**
		 * Migrate Data.
		 */
		public function migrate_wallet_settings_and_data() {
			$new_wallet_key = 'wkwc_wallet_amount';
			$new_phone_key  = 'wkwc_wallet_phone_number';

			// Migrating for wallet system module.
			if ( defined( 'WKWP_WALLET_DB_VERSION' ) ) {
				$get_wkwp_wallet_prev_db_version = get_option( '_wkwp_wallet_db_prev_version', '0.0.0' );

				if ( version_compare( $get_wkwp_wallet_prev_db_version, '1.0.0', '<' ) ) {
					// 1. Migrating wallet amount and phone number.
					$old_wallet_key = 'wallet-amount';
					$old_phone_key  = 'wp_user_phone';

					$wallet_users = get_users(
						array(
							'fields'   => 'ID',
							'meta_key' => $old_wallet_key,
						)
					);

					foreach ( $wallet_users as $user_id ) {
						$new_amount = get_user_meta( $user_id, $new_wallet_key, true );
						$new_amount = empty( $new_amount ) ? 0 : floatval( $new_amount );
						$old_amount = get_user_meta( $user_id, $old_wallet_key, true );
						$old_amount = empty( $old_amount ) ? 0 : floatval( $old_amount );

						$new_amount += $old_amount;
						update_user_meta( $user_id, $new_wallet_key, $new_amount );

						// Migrating phone numbers.
						$old_phone = get_user_meta( $user_id, $old_phone_key, false );
						$new_phone = get_user_meta( $user_id, $new_phone_key, false );

						if ( empty( $new_phone ) && ! empty( $old_phone ) ) {
							update_user_meta( $user_id, $new_phone_key, $old_phone );
						}
					}

					// 2. Migrating wallet OTP settings from wallet module 3.5.2 to 3.6.0
					$option_keys = array(
						'woocommerce_customer_otp_verification' => '_wkwc_wallet_otp_enabled',
						'woocommerce_customer_otp_access_method' => '_wkwc_wallet_otp_method',
						'woocommerce_wallet_twilio_sid'    => '_wkwc_wallet_twilio_sid',
						'woocommerce_wallet_twilio_number' => '_wkwc_wallet_twilio_number',
						'woocommerce_wallet_twilio_auth_token' => '_wkwc_wallet_twilio_auth_token',
						'woocommerce_wallet_twilio_otp_validation_limit' => '_wkwc_wallet_twilio_otp_limit',
					);

					foreach ( $option_keys as $key_35 => $key_36 ) {
						$value_35 = get_option( $key_35, false );
						$value_36 = get_option( $key_36, false );

						if ( false === $value_36 && false !== $value_35 ) {
							update_option( $key_36, $value_35 );
						}
					}
				}
			}

			// TODO:: Will migrate for WC Group Buy, MP Group Buy and Binary MLM settings and db tables.
		}
	}
}
