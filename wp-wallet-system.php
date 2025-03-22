<?php
/**
 * Plugin Name:          WooCommerce Wallet System
 * Plugin URI:           https://woo.serverhome.biz
 * Description:          WooCommerce Wallet System Plugin helps in integrating wallet payment method.
 * Version:              3.6.0
 * Requires at least:    5.2
 * Requires PHP:         7.4
 * Author:               UpEarlyDesigns
 * Author URI:           https://woo.serverhome.biz
 * License:              GPL v2 or later
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:          wp-wallet-system
 * Domain Path:          /languages
 * Requires Plugins:     woocommerce
 *
 * WPML Compatible:      yes
 * Multisite Compatible: yes
 *
 * @package WKWP_Wallet
 */

defined( 'ABSPATH' ) || exit(); // Exit if access directly.

// Define Constants.
defined( 'WKWP_WALLET_PLUGIN_FILE' ) || define( 'WKWP_WALLET_PLUGIN_FILE', plugin_dir_path( __FILE__ ) );
defined( 'WKWP_WALLET_PLUGIN_BASENAME' ) || define( 'WKWP_WALLET_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
defined( 'WKWP_WALLET_WKWC_WALLET_VERSION' ) || define( 'WKWP_WALLET_WKWC_WALLET_VERSION', '1.0.3' );
defined( 'WKWP_WALLET_WK_CACHING_VERSION' ) || define( 'WKWP_WALLET_WK_CACHING_VERSION', '1.0.3' );

// Load auto-loader classes.
require __DIR__ . '/inc/class-wkwp-wallet-autoload.php';

// Include the Module main class.
if ( ! class_exists( 'WKWP_Wallet', false ) ) {
	include_once WKWP_WALLET_PLUGIN_FILE . '/includes/class-wkwp-wallet.php';
	WKWP_Wallet::get_instance();
}
