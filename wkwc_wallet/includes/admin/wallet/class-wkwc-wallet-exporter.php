<?php
/**
 * Wallet detail exporter from admin.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Exporter' ) ) {
	/**
	 * Wallet Exporter
	 */
	class WKWC_Wallet_Exporter {
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
		 * Process exporting wallet details.
		 *
		 * @return void
		 */
		public function wkwcwallet_process_exporting_wallet_details() {
			$row_data = array();
			$users    = get_users();

			if ( ! empty( $users ) ) {
				$row_data[] = 'ID,username,email,location,orders,wallet_amount';

				foreach ( $users as $user ) {
					$row_data[] = $this->wkwcwallet_generate_wallet_details_csv_row_data( $user );
				}

				$row_data = implode( "\n", $row_data );

				header( 'Content-Type: text/csv; charset=UTF-8 BOM' );
				header( 'Content-Disposition: attachment; filename="wallet_details.csv";' );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );

				$export_file = fopen( 'php://output', 'w' );

				ob_end_clean();
				fwrite( $export_file, $row_data );
				fclose( $export_file );
				die;
			}
		}

		/**
		 * Generate wallet details csv row data
		 *
		 * @param object $user User.
		 *
		 * @return array
		 */
		public function wkwcwallet_generate_wallet_details_csv_row_data( $user ) {
			$user_data       = array();
			$user_id         = ! empty( $user->data->ID ) ? $user->data->ID : '';
			$user_data['ID'] = $user_id;
			$state_code      = get_user_meta( $user_id, 'billing_state', true );
			$country_code    = get_user_meta( $user_id, 'billing_country', true );
			$tr_helper       = WKWC_Wallet_Transactions_Helper::get_instance();

			$state   = isset( WC()->countries->states[ $country_code ][ $state_code ] ) ? WC()->countries->states[ $country_code ][ $state_code ] : $state_code;
			$country = isset( WC()->countries->countries[ $country_code ] ) ? WC()->countries->countries[ $country_code ] : $country_code;

			$customer_location = '';

			if ( $state ) {
				$customer_location .= $state . ', ';
			}

			$customer_location .= $country;
			$customer_location  = ! empty( $customer_location ) ? '"' . html_entity_decode( str_replace( '"', '""', $customer_location ) ) . '"' : '';

			$user_data['username'] = $user->data->user_login;
			$user_data['email']    = $user->data->user_email;
			$user_data['location'] = $customer_location;
			$user_data['orders']   = wc_get_customer_order_count( $user_id );

			$user_data['wallet_amount'] = html_entity_decode( get_woocommerce_currency_symbol() ) . $tr_helper->wkwc_wallet_get_amount( $user_id );

			$user_data = implode( ',', $user_data );

			return $user_data;
		}

		/**
		 * Process Exporting wallet transaction details
		 *
		 * @param  mixed $transactions transaction.
		 * @return void
		 */
		public function wkwcwallet_process_exporting_wallet_transaction_details( $transactions ) {
			$row_data = array();

			if ( ! empty( $transactions ) ) {
				$row_data[] = 'transaction_id,order_id,reference,sender_name,sender_email,customer_name,customer_email,amount,transaction_type,date';

				foreach ( $transactions as $transaction ) {
					$row_data[] = $this->wkwcwallet_generate_wallet_transaction_details_csv_row_data( $transaction );
				}

				$row_data = implode( "\n", $row_data );

				header( 'Content-Type: text/csv; charset=UTF-8 BOM' );
				header( 'Content-Disposition: attachment; filename="transaction_details.csv";' );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );

				$export_file = fopen( 'php://output', 'w' );

				ob_end_clean();
				fwrite( $export_file, $row_data );
				fclose( $export_file );
				die;
			}
		}

		/**
		 * Generate wallet transaction details csv row data
		 *
		 * @param  mixed $transaction transaction.
		 * @return array
		 */
		public function wkwcwallet_generate_wallet_transaction_details_csv_row_data( $transaction ) {
			$transaction_data = array();

			$transaction_data['transaction_id'] = $transaction['id'];
			$transaction_data['order_id']       = ! empty( $transaction['order_id'] ) ? $transaction['order_id'] : '-';

			$currency_symbol = ! empty( $transaction['order_id'] ) ? get_woocommerce_currency_symbol( get_post_meta( $transaction['order_id'], '_order_currency', true ) ) : get_woocommerce_currency_symbol();

			$customer_id                          = ! empty( $transaction['customer'] ) ? $transaction['customer'] : $transaction['sender'];
			$transaction_data['reference']        = $transaction['reference'];
			$transaction_data['sender_name']      = ! empty( $transaction['sender'] ) && ! empty( $transaction['customer'] ) ? get_userdata( $transaction['sender'] )->user_login : '-';
			$transaction_data['sender_email']     = ! empty( $transaction['sender'] ) && ! empty( $transaction['customer'] ) ? get_userdata( $transaction['sender'] )->user_email : '-';
			$transaction_data['customer_name']    = ! empty( $transaction['customer'] ) ? get_userdata( $transaction['customer'] )->user_login : get_userdata( $transaction['sender'] )->user_login;
			$transaction_data['customer_email']   = ! empty( $transaction['customer'] ) ? get_userdata( $transaction['customer'] )->user_email : get_userdata( $transaction['sender'] )->user_email;
			$transaction_data['amount']           = html_entity_decode( $currency_symbol ) . $transaction['amount'];
			$transaction_data['transaction_type'] = ucfirst( $transaction['transaction_type'] );
			$transaction_data['date']             = '"' . gmdate( 'M d, Y g:i:s A', strtotime( $transaction['transaction_date'] ) ) . '"';

			$transaction_data = implode( ',', $transaction_data );

			return $transaction_data;
		}
	}
}
