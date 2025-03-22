<?php
/**
 * Schema create on Activation
 *
 * @package WKWP_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WKWP_Wallet_Install' ) ) {
	/**
	 * Install Wallet
	 */
	class WKWP_Wallet_Install {
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
			$wpdb_obj = $this->wpdb;

			register_setting( 'wkwc-wallet-settings-group', '_wkwp_wallet_multi_cb' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwp_wallet_preferred_cb' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwp_wallet_annual_purchased_cb' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwp_wallet_bonus_amount', 
				[
					'default' => 5.0,
					'sanitize_callback' => 'floatval'
				]
			);

			$get_db_version = get_option( '_wkwp_wallet_db_version', '0.0.0' );

			if ( version_compare( WKWP_WALLET_DB_VERSION, $get_db_version, '>' ) ) {
				$charset_collate = $wpdb_obj->get_charset_collate();

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';

				// Cashback table.
				$old_cashback       = $wpdb_obj->prefix . 'cashback_rules';
				$new_cashback       = $wpdb_obj->prefix . 'wkwp_wallet_cashback_rules';
				$cashback_check     = $wpdb_obj->get_var( "SHOW TABLES LIKE '$old_cashback'" );
				$new_cashback_check = $wpdb_obj->get_var( "SHOW TABLES LIKE '$new_cashback'" );

				if ( $cashback_check !== $old_cashback && $new_cashback_check !== $new_cashback ) {
					$cashback_sql = "CREATE TABLE IF NOT EXISTS $new_cashback (
						id bigint(20) NOT NULL AUTO_INCREMENT,
						rule_type varchar(10) NOT NULL,
						rule_price_from float NOT NULL,
						rule_price_to float NOT NULL,
						amount float NOT NULL,
						cashback_for varchar(50) NOT NULL,
						rule_status varchar(10) DEFAULT 'publish',
						PRIMARY KEY (`id`)
					) $charset_collate;";

					dbDelta( $cashback_sql );
				} elseif ( $cashback_check === $old_cashback ) {
					$rename_cashback_sql = "RENAME TABLE $old_cashback to $new_cashback";
					$wpdb_obj->query( $rename_cashback_sql );

					$amount_sql = "ALTER table $new_cashback MODIFY column amount float NOT NULL";
					$wpdb_obj->query( $amount_sql );
				}

				$this->wkwp_wallet_migrate_settings();

				update_option( '_wkwp_wallet_db_prev_version', $get_db_version, true );
				update_option( '_wkwp_wallet_db_version', WKWP_WALLET_DB_VERSION, true );
			}
		}

		/**
		 * Migrate old wallet settings to the new key.
		 *
		 * @return void
		 */
		public function wkwp_wallet_migrate_settings() {
			$option_keys = array(
				'woocommerce_multiple_cashback_condition' => '_wkwp_wallet_multi_cb',
				'woocommerce_multiple_cashback_condition_preference' => '_wkwp_wallet_preferred_cb',
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
}
