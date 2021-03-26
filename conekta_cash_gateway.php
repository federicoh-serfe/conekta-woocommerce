<?php
if (!class_exists('Conekta')) {
    require_once("lib/conekta-php/lib/Conekta.php");
}
/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Conekta.io
 * Url     : https://wordpress.org/plugins/conekta-woocommerce
 */

class WC_Conekta_Cash_Gateway extends WC_Conekta_Plugin
{
    protected $GATEWAY_NAME               = "WC_Conekta_Cash_Gateway";
    protected $use_sandbox_api            = true;
    protected $order                      = null;
    protected $transaction_id             = null;
    protected $transaction_error_message  = null;
    protected $conekta_test_api_key       = '';
    protected $conekta_live_api_key       = '';
    protected $publishable_key            = '';

    public function __construct()
    {
        $this->id              = 'conektaoxxopay';
        $this->method_title    = __( 'Conekta Oxxo Pay', 'woocommerce' );
        $this->has_fields      = true;
        $this->ckpg_init_form_fields();
        $this->init_settings();
        $this->title           = $this->settings['title'];
        $this->description     = '';
        $this->icon            = $this->settings['alternate_imageurl'] ?
                                 $this->settings['alternate_imageurl'] :
                                 WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/oxxopay.png';
        $this->use_sandbox_api = strcmp($this->settings['debug'], 'yes') == 0;
        $this->test_api_key    = $this->settings['test_api_key'];
        $this->live_api_key    = $this->settings['live_api_key'];
        $this->secret_key      = $this->use_sandbox_api ?
                                 $this->test_api_key :
                                 $this->live_api_key;

        $this->lang_options = parent::ckpg_set_locale_options()->ckpg_get_lang_options();

        if (empty($this->secret_key)){
            $this->enabled = false;
        }
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id ,
            array($this, 'process_admin_options')
        );
        add_action(
            'woocommerce_thankyou_' . $this->id,
            array($this, 'ckpg_thankyou_page')
        );
        add_action(
            'woocommerce_email_before_order_table',
            array($this, 'ckpg_email_instructions')
        );
        add_action(
            'woocommerce_email_before_order_table',
            array($this, 'ckpg_email_reference')
        );
        add_action(
            'woocommerce_api_' . strtolower(get_class($this)),
            array($this, 'ckpg_webhook_handler')
        );
    }

    /**
     * Updates the status of the order.
     * Webhook needs to be added to Conekta account tusitio.com/wc-api/WC_Conekta_Cash_Gateway
     */
    public function ckpg_webhook_handler()
    {
        header('HTTP/1.1 200 OK');
        $body          = @file_get_contents('php://input');
        $event         = json_decode($body, true);
        $conekta_order = $event['data']['object'];
        $charge        = $conekta_order['charges']['data'][0];
        $order_id      = $conekta_order['metadata']['reference_id'];
        $paid_at       = date("Y-m-d", $charge['paid_at']);
        $order         = new WC_Order($order_id);

        if($charge['payment_method']['type'] === "oxxo"){
            if (strpos($event['type'], "order.paid") !== false)
            {
                update_post_meta($order->get_id(), 'conekta-paid-at', $paid_at);
                $order->payment_complete();
                $order->add_order_note(sprintf("Payment completed in Oxxo and notification of payment received"));

                parent::ckpg_offline_payment_notification($order_id, $conekta_order['customer_info']['name']);
            }elseif(strpos($event['type'], "order.expired") !== false){

                $order->update_status('cancelled', __( 'Oxxo payment has been expired', 'woocommerce' ));

            }elseif(strpos($event['type'], "order.canceled") !== false){
                
                $order->update_status('cancelled', __( 'Order has been canceled', 'woocommerce' ));
            }
        }
        
    }

    
    public function ckpg_init_form_fields()
    {
        wp_enqueue_script('functions', WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)).'/assets/js/functions.js', '', '1.0', true);
        $this->form_fields = array(
            'enabled' => array(
                'type'        => 'checkbox',
                'title'       => __('Enable/Disable', 'woothemes'),
                'label'       => __('Enable Conekta Oxxo Pay Payment', 'woothemes'),
                'default'     => 'yes'
            ),
            'debug' => array(
                'type'        => 'checkbox',
                'title'       => __('Testing', 'woothemes'),
                'label'       => __('Turn on testing', 'woothemes'),
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => __('Title', 'woothemes'),
                'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
                'default'     => __('Conekta PAgo en Efectivo en Oxxo Pay', 'woothemes')
            ),
            'test_api_key' => array(
                'type'        => 'password',
                'title'       => __('Conekta API Test Private key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'live_api_key' => array(
                'type'        => 'password',
                'title'       => __('Conekta API Live Private key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'expiration_time' => array(
                'type'        => 'select',
                'title'       => __('Expiration time type', 'woothemes'),
                'label'       => __('Hours', 'woothemes'),
                'default'     => 'no',
                'options'     => array(
                    'hours' => "Hours",
                    'days' => "Days",
                ),
            ),
            'expiration' => array(
                'type'        => 'text',
                'title'       => __('Expiration time (in days or hours) for the reference', 'woothemes'),
                'default'     => __('1', 'woothemes')
            ),
            'alternate_imageurl' => array(
                'type'        => 'text',
                'title'       => __('Alternate Image to display on checkout, use fullly qualified url, served via https', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'description' => array(
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                'default' =>__('Por favor realiza el pago en el OXXO más cercano utilizando la referencia que se encuentra a continuación.', 'woocommerce' ),
                'desc_tip' => true,
            ),
            'instructions' => array(
                'title' => __( 'Instructions', 'woocommerce' ),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', 'woocommerce'),
                'default' =>__('Por favor realiza el pago en el OXXO más cercano utilizando la referencia que se encuentra a continuación.', 'woocommerce'),
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Output for the order received page.
     *
     * @param string $order_id
     */

    // this echo's may were safe of validation, because there are proveided by os
    function ckpg_thankyou_page($order_id) {
        $order = new WC_Order( $order_id );

        /*
        session_start();
        $intans = $_SESSION['intans'];
        $instan = $_SESSION['instan'];

        echo '<p"><strong>'.('instances').':</strong> ' . $intans . '</p>';
        echo '<p"><strong>'.('instance').':</strong> ' . $instan . '</p>';
        */

        echo '<p style="font-size: 30px"><strong>'.__('Referencia').':</strong> ' . get_post_meta( esc_html($order->get_id()), 'conekta-referencia', true ). '</p>';
        echo '<p>OXXO cobrará una comisión adicional al momento de realizar el pago.</p>';
        echo '<p>INSTRUCCIONES:'. $this->settings['instructions'] .'</p>';

    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     */

    function ckpg_email_reference($order) {
        if (get_post_meta( $order->get_id(), 'conekta-referencia', true ) != null)
            {
                echo '<p style="font-size: 30px"><strong>'.__('Referencia').':</strong> ' . get_post_meta( $order->get_id(), 'conekta-referencia', true ). '</p>';
                echo '<p>OXXO cobrará una comisión adicional al momento de realizar el pago.</p>';
                echo '<p>INSTRUCCIONES:'. $this->settings['instructions'] .'</p>';
            }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function ckpg_email_instructions( $order, $sent_to_admin = false, $plain_text = false ) {
        if (get_post_meta( $order->get_id(), '_payment_method', true ) === $this->id){
            $instructions = $this->form_fields['instructions'];
            if ( $instructions && 'on-hold' === $order->get_status() ) {
                echo wpautop( wptexturize( $instructions['default'] ) ) . PHP_EOL;
            }
        }
    }

    public function ckpg_admin_options()
    {
        include_once('templates/cash_admin.php');
    }

    public function payment_fields()
    {
        include_once('templates/cash.php');
    }

    protected function ckpg_set_as_paid()
    {
        $current_order_id = WC_Conekta_Plugin::ckpg_get_conekta_unfinished_order(WC()->session->get_customer_id(), WC()->cart->get_cart_hash());
        WC_Conekta_Plugin::ckpg_insert_conekta_unfinished_order(WC()->session->get_customer_id(), WC()->cart->get_cart_hash(), $current_order_id, 'paid' );
        return true;
    }
    public function ckpg_expiration_payment( $expiration ) {

        switch( $expiration ){
            case 'hours': 
                $expiration_cont = 24;
                $expires_time = 3600;
            break;
            case 'days': 
                $expiration_cont = 32;
                $expires_time = 86400;
            break;
        }
        if($this->settings['expiration'] > 0 && $this->settings['expiration'] < $expiration_cont){
            $expires = time() + ($this->settings['expiration'] * $expires_time);
        }
        return $expires;
    }
    public function process_payment($order_id)
    {
        global $woocommerce;
        $this->order        = new WC_Order($order_id);
        if ($this->ckpg_set_as_paid())
            {
                // Mark as on-hold (we're awaiting the notification of payment)
                $this->order->update_status('on-hold', __( 'Awaiting the conekta OXXO payment', 'woocommerce' ));

                // Remove cart
                $woocommerce->cart->empty_cart();
                unset($_SESSION['order_awaiting_payment']);
                $result = array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($this->order)
                );

                return $result;
            }
        else
            {
                $this->ckpg_mark_as_failed_payment();
                global $wp_version;
                if (version_compare($wp_version, '4.1', '>=')) {
                    wc_add_notice(__('Transaction Error: Could not complete the payment', 'woothemes'), $notice_type = 'error');
                } else {
                    $woocommerce->add_error(__('Transaction Error: Could not complete the payment'), 'woothemes');
                }
            }
    }

    protected function ckpg_mark_as_failed_payment()
    {
        $this->order->add_order_note(
            sprintf(
                "%s Oxxo Pay Payment Failed : '%s'",
                $this->GATEWAY_NAME,
                $this->transaction_error_message
            )
        );
    }

    protected function ckpg_complete_order()
    {
        global $woocommerce;

        if ($this->order->get_status() == 'completed')
            return;

        $this->order->payment_complete();
        $woocommerce->cart->empty_cart();
        $this->order->add_order_note(
            sprintf(
                "%s payment completed with Transaction Id of '%s'",
                $this->GATEWAY_NAME,
                $this->transaction_id
            )
        );

        unset($_SESSION['order_awaiting_payment']);
    }

}

function ckpg_conekta_cash_order_status_completed($order_id = null)
{
    global $woocommerce;
    if (!$order_id){
        $order_id = sanitize_text_field((string) $_POST['order_id']);
    }

    $data = get_post_meta( $order_id );

    $total = $data['_order_total'][0] * 100;

    $amount = floatval($_POST['amount']);
    if(isset($amount))
    {
        $params['amount'] = round($amount);
    }
}

function ckpg_conektacheckout_add_cash_gateway($methods)
{
    array_push($methods, 'WC_Conekta_Cash_Gateway');
    return $methods;
}

add_filter('woocommerce_payment_gateways',                      'ckpg_conektacheckout_add_cash_gateway');
add_action('woocommerce_order_status_processing_to_completed',  'ckpg_conekta_cash_order_status_completed' );

function ckpg_create_cash_order()
    {
        global $woocommerce;
        try{
            $gateway = WC()->payment_gateways->get_available_payment_gateways()['conektaoxxopay'];
            \Conekta\Conekta::setApiKey($gateway->secret_key);
            \Conekta\Conekta::setApiVersion('2.0.0');
            \Conekta\Conekta::setPlugin($gateway->name);
            \Conekta\Conekta::setPluginVersion($gateway->version);
            \Conekta\Conekta::setLocale('es');
            
            $old_order = WC_Conekta_Plugin::ckpg_get_conekta_unfinished_order(WC()->session->get_customer_id(), WC()->cart->get_cart_hash());
            if(empty($old_order)){

                $customer_id = WC_Conekta_Plugin::ckpg_get_conekta_metadata(get_current_user_id(), WC_Conekta_Plugin::CONEKTA_CUSTOMER_ID);
                
                if(!empty($customer_id)){
                    $customer = \Conekta\Customer::find($customer_id); 
                }else{
                    $curstomerData = array(
                        'name' => $_POST['name'],
                        'email' => $_POST['email'],
                        'phone' => $_POST['phone']
                    );
                    $customer = \Conekta\Customer::create($curstomerData);
                }
                
                $checkout = WC()->checkout();
                $posted_data = $checkout->get_posted_data();
				$wc_order    = wc_get_order( $checkout->create_order($posted_data) );
                $data = ckpg_get_request_data($wc_order);
                $amount = (int) $data['amount'];
                $items  = $wc_order->get_items();
                $taxes  = $wc_order->get_taxes();
                $line_items       = ckpg_build_line_items($items, $gateway->ckpg_get_version());
                $discount_lines   = ckpg_build_discount_lines($data);
                $shipping_lines   = ckpg_build_shipping_lines($data);
                $tax_lines        = ckpg_build_tax_lines($taxes);
                $order_metadata   = ckpg_build_order_metadata($data);

                $order_details = array(
                    'line_items'=> $line_items,
                    'shipping_lines' => $shipping_lines,
                    'tax_lines' => $tax_lines,
                    'discount_lines'   => $discount_lines,
                    'shipping_contact'=> array(
                        "phone" => $customer['phone'],
                        "receiver" => $customer['name'],
                        "address" => array(
                        "street1" => $_POST['address_1'],
                        "street2" => $_POST['address_2'],
                        "country" => $_POST['country'],
                        "postal_code" => $_POST['postcode']
                        )
                    ),
                    'checkout' => array(
                        'allowed_payment_methods' => array("card","cash","bank_transfer"),
                        'monthly_installments_enabled' => false,
                        'monthly_installments_options' => array(),
                        "on_demand_enabled" => false
                    ),
                    'customer_info' => array(
                        'customer_id'   =>  $customer['id'],
                        'name' =>  $customer['name'],    
                        'email' => $customer['email'],    
                        'phone' => $customer['phone']
                    ),
                    'metadata' => $order_metadata,
                    'currency' => $data['currency']
                );
                $order_details = ckpg_check_balance($order_details, $amount);
                $order = \Conekta\Order::create($order_details);
                WC_Conekta_Plugin::ckpg_insert_conekta_unfinished_order(WC()->session->get_customer_id(), WC()->cart->get_cart_hash(), $order->id, $order['payment_status'] );
            }else{
                $order = \Conekta\Order::find($old_order);
            }
            $response = array(
                'checkout_id'  => $order->checkout['id'],
                'key' => $gateway->secret_key,
                'price' => WC()->cart->total,
                'cash_text' => $gateway->settings['description']
            );
        } catch(\Conekta\Handler $e) {
            $description = $e->getMessage();
            global $wp_version;
            if (version_compare($wp_version, '4.1', '>=')) {
                wc_add_notice(__('Error: ', 'woothemes') . $description , $notice_type = 'error');
            } else {
                error_log('Gateway Error:' . $description . "\n");
                $woocommerce->add_error(__('Error: ', 'woothemes') . $description);
            }
            $response = array(
                'error' => $e->getMessage()
            );
        }
        wp_send_json($response);
    }
add_action( 'wp_ajax_nopriv_ckpg_create_cash_order','ckpg_create_cash_order');