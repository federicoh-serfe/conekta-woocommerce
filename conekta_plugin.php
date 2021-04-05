<?php

if (!class_exists('Conekta')) {
	require_once("lib/conekta-php/lib/Conekta.php");
}

/*
* Title   : Conekta Payment extension for WooCommerce
* Author  : Conekta.io
* Url     : https://wordpress.org/plugins/conekta-woocommerce
*/

class WC_Conekta_Plugin extends WC_Payment_Gateway
{
	public $version  = "3.0.7";
	public $name = "WooCommerce 2";
	public $description = "Payment Gateway through Conekta.io for Woocommerce for both credit and debit cards as well as cash payments in OXXO and monthly installments for Mexican credit cards.";
	public $plugin_name = "Conekta Payment Gateway for Woocommerce";
	public $plugin_URI = "https://wordpress.org/plugins/conekta-woocommerce/";
	public $author = "Conekta.io";
	public $author_URI = "https://www.conekta.io";

	protected $lang;
	protected $lang_messages;

	const CONEKTA_CUSTOMER_ID = 'conekta_customer_id';
	const CONEKTA_PAYMENT_SOURCES_ID = 'conekta_payment_source_id';
	const CONEKTA_ON_DEMAND_ENABLED = 'conekta_on_demand_enabled';
	const MINIMUM_ORDER_AMOUNT = 20;

	public function ckpg_get_version()
	{
		return $this->version;
	}

	public function ckpg_set_locale_options()
	{
		if (function_exists("get_locale") && get_locale() !== "") {
			$current_lang = explode("_", get_locale());
			$this->lang = $current_lang[0];
			$filename = "lang/" . $this->lang . ".php";
			if (!file_exists(plugin_dir_path(__FILE__) . $filename))
				$filename = "lang/en.php";
			$this->lang_messages = require($filename);
			\Conekta\Conekta::setLocale($this->lang);
		}

		return $this;
	}

	public function ckpg_get_lang_options()
	{
		return $this->lang_messages;
	}

	public function ckpg_offline_payment_notification($order_id, $customer)
	{
		global $woocommerce;
		$order = new WC_Order($order_id);

		$title = sprintf("Se ha efectuado el pago del pedido %s", $order->get_order_number());
		$body_message = "<p style=\"margin:0 0 16px\">Se ha detectado el pago del siguiente pedido:</p><br />" . $this->ckpg_assemble_email_payment($order);

		// Email for customer
		$customer = esc_html($customer);
		$customer = sanitize_text_field($customer);

		$mail_customer = $woocommerce->mailer();
		$message = $mail_customer->wrap_message(
			sprintf(__('Hola, %s'), $customer),
			$body_message
		);
		$mail_customer->send($order->get_billing_email(), $title, $message);
		unset($mail_customer);
		//Email for admin site
		$mail_admin = $woocommerce->mailer();
		$message = $mail_admin->wrap_message(
			sprintf(__('Pago realizado satisfactoriamente')),
			$body_message
		);
		$mail_admin->send(get_option("admin_email"), $title, $message);
		unset($mail_admin);
	}

	public function ckpg_assemble_email_payment($order)
	{
		ob_start();

		wc_get_template('emails/email-order-details.php', array('order' => $order, 'sent_to_admin' => false, 'plain_text' => false, 'email' => ''));

		return ob_get_clean();
	}

	static public function ckpg_update_conekta_metadata($user_id, $meta_options, $meta_value) {
		global $wpdb;
		
		if ( empty( WC_Conekta_Plugin::ckpg_get_conekta_metadata($user_id, $meta_options) ) ){

			$sql = "INSERT INTO wp_woocommerce_conekta_metadata(id_user, meta_option, meta_value) VALUES ('{$user_id}','{$meta_options}','{$meta_value}')";
		}
		else {
			$sql ="UPDATE wp_woocommerce_conekta_metadata SET id_user = '{$user_id}', meta_option = '{$meta_options}', meta_value = '{$meta_value}' WHERE id_user = '{$user_id}' AND meta_option = '{$meta_options}'";
		}
		$wpdb->get_results($sql);
	}
	
	static public function ckpg_get_conekta_metadata($user_id, $meta_options) {
		global $wpdb;
	
		$sql = "SELECT meta_value FROM wp_woocommerce_conekta_metadata WHERE id_user = '{$user_id}' AND meta_option = '{$meta_options}'";
		
		$meta_value = $wpdb->get_var($sql);
	
		return  $meta_value;
	}

	static public function ckpg_delete_conekta_metadata($user_id, $meta_options) {
		global $wpdb;
		
		if ( !empty( WC_Conekta_Plugin::ckpg_get_conekta_metadata($user_id, $meta_options) ) ){

			$sql = "DELETE FROM `wp_woocommerce_conekta_metadata` WHERE id_user = '{$user_id}' AND  meta_option = '{$meta_options}'";
			
			$wpdb->get_results($sql);
		}

	}

	static public function ckpg_get_conekta_unfinished_order($customer_id, $cart_hash) {
		global $wpdb;
	
		$sql = "SELECT order_id FROM wp_woocommerce_conekta_unfinished_orders WHERE customer_id = '{$customer_id}' AND cart_hash = '{$cart_hash}' AND status_name <> 'paid'";
		
		$order_id = $wpdb->get_var($sql);
	
		return  $order_id;
	}

	static public function ckpg_insert_conekta_unfinished_order($user_id, $cart_hash, $order_id, $status_name) {
		global $wpdb;
		
		if ( empty( WC_Conekta_Plugin::ckpg_get_conekta_unfinished_order($user_id, $cart_hash) ) ){

			$sql = "INSERT INTO wp_woocommerce_conekta_unfinished_orders (customer_id, cart_hash, order_id, status_name) VALUES ('{$user_id}','{$cart_hash}','{$order_id}','{$status_name}')";
		} else {
			$sql ="UPDATE wp_woocommerce_conekta_unfinished_orders SET status_name = '{$status_name}' WHERE customer_id = '{$user_id}' AND cart_hash = '{$cart_hash}' AND order_id = '{$order_id}'";
		}
		$wpdb->get_results($sql);
	}
}