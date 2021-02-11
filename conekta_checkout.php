<?php

/*
Plugin Name: Conekta Payment Gateway
Plugin URI: https://wordpress.org/plugins/conekta-woocommerce/
Description: Payment Gateway through Conekta.io for Woocommerce for both credit and debit cards as well as cash payments in OXXO and monthly installments for Mexican credit cards.
Version: 3.0.7
Author: Conekta.io
Author URI: https://www.conekta.io
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*
 * Title   : Conekta Payment Extension for WooCommerce
 * Author  : Conekta.io
 * Url     : https://www.conekta.io/es/docs/plugins/woocommerce
 */

function ckpg_conekta_checkout_init_your_gateway()
{
    if (class_exists('WC_Payment_Gateway'))
    {
        if (array_key_exists("wc-ajax", $_GET) && $_GET["wc-ajax"] === "checkout") {
            if (array_key_exists("payment_method", $_POST)) {
                include_once('conekta_gateway_helper.php');
                include_once('conekta_plugin.php');
                $payment_method = sanitize_text_field( (string)$_POST["payment_method"]);
                switch ($payment_method) {
                    case 'conektacard': default:
                        include_once('conekta_card_gateway.php');
                    break;
                    case 'conektaoxxopay':
                        include_once('conekta_cash_gateway.php');
                    break;
                    case 'conektaspei':
                        include_once('conekta_spei_gateway.php');
                    break;
                }
            }
        } else {
            include_once('conekta_gateway_helper.php');
            include_once('conekta_plugin.php');
            include_once('conekta_card_gateway.php');
            include_once('conekta_cash_gateway.php');
            include_once('conekta_spei_gateway.php');
        }

    }
}

add_action('plugins_loaded', 'ckpg_conekta_checkout_init_your_gateway', 0);

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

    $wpdb->get_results($sql);
}

register_activation_hook(__FILE__, 'ckpg_conekta_activation');

function ckpg_conekta_checkout_custom_scripts_and_styles() {

    if (!is_checkout()) {
        return;
    }
    
    //Register CSS
    wp_deregister_style('checkout_card');
    wp_register_style('checkout_card', plugins_url('assets/css/card.scss', __FILE__), false, '1.0.0');
    wp_enqueue_style('checkout_card');

    //Register JS
    wp_register_script('conekta_checkout_js', plugins_url('/assets/js/conekta_checkout-js.js', __FILE__),array('jquery'), '1.0.0', true);
    wp_enqueue_script('conekta_checkout_js');
    wp_localize_script('conekta_checkout_js', 'conekta_checkout_js',['ajaxurl' => admin_url( 'admin-ajax.php' )]);
    
}
add_action( 'wp_enqueue_scripts','ckpg_conekta_checkout_custom_scripts_and_styles');