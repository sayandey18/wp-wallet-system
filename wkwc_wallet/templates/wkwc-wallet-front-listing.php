<?php
/**
 *
 * Manage wallet amount.
 *
 * @package WKWC_Wallet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if Accessed Directly.
}
$wallet            = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
$otp_method        = get_option( '_wkwc_wallet_otp_method', 'mail' );
$width_class       = ( 'sms' === $otp_method ) ? 'wkwc_wallet_col_3' : 'wkwc_wallet_col_2';
$phone             = get_user_meta( $user_id, 'wkwc_wallet_phone_number', true );
$transaction_count = count( $all_transactions );
?>
<div class="wkwc-wallet-front-container">
	<div class="add-wallet-wrapper">
		<div class="amount-container">
			<div class="amount-heading <?php echo esc_attr( $width_class ); ?>">
				<h4><?php esc_html_e( 'Wallet Amount', 'wp-wallet-system' ); ?></h4>
				<span class="wallet-money-style"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?><?php echo esc_html( wc_format_decimal( $wallet_amount, 2 ) ); ?>
					<a href="<?php echo esc_url( wc_get_endpoint_url( 'wkwc_wallet/transfer' ) ); ?>" class="page-title-action wallet-transfer-link wallet-transfer"><?php esc_html_e( 'Wallet Transfer ', 'wp-wallet-system' ); ?></a>
				</span>
			</div>
			<?php
			if ( 'sms' === $otp_method ) {
				?>
				<div class="twilio-sms-mobile <?php echo esc_attr( $width_class ); ?>"">
					<span class="twilio-sms-mobile-title"><?php esc_html_e( 'Twilio SMS Number (with country code)', 'wp-wallet-system' ); ?></span>
					<div class="sms-number-wrap">
						<?php
						if ( ! empty( $phone ) ) {
							?>
							<span id="wkwc_wallet_twilio_phone"><?php echo esc_html( $phone ); ?></span>
							<span id="wkwc_wallet_twilio_edit" title="<?php esc_attr_e( 'Edit', 'wp-wallet-system' ); ?>" class="wkwc-wallet-twilio-action dashicons dashicons-edit"></span>
							<?php
						} else {
							?>
							<input value="" id="wkwc_wallet_twilio_sms_number" type="text" placeholder="<?php esc_attr_e( 'Enter your mobile number...', 'wp-wallet-system' ); ?>">
							<span id="wkwc_wallet_twilio_update" title="<?php esc_attr_e( 'Update', 'wp-wallet-system' ); ?>" class="wkwc-wallet-twilio-action dashicons dashicons-update"></span>
							<?php
						}
						?>

					</div>
				</div>
				<?php
			}
			?>

			<div class="process-money <?php echo esc_attr( $width_class ); ?>"">
				<form class="wallet-money-form" action="" method="POST" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wkwc_wallet_add_amount_nonce', 'wkwc_wallet_add_amount_nonce_action' ); ?>
				<input type="number" step="0.01" min="0" id="add_wallet_money" class="add_wallet_money" name="add_wallet_money" />
				<label for="add_wallet_money"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></label>

				<input type="hidden" name="wallet_id" value="<?php echo esc_attr( $wallet->ID ); ?>"/>
				<input type="submit" value="<?php esc_attr_e( 'Add to Wallet', 'wp-wallet-system' ); ?>" class="add_wallet_money_button wallet-button small" name="wkwc_wallet_add_money" />
				</form>
			</div>
		</div>
	</div>

	<div class="wallet-transactions-wrapper">
		<h4><?php esc_html_e( 'Wallet Transactions', 'wp-wallet-system' ); ?></h4>
		<div class="wallet-transcation-action-wrapper">
			<span class="wkwc-wallet-hide woocommerce-message" id="wkwc_wallet_action_message"></span>
			<div class="action-wrapper">
				<div class="form-row wkwc-wallet-bulk-wrap">
					<select name="wallet_action" id="wkwc_wallet_action">
						<option value=""><?php esc_html_e( 'Bulk Actions', 'wp-wallet-system' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'wp-wallet-system' ); ?></option>
					</select>
					<input type="button" class="front_bulk_action" id="wkwc_wallet_bulk_delete" value = "<?php esc_attr_e( 'Apply', 'wp-wallet-system' ); ?>" />
					<img class="wp-spin wkwc_wallet-spin-loader" style="display: none;" src="<?php echo esc_url( admin_url( '/images/spinner.gif' ) ); ?>">
				</div>
				<?php
				if ( $transaction_count > 0 ) {
					?>
				<div class="wkwc-wallet-total-transaction-count">
					<span><?php echo wp_sprintf( /* Translations: %s: Transaction count. */ esc_html__( '%s Transactions', 'wp-wallet-system' ), esc_html( $transaction_count ) ); ?></span>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php

		if ( $transaction_count > 0 ) :
			$date = new DateTime();
			?>
				<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
					<thead>
						<tr>
							<th><input type="checkbox" name="checkall" id="wkwc_wallet_checkall"/></th>
							<th><?php esc_html_e( 'ID', 'wp-wallet-system' ); ?></th>
							<th><?php esc_html_e( 'Reference', 'wp-wallet-system' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'wp-wallet-system' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wp-wallet-system' ); ?></th>
							<th><?php esc_html_e( 'Date', 'wp-wallet-system' ); ?></th>
						</tr>
					</thead>

					<tbody>
					<?php
					$transfer_transaction = array(
						'to'   => __( 'Transfer to ', 'wp-wallet-system' ),
						'from' => __( 'Received from ', 'wp-wallet-system' ),
					);

					foreach ( $transactions as $key => $transaction ) :
						$transaction_id = $transaction['id'];
						$customer_id    = ! empty( $transaction['customer'] ) ? $transaction['customer'] : $transaction['sender'];
						$reference      = $transaction['reference'];
						$customer       = get_user_by( 'ID', $customer_id );
						$email          = $customer->user_email . ' (#' . $customer_id . ')';

						if ( 'transfer' === $transaction['transaction_type'] ) {
							if ( get_current_user_id() === $transaction['customer'] ) {
								$reference = $transfer_transaction['from'] . get_userdata( $transaction['sender'] )->data->display_name;
							} elseif ( get_current_user_id() === $transaction['sender'] ) {
								$reference = $transfer_transaction['to'] . get_userdata( $transaction['customer'] )->data->display_name;
							}
						}
						?>
							<tr>
								<td>
								<input type="checkbox" class="delete_checkbox" value="<?php echo $transaction_id; ?>" />
								</td>
								<td>
									<?php echo '<a href = "' . wc_get_endpoint_url( 'wkwc_wallet' ) . 'view/' . esc_html( $transaction_id ) . '" >#' . esc_html( $transaction_id ) . '</a>'; ?>
								</td>
								<td>
									<?php echo empty( $reference ) ? esc_html_e( 'Wallet Transfer', 'wp-wallet-system' ) : esc_html( $reference ); ?>
								</td>
								<td>
									<?php echo wp_kses_post( wc_price( $transaction['amount'] ) ); ?>
								</td>
								<td>
									<?php echo esc_html( ucfirst( $transaction['transaction_type'] ) ); ?>
								</td>
								<td>
									<?php echo esc_html( gmdate( 'M d, Y g:i:s A', strtotime( $transaction['transaction_date'] ) ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php else : ?>
				<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
					<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
						<?php esc_html_e( 'Go shop', 'wp-wallet-system' ); ?>
					</a>
					<?php esc_html_e( 'No order has been made yet.', 'wp-wallet-system' ); ?>
				</div>
			<?php endif; ?>
	</div>
		<?php
		if ( count( $all_transactions ) > 1 ) :
			?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination wallet-pagination" style="margin-top:10px;">

				<?php if ( 1 !== $wc_paged && $wc_paged > 1 ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'wkwc_wallet', $wc_paged - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'wp-wallet-system' ); ?></a>
			<?php endif; ?>

				<?php if ( ceil( count( $all_transactions ) / $limit ) > $wc_paged ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'wkwc_wallet', $wc_paged + 1 ) ); ?>"><?php esc_html_e( 'Next', 'wp-wallet-system' ); ?></a>
			<?php endif; ?>

		</div>

<?php endif; ?>
</div>
