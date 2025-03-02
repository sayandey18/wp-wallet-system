<?php
/**
 * Wallet transaction mail.
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
		<p>' . $data['message'] . '</p>
		<p>' . esc_html__( 'Your Current wallet amount: ', 'wp-wallet-system' ) . $data['wallet_balance'] . '</p>
		<p>' . esc_html__( 'Reference: ', 'wp-wallet-system' ) . $data['reference'] . '</p>
		<p>' . esc_html__( 'Transaction Note: ', 'wp-wallet-system' ) . $data['transaction_note'] . '</p>
		<br/><br/>';
if ( ! empty( $additional_content ) ) {
	$result .= '<p>' . html_entity_decode( $additional_content, ENT_QUOTES, 'UTF-8' ) . '</p>';
}

echo wp_kses_post( $result );

do_action( 'woocommerce_email_footer', $email );
