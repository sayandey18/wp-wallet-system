<?php
/**
 * Main Class.
 *
 * @package WKWP_Wallet
 *
 * @since 1.0.0
 */
defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWP_Wallet' ) ) {
	/**
	 * Main Class
	 */
	final class WKWP_Wallet {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->init_hooks();
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
		 * Defining plugin's constant.
		 *
		 * @return void
		 */
		public function define_constants() {
			defined( 'WKWP_WALLET_PLUGIN_URL' ) || define( 'WKWP_WALLET_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
			defined( 'WKWP_WALLET_VERSION' ) || define( 'WKWP_WALLET_VERSION', '3.5.2' );
			defined( 'WKWP_WALLET_DB_VERSION' ) || define( 'WKWP_WALLET_DB_VERSION', '1.0.0' );
			defined( 'WKWP_WALLET_SCRIPT_VERSION' ) || define( 'WKWP_WALLET_SCRIPT_VERSION', '1.0.1' );
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.0
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
			add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
			add_action( 'wp_footer', array( $this, 'front_footer_info' ) );

			require_once WKWP_WALLET_PLUGIN_FILE . '/class-wk-caching-core-loader.php';
			require_once WKWP_WALLET_PLUGIN_FILE . '/class-wk-wallet-core-loader.php';

			add_action( 'plugins_loaded', array( 'WK_Caching_Core_Loader', 'include_core' ), - 1 );
			add_action( 'plugins_loaded', array( 'WK_Wallet_Core_Loader', 'include_core' ), - 1 );
		}

		/**
		 * Load plugin text domain.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'wc_wallet', false, WKWP_WALLET_PLUGIN_BASENAME . '/languages' );
		}

		/**
		 * Load plugin.
		 *
		 * @return void
		 */
		public function load_plugin() {
			if ( $this->dependency_satisfied() ) {
				WKWP_Wallet_File_Handler::get_instance();
			} else {
				add_action( 'admin_notices', array( $this, 'show_dependency_notice' ) );
			}
		}

		/**
		 * Check if WooCommerce is installed and activated.
		 *
		 * @return bool
		 */
		public function dependency_satisfied() {
			if ( ! function_exists( 'WC' ) || ! defined( 'WC_VERSION' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			wp_die( __FUNCTION__ . esc_html__( 'Cloning is forbidden.', 'wp-wallet-system' ) );
		}

		/**
		 * Deserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			wp_die( __FUNCTION__ . esc_html__( 'Deserializing instances of this class is forbidden.', 'wp-wallet-system' ) );
		}

		/**
		 * Show wc not installed notice.
		 *
		 * @return void
		 */
		public function show_dependency_notice() {
			?>
			<div class="error">
				<p><?php esc_html_e( 'WKWP wallet system is enabled but not effective. It requires WooCommerce to work! Latest version is recommended', 'wp-wallet-system' ); ?></p>
			</div>
			<?php
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
				$log_enabled = apply_filters( 'wkwp_wallet_is_log_enabled', true );

				if ( $log_enabled ) {
					$source            = ( is_array( $context ) && ! empty( $context['source'] ) ) ? $context['source'] : 'wkwp_wallet';
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
		 * Show version and last working day on front end.
		 *
		 * @return void
		 */
		public function front_footer_info() {
			$show_info = filter_input( INPUT_GET, 'wkmodule_info', FILTER_SANITIZE_NUMBER_INT );
			$show_info = empty( $show_info ) ? 0 : intval( $show_info );
			if ( 200 === $show_info ) {
				?>
			<input type="hidden" data-lwd="2023-07-18-2pm" data-wkwp_wallet_version="<?php echo esc_attr( WKWP_WALLET_VERSION ); ?>">
				<?php
			}
		}
	}
}
