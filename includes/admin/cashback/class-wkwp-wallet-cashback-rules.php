<?php
/**
 *
 * Cashback Rule list.
 *
 * @package WKWP_WALLET
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if Accessed Directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Cashback rule listing.
 */
class WKWP_Wallet_Cashback_Rules extends WP_List_Table {
	/**
	 * Instance variable.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'Rule', 'wp-wallet-system' ),
				'plural'   => esc_html__( 'Rules', 'wp-wallet-system' ),
				'ajax'     => false,
			)
		);
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
	 * Prepare Items for listing.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden   = $this->get_hidden_columns();

		$this->process_bulk_action();

		$screen       = get_current_screen();
		$per_page     = $this->get_items_per_page( 'cashback_rules_per_page', 10 );
		$current_page = $this->get_pagenum();

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$request_data = isset( $_REQUEST ) ? wc_clean( $_REQUEST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby      = empty( $request_data['orderby'] ) ? 'id' : $request_data['orderby'];
		$order        = empty( $request_data['order'] ) ? 'desc' : $request_data['order'];

		$args = array(
			'orderby'     => $orderby,
			'order'       => $order,
			'total_count' => true,
		);

		$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();
		$total_items  = $rules_helper->get_rules( $args );

		$offset = ( 1 === $current_page ) ? 0 : ( $current_page - 1 ) * $per_page;

		$args['offset']      = $offset;
		$args['limit']       = $per_page;
		$args['total_count'] = false;

		$data = $this->table_data( $args );

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			)
		);

		$this->items = $data;
	}

	/**
	 * Hidden Columns
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Checkbox Column.
	 *
	 * @param array $item List items.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" id="rule_%s" name="rule[]" value="%s" />', $item['id'], $item['id'] );
	}

	/**
	 * Get Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />', // Render a checkbox instead of text.
			'rule_name'       => __( 'Rule Name', 'wp-wallet-system' ),
			'rule_price_from' => __( 'Price From', 'wp-wallet-system' ),
			'rule_price_to'   => __( 'Price To', 'wp-wallet-system' ),
			'rule_type'       => __( 'Type', 'wp-wallet-system' ),
			'amount'          => __( 'Amount', 'wp-wallet-system' ),
			'cashback_for'    => __( 'Cashback For', 'wp-wallet-system' ),
			'rule_status'     => __( 'Status', 'wp-wallet-system' ),
		);

		return $columns;
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 *
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'rule_name'       => array( 'rule_name', true ),
			'rule_price_from' => array( 'rule_price_from', true ),
			'rule_price_to'   => array( 'rule_price_to', true ),
			'rule_type'       => array( 'rule_type', true ),
			'amount'          => array( 'amount', true ),
			'cashback_for'    => array( 'cashback_for', true ),
			'rule_status'     => array( 'rule_status', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Table data.
	 *
	 * @param array $args Data arguments.
	 *
	 * @return array
	 */
	private function table_data( $args = array() ) {
		$data         = array();
		$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();
		$rules        = $rules_helper->get_rules( $args );

		foreach ( $rules as $rule ) {
			$data[] = array(
				'id'              => $rule['id'],
				'rule_name'       => $rule['rule_name'],
				'rule_price_from' => $rule['rule_price_from'],
				'rule_price_to'   => $rule['rule_price_to'],
				'rule_type'       => ucfirst( $rule['rule_type'] ),
				'amount'          => ( 'percent' === $rule['rule_type'] ) ? $rule['amount'] . '%' : wc_price( $rule['amount'] ),
				'cashback_for'    => $rule['cashback_for'],
				'rule_status'     => $rule['rule_status'],
			);
		}
		return $data;
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'trash' => __( 'Delete', 'wp-wallet-system' ),
		);

		return $actions;
	}

	/**
	 * Process Bulk Actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( 'trash' === $this->current_action() ) {
			$get_data = empty( $_GET['rule'] ) ? array() : wc_clean( $_GET['rule'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$rule_ids = is_array( $get_data ) ? array_map( 'intval', $get_data ) : array( intval( $get_data ) );

			if ( ! empty( $get_data ) ) {
				$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();
				$rules_helper->delete_rules( $rule_ids );
			}
		}
	}

	/**
	 * Default Column.
	 *
	 * @param array  $item List Item.
	 * @param string $column_name Column Name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'rule_name':
			case 'rule_price_from':
			case 'rule_price_to':
			case 'rule_type':
			case 'amount':
			case 'cashback_for':
			case 'rule_status':
				return $item[ $column_name ];
			default:
				return '-';
		}
	}

	/**
	 * Column Rule Name.
	 *
	 * @param array $item List items.
	 */
	public function column_rule_name( $item ) {
		$edit_url = add_query_arg(
			array(
				'page'    => 'wkwp_wallet_cb_rules',
				'action'  => 'edit',
				'rule_id' => $item['id'],
			),
			admin_url( 'admin.php' )
		);

		$delete_url = add_query_arg(
			array(
				'page'   => 'wkwp_wallet_cb_rules',
				'action' => 'trash',
				'rule'   => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$actions    = array(
			'edit'  => sprintf( '<a href="%s">Edit</a>', $edit_url ),
			'trash' => sprintf( '<a href="%s">Delete</a>', $delete_url ),
		);

		return sprintf( '%1$s %2$s', $item['rule_name'], $this->row_actions( $actions ) );
	}
}
