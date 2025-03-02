<?php
/**
 * Front End Functions.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWC_Wallet_Front_Functions' ) ) {
	/**
	 * Front functions class
	 */
	class WKWC_Wallet_Front_Functions {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Front Functions Construct
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
		 * Public end scripts.
		 *
		 * @hooked wp_enqueue_scripts action hook.
		 *
		 * @return void
		 */
		public function wkwc_wallet_public_scripts() {
			$ajax_obj = array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( 'wkwc-wallet-nonce' ),
			);

			wp_enqueue_style( 'wkwc-wallet-front-style', WKWC_WALLET_SUBMODULE_URL . 'assets/wkwc-wallet-front.css', array( 'dashicons' ), WKWC_WALLET_SCRIPT_VERSION );
			wp_enqueue_script( 'wkwc-wallet-front-script', WKWC_WALLET_SUBMODULE_URL . 'assets/wkwc-wallet-front.js', array( 'jquery' ), WKWC_WALLET_SCRIPT_VERSION, true );

			wp_localize_script(
				'wkwc-wallet-front-script',
				'wkwc_wallet_obj',
				array(
					'ajax' => $ajax_obj,
				)
			);
		}

		/**
		 * Creating seller badge WC Endpoint.
		 *
		 * @return void
		 */
		public function wkwc_wallet_create_wallet_wc_endpoints() {
			add_rewrite_endpoint( 'wkwc_wallet', EP_ROOT | EP_PAGES );
			flush_rewrite_rules( false );
		}

		/**
		 * Wallet Endpoint Content.
		 *
		 * @return bool
		 */
		public function wkwc_wallet_endpoint_content() {
			global $wp_query;

			$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();
			$tr_obj    = WKWC_Wallet_Transaction::get_instance();
			$user_id   = get_current_user_ID();

			if ( false !== strpos( $wp_query->query_vars['wkwc_wallet'], 'view' ) ) {
				$transaction_id = preg_replace( '/[^0-9]/', '', $wp_query->query_vars['wkwc_wallet'] );

				if ( ! empty( $transaction_id ) ) {
					$tr_obj->wkwc_wallet_transaction_view( $transaction_id );
				}
			} else {
				if ( 'transfer' === $wp_query->query_vars['wkwc_wallet'] ) {
					$tr_obj->wkwc_wallet_transfer_amount( $user_id );
					return false;
				}

				if ( 'verification' === $wp_query->query_vars['wkwc_wallet'] ) {
					$tr_obj->wkwc_wallet_verify_transfer( $user_id );
					return false;
				}

				if ( isset( $_REQUEST['wkwc_wallet_add_money'] ) ) {
					$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( ! isset( $posted_data['add_wallet_money'] ) || ! wp_verify_nonce( $posted_data['wkwc_wallet_add_amount_nonce_action'], 'wkwc_wallet_add_amount_nonce' ) ) {
						wc_print_notice( esc_html__( 'Sorry, your nonce did not verify.', 'wp-wallet-system' ), 'error' );
					} else {
						$this->wkwc_wallet_add_wallet_product_to_cart( $posted_data );
						do_action( 'wkwc_wallet_add_money_action', $posted_data );
					}
				}

				$wallet_amount = $tr_helper->wkwc_wallet_get_amount( $user_id );
				$wallet_amount = empty( $wallet_amount ) ? 0 : $wallet_amount;

				$wc_paged = empty( get_query_var( 'wkwc_wallet' ) ) ? 1 : get_query_var( 'wkwc_wallet' );
				$wc_paged = is_numeric( $wc_paged ) ? $wc_paged : 1;

				$pagenum = isset( $wc_paged ) ? absint( $wc_paged ) : 1;
				$limit   = 10;
				$offset  = ( 1 === $pagenum ) ? 0 : ( $pagenum - 1 ) * $limit;

				$args = array(
					'customer'    => $user_id,
					'cache_group' => 'wkwc_wallet_transactions',
					'cache_key'   => 'customer_id_' . $user_id,
				);

				$all_transactions = $tr_helper->get_transactions( $args );

				$args['limit']     = $limit;
				$args['offset']    = $offset;
				$args['cache_key'] = 'customer_' . $user_id . '_' . $limit . '_' . $offset;

				$transactions = $tr_helper->get_transactions( $args );

				require_once WKWC_WALLET_SUBMODULE_PATH . 'templates/wkwc-wallet-front-listing.php';
			}
		}

		/**
		 * Front Wallet amount.
		 *
		 * @param array $data Data.
		 *
		 * @throws \Exception Throwing Exception.
		 */
		public function wkwc_wallet_add_wallet_product_to_cart( $data ) {
			if ( ! empty( $data ) ) {

				if ( ! empty( WC()->session->get( 'cart' ) ) ) {
					WC()->session->set( 'wkwc_wallet_cart_contents', WC()->session->get( 'cart' ) );
				}

				$wallet_setting = get_option( 'woocommerce_wkwc_wallet_settings', array() );
				$add_money      = isset( $data['add_wallet_money'] ) ? $data['add_wallet_money'] : 0;
				$min_credit     = isset( $wallet_setting['min_credit'] ) ? floatval( $wallet_setting['min_credit'] ) : 0;
				$max_credit     = isset( $wallet_setting['max_credit'] ) ? floatval( $wallet_setting['max_credit'] ) : 0;
				$max_amount     = isset( $wallet_setting['max_amount'] ) ? floatval( $wallet_setting['max_amount'] ) : 0;

				if ( empty( $add_money ) || ! is_numeric( $add_money ) ) {
					wc_print_notice( esc_html__( 'Enter a valid numeric amount to recharge your wallet.', 'wp-wallet-system' ), 'error' );
					return false;
				}

				if ( ! empty( $min_credit ) && $add_money < $min_credit ) {
					wc_print_notice( wp_sprintf( /* translators: %s: Minimum Credit amount. */ esc_html__( 'Minimum Wallet Credit Amount must be: %s', 'wp-wallet-system' ), wc_price( $min_credit ) ), 'error' );
					return false;
				}

				if ( ! empty( $max_credit ) && $add_money > $max_credit ) {
					wc_print_notice( wp_sprintf( /* translators: %s: Minimum Credit amount. */ esc_html__( 'Maximum Wallet Credit Amount limit is: %s', 'wp-wallet-system' ), wc_price( $max_credit ) ), 'error' );
					return false;
				}

				$wallet_id       = empty( $data['wallet_id'] ) ? 0 : intval( $data['wallet_id'] );
				$is_valid_wallet = false;

				if ( $wallet_id > 0 ) {
					$wallet         = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
					$wallet_prod_id = ( $wallet instanceof \WP_Post ) ? $wallet->ID : 0;

					$is_valid_wallet = ( $wallet_prod_id === $wallet_id );
				}

				if ( ! $is_valid_wallet ) {
					wc_print_notice( esc_html__( 'Wallet product is not valid.', 'wp-wallet-system' ), 'error' );
					return false;
				}

				$user_id = get_current_user_id();

				$tr_helper     = WKWC_Wallet_Transactions_Helper::get_instance();
				$wallet_amount = $tr_helper->wkwc_wallet_get_amount( $user_id );

				$after_add = floatval( $wallet_amount ) + floatval( $add_money );

				if ( $after_add > $max_amount ) {
					wc_print_notice( wp_sprintf( /* translators: %s: Minimum Credit amount. */ esc_html__( 'Maximum Wallet Amount could not be more than %s', 'wp-wallet-system' ), wc_price( $max_amount ) ), 'error' );
					return false;
				}

				WC()->cart->empty_cart();
				WC()->cart->add_to_cart( $wallet_id );

				$added_text = wp_sprintf( /* translators: %s: Wallet product title. */ __( '%s has been added to your cart.', 'wp-wallet-system' ), strip_tags( get_the_title( $wallet_id ) ) );
				$message    = wp_sprintf( /* translators: %s: Cart URL, %s: Cart button, %s: Added text. */ '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s', esc_url( wc_get_cart_url() ), esc_html__( 'View cart', 'wp-wallet-system' ), esc_html( $added_text ) );
				wc_print_notice( $message, apply_filters( 'woocommerce_add_to_cart_notice_type', 'success' ) );
			}
		}

		/**
		 * Update custom wallet price to cart.
		 *
		 * @param object $cart_object Cart object.
		 *
		 * @hooked 'woocommerce_before_calculate_totals' Action hooks.
		 *
		 * @return void
		 */
		public function wkwc_wallet_update_cart_price( $cart_object ) {
			$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			$wallet_money = empty( $posted_data['add_wallet_money'] ) ? 0 : floatval( $posted_data['add_wallet_money'] );

			if ( empty( $wallet_money ) ) {
				$wallet_money = WC()->session->get( 'wkwc_wallet_cart_price', false );
			}

			if ( $wallet_money > 0 ) {
				$wallet_product = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
				$wallet_prod_id = ( $wallet_product instanceof \WP_Post ) ? intval( $wallet_product->ID ) : 0;

				$cart_items = $cart_object->cart_contents;

				foreach ( $cart_items as $card_data ) {
					$cart_wallet_id = empty( $card_data['product_id'] ) ? 0 : intval( $card_data['product_id'] );

					if ( $cart_wallet_id === $wallet_prod_id ) {
						$card_data['data']->set_price( $wallet_money );
						WC()->session->set( 'wkwc_wallet_cart_price', $wallet_money );
						break;
					}
				}
			}
		}


		/**
		 * Front Wallet template redirect.
		 *
		 * @hooked 'template_redirect' filter hook.
		 */
		public function wkwc_wallet_template_redirect() {
			if ( is_shop() || ( get_post_type() === 'product' && is_single() ) ) {
				$get_cart = WC()->cart->cart_contents;

				if ( ! empty( $get_cart ) ) {
					$wallet_product = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );
					$wallet_id      = ( $wallet_product instanceof \WP_Post ) ? $wallet_product->ID : 0;

					foreach ( $get_cart as $value ) {
						$cart_product_id = empty( $value['product_id'] ) ? 0 : intval( $value['product_id'] );

						if ( intval( $wallet_id ) === $cart_product_id ) {
							wc_add_notice( esc_html__( 'Cannot add new product now. Either empty cart or process it first.', 'wp-wallet-system' ) );
						}
					}
				}
			}
		}

		/**
		 * Show current plugin version and last working day on front end.
		 *
		 * @hooked wp_footer Action hook.
		 *
		 * @return void
		 */
		public function wkwc_wallet_front_footer_info() {
			$show_info = filter_input( INPUT_GET, 'wkmodule_info', FILTER_SANITIZE_NUMBER_INT );
			$show_info = empty( $show_info ) ? 0 : intval( $show_info );
			if ( 200 === $show_info ) {
				?>
			<input type="hidden" data-lwd="2023-07-18-2pm" data-wkwc_wallet_version="<?php echo esc_attr( WKWC_WALLET_SCRIPT_VERSION ); ?>" data-wkwc_wallet_slug="wkwc_wallet">
				<?php
			}
		}
	}
}
