<?php
/**
 * WK Walelt Core loader class.
 *
 * @package WKWP_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

/**
 * This file is to initiate core and to run some common methods and decide which WK_Wallet_Core_Loader core should run.
 */
if ( ! class_exists( 'WK_Wallet_Core_Loader' ) ) {
	/**
	 * WK_Wallet_Core_Loader class.
	 */
	class WK_Wallet_Core_Loader {
		/**
		 * Plugins array.
		 *
		 * @var @plugins
		 */
		public static $plugins = array();

		/**
		 * Loaded.
		 *
		 * @var $loaded.
		 */
		public static $loaded = false;

		/**
		 * Ultimate path.
		 *
		 * @var $ultimate_path
		 */
		public static $ultimate_path = '';

		/**
		 * Version
		 *
		 * @var $version.
		 */
		public static $version = null;

		/**
		 * Include core.
		 *
		 * @return void
		 */
		public static function include_core() {
			$get_configuration = self::get_the_latest();

			if ( false === self::$loaded && $get_configuration && is_array( $get_configuration ) && isset( $get_configuration['class'] ) ) {
				if ( is_callable( array( $get_configuration['class'], 'load_files' ) ) ) {
					self::$version       = $get_configuration['version'];
					self::$ultimate_path = $get_configuration['plugin_path'] . '/wk_caching/';
					self::$loaded        = true;
					call_user_func( array( $get_configuration['class'], 'load_files' ) );
				}
			}
		}

		/**
		 * Register.
		 *
		 * @param array $configuration Configuration.
		 *
		 * @return void
		 */
		public static function register( $configuration ) {
			array_push( self::$plugins, $configuration );
		}

		/**
		 * Get the latest.
		 *
		 * @return array
		 */
		public static function get_the_latest() {
			$get_all = self::$plugins;
			uasort(
				$get_all,
				function ( $a, $b ) {
					if ( version_compare( $a['version'], $b['version'], '=' ) ) {
						return 0;
					} else {
						return ( version_compare( $a['version'], $b['version'], '<' ) ) ? - 1 : 1;
					}
				}
			);

			$get_most_recent_configuration = end( $get_all );

			return $get_most_recent_configuration;
		}
	}
}


if ( ! class_exists( 'WKWP_Wallet_WK_Wallet_Core' ) ) {
	/**
	 * WKWP_Wallet_WK_Wallet_Core
	 */
	class WKWP_Wallet_WK_Wallet_Core {
		/**
		 * WP Wallet Loading WC Wallet Version.
		 *
		 * @var $version
		 */
		public static $version = WKWP_WALLET_WKWC_WALLET_VERSION;

		/**
		 * Register.
		 *
		 * @return void
		 */
		public static function register() {
			$configuration = array(
				'basename'    => WKWP_WALLET_PLUGIN_BASENAME,
				'version'     => self::$version,
				'plugin_path' => WKWP_WALLET_PLUGIN_FILE,
				'class'       => __CLASS__,
			);
			WK_Wallet_Core_Loader::register( $configuration );
		}

		/**
		 * Load files.
		 *
		 * @return void
		 */
		public static function load_files() {
			$get_global_path = WKWP_WALLET_PLUGIN_FILE . 'wkwc_wallet/';

			if ( false === @file_exists( $get_global_path . '/includes/class-wkwc-wallet.php' ) ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'WKWC Wallet should be present in folder \'wkwc_wallet\includes\' in order to run this properly.', 'wp-wallet-system' ), esc_html( self::$version ) );
				if ( defined( 'WKWC_DEV' ) && true === WKWC_DEV ) {
					die( 0 );
				}
				return false;
			}

			/**
			 * Loading Core Wallet Files.
			 */
			require_once $get_global_path . 'includes/class-wkwc-wallet.php';

			if ( WKWC_WALLET_VERSION === self::$version ) {
				do_action( 'wk_wallet_loaded', $get_global_path );
			} else {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'WK Wallet Core should be at the same version as declared in your "class-wk-wallet-core-loader.php"', 'wp-wallet-system' ), esc_html( self::$version ) );
				if ( defined( 'WKWC_DEV' ) && true === WKWC_DEV ) {
					die( 0 );
				}
				return false;
			}
		}
	}
	WKWP_Wallet_WK_Wallet_Core::register();
}

