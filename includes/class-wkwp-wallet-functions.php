<?php
/**
 * Wallet Functions
 *
 * @package WKWP_WALLET
 *
 * @since 3.6
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWP_Wallet_Functions' ) ) {
	/**
	 * Admin functions class
	 */
	class WKWP_Wallet_Functions {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Admin Functions Construct.
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
		 * Add product meta box for non-variable products.
		 *
		 * @return void
		 */
		public function wkwp_wallet_add_product_metabox() {
			global $post;

			$wallet = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );

			$current_post_id   = empty( $post->ID ) ? 0 : $post->ID;
			$wallet_product_id = empty( $wallet->ID ) ? 0 : $wallet->ID;

			$show_metabox = ( ! empty( $current_post_id ) && $current_post_id !== $wallet_product_id );

			if ( $show_metabox ) {
				$product_obj  = wc_get_product( $current_post_id );
				$show_metabox = ( $product_obj instanceof \WC_Product ) ? in_array( $product_obj->get_type(), array( 'simple', 'grouped' ), true ) : false;
			}

			if ( is_admin() && $show_metabox ) {
				add_meta_box(
					'wkwp_wallet_cachback_product',
					__( 'Wallet Cashback', 'wp-wallet-system' ),
					array( $this, 'wkwp_wallet_cashback_on_product_callback' ),
					'product',
					'side',
					'high'
				);
			}
		}

		/**
		 * Add product meta box callback for non-variable products.
		 *
		 * @param \WP_Post $current_post Current post object.
		 *
		 * @return void
		 */
		public function wkwp_wallet_cashback_on_product_callback( $current_post ) {
			$current_post_id = ( $current_post instanceof \WP_Post ) ? $current_post->ID : 0;
			if ( $current_post_id > 0 ) {
				wp_nonce_field( 'wkwp_wallet_cashback_product', 'wkwp_wallet_cashback_product_nonce' );
				$restriction = get_post_meta( $current_post_id, '_cashback_type_restriction', true );
				?>
			<div class="wkwc-wallet-front-container wkwp-wallet-product-metabox-wrapper">
				<p><label for="product-meta-quantity"><?php esc_html_e( 'Quantity', 'wp-wallet-system' ); ?></label></p>
				<p><abbr title="<?php esc_attr_e( 'Product Quantity', 'wp-wallet-system' ); ?>"><input type="number" name="cashback_min_quantity_restriction" id="product-meta-quantity" value="<?php echo esc_attr( get_post_meta( $current_post_id, '_cashback_min_quantity_restriction', true ) ); ?>" /></abbr>
				<span class="tooltip-desc"><?php esc_html_e( 'Minimum Cart Quantity', 'wp-wallet-system' ); ?></span>
				</p>
				<p><label for="product-meta-cashback-type"><?php esc_html_e( 'Type', 'wp-wallet-system' ); ?></label></p>
				<p>
					<abbr title="Discount Type">
						<select id="product-meta-cashback-type" name="cashback_type_restriction">
							<option value="fixed" <?php selected( $restriction, 'fixed', true ); ?>><?php esc_html_e( 'Fixed', 'wp-wallet-system' ); ?></option>
							<option value="percentage" <?php selected( $restriction, 'percentage', true ); ?>><?php esc_html_e( 'Percentage', 'wp-wallet-system' ); ?></option>
						</select>
					</abbr>
					<span class="tooltip-desc"><?php esc_html_e( 'Cashback Based On Type', 'wp-wallet-system' ); ?></span>
				</p>
				<p><label id="product-meta-cashback-amount"><?php esc_html_e( 'Amount', 'wp-wallet-system' ); ?></label></p>
				<p><input type="number" name="cashback_amount_awarded" value="<?php echo esc_attr( get_post_meta( $current_post_id, '_cashback_amount_awarded', true ) ); ?>" />
				<span class="tooltip-desc"><?php esc_html_e( 'Cashback Amount', 'wp-wallet-system' ); ?></span>
				</p>
			</div>
				<?php
			}
		}

		/**
		 * Save non-variable product cashbox metabox setting.
		 *
		 * @param int $post_id Post id.
		 *
		 * @return void
		 */
		public function wkwp_wallet_save_product_metabox( $post_id ) {
			$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			$nonce = empty( $posted_data['wkwp_wallet_cashback_product_nonce'] ) ? '' : $posted_data['wkwp_wallet_cashback_product_nonce'];

			if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wkwp_wallet_cashback_product' ) ) {
				$type_restriction = empty( $posted_data['cashback_type_restriction'] ) ? '' : $posted_data['cashback_type_restriction'];
				$qty_restriction  = empty( $posted_data['cashback_min_quantity_restriction'] ) ? 'fixed' : $posted_data['cashback_min_quantity_restriction'];
				$amount           = empty( $posted_data['cashback_amount_awarded'] ) ? 0 : floatval( $posted_data['cashback_amount_awarded'] );

				$this->wkwp_wallet_save_product_cashback( $post_id, $type_restriction, $qty_restriction, $amount );
			}
		}

		/**
		 * Save non-variable product cashbox metabox setting.
		 *
		 * @param int    $post_id Post id.
		 * @param string $type_restriction Type restrictions.
		 * @param string $qty_restriction Quantity restrictions.
		 * @param float  $amount Cachback amount.
		 *
		 * @return void
		 */
		public function wkwp_wallet_save_product_cashback( $post_id, $type_restriction, $qty_restriction, $amount ) {
			$error = '';

			if ( empty( $amount ) ) {
				$error = __( 'Enter a valid numeric cashback amount greater than 0.', 'wp-wallet-system' );
			}

			if ( 'percentage' === $type_restriction && $amount > 100 ) {
				$error = __( 'Cashback cannot be greater than 100%', 'wp-wallet-system' );
			}

			if ( empty( $error ) && 'fixed' === $type_restriction ) {
				$price = get_post_meta( $post_id, '_price', true );

				if ( $amount > $price ) {
					$error = __( 'Cashback Amount cannot be greater than product price.', 'wp-wallet-system' );
				}
			}

			if ( ! empty( $error ) ) {
				WC_Admin_Meta_Boxes::add_error( $error );
				update_post_meta( $post_id, '_cashback_amount_awarded', '' );
			} else {
				update_post_meta( $post_id, '_cashback_amount_awarded', $amount );
				update_post_meta( $post_id, '_cashback_min_quantity_restriction', $qty_restriction );
				update_post_meta( $post_id, '_cashback_type_restriction', $type_restriction );
			}
		}

		/**
		 * Add product meta box callback for variable products.
		 *
		 * @param int    $loop Variation loop count.
		 * @param array  $variation_data Variation data.
		 * @param object $variation Variation object.
		 *
		 * @return void
		 */
		public function wkwp_wallet_variation_product_metabox( $loop, $variation_data, $variation ) {
			$variation_id = ( $variation instanceof \WP_Post ) ? $variation->ID : 0;

			if ( $variation_id > 0 ) {
				$variation_obj = wc_get_product( $variation_id );
				?>
		<div class="wkwc-wallet-front-container" id="show_if_variable_product">
			<strong><?php esc_html_e( 'Wallet Cashback:', 'wp-wallet-system' ); ?> </strong>
			<div class="wc-wallet-product-variation-metabox-wrapper">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'            => 'cashback_min_quantity_restriction[ ' . $loop . ' ]',
						'name'          => 'cashback_min_quantity_restriction[' . $loop . ']',
						'wrapper_class' => 'form-row',
						'type'          => 'number',
						'label'         => __( 'Quantity', 'wp-wallet-system' ),
						'desc_tip'      => 'true',
						'description'   => __( 'Minimum Cart Quantity', 'wp-wallet-system' ),
						'value'         => $variation_obj->get_meta( '_cashback_min_quantity_restriction', true ),
					)
				);

				woocommerce_wp_select(
					array(
						'id'            => "product-meta-cashback-type{$loop}",
						'name'          => "cashback_type_restriction[{$loop}]",
						'value'         => $variation_obj->get_meta( '_cashback_type_restriction', true ),
						'label'         => __( 'Type', 'wp-wallet-system' ),
						'options'       => array(
							'fixed'      => __( 'Fixed', 'wp-wallet-system' ),
							'percentage' => __( 'Percentage', 'wp-wallet-system' ),
						),
						'desc_tip'      => true,
						'description'   => __( 'Cashback Based On Type', 'wp-wallet-system' ),
						'wrapper_class' => 'form-row',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'            => 'cashback_amount_awarded[' . $loop . ']',
						'name'          => 'cashback_amount_awarded[' . $loop . ']',
						'wrapper_class' => 'form-row',
						'type'          => 'number',
						'label'         => __( 'Amount', 'wp-wallet-system' ),
						'desc_tip'      => 'true',
						'description'   => __( 'Cashback Amount', 'wp-wallet-system' ),
						'value'         => $variation_obj->get_meta( '_cashback_amount_awarded', true ),
					)
				);
				?>
			</div>
		</div>
				<?php
			}
		}

		/**
		 * Process wallet after order completed.
		 *
		 * @param int $variation_id Variation id.
		 * @param int $i Loop count.
		 *
		 * @return void
		 */
		public function wkwp_wallet_save_variation_product_metabox( $variation_id, $i ) {
			$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			$type_restrictions = empty( $posted_data['cashback_type_restriction'] ) ? array() : $posted_data['cashback_type_restriction'];
			$qty_restrictions  = empty( $posted_data['cashback_min_quantity_restriction'] ) ? array() : $posted_data['cashback_min_quantity_restriction'];
			$amounts           = empty( $posted_data['cashback_amount_awarded'] ) ? 0 : array_map( 'floatval', $posted_data['cashback_amount_awarded'] );

			$type_restriction = empty( $type_restrictions[ $i ] ) ? 'fixed' : $type_restrictions[ $i ];
			$qty_restriction  = empty( $qty_restrictions[ $i ] ) ? '' : $qty_restrictions[ $i ];
			$amount           = empty( $amounts[ $i ] ) ? 0 : $amounts[ $i ];

			$this->wkwp_wallet_save_product_cashback( $variation_id, $type_restriction, $qty_restriction, $amount );

		}

		/**
		 * Process wallet after order completed.
		 *
		 * @param int $order_id Order id.
		 *
		 * @return bool
		 */
		public function wkwp_wallet_after_order_completed( $order_id ) {
			$wc_order    = wc_get_order( $order_id );
			$customer_id = $wc_order->get_customer_id();

			WKWP_Wallet::log( "Order completed cachback for order id: $order_id to customer id: $customer_id" );

			if ( $customer_id < 1 ) {
				return false;
			}

			$order_total = (float) $wc_order->get_subtotal();

			$wallet            = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
			$wallet_product_id = empty( $wallet->ID ) ? 0 : intval( $wallet->ID );

			$wallet_in_order = false;

			$line_items = $wc_order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );

			foreach ( $line_items as $item ) {
				$product_id = $item->get_data()['product_id'];
				if ( intval( $product_id ) === $wallet_product_id ) {
					$wallet_in_order = true;
					break;
				}
			}

			$total_cashback = 0;
			$multi_cb       = get_option( '_wkwp_wallet_multi_cb', false );
			$cb_preference  = get_option( '_wkwp_wallet_preferred_cb', false );

			$log_data = array(
				'order_id'          => $order_id,
				'customer_id'       => $customer_id,
				'wallet_in_order'   => $wallet_in_order,
				'wallet_product_id' => $wallet_product_id,
				'order_total'       => $order_total,
				'multi_cb'          => $multi_cb,
				'cb_preference'     => $cb_preference,
			);

			if ( ! $wallet_in_order && ( wc_string_to_bool( $multi_cb ) || 'cart' === $cb_preference ) ) {
				$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();

				$args = array(
					'fields'       => 'rule_type, amount',
					'cashback_for' => 'cart',
					'rule_status'  => 'publish',
					'rule_price'   => $order_total,
				);

				$matched_rule = $rules_helper->get_rules( $args );

				$log_data['matched_rule_count'] = is_iterable( $matched_rule ) ? count( $matched_rule ) : $matched_rule;

				if ( ! empty( $matched_rule ) ) {
					$rule_type       = empty( $matched_rule['rule_type'] ) ? '' : $matched_rule['rule_type'];
					$cashback_amount = empty( $matched_rule['amount'] ) ? '' : floatval( $matched_rule['amount'] );

					$log_data['rule_type']     = $rule_type;
					$log_data['cart_cashback'] = $cashback_amount;

					if ( ! empty( $rule_type ) && ! empty( $cashback_amount ) && 'percent' === $rule_type ) {
						$cashback_amount                   = ( $cashback_amount * $order_total ) / 100;
						$log_data['cart_cashback_percent'] = $cashback_amount;
					}

					if ( $cashback_amount > 0 ) {

						$total_cashback += $cashback_amount;

						$log_data['total_cashback_cart'] = $total_cashback;

						$reference   = __( 'Cart cashback received on purchase.', 'wp-wallet-system' );
						$wallet_note = wp_sprintf( /* translators: %s: Order number. */ __( 'Order no: %s', 'wp-wallet-system' ), $order_id );

						$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

						$data = array(
							'order_id'           => $order_id,
							'transaction_type'   => 'credit',
							'amount'             => $cashback_amount,
							'sender'             => 1,
							'customer'           => $customer_id,
							'transaction_note'   => $wallet_note,
							'transaction_status' => 'cashback',
							'reference'          => $reference,
						);

						$tr_helper->create_transaction( $data );
					}
				}
			}

			if ( ! $wallet_in_order && ( wc_string_to_bool( $multi_cb ) || 'product' === $cb_preference ) ) {

				foreach ( $line_items as $key => $item ) {
					$product_id     = $item->get_data()['product_id'];
					$wc_product     = wc_get_product( $item->get_data()['product_id'] );
					$order_item_qty = $item->get_data()['quantity'];

					$log_data[ $key ]['product_id']     = $product_id;
					$log_data[ $key ]['order_item_qty'] = $order_item_qty;

					if ( $wc_product->is_type( 'variable' ) ) {
						$product_id                       = get_post_meta( $item->get_data()['variation_id'] );
						$log_data[ $key ]['variation_id'] = $product_id;
					}

					$cashback_qty                     = get_post_meta( $product_id, '_cashback_min_quantity_restriction', true );
					$log_data[ $key ]['cashback_qty'] = $cashback_qty;

					if ( intval( $cashback_qty ) <= intval( $order_item_qty ) ) {
						$cashback_type    = get_post_meta( $product_id, '_cashback_type_restriction', true );
						$cashback_awarded = get_post_meta( $product_id, '_cashback_amount_awarded', true );

						$cashback_awarded = empty( $cashback_awarded ) ? 0 : floatval( $cashback_awarded );

						$log_data[ $key ]['cashback_type']    = $cashback_type;
						$log_data[ $key ]['product_cashback'] = $cashback_awarded;

						if ( 'percentage' === $cashback_type ) {
							$cashback_awarded                            += ( $cashback_awarded * $order_total ) / 100;
							$log_data[ $key ]['product_cashback_percent'] = $cashback_awarded;
						}

						if ( $cashback_awarded > 0 ) {

							$total_cashback += $cashback_awarded;

							$log_data[ $key ]['total_cashback_product'] = $cashback_awarded;

							$reference   = __( 'Product cashback received on purchase.', 'wp-wallet-system' );
							$wallet_note = wp_sprintf( /* translators: %s: Order number, %s: Product title. */ __( 'Order no: %1$s, Product name: %2$s', 'wp-wallet-system' ), $order_id, get_the_title( $product_id ) );

							$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

							$data = array(
								'order_id'           => $order_id,
								'transaction_type'   => 'credit',
								'amount'             => $cashback_awarded,
								'sender'             => 1,
								'customer'           => $customer_id,
								'transaction_note'   => $wallet_note,
								'transaction_status' => 'cashback',
								'reference'          => $reference,
							);

							$tr_helper->create_transaction( $data );
						}
					}
				}
			}

			if ( $wallet_in_order ) {
				$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();

				$args = array(
					'fields'       => 'rule_type, amount',
					'cashback_for' => 'recharge',
					'rule_status'  => 'publish',
					'rule_price'   => $order_total,
				);

				$matched_rule = $rules_helper->get_rules( $args );

				$log_data['matched_rule_wallet_count'] = count( $matched_rule );

				if ( ! empty( $matched_rule ) ) {
					$rule_type       = empty( $matched_rule['rule_type'] ) ? '' : $matched_rule['rule_type'];
					$cashback_amount = empty( $matched_rule['amount'] ) ? '' : floatval( $matched_rule['amount'] );

					$log_data['recharge']['rule_type']       = $rule_type;
					$log_data['recharge']['cashback_amount'] = $cashback_amount;

					if ( ! empty( $rule_type ) && ! empty( $cashback_amount ) && 'percent' === $rule_type ) {
						$cashback_amount = ( $cashback_amount * $order_total ) / 100;

						$log_data['recharge']['cashback_amount_percent'] = $cashback_amount;
					}

					if ( $cashback_amount > 0 ) {

						$total_cashback += $cashback_amount;

						$log_data['recharge']['total_cashback'] = $total_cashback;

						$reference   = __( 'Recharge cashback received.', 'wp-wallet-system' );
						$wallet_note = wp_sprintf( /* translators: %s: Order number. */ __( 'Order no: %s', 'wp-wallet-system' ), $order_id );

						$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

						$data = array(
							'order_id'           => $order_id,
							'transaction_type'   => 'credit',
							'amount'             => $cashback_amount,
							'sender'             => 1,
							'customer'           => $customer_id,
							'transaction_note'   => $wallet_note,
							'transaction_status' => 'recharge_cashback',
							'reference'          => $reference,
						);

						$tr_helper->create_transaction( $data );
					}
				}
			}

			WKWP_Wallet::log( 'Order completed cachback: Log data: ' . print_r( $log_data, true ) );
		}
	}
}
