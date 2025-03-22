<?php
/**
 * Wallet Functions.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Functions' ) ) {
	/**
	 * Wallet Front functions class.
	 */
	class WKWC_Wallet_Functions {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Front Functions Construct.
		 *
		 * @return void
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
		 * Front wallet order completed.
		 *
		 * @param int $order_id Order Id.
		 */
		public function wkwc_wallet_update_after_order_completed( $order_id ) {
			$wallet_updated = get_post_meta( $order_id, '_wkwc_wallet_credited', true );

			WKWC_Wallet::log( "Order id: $order_id, Wallet updated: $wallet_updated" );

			if ( ! empty( $wallet_updated ) ) {
				return true;
			}

			$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();
			$order     = wc_get_order( $order_id );
			$wallet    = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
			$wallet_id = ( $wallet instanceof \WP_Post ) ? $wallet->ID : 0;

			WKWC_Wallet::log( "Wallet id: $wallet_id" );

			if ( $order_id > 0 && $order instanceof \WC_Order && $wallet_id > 0 ) {
				foreach ( $order->get_items() as $item ) {

					if ( intval( $item->get_product_id() ) === intval( $wallet_id ) ) {
						$note  = esc_html__( 'Order No.', 'wp-wallet-system' ) . ' : ' . esc_html( $order_id ) . "\n";
						$note .= esc_html__( 'Wallet Transaction', 'wp-wallet-system' ) . ' : ' . html_entity_decode( get_woocommerce_currency_symbol() ) . wc_format_decimal( $order->get_total(), 2 ) . "\n";

						$transaction_data = array(
							'order_id'           => $order_id,
							'reference'          => esc_html__( 'Wallet Credit', 'wp-wallet-system' ),
							'sender'             => WKWC_Wallet::wkwc_wallet_get_first_admin_user_id(),
							'customer'           => $order->get_customer_id(),
							'amount'             => floatval( $order->get_total() ),
							'transaction_type'   => 'credit',
							'transaction_status' => 'recharged',
							'transaction_note'   => $note,
						);
						$tr_helper->create_transaction( $transaction_data );
					}
				}
			}

			update_post_meta( $order_id, '_wkwc_wallet_credited', $order_id );
		}

		/**
		 * Handles checkout wallet partial pay option.
		 *
		 * @hooked 'woocommerce_review_order_before_submit' Action hook.
		 */
		public function wkwc_wallet_wallet_payment() {
			$user_id = get_current_user_ID();
			if ( $user_id > 0 ) {
				$tr_helper    = WKWC_Wallet_Transactions_Helper::get_instance();
				$wallet_money = $tr_helper->wkwc_wallet_get_amount( $user_id );
				$gateways     = WC()->payment_gateways->payment_gateways();

				$show_wallet_method     = true;
				$wallet_gateway_enabled = false;
				$virtual_allowed        = false;
				$max_debit              = 0;
				$gateway_title          = __( 'Pay via Wallet', 'wp-wallet-system' );
				$gateway_desc           = __( '', 'wp-wallet-system' );

				foreach ( $gateways as $id => $gateway ) {
					if ( 'wkwc_wallet' === $id && 'yes' === $gateway->enabled ) {
						$wallet_gateway_enabled = true;
						$gateway_title          = $gateway->get_option( 'title', $gateway_title );
						$gateway_desc           = $gateway->get_option( 'description', $gateway_desc );
						$max_debit              = $gateway->get_option( 'max_debit', 0 );
						$virtual_allowed        = wc_string_to_bool( $gateway->get_option( 'enable_for_virtual', $virtual_allowed ) );
						break;
					}
				}

				$wallet         = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
				$wallet_prod_id = ( $wallet instanceof \WP_Post ) ? $wallet->ID : 0;

				foreach ( WC()->cart->get_cart() as $cart_item ) {

					if ( ( ! $virtual_allowed && $cart_item['data']->is_virtual() ) || $cart_item['data']->get_id() === $wallet_prod_id ) {
						$show_wallet_method = false;
						break;
					}
				}

				if ( $show_wallet_method && $wallet_gateway_enabled && $wallet_money > 0 && floatval( $max_debit ) > 0 ) {
					$session_value = WC()->session->get( 'wkwc_wallet_allowed_wallet_amount' );
					echo '<div class="wkwc_wallet_checkout_wrapper">';
					woocommerce_form_field(
						'wkwc_wallet-checkout-payment',
						array(
							'type'  => 'checkbox',
							'id'    => 'wkwc_wallet-checkout-payment',
							'label' => $gateway_title . ' <span class="wallet-money-style-small">' . wp_kses_post( wc_price( $wallet_money ) ) . '</span>.',
						),
						! empty( $session_value )
					);
					echo '<img class="wp-spin wkwc_wallet-spin-loader" style="display: none;" src="' . esc_url( admin_url( '/images/spinner.gif' ) ) . '">';
					echo '</div>';
					echo '<p>' . esc_html( $gateway_desc ) . '</p>';
					do_action('wkwc_after_wallet_checkbox');
					echo '<div style="display:none;" class="wkwc_wallet-otp-wrap">';

					woocommerce_form_field(
						'wkwc_wallet_checkout_otp',
						array(
							'type'        => 'password',
							'placeholder' => __( 'Enter the OTP.', 'wp-wallet-system' ),
						)
					);
					echo '<input class="wkwc_wallet-verify-otp-button" id="wkwc_wallet_verify_otp" type="button" value="' . esc_attr__( 'Verify', 'wp-wallet-system' ) . '">';
					echo '</div>';
					echo '<div class="wkwc_wallet-otp-msg-wrap wkwc-wallet-hide"><p class="wkwc_wallet-otp-msg wkwc_wallet-error">' . esc_html__( 'Invalid OTP', 'wp-wallet-system' ) . '</p></div>';
				}
			}
		}

		/**
		 * Add to cart fee function.
		 *
		 * @hooked 'woocommerce_cart_calculate_fees' Action hook.
		 */
		public function wkwc_wallet_add_cart_fee() {
			if ( is_checkout() ) {
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return;
				}

				$user_id       = get_current_user_id();
				$tr_helper     = WKWC_Wallet_Transactions_Helper::get_instance();
				$wallet_amount = $tr_helper->wkwc_wallet_get_amount( $user_id );

				$allowed_amount = WC()->session->get( 'wkwc_wallet_allowed_wallet_amount' );
				$allowed_amount = is_null( $allowed_amount ) ? 0 : floatval( $allowed_amount );

				if ( ! empty( $wallet_amount ) && $allowed_amount > 0 ) {
					$amount = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() + WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();

					if ( $amount > $allowed_amount ) {
						$extra_cost = ( - 1 ) * $allowed_amount;

						$fee = array(
							'id'        => 'wkwc_wallet_wallet_fee',
							'name'      => esc_html__( 'Payment via Wallet', 'wp-wallet-system' ),
							'amount'    => $extra_cost,
							'taxable'   => false,
							'tax_class' => '',
						);

						WC()->cart->fees_api()->add_fee( $fee );
					}
				}
			}
		}

		/**
		 * This function handles checkout payment gateways.
		 *
		 * @param array $available_gateways All available gateways.
		 *
		 * @hooked 'woocommerce_available_payment_gateways' filter hook.
		 */
		public function wkwc_wallet_payment_gateway_handler( $available_gateways ) {
			$cart_total = 0;

			if ( is_user_logged_in() && ! is_admin() ) {
				$allowed_amount = null;

				$get_cart = WC()->cart;

				if ( ! empty( $get_cart ) ) {
					$wallet_product = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
					$wallet_prod_id = ( $wallet_product instanceof \WP_Post ) ? $wallet_product->ID : 0;
					$wallet_in_cart = in_array( $wallet_prod_id, array_column( WC()->cart->get_cart(), 'product_id' ), true );

					if ( $wallet_in_cart ) {
						unset( $available_gateways['wkwc_wallet'] );
						unset( $available_gateways['cod'] );
						unset( $available_gateways['cheque'] );
						return $available_gateways;
					}
				}

				if ( ! is_null( WC()->session ) && WC()->session->has_session() ) {
					$allowed_amount = WC()->session->get( 'wkwc_wallet_allowed_wallet_amount' );
				}

				if ( is_null( $allowed_amount ) ) {
					$is_full_payment = false;
					if ( ! is_null( WC()->session ) && WC()->session->has_session() ) {
						$is_full_payment = WC()->session->get( 'wkwc_wallet_is_full_payment', $is_full_payment );
					}

					if ( ! $is_full_payment ) {
						unset( $available_gateways['wkwc_wallet'] );
					} else {
						WC()->session->__unset( 'wkwc_wallet_is_full_payment' );
					}

					return $available_gateways;
				}

				$cart_total     = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() + WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
				$allowed_amount = is_null( $allowed_amount ) ? 0 : floatval( $allowed_amount );

				$user_id = get_current_user_ID();

				$tr_helper     = WKWC_Wallet_Transactions_Helper::get_instance();
				$wallet_amount = $tr_helper->wkwc_wallet_get_amount( $user_id );

				$wallet_setting = get_option( 'woocommerce_wkwc_wallet_settings', array() );

				$max_debit      = empty( $wallet_setting['max_debit'] ) ? 0 : floatval( $wallet_setting['max_debit'] );
				$max_debit_type = empty( $wallet_setting['max_debit_type'] ) ? 0 : intval( $wallet_setting['max_debit_type'] );

				if ( ! empty( $max_debit ) ) {
					if ( empty( $max_debit_type ) && $wallet_amount > $max_debit && $max_debit < $cart_total ) {
						unset( $available_gateways['wkwc_wallet'] );

						return $available_gateways;
					}

					if ( $max_debit_type > 0 && $max_debit < 100 ) {
						unset( $available_gateways['wkwc_wallet'] );

						return $available_gateways;
					}
				}

				if ( $allowed_amount > 0 && $wallet_amount >= $cart_total && $allowed_amount >= $cart_total ) {
					foreach ( array_keys( $available_gateways ) as $gateway_id ) {
						if ( 'wkwc_wallet' !== $gateway_id ) {
							unset( $available_gateways[ $gateway_id ] );
						}
					}
					WC()->session->set( 'wkwc_wallet_is_full_payment', true );
				} else {
					unset( $available_gateways['wkwc_wallet'] );
				}
			} else {
				unset( $available_gateways['wkwc_wallet'] );
			}

			return $available_gateways;
		}

		/**
		 * Wallet order processing.
		 *
		 * @param int $order_id Order Id.
		 *
		 * @hooked 'woocommerce_checkout_order_processed' Action hook.
		 */
		public function wkwc_wallet_order_processing( $order_id ) {
			$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

			if ( ! empty( WC()->session->get( 'wkwc_wallet_allowed_wallet_amount' ) ) ) {
				WC()->session->__unset( 'wkwc_wallet_allowed_wallet_amount', null );
				WC()->session->__unset( 'wkwc_wallet_cart_price', null );
			}

			$order            = wc_get_order( $order_id );
			$transaction_data = array(
				'order_id'           => $order_id,
				'sender'             => WKWC_Wallet::wkwc_wallet_get_first_admin_user_id(),
				'customer'           => $order->get_customer_id(),
				'transaction_type'   => 'debit',
				'transaction_date'   => gmdate( 'Y-m-d H:i:s' ),
				'transaction_status' => 'wallet_used',
			);

			$note = esc_html__( 'Order No.', 'wp-wallet-system' ) . ' : ' . esc_html( $order_id ) . "\n";

			$payment_method = $order->get_payment_method();

			if ( 'wkwc_wallet' === $payment_method ) {
				$order_wallet_amount = $order->get_total();

				$note .= esc_html__( 'Wallet Transaction', 'wp-wallet-system' ) . ' : ' . html_entity_decode( get_woocommerce_currency_symbol() ) . wc_format_decimal( $order->get_total(), 2 ) . "\n";

				$transaction_data['reference']        = esc_html__( 'Wallet full checkout', 'wp-wallet-system' );
				$transaction_data['amount']           = floatval( $order_wallet_amount );
				$transaction_data['transaction_note'] = $note;
			}
			WKWC_Wallet::log( "Order id: $order_id, Checkout order processing, transaction data: " . print_r( $transaction_data, true ) );

			if ( empty( $transaction_data['transaction_note'] ) ) {
				foreach ( $order->get_fees() as $value ) {

					if ( __( 'Payment via Wallet', 'wp-wallet-system' ) === (string) $value->get_data()['name'] ) {
						$fees                = $value->get_data()['total'];
						$order_wallet_amount = - ( $fees );

						$note .= esc_html__( 'Wallet Transaction', 'wp-wallet-system' ) . ' : ' . html_entity_decode( get_woocommerce_currency_symbol() ) . wc_format_decimal( $fees, 2 ) . "\n";

						$transaction_data['reference']        = esc_html__( 'Wallet partial checkout', 'wp-wallet-system' );
						$transaction_data['amount']           = floatval( $order_wallet_amount );
						$transaction_data['transaction_note'] = $note;
					}
				}
			}

			WKWC_Wallet::log( "Order id: $order_id, Checkout order processing, transaction data: " . print_r( $transaction_data, true ) );

			if ( ! empty( $transaction_data['transaction_note'] ) ) {
				$tr_helper->create_transaction( $transaction_data );
			}
		}

		/**
		 * Adds a signup bonus to the user's wallet upon registration.
		 *
		 * @param int $user_id The ID of the newly registered user.
		 * @return bool True on success, false on failure.
		 */
		public function wkwp_wallet_user_register_bonus( $user_id ) {
			global $wpdb;
		
			// Get the signup bonus amount from settings (default: 5.0)
			$bonus_amount = floatval( get_option( '_wkwp_wallet_bonus_amount', 5.0 ) );

			// Dynamically get an admin ID (fallback to 1 if no admin found)
			$admin_user = get_users(
				[
					'role'    => 'administrator',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => ['ID'],
				]
			);
		
			$admin_id = !empty( $admin_user ) ? $admin_user[0]->ID : 1;
		
			// Update the user's wallet balance and proceed only if successful
			if ( update_user_meta( $user_id, 'wkwc_wallet_amount', $bonus_amount ) ) {
		
				// Prepare transaction data
				$transaction_data = [
					'order_id'           => '', // No order ID for a signup bonus
					'reference'          => 'User sign-up bonus',
					'sender'             => $admin_id,
					'customer'           => $user_id,
					'amount'             => $bonus_amount,
					'transaction_type'   => 'credit',
					'transaction_date'   => current_time('mysql'),
					'transaction_status' => 'completed',
					'transaction_note'   => 'Signup bonus credited to wallet',
				];
		
				// Insert transaction into the wallet transactions table
				$table_name = $wpdb->prefix . 'wkwc_wallet_transactions';
				$inserted   = $wpdb->insert(
					$table_name,
					$transaction_data,
					[
						'%s', '%s', '%d', '%d',
						'%f', '%s', '%s', '%s', '%s',
					]
				);
		
				return (bool) $inserted;
			}
		
			return false;
		}

		/**
		 * Woocommerce cart update/change.
		 * 
		 * @hooked 'woocommerce_cart_updated' Action hook.
		 */
		// public function wkwc_wallet_update_on_cart_change() {
		// 	if ( ! empty( WC()->session->get( 'wkwc_wallet_allowed_wallet_amount' ) ) ) {
		// 		WC()->session->__unset( 'wkwc_wallet_allowed_wallet_amount' );
		// 		WC()->session->__unset( 'wkwc_wallet_cart_price' );
		// 	}
		// }
	}
}
