<?php
/**
 * File Handler
 *
 * @package WKWP_WALLET
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

/**
 * File handler class.
 *
 * @class WKWP_Wallet_File_Handler
 */
final class WKWP_Wallet_File_Handler {
	/**
	 * Group wpdb.
	 *
	 * @var $wpdb
	 */
	protected $wpdb;

	/**
	 * The single instance of the class.
	 *
	 * @var $instance
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @return Main instance.
	 * @since 1.0.0
	 * @static
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( $this->is_request( 'admin' ) ) {
			WKWP_Wallet_Admin_Hooks::get_instance();
			WKWP_Wallet_Install::get_instance();
			WKWP_Wallet_Settings::get_instance();
			WKWP_Wallet_Hooks::get_instance();
		}
	}

	/**
	 * Which type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		if ( 'admin' === $type ) {
			return is_admin();
		}

		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}
}
