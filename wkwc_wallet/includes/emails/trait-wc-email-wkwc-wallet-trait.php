<?php
/**
 * Using trait for including common feature in all email files.
 *
 * In other words to experience multiple inheritance.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! trait_exists( 'WC_Email_WKWC_Wallet_Trait' ) ) {
	/**
	 * WC_Email_WKWC_Wallet_Trait
	 */
	trait WC_Email_WKWC_Wallet_Trait {
		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading'      => $this->get_heading(),
					'customer_email'     => $this->get_recipient(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
					'data'               => $this->data,
					'additional_content' => $this->get_additional_content(),
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading'      => $this->get_heading(),
					'customer_email'     => $this->get_recipient(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
					'data'               => $this->data,
					'additional_content' => $this->get_additional_content(),
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Default Additional content.
		 */
		public function get_default_additional_content() {
			return __( 'Thank you for choosing {site_title}!', 'wp-wallet-system' );
		}

		/**
		 * Return Common email data.
		 *
		 * @param array $mail_data Mail data.
		 *
		 * @return array
		 */
		public function wkwc_wallet_get_common_mail_data( $mail_data ) {
			$name = empty( $mail_data['name'] ) ? __( 'Someone', 'wp-wallet-system' ) : $mail_data['name'];
			return array(
				'hi_msg'      => html_entity_decode( wp_sprintf( /* translators: %s: Customer name. */ esc_html__( 'Hi %s,', 'wp-wallet-system' ), esc_html( $name ) ), ENT_QUOTES, 'UTF-8' ),
				'message'     => empty( $mail_data['message'] ) ? __( 'Message from Admin', 'wp-wallet-system' ) : $mail_data['message'],
				'admin_email' => get_option( 'admin_email', false ),
			);
		}
	}
}
