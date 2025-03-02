<?php
/**
 * Settings template
 *
 * @package WKWP_WALLET
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

?>
<tr>
	<td colspan="2"><h3><?php esc_html_e( 'Cashback Settings', 'wp-wallet-system' ); ?></h3><hr></td>
</tr>
<tr>
	<th scope="row" class="titledesc">
		<label for="multiplce conditions"><?php esc_html_e( 'Multiple Cashback Conditions', 'wp-wallet-system' ); ?></label>
	</th>
	<td class="forminp forminp-text">
		<?php echo wc_help_tip( esc_html__( 'If checked, multipce cashback conditions can be applied.', 'wp-wallet-system' ), true ); ?>
		<input type="checkbox" id="_wkwp_wallet_multi_cb" name="_wkwp_wallet_multi_cb" value="1" <?php checked( get_option( '_wkwp_wallet_multi_cb', 1 ), 1 ); ?> />
	</td>
</tr>
<tr>
	<th scope="row">
		<label for="otp-method"><?php esc_html_e( 'Preference in Multiple Cashback', 'wp-wallet-system' ); ?></label>
	</th>
	<td>
		<?php echo wc_help_tip( esc_html__( 'Select preference for multiple cashback conditions.', 'wp-wallet-system' ), true ); ?>
		<select name="_wkwp_wallet_preferred_cb" id="_wkwp_wallet_preferred_cb" class="regular-text">
			<option value="product" <?php selected( get_option( '_wkwp_wallet_preferred_cb' ), 'product' ); ?> ><?php esc_html_e( 'Product', 'wp-wallet-system' ); ?></option>
			<option value="cart" <?php selected( get_option( '_wkwp_wallet_preferred_cb' ), 'cart' ); ?> ><?php esc_html_e( 'Cart', 'wp-wallet-system' ); ?></option>
		</select>
	</td>
</tr>

