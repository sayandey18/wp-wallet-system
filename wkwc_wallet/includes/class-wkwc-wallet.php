<?php
/**
 * This class is a main loader class for all core files.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet' ) ) {
	/**
	 * WKWC_Wallet Class.
	 */
	class WKWC_Wallet {
		/**
		 * Constructor.
		 */
		public function __construct() {
		}

		/**
		 * Init function hooked on `admin_init`
		 * Set the required variables and register some important hooks
		 */
		public static function init() {
			self::define_constants();
			add_action( 'init', array( __CLASS__, 'localization' ) );
			add_action( 'plugins_loaded', array( __CLASS__, 'initialze' ) );
			add_action( 'admin_init', array( __CLASS__, 'wkwc_wallet_register_settings' ) );
			add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'wkwc_wallet_add_payment_gateway' ), 11 );
		}

		/**
		 * Define constants.
		 */
		public static function define_constants() {
			defined( 'WKWC_WALLET_VERSION' ) || define( 'WKWC_WALLET_VERSION', '1.0.3' );
			defined( 'WKWC_WALLET_DB_VERSION' ) || define( 'WKWC_WALLET_DB_VERSION', '1.0.3' );
			defined( 'WKWC_WALLET_SCRIPT_VERSION' ) || define( 'WKWC_WALLET_SCRIPT_VERSION', '1.0.3' );
			defined( 'WKWC_WALLET_SUBMODULE_URL' ) || define( 'WKWC_WALLET_SUBMODULE_URL', plugin_dir_url( dirname( __FILE__ ) ) );
			defined( 'WKWC_WALLET_SUBMODULE_PATH' ) || define( 'WKWC_WALLET_SUBMODULE_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public static function includes() {
			if ( self::is_request( 'admin' ) ) {
				WKWC_Wallet_Install::get_instance();
				WKWC_Wallet_Admin_Hooks::get_instance();
				WKWC_Wallet_Admin_Ajax_Hooks::get_instance();
				WKWC_Wallet_Product::get_instance();
			}
			WKWC_Wallet_Front_Hooks::get_instance();
			WKWC_Wallet_Hooks::get_instance();
			WKWC_Wallet_Front_Filter_Hooks::get_instance();
			WKWC_Wallet_Front_Ajax_Hooks::get_instance();
			WKWC_Wallet_Email_Handler::get_instance();
		}

		/**
		 * Localization.
		 *
		 * @return void
		 */
		public static function localization() {
			load_plugin_textdomain( 'wkwc_wallet', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Initialization.
		 *
		 * @return void
		 */
		public static function initialze() {
			// Load core auto-loader.
			require dirname( __DIR__ ) . '/inc/class-wkwc-wallet-autoload.php';

			$otp_method = get_option( '_wkwc_wallet_otp_method', 'mail' );

			if ( 'mail' !== $otp_method ) {
				if ( ! file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
					add_action( 'admin_notices', array( __CLASS__, 'wkwc_wallet_twilio_not_installed_notice' ) );
				}
			}

			self::includes();
		}

		/**
		 * Twilio library not installed notice.
		 *
		 * @return void
		 */
		public static function wkwc_wallet_twilio_not_installed_notice() {
			$configuation = WK_Caching_Core_Loader::get_the_latest();

			if ( ! empty( $configuation['plugin_path'] ) ) {
				?>
			<div class="error">
				<p>
					<?php
					esc_html_e( 'Please run the command "composer install" at following path to install Twilio library library for SMS feature.', 'wp-wallet-system' );
					?>
				</p>
				<p><?php echo esc_html( $configuation['plugin_path'] ) . 'wkwc_wallet'; ?></p>
			</div>
				<?php
			}
		}

		/**
		 * Which type of request is this?
		 *
		 * @param string $type admin, ajax, cron or frontend.
		 *
		 * @return bool
		 */
		private static function is_request( $type ) {
			if ( 'admin' === $type ) {
				return is_admin();
			}

			return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}

		/**
		 * Wallet settings.
		 *
		 * @return void
		 */
		public static function wkwc_wallet_register_settings() {
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_otp_enabled' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_otp_method' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_twilio_sid' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_twilio_number' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_twilio_auth_token' );
			register_setting( 'wkwc-wallet-settings-group', '_wkwc_wallet_twilio_otp_limit' );
		}

		/**
		 * Add the gateway to woocommerce.
		 *
		 * @param array $methods All payment methods.
		 */
		public static function wkwc_wallet_add_payment_gateway( $methods ) {
			$methods[] = 'WC_Gateway_WKWC_Wallet';

			return $methods;
		}

		/**
		 * To get first admin user id. It will return smallest admin user id on the site.
		 *
		 * @return int
		 */
		public static function wkwc_wallet_get_first_admin_user_id() {
			// Find and return first admin user id.
			$first_admin_user_id = 0;
			$admin_users         = get_users(
				array(
					'role'    => 'administrator',
					'orderby' => 'ID',
					'order'   => 'ASC',
					'number'  => 1,
				)
			);

			if ( count( $admin_users ) > 0 && $admin_users[0] instanceof \WP_User ) {
				$first_admin_user_id = $admin_users[0]->ID;
			}

			return $first_admin_user_id;
		}

		/**
		 * Log function for debugging.
		 *
		 * @param mixed  $message Message string or array.
		 * @param array  $context Additional parameter, like file name 'source'.
		 * @param string $level One of the following:
		 *     'emergency': System is unusable.
		 *     'alert': Action must be taken immediately.
		 *     'critical': Critical conditions.
		 *     'error': Error conditions.
		 *     'warning': Warning conditions.
		 *     'notice': Normal but significant condition.
		 *     'info': Informational messages.
		 *     'debug': Debug-level messages.
		 */
		public static function log( $message, $context = array(), $level = 'info' ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$log_enabled = apply_filters( 'wkwc_wallet_is_log_enabled', true );

				if ( $log_enabled ) {
					$source            = ( is_array( $context ) && ! empty( $context['source'] ) ) ? $context['source'] : 'wkwc_wallet';
					$context['source'] = $source;
					$logger            = wc_get_logger();
					$current_user_id   = get_current_user_id();

					$in_action = sprintf( ( /* translators: %s current user id */ esc_html__( 'User in action: %s: ', 'wp-wallet-system' ) ), $current_user_id );
					$message   = $in_action . $message;

					$logger->log( $level, $message, $context );
				}
			}
		}

		/**
		 * Get display name of a user.
		 *
		 * @param int           $user_id User id.
		 * @param object|string $user User objct.
		 * @param object|string $display_type Name display type e.g. 'full'|'nick.
		 *
		 * @return string
		 */
		public static function wkwc_wallet_get_user_display_name( $user_id = 0, $user = '', $display_type = 'full' ) {
			$display_name = __( 'Anonymous User', 'wp-wallet-system' );

			if ( ! $user instanceof \WP_User && $user_id > 0 ) {
				$user = get_user_by( 'ID', $user_id );
			}

			if ( is_a( $user, 'WP_User' ) ) {
				if ( 'nick' === $display_type ) {
					$display_name = empty( $user->user_nicename ) ? $user->display_name : $user->user_nicename;
				} else {
					$display_name  = empty( $user->first_name ) ? get_user_meta( $user_id, 'first_name', true ) : $user->first_name;
					$display_name .= empty( $display_name ) ? '' : ( empty( $user->last_name ) ? ' ' . get_user_meta( $user_id, 'last_name', true ) : ' ' . $user->last_name );
					$display_name  = empty( $display_name ) ? $user->display_name : $display_name;
					$display_name  = empty( $display_name ) ? $user->user_nicename : $display_name;
				}
			}

			return apply_filters( 'wkwc_wallet_get_user_display_name', $display_name, $user_id );
		}
	}
	WKWC_Wallet::init();
}
