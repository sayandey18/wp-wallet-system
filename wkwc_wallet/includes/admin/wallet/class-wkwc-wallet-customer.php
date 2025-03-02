<?php
/**
 * Wallet customer list wrapper at admin end.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Customer' ) ) {
	/**
	 * WKWC_Wallet_Customer Class.
	 */
	class WKWC_Wallet_Customer {
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
		 * Show customer wallet table.
		 *
		 * @return void
		 */
		public function wkwc_show_customer_wallet_table() {
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Customer Wallet', 'wp-wallet-system' ); ?></h1>
				<a href='admin.php?page=wkwp_wallet&action=update' class='page-title-action'><?php esc_html_e( 'Manual Transaction', 'wp-wallet-system' ); ?></a>
				<form method="post" style="display: inline-block;"><input type="submit" name="wkwc_wallet_export_csv" class="page-title-action export_wallet_details_csv" value="<?php esc_attr_e( 'Export Wallet', 'wp-wallet-system' ); ?>">
					<?php
					wp_nonce_field( 'wkwc_wallet_cashback_product', 'wkwc_wallet_cashback_product_nonce' );
					?>
				</form>
				<?php
				$posted_data = isset( $_POST ) ? wc_clean( $_POST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( isset( $posted_data['wkwc_wallet_cashback_product_nonce'] ) ) {
					$nonce = wp_unslash( $posted_data['wkwc_wallet_cashback_product_nonce'] );

					if ( wp_verify_nonce( $nonce, 'wkwc_wallet_cashback_product' ) ) {
						if ( isset( $_REQUEST['wkwc_wallet_export_csv'] ) ) {
							$wallet_exporter = WKWC_Wallet_Exporter::get_instance();
							$wallet_exporter->wkwcwallet_process_exporting_wallet_details();
						}
					}
				}

				$transaction = filter_input( INPUT_GET, 'transaction', FILTER_SANITIZE_STRING );

				if ( in_array( $transaction, array( 'debit', 'credit' ), true ) ) {
					$msg = esc_html__( 'Amount is debited successfully.', 'wp-wallet-system' );

					if ( 'credit' === $transaction ) {
						$msg = esc_html__( 'Amount is credited successfully.', 'wp-wallet-system' );
					}
					?>
						<div class='notice notice-success is-dismissible'>
							<p><?php echo esc_html( $msg ); ?></p>
						</div>
						<?php
				}
				$page_name = isset( $_REQUEST['page'] ) ? wc_clean( $_REQUEST['page'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				?>
				<div id='notify-field'></div>
				<form method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $page_name ); ?>" />
				<?php
					$search = empty( $_GET['s'] ) ? '' : htmlspecialchars( wp_unslash( wc_clean( $_GET['s'] ) ) ); // wpcs: input var okay. wpcs: csrf okay.

					$wallet_obj = WKWC_Wallet_Table::get_instance();
					$wallet_obj->prepare_items( $search );
					$wallet_obj->display();
				?>
				</form>
			</div>
			<?php
		}
	}
}
