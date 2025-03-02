<?php
/**
 * Email Handler.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

/**
 * File handler class.
 */
class WKWC_Wallet_Email_Handler {
	/**
	 * The single instance of the class.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * File handler construct.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_actions', array( $this, 'wkwc_wallet_add_woocommerce_email_actions' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'wkwc_wallet_add_new_email_notification' ), 10, 1 );

		add_action( 'wkwc_wallet_transfer_otp_sent', array( $this, 'wkwc_wallet_send_otp_mail' ) );
		add_action( 'wkwc_wallet_amount_updated', array( $this, 'wkwc_wallet_transaction_email' ) );
	}

	/**
	 * Main WKWC_Wallet_Email_Handler Instance.
	 *
	 * Ensures only one instance of WKWC_Wallet_Email_Handler is loaded or can be loaded.
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
	 * Add email action.
	 *
	 * @param array $actions Actions.
	 *
	 * @return array
	 */
	public function wkwc_wallet_add_woocommerce_email_actions( $actions ) {
		$actions[] = 'wkwc_wallet_transfer_send_otp';
		$actions[] = 'wkwc_wallet_transaction_mail';

		return $actions;
	}

	/**
	 * Add mail class.
	 *
	 * @param array $email Emails.
	 *
	 * @return array
	 */
	public function wkwc_wallet_add_new_email_notification( $email ) {
		$email['WC_Email_WKWC_Wallet_Transfer_Send_OTP'] = new WC_Email_WKWC_Wallet_Transfer_Send_OTP();
		$email['WC_Email_WKWC_Wallet_Transaction_Mail']  = new WC_Email_WKWC_Wallet_Transaction_Mail();

		return $email;
	}

	/**
	 * Send OTP email.
	 *
	 * @param array $mail_data Mail Data.
	 */
	public function wkwc_wallet_send_otp_mail( $mail_data ) {
		$mail_data['name'] = WKWC_Wallet::wkwc_wallet_get_user_display_name( $mail_data['customer_id'] );

		WKWC_Wallet::log( 'Mail data: ' . print_r( $mail_data, true ) );

		do_action( 'wkwc_wallet_transfer_send_otp', $mail_data );
	}

	/**
	 * Send wallet updated data.
	 *
	 * @param array $mail_data Mail Data.
	 */
	public function wkwc_wallet_transaction_email( $mail_data ) {
		$receiver_id = empty( $mail_data['customer'] ) ? 0 : $mail_data['customer'];

		if ( $receiver_id > 0 ) {
			$receiver           = get_user_by( 'ID', $receiver_id );
			$mail_data['name']  = WKWC_Wallet::wkwc_wallet_get_user_display_name( $receiver_id, $receiver );
			$mail_data['email'] = ( $receiver instanceof \WP_User ) ? $receiver->user_email : '';
		}

		WKWC_Wallet::log( 'Mail data for wallet amount updated: ' . print_r( $mail_data, true ) );

		do_action( 'wkwc_wallet_transaction_mail', $mail_data );

		if ( 'transfered' === $mail_data['transaction_status'] && $mail_data['sender'] > 0 ) {
			$sender                          = get_user_by( 'ID', $mail_data['sender'] );
			$mail_data['name']               = WKWC_Wallet::wkwc_wallet_get_user_display_name( $receiver_id, $sender );
			$mail_data['email']              = ( $sender instanceof \WP_User ) ? $sender->user_email : '';
			$mail_data['transaction_status'] = 'sent';

			do_action( 'wkwc_wallet_transaction_mail', $mail_data );
		}
	}
}
