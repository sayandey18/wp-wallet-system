<?php
/**
 * Wallet SMS verification.
 *
 * @package WKWC_Wallet
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit();

// Use the REST API Client to make requests to the Twilio REST API.
use Twilio\Rest\Client;

/**
 * WKWC_Wallet_SMS_Handler class.
 */
class WKWC_Wallet_SMS_Handler {
	/**
	 * The single instance of the class.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * File handler construct.
	 */
	public function __construct() {
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @return Main instance.
	 * @since 1.0.0
	 * @static
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Send OTP on SMS.
	 *
	 * @param string $phone_number Phone number.
	 * @param string $message OTP message.
	 * @param array  $result OTP send result.
	 * @param string $otp OTP value sent.
	 *
	 * @return array
	 */
	public function wkwc_wallet_send_otp_sms( $phone_number, $message, $result = array(), $otp = '' ) {
		$result['message'] = esc_html__( 'There are some issue in sending SMS, contact the administrator.', 'wp-wallet-system' );

		WKWC_Wallet::log( "Sending sms OTP to phone number:  $phone_number, message: $message" );

		if ( ! file_exists( WKWC_WALLET_SUBMODULE_PATH . 'vendor/autoload.php' ) ) {
			return $result;
		}

		if ( ! empty( $phone_number ) ) {
			try {

				require WKWC_WALLET_SUBMODULE_PATH . 'vendor/autoload.php';

				// Your Account SID and Auth Token from twilio.com/console.
				$sid           = get_option( '_wkwc_wallet_twilio_sid', true );
				$token         = get_option( '_wkwc_wallet_twilio_auth_token', true );
				$twilio_number = get_option( '_wkwc_wallet_twilio_number', true );

				WKWC_Wallet::log( "Sending sms OTP SID:; $sid, Token: $token, Twilio Number: $twilio_number" );

				$client = new Client( $sid, $token );

				// Use the client to do fun stuff like send text messages!
				$client->messages->create(
					// The number you'd like to send the message to.
					$phone_number,
					array(
						// A Twilio phone number you purchased at twilio.com/console.
						'from' => $twilio_number,
						// the body of the text message you'd like to send.
						'body' => $message,
					)
				);

				$phone_length = strlen( $phone_number );
				$masked_phone = substr( $phone_number, 0, 3 ) . str_repeat( '*', $phone_length - 5 ) . substr( $phone_number, -2 );

				$success_message = wp_sprintf( /* translators: %s: OTP */ esc_html__( 'Your six digit OTP for wallet transaction has been successfully sent to the phone: %s', 'wp-wallet-system' ), $masked_phone );

				$result['message'] = apply_filters( 'wkwc_wallet_otp_success_message', $success_message, $otp );
				$result['success'] = true;

				WKWC_Wallet::log( 'Sending sms OTP Result: ' . print_r( $result, true ) );

			} catch ( Exception $e ) {
				$result['message'] = __( 'SMS Service issue, Try again later.', 'wp-wallet-system' );
				$code              = $e->getCode();
				WKWC_Wallet::log( "Sending sms OTP Exception code: $code, Message: " . print_r( $e->getMessage(), true ) );
			}
		}

		return $result;
	}
}
