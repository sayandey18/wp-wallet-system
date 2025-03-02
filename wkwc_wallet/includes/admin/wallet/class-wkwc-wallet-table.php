<?php
/**
 * This Customer wallet table on admin end.
 *
 * @package WKWC_Wallet
 */
defined( 'ABSPATH' ) || exit(); // Exit if access directly.

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WKWC_Wallet_Table' ) ) {
	/**
	 * WKWC_Wallet_Table Class.
	 */
	class WKWC_Wallet_Table extends WP_List_Table {
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
			parent::__construct(
				array(
					'singular' => esc_html__( 'Customer Wallet', 'wp-wallet-system' ),
					'plural'   => esc_html__( 'Customer Wallet', 'wp-wallet-system' ),
					'ajax'     => false,
				)
			);
		}

		/**
		 * Prepare items
		 *
		 * @param string $search Search.
		 *
		 * @return void
		 */
		public function prepare_items( $search = '' ) {
			$columns     = $this->get_columns();
			$sortable    = $this->get_sortable_columns();
			$hidden      = $this->get_hidden_columns();
			$screen      = get_current_screen();
			$per_page    = $this->get_items_per_page( 'rule_per_page', 20 );
			$per_page    = (string) $per_page;
			$data        = $this->table_data( $search );
			$total_items = count( $data );

			$this->_column_headers = array( $columns, $hidden, $sortable );

			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $screen->get_option( 'per_page', 'default' );
			}

			$total_pages  = ceil( $total_items / $per_page );
			$current_page = $this->get_pagenum();
			$data         = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

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
		 * Define the columns that are going to be used in the table
		 *
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'id'           => __( 'ID', 'wp-wallet-system' ),
				'username'     => __( 'Username', 'wp-wallet-system' ),
				'email'        => __( 'Email', 'wp-wallet-system' ),
				'location'     => __( 'Location', 'wp-wallet-system' ),
				'orders'       => __( 'Orders', 'wp-wallet-system' ),
				'wallet_money' => __( 'Wallet Money', 'wp-wallet-system' ),
				'last_order'   => __( 'Last Order', 'wp-wallet-system' ),
				'user_actions' => __( 'Actions', 'wp-wallet-system' ),
			);

			return $columns;
		}

		/**
		 * Column default
		 *
		 * @param array  $item Item.
		 * @param string $column_name column name.
		 *
		 * @return array
		 */
		public function column_default( $item, $column_name ) {
			$user = get_user_by( 'id', $item['id'] );

			switch ( $column_name ) {
				case 'id':
				case 'username':
				case 'email':
				case 'location':
				case 'orders':
				case 'wallet_money':
				case 'last_order':
					return $item[ $column_name ];

				case 'user_actions':
					ob_start();
					?><p>
					<?php
					do_action( 'woocommerce_admin_user_actions_start', $user );

					$actions = array();

					$actions['edit'] = array(
						'url'    => admin_url( 'user-edit.php?user_id=' . $user->ID ),
						'name'   => __( 'Edit', 'wp-wallet-system' ),
						'action' => 'edit',
					);

					$actions['view'] = array(
						'url'    => admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $user->ID ),
						'name'   => __( 'View orders', 'wp-wallet-system' ),
						'action' => 'view',
					);

					$orders = wc_get_orders(
						array(
							'limit'    => 1,
							'status'   => array( 'wc-completed', 'wc-processing' ),
							'customer' => array( array( 0, $user->user_email ) ),
						)
					);

					if ( $orders ) {
						$actions['link'] = array(
							'url'    => wp_nonce_url( add_query_arg( 'link_orders', $user->ID ), 'link_orders' ),
							'name'   => __( 'Link prev orders', 'wp-wallet-system' ),
							'action' => 'link',
						);
					}

					$actions = apply_filters( 'woocommerce_admin_user_actions', $actions, $user );

					foreach ( $actions as $action ) {
						printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
					}

					do_action( 'woocommerce_admin_user_actions_end', $user );
					?>
				</p>
					<?php
					$user_actions = ob_get_contents();
					ob_end_clean();

					return $user_actions;

				default:
					return '-';
			}
		}

		/**
		 * Decide which columns to activate the sorting functionality on
		 *
		 * @return array $sortable, the array of columns that can be sorted by the user
		 */
		public function get_sortable_columns() {
			$sortable = array(
				'id'       => array( 'id', true ),
				'username' => array( 'username', true ),
				'email'    => array( 'email', true ),
			);

			return $sortable;
		}

		/**
		 * Get hidden columns
		 *
		 * @return array
		 */
		public function get_hidden_columns() {
			return array( 'status_send' );
		}

		/**
		 * Table data
		 *
		 * @param  mixed $search search.
		 * @param  mixed $perpage per_page.
		 * @return array
		 */
		private function table_data( $search, $perpage = -1 ) {
			$admin_users = new WP_User_Query(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
				)
			);

			$request_data = isset( $_REQUEST ) ? wc_clean( $_REQUEST ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby      = empty( $request_data['orderby'] ) ? 'username' : $request_data['orderby']; // If no sort, default to title.
			$sort_order   = empty( $request_data['order'] ) ? 'desc' : $request_data['order']; // If no order, default to asc.

			$query = new WP_User_Query(
				array(
					'exclude'        => array_merge( $admin_users->get_results() ),
					'number'         => $perpage,
					'search'         => '*' . esc_attr( $search ) . '*',
					'search_columns' => array(
						'user_nicename',
					),
					'ordeby'         => $orderby,
					'order'          => $sort_order,
				)
			);

			$items     = $query->get_results();
			$data      = array();
			$actions   = array();
			$user      = wp_get_current_user();
			$tr_helper = WKWC_Wallet_Transactions_Helper::get_instance();

			foreach ( $items as $item ) {
				$actions['edit'] = array(
					'url'    => admin_url( 'user-edit.php?user_id=' . $user->ID ),
					'name'   => __( 'Edit', 'wp-wallet-system' ),
					'action' => 'edit',
				);

				$actions['view'] = array(
					'url'    => admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $user->ID ),
					'name'   => __( 'View orders', 'wp-wallet-system' ),
					'action' => 'view',
				);

				$orders = wc_get_orders(
					array(
						'limit'    => 1,
						'status'   => array( 'wc-completed', 'wc-processing' ),
						'customer' => array(
							array(
								0,
								$user->user_email,
							),
						),
					)
				);

				if ( $orders ) {
					$actions['link'] = array(
						'url'    => wp_nonce_url( add_query_arg( 'link_orders', $user->ID ), 'link_orders' ),
						'name'   => esc_html__( 'Link previous orders', 'wp-wallet-system' ),
						'action' => 'link',
					);
				}

				$customer_id        = $item->data->ID;
				$state_code         = get_user_meta( $customer_id, 'billing_state', true );
				$country_code       = get_user_meta( $customer_id, 'billing_country', true );
				$state              = isset( WC()->countries->states[ $country_code ][ $state_code ] ) ? WC()->countries->states[ $country_code ][ $state_code ] : $state_code;
				$country            = isset( WC()->countries->countries[ $country_code ] ) ? WC()->countries->countries[ $country_code ] : $country_code;
				$customer_location  = empty( $state ) ? '-' : $state . ', ';
				$customer_location .= empty( $country ) ? '' : $country . ', ';

				$orders                = wc_get_customer_order_count( $customer_id );
				$wallet_purchase_money = $tr_helper->wkwc_wallet_get_amount( $customer_id );

				$last_order = wc_get_orders(
					array(
						'limit'    => 1,
						'status'   => array( 'wc-completed', 'wc-processing' ),
						'customer' => $customer_id,
					)
				);

				if ( ! empty( $last_order ) ) {
					$last_order = $last_order[0];
					$last_order = '<a href="' . admin_url( 'post.php?post=' . $last_order->get_id() . '&action=edit' ) . '">' . _x( '#', 'hash before order number', 'wp-wallet-system' ) . $last_order->get_order_number() . '</a> &ndash; ' . date_i18n( get_option( 'date_format' ), strtotime( $last_order->get_date_created() ) );
				} else {
					$last_order = '-';
				}

				$data[] = array(
					'id'           => $customer_id,
					'username'     => $item->data->user_login,
					'email'        => $item->data->user_email,
					'location'     => $customer_location,
					'orders'       => $orders,
					'wallet_money' => wc_price( $wallet_purchase_money ),
					'last_order'   => $last_order,
				);
			}

			return $data;
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
	}
}
