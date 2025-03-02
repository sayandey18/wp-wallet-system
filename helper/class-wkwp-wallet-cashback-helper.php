<?php
/**
 * The Customer wallet cashback helper.
 *
 * @package WKWP_WALLET
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WKWP_Wallet_Cashback_Helper' ) ) {
	/**
	 * WKWP_Wallet_Cashback_Helper Class.
	 */
	class WKWP_Wallet_Cashback_Helper {
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
		public function update_rule( $data ) {
			$wpdb_obj   = $this->wpdb;
			$table_name = $wpdb_obj->prefix . 'wkwp_wallet_cashback_rules';

			$default_data = array(
				'id'              => 0,
				'rule_type'       => '',
				'rule_price_from' => '',
				'rule_price_to'   => '',
				'amount'          => 0,
				'cashback_for'    => '',
				'rule_status'     => 'completed',
			);

			$data = wp_parse_args( $data, $default_data );

			if ( $data['id'] > 0 ) {
				return $wpdb_obj->update( $table_name, $data, array( 'id' => $data['id'] ) );
			}

			return $wpdb_obj->insert( $table_name, $data );
		}

		/**
		 * Get Cachback rules.
		 *
		 * @param array $args Transaction Data query params.
		 *
		 * @return array
		 */
		public function get_rules( $args ) {
			$wpdb_obj = $this->wpdb;

			$sql = 'SELECT ';

			if ( ! empty( $args['total_count'] ) ) {
				$sql .= ' COUNT(`id`) ';
			} else {
				$sql .= empty( $args['fields'] ) ? '* ' : $args['fields'];
			}

			$sql .= " FROM {$wpdb_obj->prefix}wkwp_wallet_cashback_rules WHERE 1=1";

			if ( ! empty( $args['id'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND `id`=%d', esc_sql( $args['id'] ) );
			}

			if ( ! empty( $args['rule_type'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND (`rule_type`=%s)', esc_sql( $args['rule_type'] ) );
			}

			if ( ! empty( $args['cashback_for'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND `cashback_for` = %s', esc_sql( $args['cashback_for'] ) );
			}

			if ( ! empty( $args['rule_status'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND `rule_status` = %s', esc_sql( $args['rule_status'] ) );
			}

			if ( ! empty( $args['id'] ) ) {
				return $wpdb_obj->get_row( $sql, 'ARRAY_A' );
			}

			if ( ! empty( $args['rule_price'] ) ) {
				$sql .= $wpdb_obj->prepare( ' AND %s BETWEEN `rule_price_from` AND `rule_price_to`', esc_sql( $args['rule_price'] ) );

				return $wpdb_obj->get_row( $sql, 'ARRAY_A' );
			}

			if ( empty( $args['total_count'] ) ) {
				$orderby = empty( $args['orderby'] ) ? 'id' : $args['orderby']; // If no sort, default to date.
				$order   = empty( $args['order'] ) ? 'desc' : $args['order']; // If no order, default to asc.

				$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

				$sql .= " ORDER BY {$orderby_sql}";
			}

			if ( ! empty( $args['limit'] ) ) {
				$offset = empty( $args['offset'] ) ? 0 : intval( $args['offset'] );
				$sql   .= $wpdb_obj->prepare( ' LIMIT %d, %d', esc_sql( $offset ), esc_sql( $args['limit'] ) );
			}

			if ( ! empty( $args['total_count'] ) ) {
				return $wpdb_obj->get_var( $sql );
			}

			return $wpdb_obj->get_results( $sql, 'ARRAY_A' );
		}

		/**
		 * Delete Cashback rules.
		 *
		 * @param array $rule_ids Rule Ids.
		 *
		 * @return bool
		 */
		public function delete_rules( $rule_ids ) {
			$wpdb_obj = $this->wpdb;

			$ids = is_array( $rule_ids ) ? implode( ',', $rule_ids ) : '';

			$result = $wpdb_obj->query( "DELETE FROM {$wpdb_obj->prefix}wkwp_wallet_cashback_rules WHERE `id` IN ( $ids )" );

			return( $result > 0 );
		}
	}
}
