<?php
/**
 * Send wallet transaction mail notifications.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WC_Email_WKWC_Wallet_Transaction_Mail' ) ) {
	/**
	 * WC_Email_WKWC_Wallet_Transactions
	 */
	class WC_Email_WKWC_Wallet_Transaction_Mail extends WC_Email {
		use WC_Email_WKWC_Wallet_Trait;
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'wkwc_wallet_transaction_mail';
			$this->title       = esc_html__( 'Send Wallet Transaction Notifications', 'wp-wallet-system' );
			$this->description = esc_html__( 'A notification will be sent to the customer when any transaction from the wallet happened.', 'wp-wallet-system' );

			$this->customer_email = true;

			$this->template_html  = 'emails/wkwc-wallet-transaction-mail.php';
			$this->template_plain = 'emails/plain/wkwc-wallet-transaction-mail.php';
			$this->template_base  = WKWC_WALLET_SUBMODULE_PATH . 'woocommerce/';

			add_action( 'wkwc_wallet_transaction_mail_notification', array( $this, 'trigger' ), 10, 1 );

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

			WKWC_Wallet::log( __FUNCTION__ . ': ' . __LINE__ . "Enabled: $enabled, Recipient: $recipient Transaction data: " . print_r( $data, true ) );

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->setup_locale();

			$data['mail_data'] = $this->wkwc_wallet_get_common_mail_data( $data );

			$data['message'] = $this->prepare_message( $data );
			$tr_helper       = WKWC_Wallet_Transactions_Helper::get_instance();
			$customer        = empty( $data['customer'] ) ? 0 : $data['customer'];
			$sender          = empty( $data['sender'] ) ? 0 : $data['sender'];

			$wallet_amount = ( 'sent' === $data['transaction_status'] ) ? $tr_helper->wkwc_wallet_get_amount( $sender ) : $tr_helper->wkwc_wallet_get_amount( $customer );

			$data['wallet_balance'] = wc_price( $wallet_amount );

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
			return __( 'A New Wallet Transaction', 'wp-wallet-system' );
		}

		/**
		 * Get default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'A new transaction on your Wallet {site_title}', 'wp-wallet-system' );
		}

		/**
		 * Prepare transaction message.
		 *
		 * @param array $data Mail data.
		 *
		 * @return string
		 */
		public function prepare_message( $data ) {
			$type      = empty( $data['transaction_type'] ) ? '' : $data['transaction_type'];
			$status    = empty( $data['transaction_status'] ) ? '' : $data['transaction_status'];
			$order_id  = empty( $data['order_id'] ) ? 0 : $data['order_id'];
			$sender_id = empty( $data['sender'] ) ? 0 : $data['sender'];
			$customer  = empty( $data['customer'] ) ? 0 : $data['customer'];
			$amount    = empty( $data['amount'] ) ? '' : $data['amount'];

			$message = '';

			switch ( $status ) {
				case 'recharge_cashback':
					$message = wp_sprintf( /* translators: %s: Cashback amount. */ esc_html__( 'Your have received a %s cashback  in your wallet for recharging it.', 'wp-wallet-system' ), wc_price( $amount ) );
					break;

				case 'wallet_used':
					$message = wp_sprintf( /* translators: %1$s: Wallet amount, %$2s: Order id. */ esc_html__( 'You have used %1$s from your wallet to place the order %2$s.', 'wp-wallet-system' ), wc_price( $amount ), $order_id );
					break;

				case 'cashback':
					$message = wp_sprintf( /* translators: %1$s: Cashback amount, %$2s: Order id. */ esc_html__( 'You have received  a cashback %1$s for placing the order %2$s.', 'wp-wallet-system' ), wc_price( $amount ), $order_id );
					break;

				case 'refunded':
					$message = wp_sprintf( /* translators: %1$s: Refunded wallet amount, %$2s: Order id. */ esc_html__( 'Your wallet has been credited by %1$s on refunded the order %2$s', 'wp-wallet-system' ), wc_price( $amount ), $order_id );
					break;

				case 'transfered':
					$sender  = WKWC_Wallet::wkwc_wallet_get_user_display_name( $sender_id );
					$message = wp_sprintf( /* translators: %1$s: Transfer amount, %$2s: Sender Name. */ esc_html__( 'Your wallet has been credited by %1$s, sent by: %2$s', 'wp-wallet-system' ), wc_price( $amount ), $sender );
					break;

				case 'sent':
					$receiver = WKWC_Wallet::wkwc_wallet_get_user_display_name( $customer );
					$message  = wp_sprintf( /* translators: %1$s: Transfer amount, %$2s: Receiver Name. */ esc_html__( 'Your have transfered %1$s from your wallet to %2$s', 'wp-wallet-system' ), wc_price( $amount ), $receiver );
					break;

				case 'manual_transaction':
					$action  = ( 'credit' === $type ) ? esc_html__( 'credited to', 'wp-wallet-system' ) : esc_html__( 'debited from', 'wp-wallet-system' );
					$message = wp_sprintf( /* translators: %1$s: Manual amount, %$2s: Action. */ esc_html__( '%1$s has been %2$s to your wallet by admin', 'wp-wallet-system' ), wc_price( $amount ), $action );
					break;

				default:
					$message = wp_sprintf( /* translators: %1$s: Recharge amount. */ esc_html__( 'Your wallet has been recharged by %s', 'wp-wallet-system' ), wc_price( $amount ) );
					break;
			}

			return $message;
		}
	}
}
