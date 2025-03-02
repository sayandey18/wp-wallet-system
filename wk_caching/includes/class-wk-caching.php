<?php
/**
 * This class is a main loader class for all caching core files.
 *
 * @package WK_Caching
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WK_Caching' ) ) {
	/**
	 * WK_Caching Class.
	 */
	class WK_Caching {
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
			add_action( 'plugins_loaded', array( __CLASS__, 'initialize' ) );
			add_action( 'wp_footer', array( __CLASS__, 'wk_caching_front_footer_info' ) );
		}

		/**
		 * Show caching settings.
		 */
		public static function wkwc_show_caching_settings() {
			$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! empty( $posted_data['wk_caching_set_save'] ) ) {
				$enabled = ! empty( $posted_data['wkwc_caching_enabled'] );
				update_option( 'wkwc_caching_enabled', $enabled );
			}
			$enabled = get_option( 'wkwc_caching_enabled', true );
			?>
			<h1 class=wkwc_caching_title><?php esc_html_e( 'Plugin Core Caching', 'wp-wallet-system' ); ?></h1>
			<div class="wrap">
				<form method="post">
					<table class="wkwc_caching_setting_table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable', 'wp-wallet-system' ); ?></th>
							<td><input <?php checked( $enabled, 1, true ); ?> type="checkbox" name="wkwc_caching_enabled" value="1"></td>
						</tr>
						<tr>
							<td>
								<?php submit_button( esc_attr__( 'Save', 'wp-wallet-system' ), 'primary', 'wk_caching_set_save' ); ?>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<?php

			$all_keys = array();

			if ( class_exists( 'WK_Caching_Core' ) ) {
				$cache_obj = \WK_Caching_Core::get_instance();

				if ( ! empty( $_GET['wkwc_caching_clear'] ) && 'yes' === sanitize_text_field( $_GET['wkwc_caching_clear'] ) ) {
					$cache_obj->reset( '', '', true );
				}

				$all_keys = $cache_obj->get_all( 'all_keys' );
			}

			echo '<p><a class="wk_caching_clear_all" href="' . esc_url( admin_url( 'admin.php?page=wkwp_wallet_settings&wkwc_show_caching=yes&wkwc_caching_clear=yes' ) ) . '">' . esc_html__( 'Clear all', 'wp-wallet-system' ) . '</a></p>';
			echo '<p>' . wp_sprintf( /* Translators: %s: Saved cache keys count. */ esc_html__( ' Saved keys count: %s', 'wp-wallet-system' ), count( $all_keys ) ) . '</p>';
			echo '<p><a class="wk_caching_show_keys" href="' . esc_url( admin_url( 'admin.php?page=wkwp_wallet_settings&wkwc_show_caching=yes&wkwc_caching_show_all=keys' ) ) . '">' . esc_html__( 'Show Keys', 'wp-wallet-system' ) . '</a></p>';
			echo '<p><a class="wk_caching_show_data" href="' . esc_url( admin_url( 'admin.php?page=wkwp_wallet_settings&wkwc_show_caching=yes&wkwc_caching_show_all=data' ) ) . '">' . esc_html__( 'Show Data', 'wp-wallet-system' ) . '</a></p>';

			if ( class_exists( 'WK_Caching_Core' ) ) {
				$cache_obj     = \WK_Caching_Core::get_instance();
				$show_all_type = empty( $_GET['wkwc_caching_show_all'] ) ? '' : sanitize_text_field( $_GET['wkwc_caching_show_all'] );

				if ( ! empty( $show_all_type ) ) {
					if ( 'data' === $show_all_type ) {
						$data = $cache_obj->get_all( 'data', $all_keys );
						echo '<pre>';
						print_r( $data );
						echo '</pre>';
					} else {
						echo '<pre>';
						print_r( $all_keys );
						echo '</pre>';
					}
				}
			}
		}

		/**
		 * Define constants.
		 */
		public static function define_constants() {
			defined( 'WK_CACHING_VERSION' ) || define( 'WK_CACHING_VERSION', '1.0.3' );
		}

		/**
		 * Localization.
		 *
		 * @return void
		 */
		public static function localization() {
			load_plugin_textdomain( 'wk_caching', false, plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
		}

		/**
		 * Initialization.
		 *
		 * @return void
		 */
		public static function initialize() {
			$caching_enabled = get_option( 'wkwc_caching_enabled', true );

			if ( $caching_enabled ) {
				// Load core auto-loader.
				require dirname( __DIR__ ) . '/inc/class-wk-caching-autoload.php';
				if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
					require dirname( __DIR__ ) . '/vendor/autoload.php';
				} else {
					add_action( 'admin_notices', array( __CLASS__, 'wk_caching_phpfasecache_not_installed_notice' ) );
				}
			}
		}

		/**
		 * PHP fast cache not installed notice.
		 *
		 * @return void
		 */
		public static function wk_caching_phpfasecache_not_installed_notice() {
			$configuation = WK_Caching_Core_Loader::get_the_latest();
			if ( ! empty( $configuation['plugin_path'] ) ) {
				?>
			<div class="error">
				<p>
					<?php
					esc_html_e( 'Please run the command "composer install" at following path to install PHP Fast Cache library.', 'wp-wallet-system' );
					?>
				</p>
				<p><?php echo esc_html( $configuation['plugin_path'] ) . 'wk_caching'; ?></p>
			</div>
				<?php
			}
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
				$log_enabled = apply_filters( 'wk_caching_is_log_enabled', true );

				if ( $log_enabled ) {
					$source            = ( is_array( $context ) && ! empty( $context['source'] ) ) ? $context['source'] : 'wk_caching';
					$context['source'] = $source;
					$logger            = wc_get_logger();
					$current_user_id   = get_current_user_id();

					$in_action = wp_sprintf( ( /* translators: %s current user id */ esc_html__( 'User in action: %s: ', 'wp-wallet-system' ) ), $current_user_id );
					$message   = $in_action . $message;

					$logger->log( $level, $message, $context );
				}
			}
		}

		/**
		 * Show current plugin version and last working day on front end.
		 *
		 * @hooked wp_footer Action hook.
		 *
		 * @return void
		 */
		public static function wk_caching_front_footer_info() {
			$show_info = filter_input( INPUT_GET, 'wkmodule_info', FILTER_SANITIZE_NUMBER_INT );
			$show_info = empty( $show_info ) ? 0 : intval( $show_info );
			if ( 200 === $show_info ) {
				?>
			<input type="hidden" data-lwd="2023-07-18-2pm" data-wk_caching_version="<?php echo esc_attr( WK_CACHING_VERSION ); ?>" data-wk_caching_slug="wk_caching">
				<?php
			}
		}
	}
	WK_Caching::init();
}
