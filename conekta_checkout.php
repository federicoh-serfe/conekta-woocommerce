<?php
/**
 * Plugin Name: Conekta Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/conekta-woocommerce/
 * Description: Payment Gateway through Conekta.io for Woocommerce for both credit and debit cards as well as cash payments in OXXO and monthly installments for Mexican credit cards.
 * Version: 3.0.7
 * Author: Conekta.io
 * Author URI: https://www.conekta.io
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Title   : Conekta Payment Extension for WooCommerce
 * Author  : Conekta.io
 * Url     : https://www.conekta.io/es/docs/plugins/woocommerce
 */
function ckpg_conekta_checkout_init_your_gateway() {
	if ( class_exists( 'WC_Payment_Gateway' ) ) {
		if ( array_key_exists( 'wc-ajax', $_GET ) && 'checkout' === $_GET['wc-ajax'] ) {
			if ( array_key_exists( 'payment_method', $_POST ) ) {
				include_once 'conekta_gateway_helper.php';
				include_once 'conekta_plugin.php';
				include_once 'class-wc-conekta-payment-gateway.php';
			}
		} else {
			include_once 'conekta_gateway_helper.php';
			include_once 'conekta_plugin.php';
			include_once 'class-wc-conekta-payment-gateway.php';
		}
	}
}

add_action( 'plugins_loaded', 'ckpg_conekta_checkout_init_your_gateway', 0 );

function ckpg_conekta_activation() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woocommerce_conekta_metadata (
		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		id_user VARCHAR(256) NOT NULL,
		meta_option VARCHAR(255) NOT NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY id_user (id_user),
		KEY meta_id (meta_id)
	) $charset_collate;";

	$wpdb->get_results( $sql );

	$order_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woocommerce_conekta_unfinished_orders (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		customer_id VARCHAR(255) NOT NULL,
		cart_hash VARCHAR(255) NOT NULL,
		order_id VARCHAR(255) NOT NULL,
		order_number INT NOT NULL,
		status_name VARCHAR(255) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	$wpdb->get_results( $order_sql );
}

register_activation_hook( __FILE__, 'ckpg_conekta_activation' );

function ckpg_conekta_checkout_custom_scripts_and_styles() {

	if ( ! is_checkout() ) {
		return;
	}

	// Register CSS.
	wp_deregister_style( 'checkout_card' );
	wp_register_style( 'checkout_card', plugins_url( 'assets/css/card.scss', __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'checkout_card' );

	// Register JS.
	wp_register_script( 'conekta_checkout_js', plugins_url( '/assets/js/conekta_checkout-js.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'conekta_checkout_js' );
	wp_localize_script( 'conekta_checkout_js', 'conekta_checkout_js', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	wp_register_script( 'tokenize', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/assets/js/tokenize.js', array( 'jquery' ), '1.0', true ); // check import convention.
	wp_enqueue_script( 'tokenize' );
	wp_localize_script( 'tokenize', 'tokenize', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	wp_register_script( 'conekta-checkout', 'https://pay.conekta.com/v1.0/js/conekta-checkout.min.js', array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'conekta-checkout' );

}
add_action( 'wp_enqueue_scripts', 'ckpg_conekta_checkout_custom_scripts_and_styles' );
