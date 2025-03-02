<?php
/**
 * Settings template
 *
 * @package WKWC_WALLET
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

settings_errors();
settings_fields( 'wkwc-wallet-settings-group' );
?>
<div class="wrap wkwc_wallet_wrap">
<h1 class=""><?php esc_html_e( 'Wallet Settings', 'wp-wallet-system' ); ?></h1>
<h3><?php esc_html_e( 'OTP Settings', 'wp-wallet-system' ); ?></h3><hr/>

<form method="POST" action="options.php" enctype="multipart/form-data" class="otp-settings">
	<?php settings_fields( 'wkwc-wallet-settings-group' ); ?>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row" class="titledesc">
				<label for="opt-verification"><?php esc_html_e( 'OTP Verification', 'wp-wallet-system' ); ?></label>
			</th>
			<td class="forminp forminp-text">
				<?php echo wc_help_tip( esc_html__( 'If checked, wallet checkout and wallet transfers requries OTP.', 'wp-wallet-system' ), true ); ?>
				<input type="checkbox" id="_wkwc_wallet_otp_enabled" name="_wkwc_wallet_otp_enabled" value="1" <?php checked( get_option( '_wkwc_wallet_otp_enabled', 1 ), 1 ); ?> />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="otp-method"><?php esc_html_e( 'OTP Access Method', 'wp-wallet-system' ); ?></label>
			</th>
			<td>
				<?php echo wc_help_tip( esc_html__( 'Select OTP verification method.', 'wp-wallet-system' ), true ); ?>
				<select name="_wkwc_wallet_otp_method" id="wkwc_wallet_otp_method" class="regular-text">
					<option value="sms" <?php selected( get_option( '_wkwc_wallet_otp_method' ), 'sms' ); ?> ><?php esc_html_e( 'SMS', 'wp-wallet-system' ); ?></option>
					<option value="mail" <?php selected( get_option( '_wkwc_wallet_otp_method' ), 'mail' ); ?> ><?php esc_html_e( 'Mail', 'wp-wallet-system' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'If SMS is enabled, Twillio account and credentials will be requried.', 'wp-wallet-system' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row" class="titledesc">
				<label for="woocommerce_wallet_twilio_otp_validation_limit"><?php esc_html_e( 'OTP Validation Limit in minute(s)', 'wp-wallet-system' ); ?></label>
			</th>
			<td>
				<input min='1' type="number" name="_wkwc_wallet_twilio_otp_limit" id="woocommerce_wallet_twilio_otp_validation_limit" value = "<?php echo esc_attr( get_option( '_wkwc_wallet_twilio_otp_limit', false ) ); ?>" />
			</td>
		</tr>

		<tr class="smshide">
			<th scope="row" class="titledesc">
				<label for="twilio_sid"><?php esc_html_e( 'Account SID', 'wp-wallet-system' ); ?></label>
			</th>
			<td>
				<input type="password" name="_wkwc_wallet_twilio_sid" pattern="[A-z0-9]{2,}" id="woocommerce_wallet_twilio_sid" value="<?php echo esc_attr( get_option( '_wkwc_wallet_twilio_sid', false ) ); ?>" />
			</td>
		</tr>

		<tr class="smshide">
			<th scope="row" class="titledesc">
				<label for="woocommerce_wallet_twilio_number"><?php esc_html_e( 'Twilio Number', 'wp-wallet-system' ); ?></label>
			</th>

			<td>
				<input type="password" name="_wkwc_wallet_twilio_number" id="woocommerce_wallet_twilio_number" value="<?php echo esc_attr( get_option( '_wkwc_wallet_twilio_number', false ) ); ?>" />
			</td>
		</tr>

		<tr class="smshide">
			<th scope="row" class="titledesc">
				<label for="woocommerce_wallet_twilio_auth_token"><?php esc_html_e( 'Auth Token', 'wp-wallet-system' ); ?></label>
			</th>
			<td>
				<input type="password" pattern="[A-z0-9]{2,}" name="_wkwc_wallet_twilio_auth_token" id="woocommerce_wallet_twilio_auth_token" value="<?php echo esc_attr( get_option( '_wkwc_wallet_twilio_auth_token', false ) ); ?>" />
			</td>
		</tr>

		<?php do_action( 'wkwc_wallet_add_settings_fields' ); ?>
		</tbody>
	</table>
	<?php submit_button( esc_html__( 'Save Changes', 'wp-wallet-system' ), 'primary' ); ?>

	<?php if ( defined( 'WKWC_DEV' ) && true === WKWC_DEV ) { ?>
		<p class="wkwc_wallet_caching_settings_links"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wkwp_wallet_settings&wkwc_show_caching=yes' ) ); ?>"><?php esc_html_e( 'Show Caching Settings', 'wp-wallet-system' ); ?></a></span>
	<?php } ?>
</form>
<hr/>
</div>

