<?php
/**
 * Admin End Functions
 *
 * @package WKWP_WALLET
 *
 * @since 3.6
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

if ( ! class_exists( 'WKWP_Wallet_Admin_Functions' ) ) {
	/**
	 * Admin functions class
	 */
	class WKWP_Wallet_Admin_Functions {
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
		 * Adding enqueue scripts.
		 */
		public function wkwp_wallet_enqueue_admin_scripts() {
			wp_enqueue_style( 'wkwp_wallet_admin_script', WKWP_WALLET_PLUGIN_URL . 'assets/admin.css', array(), WKWP_WALLET_SCRIPT_VERSION, false );
		}

		/**
		 * Show setting links.
		 *
		 * @param array $links Setting links.
		 *
		 * @return array
		 */
		public function add_plugin_setting_links( $links ) {
			$links   = is_array( $links ) ? $links : array();
			$links[] = '<a href="' . esc_url( admin_url( '/admin.php?page=wkwp_wallet_settings' ) ) . '">' . esc_html__( 'Settings', 'wp-wallet-system' ) . '</a>';
			$links[] = '<a href="' . esc_url( admin_url( '/admin.php?page=wc-settings&tab=checkout&section=wkwc_wallet' ) ) . '">' . esc_html__( 'Gateway', 'wp-wallet-system' ) . '</a>';

			return $links;
		}

		/**
		 * Add admin menu.
		 */
		public function wkwp_wallet_create_admin_menu() {
			$capability = apply_filters( 'wkmp_dashboard_menu_capability', 'manage_options' );
			add_menu_page(
				esc_html__( 'Wallet System', 'wp-wallet-system' ),
				esc_html__( 'Wallet System', 'wp-wallet-system' ),
				$capability,
				'wkwp_wallet',
				null,
				'dashicons-portfolio',
				55
			);

			add_submenu_page(
				'wkwp_wallet',
				esc_html__( 'Wallet System', 'wp-wallet-system' ) . ' | ' . esc_html__( 'Wallet System', 'wp-wallet-system' ),
				esc_html__( 'Customer Wallet', 'wp-wallet-system' ),
				$capability,
				'wkwp_wallet',
				array(
					$this,
					'wkwp_wallet',
				)
			);

			add_submenu_page(
				'wkwp_wallet',
				esc_html__( 'Transactions', 'wp-wallet-system' ) . ' | ' . esc_html__( 'Wallet System', 'wp-wallet-system' ),
				esc_html__( 'Transactions', 'wp-wallet-system' ),
				$capability,
				'wkwp_wallet_transactions',
				array(
					$this,
					'wkwp_wallet_transactions',
				)
			);

			add_submenu_page(
				'wkwp_wallet',
				esc_html__( 'Cashback Rules', 'wp-wallet-system' ) . ' | ' . esc_html__( 'Wallet System', 'wp-wallet-system' ),
				esc_html__( 'Cashback Rules', 'wp-wallet-system' ),
				$capability,
				'wkwp_wallet_cb_rules',
				array(
					$this,
					'wkwp_wallet_cb_rules',
				)
			);

			add_submenu_page(
				'wkwp_wallet',
				esc_html__( 'Settings', 'wp-wallet-system' ) . ' | ' . esc_html__( 'Wallet System', 'wp-wallet-system' ),
				esc_html__( 'Settings', 'wp-wallet-system' ),
				$capability,
				'wkwp_wallet_settings',
				array(
					$this,
					'wkwp_wallet_settings',
				)
			);
		}

		/**
		 * Add admin menu.
		 *
		 * @return bool|void
		 */
		public function wkwp_wallet() {
			$action = empty( $_GET['action'] ) ? '' : htmlspecialchars( wp_unslash( wc_clean( $_GET['action'] ) ) ); // wpcs: input var okay. wpcs: csrf okay.

			if ( 'update' === $action ) {
				$update = WKWC_Wallet_Customer_Update::get_instance();
				$update->wkwc_show_wallet_update_form();

				return false;
			}

			$obj = WKWC_Wallet_Customer::get_instance();
			$obj->wkwc_show_customer_wallet_table();
		}

		/**
		 * Add wallet menu.
		 *
		 * @return void|bool
		 */
		public function wkwp_wallet_transactions() {
			$transaction_id = filter_input( INPUT_GET, 'transaction_id', FILTER_SANITIZE_NUMBER_INT );
			if ( ! empty( $transaction_id ) ) {
				$wc_transaction = WKWC_Wallet_Transaction::get_instance();
				$wc_transaction->wkwc_wallet_transaction_view( $transaction_id );
				return false;
			}

			$obj          = WKWC_Wallet_Transaction_List::get_instance();
			$request_data = isset( $_REQUEST ) ? wc_clean( $_REQUEST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$obj->prepare_items();
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Wallet Transaction List', 'wp-wallet-system' ); ?></h1>
				<form method="post" style="display: inline-block;">
					<input type="submit" name="export_wallet_transaction_details_csv" class="page-title-action export_wallet_transaction_details_csv" value="<?php esc_attr_e( 'Export Wallet Transaction Details', 'wp-wallet-system' ); ?>">
				</form>
				<form method = "get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $request_data['page'] ); ?>" />
				<?php $obj->display(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Add cashback menu.
		 *
		 * @return bool
		 */
		public function wkwp_wallet_cb_rules() {
			$get_data = empty( $_GET ) ? array() : wc_clean( $_GET );
			$action   = empty( $get_data['action'] ) ? '' : htmlspecialchars( $get_data['action'], ENT_QUOTES, 'UTF-8' );

			$cb_obj = WKWP_Wallet_Cashback_Rule::get_instance();

			if ( in_array( $action, array( 'add', 'edit' ), true ) ) {
				$rule_id = empty( $get_data['rule_id'] ) ? 0 : intval( $get_data['rule_id'] );
				$cb_obj->wkwp_wallet_edit_cashback_rule( $rule_id );
				return false;
			}

			$message = ( 'trash' === $action ) ? esc_html__( 'Rule(s) are deleted successfully.', 'wp-wallet-system' ) : '';

			$resp_code = filter_input( INPUT_GET, 'resp_code', FILTER_SANITIZE_NUMBER_INT );
			$message   = empty( $resp_code ) ? $message : $cb_obj->wkwp_get_msg_by_response_code( $resp_code );
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Cashback Rules', 'wp-wallet-system' ); ?></h1>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wkwp_wallet_cb_rules&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Rule', 'wp-wallet-system' ); ?></a>
				<?php
				if ( ! empty( $message ) ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
					<?php
				}

				$obj = WKWP_Wallet_Cashback_Rules::get_instance();
				$obj->prepare_items();
				$page_name = isset( $_REQUEST['page'] ) ? wc_clean( $_REQUEST['page'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				?>
				<form method="GET">
					<input type="hidden" name="page" value="<?php echo esc_attr( $page_name ); ?>" />
					<?php $obj->display(); ?>
				</form>
				</div>
			<?php
		}

		/**
		 * Setting Cashback Rule Handler.
		 */
		public function wkwp_wallet_add_update_cb_rule() {

			$post_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( isset( $post_data['_wkwp_wallet_nonce'] ) && wp_verify_nonce( $post_data['_wkwp_wallet_nonce'], 'wkwp_wallet_cb_rule_action' ) ) {

				$obj    = WKWP_Wallet_Cashback_Rule::get_instance();
				$result = $obj->wkwp_wallet_handle_cb_rule_form( $post_data );

				if ( empty( $result['success'] ) ) {
					$args = array(
						'page'            => 'wkwp_wallet_cb_rules',
						'action'          => 'add',
						'resp_code'       => $result['resp_code'],
						'rule_type'       => $post_data['rule_type'],
						'rule_id'         => $post_data['rule_id'],
						'rule_price_from' => $post_data['rule_price_from'],
						'rule_price_to'   => $post_data['rule_price_to'],
						'rule_annually'   => $post_data['rule_annually'],
						'amount'          => $post_data['amount'],
						'cashback_for'    => $post_data['cashback_for'],
						'rule_status'     => $post_data['rule_status'],
					);
					wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
					exit();
				}

				wp_safe_redirect( admin_url( 'admin.php?page=wkwp_wallet_cb_rules&resp_code=' . $result['resp_code'] ) );
				exit();
			}
		}

		/**
		 * Add settings menu.
		 *
		 * @return void
		 */
		public function wkwp_wallet_settings() {
			WKWC_Wallet_Settings::get_instance();
		}
	}
}
