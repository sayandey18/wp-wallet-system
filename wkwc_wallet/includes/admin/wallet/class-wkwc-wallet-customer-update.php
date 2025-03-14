<?php
/**
 * Customer wallet update for manual transactions.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Customer_Update' ) ) {
	/**
	 * WKWC_Wallet_Customer_Update Class.
	 */
	class WKWC_Wallet_Customer_Update {
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
		 * Show customer wallet table.
		 *
		 * @return void
		 */
		public function wkwc_show_wallet_update_form() {
			$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( isset( $posted_data['wkwc_wallet_manual_transaction_submit'] ) ) {
				$nonce = empty( $posted_data['wkwc_wallet_manual_transaction_nonce'] ) ? '' : wp_unslash( $posted_data['wkwc_wallet_manual_transaction_nonce'] );

				if ( wp_verify_nonce( $nonce, 'wkwc_wallet_manual_transaction' ) ) {
					$this->wkwc_wallet_handle_manual_transaction( $posted_data );
				}
			}
			$amount    = empty( $posted_data['wallet-transaction-amount'] ) ? '' : floatval( $posted_data['wallet-transaction-amount'] );
			$type      = empty( $posted_data['wallet-action'] ) ? 'credit' : $posted_data['wallet-action'];
			$note      = empty( $posted_data['wallet-note'] ) ? '' : $posted_data['wallet-note'];
			$customers = empty( $posted_data['wkwc_wallet_customer'] ) ? array() : $posted_data['wkwc_wallet_customer'];

			?>
			<div class="wrap woocommerce">
				<form method="post" action="" enctype="multipart/form-data">
					<h1><?php esc_html_e( 'Wallet Manual Transaction', 'wp-wallet-system' ); ?></h1>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="wallet-customer"><?php esc_html_e( 'Customer Name', 'wp-wallet-system' ); ?></label>
								</th>

								<td>
									<select multiple class="wkwc-wallet-customer" required name="wkwc_wallet_customer[]" id="wkwc_wallet_customer" title="<?php esc_attr_e( 'Customer', 'wp-wallet-system' ); ?>">
									<?php
									foreach ( $customers as $customer_id ) {
										?>
										<option selected value="<?php echo esc_attr( $customer_id ); ?>"><?php echo esc_html( WKWC_Wallet::wkwc_wallet_get_user_display_name( $customer_id ) ); ?></option>
										<?php
									}
									?>
								</select>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="wallet-transaction-amount"><?php esc_html_e( 'Amount', 'wp-wallet-system' ); ?></label>
								</th>

								<td>
									<input type="number" required class="" value="<?php echo esc_attr( $amount ); ?>" name="wallet-transaction-amount" id="wallet-transaction-amount" step="0.01" min="0">
								</td>
							</tr>

							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="wallet-action"><?php esc_html_e( 'Action', 'wp-wallet-system' ); ?></label>
								</th>

								<td>
									<select class="" name="wallet-action" id="wallet-action" title="action">
										<option value="credit"><?php esc_html_e( 'Credit', 'wp-wallet-system' ); ?></option>
										<option <?php selected( $type, 'debit', true ); ?> value="debit"><?php esc_html_e( 'Debit', 'wp-wallet-system' ); ?></option>
									</select>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="wallet-note"><?php esc_html_e( 'Transaction Note', 'wp-wallet-system' ); ?></label>
								</th>

								<td>
									<textarea cols="46" pattern="([A-z0-9\s]){2,}" rows="7" name="wallet-note" id="wallet-note" title="<?php esc_attr_e( 'note', 'wp-wallet-system' ); ?>"><?php echo esc_html( $note ); ?></textarea>
								</td>
							</tr>

						</tbody>
					</table>
						<?php wp_nonce_field( 'wkwc_wallet_manual_transaction', 'wkwc_wallet_manual_transaction_nonce' ); ?>
					<p class="submit">
						<input name="wkwc_wallet_manual_transaction_submit" class="button-primary" type="submit" value="<?php esc_attr_e( 'Update Wallet', 'wp-wallet-system' ); ?>">
						<a href="<?php echo esc_url( admin_url( '/admin.php?page=wkwp_wallet' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Cancel', 'wp-wallet-system' ); ?></a>
					</p>
				</form>
			</div>

			<?php
		}

		/**
		 * Manual wallet transaction submit.
		 *
		 * @param array $post_data Form submitted post data.
		 *
		 * @return void
		 */
		public function wkwc_wallet_handle_manual_transaction( $post_data = array() ) {
			$wallet_customers = empty( $post_data['wkwc_wallet_customer'] ) ? array() : array_map( 'intval', $post_data['wkwc_wallet_customer'] );
			$wallet_amount    = empty( $post_data['wallet-transaction-amount'] ) ? 0 : floatval( $post_data['wallet-transaction-amount'] );
			$wallet_action    = empty( $post_data['wallet-action'] ) ? '' : $post_data['wallet-action'];
			$wallet_note      = empty( $post_data['wallet-note'] ) ? '' : $post_data['wallet-note'];

			$errmsg = '';

			if ( empty( $wallet_customers ) || empty( $wallet_amount ) || empty( $wallet_action ) || empty( $wallet_note ) ) {
				$errmsg = __( 'Some fields are empty.', 'wp-wallet-system' );
			}

			if ( empty( $errmsg ) ) {
				$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

				$wallet_setting       = get_option( 'woocommerce_wkwc_wallet_settings', array() );
				$maximum_store_amount = empty( $wallet_setting['max_amount'] ) ? 0 : $wallet_setting['max_amount'];

				foreach ( $wallet_customers as $wallet_customer ) {
					$check_val  = '';
					$reference  = '';
					$old_amount = $tr_helper->wkwc_wallet_get_amount( $wallet_customer );

					if ( 'credit' === $wallet_action ) {
						$new_amount = $old_amount + $wallet_amount;
						if ( $new_amount <= $maximum_store_amount ) {
							$reference = __( 'Manual Wallet Credit', 'wp-wallet-system' );
							$check_val = 'updated';
						} else {
							$errmsg = wp_sprintf( /* translators: %s: Maximum amount. */ esc_html__( 'You Could not store more than %s', 'wp-wallet-system' ), $maximum_store_amount );
						}
					} elseif ( 'debit' === $wallet_action ) {
						if ( $old_amount >= $wallet_amount ) {
							$new_amount = $old_amount - $wallet_amount;
							$reference  = __( 'Manual Wallet Debit', 'wp-wallet-system' );
							$check_val  = 'updated';
						} else {
							$errmsg = wp_sprintf( /* translators: %s: Maximum amount. */ esc_html__( 'Wallet has less amount than %s', 'wp-wallet-system' ), $wallet_amount );
						}
					} else {
						$errmsg = __( 'Insufficient Amount.', 'wp-wallet-system' );
					}

					if ( $check_val ) {
						$data = array(
							'transaction_type'   => $wallet_action,
							'amount'             => $wallet_amount,
							'sender'             => get_current_user_id(),
							'customer'           => $wallet_customer,
							'transaction_note'   => $wallet_note,
							'transaction_status' => 'manual_transaction',
							'reference'          => $reference,

						);

						$tr_helper->create_transaction( $data );
					}
				}

				if ( ! empty( $wallet_action ) && ! empty( $check_val ) ) {
					wp_safe_redirect( site_url() . '/wp-admin/admin.php?page=wkwp_wallet&transaction=' . $wallet_action );

					exit;
				}
			}

			if ( ! empty( $errmsg ) ) {
				?>
				<div class='notice notice-error is-dismissible'>
					<p><?php echo esc_html( $errmsg ); ?></p>
				</div>
				<?php
			}
		}
	}
}
