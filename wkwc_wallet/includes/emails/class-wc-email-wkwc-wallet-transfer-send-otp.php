<?php
/**
 * Send OTP for wallet transfer.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WC_Email_WKWC_Wallet_Transfer_Send_OTP' ) ) {
	/**
	 * WC_Email_WKWC_Wallet_Transfer_Send_OTP
	 */
	class WC_Email_WKWC_Wallet_Transfer_Send_OTP extends WC_Email {
		use WC_Email_WKWC_Wallet_Trait;
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'wkwc_wallet_transfer_send_otp';
			$this->title       = esc_html__( 'Send Wallet Transaction OTP', 'wp-wallet-system' );
			$this->description = esc_html__( 'OTP will be sent when transferring wallet amount.', 'wp-wallet-system' );

			$this->customer_email = true;

			$this->template_html  = 'emails/wkwc-wallet-transfer-send-otp.php';
			$this->template_plain = 'emails/plain/wkwc-wallet-transfer-send-otp.php';
			$this->template_base  = WKWC_WALLET_SUBMODULE_PATH . 'woocommerce/';

			add_action( 'wkwc_wallet_transfer_send_otp_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Trigger.
		 *
		 * @param array $data initiated data.
		 *
		 * @return void
		 */
		public function trigger( $data ) {
			$this->recipient = empty( $data['email'] ) ? '' : $data['email'];

			$enabled   = $this->is_enabled();
			$recipient = $this->get_recipient();

			WKWC_Wallet::log( __FUNCTION__ . ': ' . __LINE__ . "Enabled: $enabled, Recipient: $recipient Group data: " . print_r( $data, true ) );

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->setup_locale();

			$data['mail_data'] = $this->wkwc_wallet_get_common_mail_data( $data );

			$this->data = $data;

			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);

			$this->restore_locale();
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Wallet Transaction OTP', 'wp-wallet-system' );
		}

		/**
		 * Get default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '6 digit wallet transaction OTP {site_title}', 'wp-wallet-system' );
		}
	}
}
