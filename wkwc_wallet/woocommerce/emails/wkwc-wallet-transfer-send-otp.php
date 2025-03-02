<?php
/**
 * Wallet OTP mail.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

$mail_data  = empty( $data['mail_data'] ) ? array() : $data['mail_data'];
$text_align = is_rtl() ? 'right' : 'left';

do_action( 'woocommerce_email_header', $email_heading, $email );

$result = '
	<div style="margin-bottom: 40px;">
		<p>' . $mail_data['hi_msg'] . '</p>
		<p>' . $mail_data['message'] . '</p>

		<br/><br/>';
if ( ! empty( $additional_content ) ) {
	$result .= '<p>' . html_entity_decode( $additional_content, ENT_QUOTES, 'UTF-8' ) . '</p>';
}

echo wp_kses_post( $result );

do_action( 'woocommerce_email_footer', $email );
