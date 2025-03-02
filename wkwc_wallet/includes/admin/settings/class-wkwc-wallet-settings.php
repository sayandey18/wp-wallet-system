<?php
/**
 *
 * Wallet settings.
 *
 * @package WKWC_WALLET
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if Accessed Directly.
}

/**
 * WKWC_Wallet_Settings
 */
class WKWC_Wallet_Settings {
	/**
	 * Instance variable.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	public function __construct() {
		$this->wkwc_wallet_settings();
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
	 * Wallet settings.
	 *
	 * @return void
	 */
	public function wkwc_wallet_settings() {
		if ( ! empty( $_GET['wkwc_show_caching'] ) && 'yes' === sanitize_text_field( $_GET['wkwc_show_caching'] ) && class_exists( 'WK_Caching' ) ) {
			\WK_Caching::wkwc_show_caching_settings();
		} else {
			require_once __DIR__ . '/wkmc-wallet-settings-content.php';
		}

	}
}
