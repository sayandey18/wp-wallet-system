<?php
/**
 * This Customer wallet transactions helper.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWC_Wallet_Transactions_Helper' ) ) {
	/**
	 * WKWC_Wallet_Transactions_Helper Class.
	 */
	class WKWC_Wallet_Transactions_Helper {
		/**
		 * Instance variable.
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * DB Variable.
		 *
		 * @var object
		 */
		protected $wpdb;

		/**
		 * Constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
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
		 * Generate Transaction.
		 *
		 * @param array $data Transaction Data.
		 */
		public function create_transaction( $data ) {
			$wpdb_obj   = $this->wpdb;
			$table_name = $wpdb_obj->prefix . 'wkwc_wallet_transactions';

			$default_data = array(
				'order_id'           => '',
				'reference'          => '',
				'sender'             => '',
				'customer'           => 0,
				'amount'             => 0,
				'transaction_type'   => '',
				'transaction_date'   => gmdate( 'Y-m-d H:i:s' ),
				'transaction_status' => '',
				'transaction_note'   => '',
			);

			$data     = wp_parse_args( $data, $default_data );
			$response = $wpdb_obj->insert( $table_name, $data );

			$this->wkwc_wallet_update_amount( $data );

			if ( class_exists( 'WK_Caching_Core' ) ) {
				$reset = $this->maybe_reset_cache( $data );

				WKWC_Wallet::log( 'create_transaction cache reset has been done for these keys: ' . print_r( $reset, true ) );
			}

			return $response;
		}

		/**
		 * Get Transaction data.
		 *
		 * @param array $args Transaction Data query params.
		 *
		 * @return array
		 */
		public function get_transactions( $args ) {
			$wpdb_obj = $this->wpdb;

			$cache_group = empty( $args['cache_group'] ) ? 0 : $args['cache_group'];
			$cache_key   = empty( $args['cache_key'] ) ? '' : $args['cache_key'];

			if ( class_exists( 'WK_Caching_Core' ) && ! empty( $cache_key ) ) {
				$cache_obj = \WK_Caching_Core::get_instance();
				$result    = $cache_obj->get( $cache_key, $cache_group );

				if ( ! empty( $result ) ) {
					WKWC_Wallet::log( "Get get_transactions Cached group: $cache_group, Cached key: $cache_key" );
					return $result;
				}
			}

			$sql = 'SELECT ';

			$sql .= empty( $args['fields'] ) ? '* ' : $args['fields'];
			$sql .= " FROM {$wpdb_obj->prefix}wkwc_wallet_transactions WHERE 1=1";

			if ( ! empty( $args['transaction_id'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND `id`=%d', esc_sql( $args['transaction_id'] ) );
			}

			if ( ! empty( $args['customer'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND (`customer`=%d || `sender`=%d)', esc_sql( $args['customer'] ), esc_sql( $args['customer'] ) );
			}

			if ( ! empty( $args['transaction_from_date'] ) && ! empty( $args['transaction_to_date'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND DATE(transaction_date) BETWEEN %s and %s', esc_sql( $args['transaction_from_date'] ), esc_sql( $args['transaction_to_date'] ) );
			}

			if ( ! empty( $args['transaction_type'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND `transaction_type` = %s', esc_sql( $args['transaction_type'] ) );
			}

			$orderby = empty( $args['orderby'] ) ? 'id' : $args['orderby']; // If no sort, default to date.
			$order   = empty( $args['order'] ) ? 'desc' : $args['order']; // If no order, default to asc.

			$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );
			$sql        .= " ORDER BY {$orderby_sql}";

			if ( ! empty( $args['limit'] ) ) {
				$offset = empty( $args['offset'] ) ? 0 : intval( $args['offset'] );
				$sql   .= $wpdb_obj->prepare( ' LIMIT %d, %d', esc_sql( $offset ), esc_sql( $args['limit'] ) );
			}

			$result = $wpdb_obj->get_results( $sql, 'ARRAY_A' );

			if ( class_exists( 'WK_Caching_Core' ) && ! empty( $cache_key ) ) {
				$cache_obj = \WK_Caching_Core::get_instance();
				$cache_obj->set( $cache_key, $result, $cache_group );
				WKWC_Wallet::log( "Set get_transactions Cached group: $cache_group, Cached key: $cache_key" );
			}

			return $result;
		}

		/**
		 * Delete Transactions data.
		 *
		 * @param array $tr_ids Transaction ids.
		 *
		 * @return bool
		 */
		public function wkwc_wallet_delete_transactions( $tr_ids ) {
			$wpdb_obj = $this->wpdb;

			$ids = is_array( $tr_ids ) ? implode( ',', $tr_ids ) : '';

			if ( class_exists( 'WK_Caching_Core' ) ) {
				$reset = $this->maybe_reset_transactions_cache( $tr_ids );

				WKWC_Wallet::log( 'wkwc_wallet_delete_transactions cache reset has been done for these Transactions ids: ' . print_r( $reset, true ) );
			}

			$result = $wpdb_obj->query( "DELETE FROM {$wpdb_obj->prefix}wkwc_wallet_transactions WHERE `id` IN ( $ids )" );

			return( $result > 0 );
		}

		/**
		 * Update wallet amount
		 *
		 * @param array $wallet_data Wallet transaction data.
		 *
		 * @return bool
		 */
		public function wkwc_wallet_update_amount( $wallet_data = array() ) {
			$sender_id   = empty( $wallet_data['sender'] ) ? 0 : intval( $wallet_data['sender'] );
			$receiver_id = empty( $wallet_data['customer'] ) ? 0 : intval( $wallet_data['customer'] );
			$amount      = empty( $wallet_data['amount'] ) ? 0 : floatval( $wallet_data['amount'] );
			$type        = empty( $wallet_data['transaction_type'] ) ? '' : $wallet_data['transaction_type'];

			WKWC_Wallet::log( "Sender User Id: $sender_id, Receiver id: $receiver_id, Wallet amount: $amount, type: $type " );

			if ( in_array( $type, array( 'credit', 'debit', 'transfer', 'refund' ), true ) ) {
				$old_amount = $this->wkwc_wallet_get_amount( $receiver_id );

				$new_amount = ( 'debit' === $type ) ? $old_amount - $amount : $old_amount + $amount;
				update_user_meta( $receiver_id, 'wkwc_wallet_amount', $new_amount );
			}

			$sender_user = get_user_by( 'id', $sender_id );

			if ( 'transfer' === $type && $sender_user instanceof \WP_User && ! in_array( 'administrator', $sender_user->roles, true ) ) {
				$old_amount = $this->wkwc_wallet_get_amount( $sender_id );

				$new_amount = $old_amount - $amount;
				update_user_meta( $sender_id, 'wkwc_wallet_amount', $new_amount );
			}

			do_action( 'wkwc_wallet_amount_updated', $wallet_data );

			return true;
		}

		/**
		 * Get wallet amount
		 *
		 * @param int $user_id User Id.
		 *
		 * @return float
		 */
		public function wkwc_wallet_get_amount( $user_id ) {
			$amount = 0;
			if ( $user_id > 0 ) {
				$amount = get_user_meta( $user_id, 'wkwc_wallet_amount', true );
				$amount = empty( $amount ) ? 0 : floatval( $amount );
			}
			return $amount;
		}

		/**
		 * Maybe reset cache on deleting transactions.
		 *
		 * @param array $transaction_ids Transaction Ids.
		 *
		 * @return array
		 */
		public function maybe_reset_transactions_cache( $transaction_ids ) {
			$transaction_ids = is_array( $transaction_ids ) ? $transaction_ids : array();
			$reset           = array();

			foreach ( $transaction_ids as $transaction_id ) {
				$transaction = $this->get_transactions(
					array(
						'transaction_id' => $transaction_id,
						'cache_group'    => 'wkwc_wallet_transaction',
						'cache_key'      => 'transaction_id_' . $transaction_id,
					)
				);

				if ( ! empty( $transaction ) && is_iterable( $transaction ) ) {
					foreach ( $transaction as $key => $transaction_data ) {
						$reset[ $transaction_id . '_' . $key ] = $this->maybe_reset_cache( $transaction_data );
					}
				}
			}

			return $reset;
		}

		/**
		 * Maybe reset cache on creating new transactions.
		 *
		 * @param array $transaction_data Transaction data.
		 *
		 * @return array
		 */
		public function maybe_reset_cache( $transaction_data ) {
			$cache_obj    = \WK_Caching_Core::get_instance();
			$cache_groups = array( 'wkwc_wallet_transaction', 'wkwc_wallet_transactions' );

			$cache_keys = array( 'customer_', 'customer_id_', 'transaction_id_' );
			$reset      = array();

			WKWC_Wallet::log( 'maybe_reset_cache Reseting cache data for transaction data: ' . print_r( $transaction_data, true ) );

			foreach ( $transaction_data as $key => $value ) {
				if ( in_array( $key, array( 'sender', 'customer' ), true ) && ! empty( $value ) ) {
					foreach ( $cache_keys as $c_key ) {
						$cache_key = $c_key . intval( $value );
						foreach ( $cache_groups as $cache_group ) {
							$success                                  = $cache_obj->reset( $cache_key, $cache_group );
							$reset[ $cache_group . '_' . $cache_key ] = array( $cache_group, $success );
						}
					}
				}
			}

			return $reset;
		}
	}
}
