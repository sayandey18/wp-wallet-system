<?php
/**
 * Creating wallet product in WooCommerce at admin end.
 *
 * @package WKWC_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Product' ) ) {
	/**
	 * WKWC_Wallet_Product class.
	 */
	class WKWC_Wallet_Product {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Constructor for the wallet class. Loads options and hooks in the init method.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'wkwc_wallet_product_creation' ) );
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
		 * ( Wallet ) a virtual product created on installation.
		 */
		public function wkwc_wallet_product_creation() {
			$wallet = get_page_by_path( 'wkwc_wallet', OBJECT, 'product' );

			if ( empty( $wallet->post_title ) ) {
				$post    = array(
					'post_author' => get_current_user_ID(),
					'post_status' => 'publish',
					'post_title'  => esc_html__( 'Wallet', 'wp-wallet-system' ),
					'post_name'   => 'wkwc_wallet',
					'post_type'   => 'product',
				);
				$post_id = wp_insert_post( $post );
				wp_set_object_terms( $post_id, 'simple', 'product_type' );

				update_post_meta( $post_id, '_regular_price', '100' );
				update_post_meta( $post_id, '_visibility', 'hidden' );
				update_post_meta( $post_id, '_sku', '' );
				update_post_meta( $post_id, '_virtual_product', 'yes' );
				update_post_meta( $post_id, '_price', '100' );
				update_post_meta( $post_id, '_manage_stock', 'no' );
				update_post_meta( $post_id, '_stock_status', 'instock' );
				update_post_meta( $post_id, 'total_sales', '0' );
				update_post_meta( $post_id, '_downloadable', 'no' );
				update_post_meta( $post_id, '_virtual', 'yes' );
				update_post_meta( $post_id, '_purchase_note', '' );
				update_post_meta( $post_id, '_featured', 'no' );
				update_post_meta( $post_id, '_weight', '' );
				update_post_meta( $post_id, '_length', '' );
				update_post_meta( $post_id, '_width', '' );
				update_post_meta( $post_id, '_height', '' );
				update_post_meta( $post_id, '_product_attributes', '' );
				update_post_meta( $post_id, '_sale_price', '' );
				update_post_meta( $post_id, '_sale_price_dates_from', '' );
				update_post_meta( $post_id, '_sale_price_dates_to', '' );
				update_post_meta( $post_id, '_sold_individually', 'yes' );
				update_post_meta( $post_id, '_manage_stock', 'no' );
				update_post_meta( $post_id, '_backorders', 'no' );
				update_post_meta( $post_id, '_stock', '' );
				update_post_meta( $post_id, '_upsell_ids', '' );
				update_post_meta( $post_id, '_crosssell_ids', '' );
				update_post_meta( $post_id, '_product_version', '2.6.11' );
				update_post_meta( $post_id, '_product_image_gallery', '' );
				update_post_meta( $post_id, '_tax_status', 'none' );
				update_post_meta( $post_id, '_stock', '' );

				$url        = WKWC_WALLET_SUBMODULE_URL . 'assets/images/wallet.png';
				$upload_dir = wp_upload_dir();
				$filename   = basename( $url );
				$filetype   = wp_check_filetype( basename( $filename ), null );

				if ( ! file_exists( $upload_dir['path'] . '/' . $filename ) ) {
					// Magic sideload image returns an HTML image, not an ID.
					media_sideload_image( $url, $post_id );
				}

				$upload_file    = $upload_dir['path'] . '/' . $filename;
				$parent_post_id = $post_id;

				$attachment_file = array(
					'guid'           => $upload_dir['path'] . '/' . $filename,
					'post_mime_type' => $filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attach_id = wp_insert_attachment( $attachment_file, $upload_file, $parent_post_id );

				set_post_thumbnail( $post_id, $attach_id );

				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['path'] . '/' . $filename );

				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
		}
	}
}
