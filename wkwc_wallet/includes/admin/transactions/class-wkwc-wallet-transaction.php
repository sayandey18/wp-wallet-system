<?php
/**
 * Single transaction view in admin.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Transaction' ) ) {
	/**
	 * WKWC_Wallet_Transaction Class.
	 */
	class WKWC_Wallet_Transaction {
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
		 * Show customer wallet transaction data.
		 *
		 * @param int $id Transaction id.
		 *
		 * @return void
		 */
		public function wkwc_wallet_transaction_view( $id ) {
			$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

			$transaction = $tr_helper->get_transactions(
				array(
					'transaction_id' => $id,
					'cache_group'    => 'wkwc_wallet_transaction',
					'cache_key'      => 'transaction_id_' . $id,
				)
			);

			$transaction      = ( is_array( $transaction ) && 1 === count( $transaction ) ) ? $transaction[0] : array();
			$order_id         = empty( $transaction['order_id'] ) ? 0 : intval( $transaction['order_id'] );
			$sender_id        = empty( $transaction['sender'] ) ? 0 : intval( $transaction['sender'] );
			$customer_id      = empty( $transaction['customer'] ) ? 0 : intval( $transaction['customer'] );
			$amount           = empty( $transaction['amount'] ) ? 0 : floatval( $transaction['amount'] );
			$transaction_type = empty( $transaction['transaction_type'] ) ? '' : $transaction['transaction_type'];
			$transaction_date = empty( $transaction['transaction_date'] ) ? '' : $transaction['transaction_date'];
			$reference        = empty( $transaction['reference'] ) ? '' : $transaction['reference'];
			$transaction_note = empty( $transaction['transaction_note'] ) ? '-' : $transaction['transaction_note'];
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Wallet Transaction Details', 'wp-wallet-system' ); ?></h1>
				<div class='wallet-transaction-view-wrapper'>
					<table class="wallet-transaction-view">
						<tbody>
							<?php
							if ( $sender_id > 0 || $customer_id > 0 || $order_id > 0 ) {
								?>
							<tr>
								<th><?php esc_html_e( 'Amount', 'wp-wallet-system' ); ?></th>
								<td><?php echo wc_price( $amount ); ?></td>
							</tr>
								<?php
								if ( $order_id > 0 ) {
									$order_url = get_edit_post_link( $order_id );

									if ( ! is_admin() ) {
										$order     = wc_get_order( $order_id );
										$order_url = $order->get_view_order_url();
									}
									?>
							<tr>
								<th><?php esc_html_e( 'Order ID', 'wp-wallet-system' ); ?></th>
								<td><?php echo '<a href="' . esc_url( $order_url ) . '" >#' . esc_html( $order_id ) . '</a>'; ?></td>
							</tr>
								<?php } ?>
							<tr>
								<th><?php esc_html_e( 'Action', 'wp-wallet-system' ); ?></th>
								<td><?php echo esc_html( ucfirst( $transaction_type ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Type', 'wp-wallet-system' ); ?></th>
								<td><?php echo esc_html( ucfirst( $transaction['reference'] ) ); ?></td>
							</tr>
								<?php
								if ( $customer_id > 0 ) {
									$customer       = get_user_by( 'ID', $customer_id );
									$customer_email = $customer->user_email . ' (#' . $customer_id . ')';
									?>
							<tr>
								<th><?php esc_html_e( 'Receiver', 'wp-wallet-system' ); ?></th>
								<td><?php echo esc_html( $customer_email ); ?></td>
							</tr>
									<?php
								}
								if ( $sender_id > 0 ) :
									$sender       = get_user_by( 'ID', $sender_id );
									$sender_email = $sender->user_email . ' (#' . $sender_id . ')';
									?>
							<tr>
								<th><?php esc_html_e( 'Payer', 'wp-wallet-system' ); ?></th>
								<td><?php echo esc_html( $sender_email ); ?></td>
							</tr>
								<?php endif; ?>
							<tr>
								<th><?php esc_html_e( 'Transaction On', 'wp-wallet-system' ); ?></th>
								<td><?php echo gmdate( 'M d, Y g:i:s A', strtotime( $transaction_date ) ); ?></td>
							</tr>
								<?php if ( 'Recharge Wallet' !== $reference ) { ?>
							<tr>
								<th><?php esc_html_e( 'Transaction Note', 'wp-wallet-system' ); ?></th>
								<td><?php echo esc_html( stripslashes( $transaction_note ) ); ?></td>
							</tr>
									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="2"><?php esc_html_e( 'Not a valid transaction.', 'wp-wallet-system' ); ?></td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		}

		/**
		 * Show customer transfer data.
		 *
		 * @param int $sender_id Sender id.
		 *
		 * @return void
		 */
		public function wkwc_wallet_transfer_amount( $sender_id ) {
			$tr_helper    = WKWC_Wallet_Transactions_Helper::get_instance();
			$wallet_money = $tr_helper->wkwc_wallet_get_amount( $sender_id );

			$form_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			$receiver = empty( $form_data['wkwc_wallet_receiver'] ) ? '' : $form_data['wkwc_wallet_receiver'];
			$amount   = empty( $form_data['wkwc_wallet_pay_amount'] ) ? '' : $form_data['wkwc_wallet_pay_amount'];
			$note     = empty( $form_data['wkwc_wallet_pay_note'] ) ? '' : $form_data['wkwc_wallet_pay_note'];
			?>
			<div class="wrap wkwc-wallet-front-container">
				<div class="main-container">

					<div style="padding: 6px; background-color: #f8f8f8;border: solid 1px #ccc;border-radius: 3px; display:inline-block; width:100%">
						<div style="float: left; margin-left: 3px;">
							<span class="wallet-money-style"><sup><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></sup>	<?php echo esc_html( wc_format_decimal( $wallet_money, 2 ) ); ?></span>
							<h4 style="margin:0; padding:0; font-size:17px; font-weight:500;"><?php esc_html_e( 'Wallet Amount', 'wp-wallet-system' ); ?></h4>
						</div>
						<span class="curreny_symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>

					</div>

					<div class = "form-wrapper wallet-transfer-container">
						<div>
							<table id="wkwc_wallet_transfer_from">
								<tr>
									<td>
										<label for="wkwc_wallet_receiver"><?php esc_html_e( 'Receiver&#8217;s Email : ', 'wp-wallet-system' ); ?></label>
									</td>
									<td>
										<input value="<?php echo esc_attr( $receiver ); ?>" type="email" id="wkwc_wallet_receiver" placeholder="<?php esc_attr_e( 'e.g. example@xyz.com', 'wp-wallet-system' ); ?>" />
									</td>
								</tr>
								<tr>
									<td>
										<label for="wkwc_wallet_pay_amount"><?php esc_html_e( 'Amount To Transfer: ', 'wp-wallet-system' ); ?></label>
									</td>
									<td>
										<input value="<?php echo esc_attr( $amount ); ?>" type="number" id="wkwc_wallet_pay_amount" placeholder="e.g. 100" step="0.01" min="1" max="<?php echo esc_attr( wc_format_decimal( $wallet_money, 2 ) ); ?>" />
									</td>
								</tr>
								<tr class="wkwc_wallet_transaction_note">
									<td>
										<label for="wkwc_wallet_pay_note"><?php esc_html_e( 'Transaction Note: ', 'wp-wallet-system' ); ?></label>
									</td>
									<td>
										<input value="<?php echo esc_attr( $note ); ?>" type="text" id="wkwc_wallet_pay_note" placeholder="e.g Abc"/>
									</td>
								</tr>
								<tr>
									<td>

										<button type="buttun" id="wkwc_wallet_transfer_money" class="wallet-button"><?php esc_html_e( 'Transfer', 'wp-wallet-system' ); ?></button>
										<button type="buttun" id="wkwc_wallet_verify_otp" class="wallet-button wallet-verify wkwc-wallet-hide"><?php esc_html_e( 'Verify & Transfer', 'wp-wallet-system' ); ?></button>
										<button type="buttun" id="wkwc_wallet_resend_otp" class="wallet-button wallet-resend wkwc-wallet-hide"><?php esc_html_e( 'Resend', 'wp-wallet-system' ); ?></button>
										<img class="wp-spin wkwc_wallet-spin-loader" style="display: none;" src="<?php echo esc_url( admin_url( '/images/spinner.gif' ) ); ?>">
									</td>
									<td><span class="wkwc_wallet_message error" id="wkwc_wallet_err_msg"></span></td>
								</tr>
							</table>
						</div>

					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Verify wallet transfer.
		 *
		 * @param array $posted_data Posted form data.
		 *
		 * @return bool
		 */
		public function wkwc_wallet_verify_transfer( $posted_data ) {
			$sender_id = get_current_user_id();
			$receiver  = empty( $posted_data['wkwc_wallet_receiver'] ) ? '' : $posted_data['wkwc_wallet_receiver'];
			$amount    = empty( $posted_data['wkwc_wallet_pay_amount'] ) ? 0 : $posted_data['wkwc_wallet_pay_amount'];
			$note      = empty( $posted_data['wkwc_wallet_pay_note'] ) ? '' : $posted_data['wkwc_wallet_pay_note'];

			$result = array(
				'success' => false,
				'message' => esc_html__( 'OTP could not be sent', 'wp-wallet-system' ),
			);

			if ( empty( $receiver ) ) {
				$result['message'] = __( 'Customer Email is required.', 'wp-wallet-system' );
				return $result;
			}

			if ( empty( $amount ) || ! is_numeric( $amount ) || $amount <= 0 ) {
				$result['message'] = __( 'Please enter a numeric amount greater than 0.', 'wp-wallet-system' );
				return $result;
			}

			$wallet_setting = get_option( 'woocommerce_wkwc_wallet_settings', array() );
			$max_transfer   = empty( $wallet_setting['max_transfer'] ) ? 0 : floatval( $wallet_setting['max_transfer'] );

			if ( ! empty( $max_transfer ) && $amount > $max_transfer ) {
				$result['message'] = wp_sprintf( /* translators: %1$s: Max transfer value. */ esc_html__( 'You can not transfer more than %s', 'wp-wallet-system' ), get_woocommerce_currency_symbol() . ' ' . number_format( $max_transfer, 2, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) );
				return $result;
			}

			$receiver_user = get_user_by( 'email', $receiver );

			if ( ! $receiver_user instanceof \WP_User ) {
				$result['message'] = __( 'Receiver does not exist.', 'wp-wallet-system' );
				return $result;
			}

			$sender       = get_user_by( 'id', $sender_id );
			$sender_email = ( $sender instanceof \WP_User ) ? $sender->user_email : '';

			if ( $receiver === $sender_email ) {
				$result['message'] = __( 'Cannot Transfer money to yourself.', 'wp-wallet-system' );
				return $result;
			}

			$tr_helper      = WKWC_Wallet_Transactions_Helper::get_instance();
			$wallet_balance = $tr_helper->wkwc_wallet_get_amount( $sender_id );

			if ( floatval( $wallet_balance ) < floatval( $amount ) ) {
				$result['message'] = __( 'You have insufficient wallet balance.', 'wp-wallet-system' );
				return $result;
			}

			$otp_enabled = get_option( '_wkwc_wallet_otp_enabled', false );

			if ( $otp_enabled ) {
				$receiver_name = WKWC_Wallet::wkwc_wallet_get_user_display_name( $receiver_user, $receiver_user );
				$otp_data      = array(
					'sender_id'    => $sender_id,
					'sender_email' => $sender_email,
					'receiver_id'  => $receiver_user->ID,
					'amount'       => $amount,
					'note'         => $note,
					'message'      => wp_sprintf( /* translators: %1$s: Blog info, %2$s: Transfer amount, %3$s: Receiver name. */ esc_html__( 'You have initiated a transfer of amount: %1$s to %2$s on the site [%3$s]', 'wp-wallet-system' ), get_woocommerce_currency_symbol() . ' ' . number_format( $amount, 2, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ), $receiver_name, get_bloginfo( 'name' ) ),
				);

				$result = $this->wkwc_wallet_send_otp( $otp_data );
			}
			return $result;
		}

		/**
		 * Send OTP for transfer validation.
		 *
		 * @param array $otp_data OTP data.
		 *
		 * @return array
		 */
		public function wkwc_wallet_send_otp( $otp_data ) {
			$otp_method   = get_option( '_wkwc_wallet_otp_method', 'mail' );
			$otp_limit    = get_option( '_wkwc_wallet_twilio_otp_limit', 0 );
			$otp_sec      = empty( $otp_limit ) ? 0 : intval( $otp_limit ) * 60;
			$sender_id    = empty( $otp_data['sender_id'] ) ? 0 : intval( $otp_data['sender_id'] );
			$sender_email = empty( $otp_data['sender_email'] ) ? '' : $otp_data['sender_email'];
			$message      = empty( $otp_data['message'] ) ? '' : $otp_data['message'];

			$result = array(
				'success'     => false,
				'otp_seconds' => $otp_sec,
				'message'     => esc_html__( 'Unable to sent the OTP.', 'wp-wallet-system' ),
			);

			$random_code = random_int( 100000, 999999 );
			$message    .= wp_sprintf( /* translators: %s: OTP */ esc_html__( ' Your six digit OTP for this wallet transaction is: %s', 'wp-wallet-system' ), $random_code );

			if ( 'mail' === $otp_method ) {
				$data = array(
					'customer_id' => $sender_id,
					'email'       => $sender_email,
					'message'     => $message,
					'action'      => 'wallet_otp',
				);

				$em           = explode( '@', $sender_email );
				$name         = implode( '@', array_slice( $em, 0, count( $em ) - 1 ) );
				$len          = floor( strlen( $name ) / 2 );
				$masked_email = substr( $name, 0, $len ) . str_repeat( '*', $len ) . '@' . end( $em );

				$success_message = wp_sprintf( /* translators: %s: OTP */ esc_html__( 'Your six digit OTP for wallet transaction has been successfully sent to the email: %s', 'wp-wallet-system' ), $masked_email );

				$result['success'] = true;
				$result['message'] = apply_filters( 'wkwc_wallet_otp_success_message', $success_message, $random_code );

				do_action( 'wkwc_wallet_transfer_otp_sent', $data );
			} else {
				$sender_phone = get_user_meta( $sender_id, 'wkwc_wallet_phone_number', true );

				if ( empty( $sender_phone ) ) {
					$result['message'] = esc_html__( 'You must update your phone number along with country from my-account/wallet section.', 'wp-wallet-system' );
				} else {
					$sms_helper = WKWC_Wallet_SMS_Handler::get_instance();
					$result     = $sms_helper->wkwc_wallet_send_otp_sms( $sender_phone, $message, $result, $random_code );
				}
			}

			if ( ! empty( $result['success'] ) ) {
				$valid_upto                 = strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $otp_sec;
				$otp_data['otp']            = $random_code;
				$otp_data['otp_valid_upto'] = $valid_upto;
				WC()->session->set( 'wkwc_wallet_transfer_otp', $otp_data );

				WKWC_Wallet::log( "wkwc_wallet_send_otp Otp: $random_code has been sent and valid upto: $valid_upto" );
			}

			return $result;
		}

		/**
		 * Set wallet as a payment method.
		 *
		 * @param array $wallet_setting Wallet settings.
		 *
		 * @return bool
		 */
		public function wkwc_wallet_set_wallet_payment( $wallet_setting = array() ) {
			$wallet_setting = empty( $wallet_setting ) ? get_option( 'woocommerce_wkwc_wallet_settings', array() ) : $wallet_setting;

			$user_id = get_current_user_ID();
			$total   = WC()->cart->get_total( 'edit' );

			$max_debit      = isset( $wallet_setting['max_debit'] ) ? floatval( $wallet_setting['max_debit'] ) : 0;
			$max_debit_type = empty( $wallet_setting['max_debit_type'] ) ? 0 : intval( $wallet_setting['max_debit_type'] );

			$tr_helper     = WKWC_Wallet_Transactions_Helper::get_instance();
			$wallet_amount = $tr_helper->wkwc_wallet_get_amount( $user_id );
			$method_set    = false;

			if ( ! empty( $wallet_amount ) && ! empty( $max_debit ) ) {
				$allowed_amount = 0;
				if ( 0 === $max_debit_type ) {
					$allowed_amount = ( $wallet_amount > $max_debit ) ? $max_debit : $wallet_amount;
				} elseif ( 1 === $max_debit_type ) {
					$calculated_amount = ( $total * $max_debit ) / 100;
					$allowed_amount    = ( $wallet_amount > $calculated_amount ) ? $calculated_amount : $wallet_amount;
				}

				if ( $allowed_amount > 0 ) {
					WC()->session->set( 'wkwc_wallet_allowed_wallet_amount', $allowed_amount );
				}
				$method_set = true;
			}

			return $method_set;
		}
	}
}
