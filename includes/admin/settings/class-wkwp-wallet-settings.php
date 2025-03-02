<?php
/**
 *
 * Wallet settings.
 *
 * @package WKWP_WALLET
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if Accessed Directly.
}

/**
 * WKWP_Wallet_Settings
 */
class WKWP_Wallet_Settings {
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
		add_action( 'wkwc_wallet_add_settings_fields', array( $this, 'wkwp_wallet_cashback_settings' ) );
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
	 * Add cashback settings.
	 */
	public static function wkwp_wallet_cashback_settings() {
		require_once __DIR__ . '/wkwp-wallet-settings-content.php';
	}
}
