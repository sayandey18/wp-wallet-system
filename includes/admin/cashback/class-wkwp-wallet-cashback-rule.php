<?php
/**
 * Cashback rule add update.
 *
 * @package WKWC_WALLET
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cashback Rule.
 */
class WKWP_Wallet_Cashback_Rule {
	/**
	 * Instance variable.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * Constructor of this class.
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
	 * Add/Update wallet cashback rule.
	 *
	 * @return void
	 */
	public function wkwp_wallet_edit_cashback_rule() {
		$get_data  = isset( $_GET ) ? wc_clean( $_GET ) : array(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rule_id   = empty( $get_data['rule_id'] ) ? 0 : intval( $get_data['rule_id'] );
		$resp_code = empty( $get_data['resp_code'] ) ? 0 : intval( $get_data['resp_code'] );
		$errmsg    = empty( $resp_code ) ? '' : $this->wkwp_get_msg_by_response_code( $resp_code );

		$default_data = array(
			'rule_name'       => '',
			'rule_type'       => '',
			'rule_price_from' => '',
			'rule_price_to'   => '',
			'amount'          => '',
			'cashback_for'    => '',
			'rule_status'     => '',
		);

		if ( $rule_id > 0 ) {
			$default_data['rule_name']       = empty( $get_data['rule_name'] ) ? '' : $get_data['rule_name'];
			$default_data['rule_type']       = empty( $get_data['rule_type'] ) ? 'fixed' : $get_data['rule_type'];
			$default_data['rule_price_from'] = empty( $get_data['rule_price_from'] ) ? 0 : $get_data['rule_price_from'];
			$default_data['rule_price_to']   = empty( $get_data['rule_price_to'] ) ? 0 : $get_data['rule_price_to'];
			$default_data['amount']          = empty( $get_data['amount'] ) ? 0 : $get_data['amount'];
			$default_data['cashback_for']    = empty( $get_data['cashback_for'] ) ? 'recharge' : $get_data['cashback_for'];
			$default_data['rule_status']     = empty( $get_data['rule_status'] ) ? 'publish' : $get_data['rule_status'];
		}

		$rule_data = array();
		$title     = empty( $rule_id ) ? esc_html__( 'Add Cashback Rule', 'wp-wallet-system' ) : esc_html__( 'Update Cashback Rule', 'wp-wallet-system' );

		if ( ! empty( $rule_id ) && empty( $resp_code ) ) {
			$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();
			$rule_data    = $rules_helper->get_rules( array( 'id' => $rule_id ) );
		}
		$rule_data = wp_parse_args( $rule_data, $default_data );
		?>
		<div class="wrap wkwp-wallet-edit-cashback">
			<h1><?php echo esc_html( $title ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<table class="form-table">
					<tbody>

					<tr valign="top">
						<td colspan="2"><p class="error"><?php echo esc_html( $errmsg ); ?></p></td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="rule_name"><?php esc_html_e( 'Rule Name', 'wp-wallet-system' ); ?>
							<span class="error"> *</span></label>
						</th>

						<td>
							<input type="text" name="rule_name" id="rule_name" value="<?php echo esc_attr( $rule_data['rule_name'] ); ?>" required>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="cashback_for"><?php esc_html_e( 'Cashback For', 'wp-wallet-system' ); ?></label>
							<span class="error"> *</span></label>
						</th>

						<td class="">
							<select name="cashback_for" id="cashback_for" required>
								<option value="recharge" <?php selected( 'recharge', $rule_data['cashback_for'], true ); ?>><?php esc_html_e( 'Recharge wallet', 'wp-wallet-system' ); ?></option>
								<option value="cart" <?php selected( 'cart', $rule_data['cashback_for'], true ); ?>><?php esc_html_e( 'Purchase Product', 'wp-wallet-system' ); ?></option>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="rule_type"><?php esc_html_e( 'Cashback Type', 'wp-wallet-system' ); ?></label>
							<span class="error"> *</span></label>
						</th>

						<td class="">
							<select class="" name="rule_type" id="rule_type" title="cashback type">
								<option value="fixed" <?php selected( 'fixed', $rule_data['rule_type'], true ); ?>><?php esc_html_e( 'Fixed', 'wp-wallet-system' ); ?></option>
								<option value="percent" <?php selected( 'percent', $rule_data['rule_type'], true ); ?>><?php esc_html_e( 'Percent', 'wp-wallet-system' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'You can set cashback type either fixed or percentage based. Cashback will be calculated based on this selection.', 'wp-wallet-system' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="cashback_amount"><?php esc_html_e( 'Cashback Amount', 'wp-wallet-system' ); ?>
							<span class="error"> *</span></label>
						</th>

						<td class="">
							<input type="number" step="0.01" name="amount" id="cashback_amount" value="<?php echo esc_attr( $rule_data['amount'] ); ?>" min="1" required>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="rule_price_from"><?php esc_html_e( 'Cart Total From', 'wp-wallet-system' ); ?>
							<span class="error"> *</span></label>
						</th>

						<td>
							<input step="0.01" type="number" name="rule_price_from" id="rule_price_from" value="<?php echo esc_attr( $rule_data['rule_price_from'] ); ?>" min="1" required>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="rule_price_to"><?php esc_html_e( 'Cart Total To', 'wp-wallet-system' ); ?>
							<span class="error"> *</span></label>
						</th>

						<td>
							<input step="0.01" type="number" name="rule_price_to" id="rule_price_to" value="<?php echo esc_attr( $rule_data['rule_price_to'] ); ?>" min="1" required>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="cashback_status"><?php esc_html_e( 'Status', 'wp-wallet-system' ); ?></label>
							<span class="error"> *</span></label>
						</th>

						<td class="">
							<select name="rule_status" id="cashback_status" required>
								<option value="publish"	<?php selected( 'publish', $rule_data['rule_status'], true ); ?>><?php esc_html_e( 'Publish', 'wp-wallet-system' ); ?></option>
								<option value="draft" <?php selected( 'draft', $rule_data['rule_status'], true ); ?>><?php esc_html_e( 'Draft', 'wp-wallet-system' ); ?></option>
							</select>
						</td>
					</tr>

					</tbody>
				</table>

				<p class="submit">
					<input name="wkwp_wallet_cb_rule_submit" class="button-primary cashback-save-button" type="submit" value="Save">
					<input name="action" type="hidden" value="wkwp_wallet_update_cb_rule">
					<input name="rule_id" type="hidden" value="<?php echo esc_attr( $rule_id ); ?>">
					<input name="_wkwp_wallet_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'wkwp_wallet_cb_rule_action' ) ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wkwp_wallet_cb_rules' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Cancel', 'wp-wallet-system' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Setting Cashback Rule Handler.
	 *
	 * @param array $post_data Posted data.
	 *
	 * @return string|bool
	 */
	public function wkwp_wallet_handle_cb_rule_form( $post_data ) {
		$rule_id = empty( $post_data['rule_id'] ) ? 0 : intval( $post_data['rule_id'] );

		$response = array(
			'success'   => false,
			'resp_code' => '101',
		);

		$has_error = $this->wkwp_wallet_validate_form( $rule_id, $post_data );

		if ( $has_error ) {
			$response['resp_code'] = $has_error;
			return $response;
		}

		$rule_data = array(
			'id'              => $rule_id,
			'rule_name'       => empty( $post_data['rule_name'] ) ? 'fixed' : $post_data['rule_name'],
			'rule_type'       => empty( $post_data['rule_type'] ) ? 'fixed' : $post_data['rule_type'],
			'rule_price_from' => empty( $post_data['rule_price_from'] ) ? 0 : $post_data['rule_price_from'],
			'rule_price_to'   => empty( $post_data['rule_price_to'] ) ? 0 : $post_data['rule_price_to'],
			'cashback_for'    => empty( $post_data['cashback_for'] ) ? 'recharge' : $post_data['cashback_for'],
			'amount'          => empty( $post_data['amount'] ) ? 0 : $post_data['amount'],
			'rule_status'     => empty( $post_data['rule_status'] ) ? 'publish' : $post_data['rule_status'],
		);

		$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();

		if ( $rule_id < 1 ) {
			$inserted = $rules_helper->update_rule( $rule_data );

			if ( ! is_wp_error( $inserted ) ) {
				$response['resp_code'] = '120';
				$response['success']   = true;
				return $response;
			}
			$response['resp_code'] = '106';
			return $response;
		}

		$updated = $rules_helper->update_rule( $rule_data );

		if ( ! is_wp_error( $updated ) ) {
			$response['resp_code'] = '121';
			$response['success']   = true;
			return $response;
		}

		$response['resp_code'] = '109';
		return $response;
	}

	/**
	 * Validating cashback from submission.
	 *
	 * @param array $form_data From data.
	 * @param int   $rule_id Rule id.
	 *
	 * @return string
	 */
	public function wkwp_wallet_validate_form( $rule_id, $form_data = array() ) {
		$com_type        = empty( $form_data['rule_type'] ) ? 'fixed' : $form_data['rule_type'];
		$rule_name       = empty( $form_data['rule_name'] ) ? 'empty' : $form_data['rule_name'];
		$starting_price  = empty( $form_data['rule_price_from'] ) ? 0 : $form_data['rule_price_from'];
		$end_price       = empty( $form_data['rule_price_to'] ) ? 0 : $form_data['rule_price_to'];
		$cashback_amount = empty( $form_data['amount'] ) ? 0 : $form_data['amount'];
		$cashback_for    = empty( $form_data['cashback_for'] ) ? 'recharge' : $form_data['cashback_for'];

		if ( $starting_price >= $end_price ) {
			return '102';
		}

		if ( 'fixed' === $com_type && $cashback_amount > $end_price ) {
			return '103';
		}

		if ( 'percent' === $com_type && $cashback_amount > 100 ) {
			return '104';
		}

		if ( ! is_numeric( $starting_price ) || ! is_numeric( $end_price ) || ! is_numeric( $cashback_amount ) ) {
			return '105';
		}

		if ( 'empty' === $rule_name ) {
			return '110';
		}

		if ( $rule_id > 0 ) {
			$rules_helper = WKWP_Wallet_Cashback_Helper::get_instance();

			$args = array(
				'fields' => 'id, rule_price_from, rule_price_to, cashback_for',
			);

			$rule_posts = $rules_helper->get_rules( $args );

			foreach ( $rule_posts as $rule_post ) {
				if ( intval( $rule_post['id'] ) !== $rule_id ) {
					if ( ( $rule_post['rule_price_from'] <= $starting_price ) && ( $starting_price <= $rule_post['rule_price_to'] ) && $cashback_for === $rule_post['cashback_for'] ) {
						return '107';
					}

					if ( ( $rule_post['rule_price_from'] <= $end_price ) && ( $end_price <= $rule_post['rule_price_to'] ) && $cashback_for === $rule_post['cashback_for'] ) {
						return '108';
					}
				}
			}
		}

		return false;
	}

	/**
	 * Ger error message by error code.
	 *
	 * @param string $code Error Code.
	 *
	 * @return string
	 */
	public function wkwp_get_msg_by_response_code( $code ) {
		$response_codes = array(
			'101' => esc_html__( 'Something went wrong!', 'wp-wallet-system' ),
			'102' => esc_html__( 'Enter Valid Range for starting and end price.', 'wp-wallet-system' ),
			'103' => esc_html__( 'Cashback cannot be greater than total cart amount.', 'wp-wallet-system' ),
			'104' => esc_html__( 'Cashback cannot be greater than 100%.', 'wp-wallet-system' ),
			'105' => esc_html__( 'Enter Valid Number for prices.', 'wp-wallet-system' ),
			'106' => esc_html__( 'Some issue in adding a new rule.', 'wp-wallet-system' ),
			'107' => esc_html__( 'This range of starting price is already covered in some other rule.', 'wp-wallet-system' ),
			'108' => esc_html__( 'This range of end price is already covered in some other rule.', 'wp-wallet-system' ),
			'109' => esc_html__( 'Some issue in updating the cashback rule.', 'wp-wallet-system' ),
			'110' => esc_html__( 'Unique rule name is required.', 'wp-wallet-system' ),
			'120' => esc_html__( 'A new cashback rule is added successfully.', 'wp-wallet-system' ),
			'121' => esc_html__( 'The cashback rule is updated successfully.', 'wp-wallet-system' ),
		);

		return empty( $response_codes[ $code ] ) ? $response_codes['101'] : $response_codes[ $code ];
	}
}
