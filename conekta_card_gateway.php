<?php

if (!class_exists('Conekta')) {
    require_once("lib/conekta-php/lib/Conekta.php");
}

/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://wordpress.org/plugins/conekta-woocommerce
*/
class WC_Conekta_Card_Gateway extends WC_Conekta_Plugin
{
    protected $GATEWAY_NAME              = "WC_Conekta_Card_Gateway";
    protected $is_sandbox                = true;
    protected $order                     = null;
    protected $transaction_id            = null;
    protected $conekta_order_id          = null;
    protected $transaction_error_message = null;
    protected $currencies                = array('MXN', 'USD');

    public function __construct()
    {
        global $woocommerce;
        $this->id = 'conektacard';
        $this->method_title = __('Conekta Card', 'conektacard');
        $this->has_fields = true;
        $this->ckpg_init_form_fields();
        $this->init_settings();

        $this->title       = $this->settings['title'];
        $this->description = '';
        $this->icon        = $this->settings['alternate_imageurl'] ?
            $this->settings['alternate_imageurl'] : WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__))
            . '/images/credits.png';

        $this->use_sandbox_api      = strcmp($this->settings['debug'], 'yes') == 0;
        $this->enable_meses         = strcmp($this->settings['meses'], 'yes') == 0;
        $this->enable_iframe        = strcmp($this->settings['iframe'], 'yes') == 0;
        $this->test_api_key         = $this->settings['test_api_key'];
        $this->live_api_key         = $this->settings['live_api_key'];
        $this->test_publishable_key = $this->settings['test_publishable_key'];
        $this->live_publishable_key = $this->settings['live_publishable_key'];
        $this->publishable_key      = $this->use_sandbox_api ?  $this->test_publishable_key : $this->live_publishable_key;
        $this->secret_key           = $this->use_sandbox_api ?  $this->test_api_key : $this->live_api_key;
        $this->lang_options         = parent::ckpg_set_locale_options()->ckpg_get_lang_options();
        
        if( $this->enable_meses ) {

            if(  !is_admin() ) {
                
                if( (  !empty($woocommerce->cart->total) && ( intval($woocommerce->cart->total) < $this->settings['amount_monthly_install'] ) ) ) {
                    foreach(array_keys($this->lang_options['monthly_installments'] ) as $monthly) {
                        unset($this->lang_options['monthly_installments'][$monthly]);
                    }
                    
                } else {
                    
                    foreach(array_keys($this->lang_options['monthly_installments'] ) as $monthly) {
                        
                        if( $this->settings[$monthly .'_months_msi'] == 'no' && isset( $this->lang_options['monthly_installments'][$monthly] ) ) {
                            unset($this->lang_options['monthly_installments'][$monthly]);
                        }
                    }
                }
                
            } else {
                $min_amount = 300;
                switch( $this->ckpg_find_last_month() ) {
                    case '3_months_msi' : $min_amount = 300; break;
                    case '6_months_msi' : $min_amount = 600; break;
                    case '9_months_msi' : $min_amount = 900; break;
                    case '12_months_msi' : $min_amount = 1200; break;
                    case '18_months_msi' : $min_amount = 1800; break;
                }
                if( $this->settings['amount_monthly_install'] < $min_amount ) {
                    $this->settings['amount_monthly_install'] = '';
                    update_option('woocommerce_conektacard_settings',$this->settings);
                }
            }
        } else {
            if( is_admin() ) {
                $this->settings['amount_monthly_install'] = '';
                $this->settings['3_months_msi'] = '';
                $this->settings['6_months_msi'] = '';
                $this->settings['9_months_msi'] = '';
                $this->settings['12_months_msi'] = '';
                $this->settings['18_months_msi'] = '';
                update_option('woocommerce_conektacard_settings',$this->settings);
            }
        }

        add_action('wp_enqueue_scripts', array($this, 'ckpg_payment_fields'));
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );
        add_action('admin_notices', array(&$this, 'ckpg_perform_ssl_check'));

        if (!$this->ckpg_validate_currency()) {
            $this->enabled = false;
        }

        if (empty($this->secret_key)) {
            $this->enabled = false;
        }
    }

    /**
    * Checks to see if SSL is configured and if plugin is configured in production mode
    * Forces use of SSL if not in testing
    */
    public function ckpg_perform_ssl_check()
    {
        if (
            !$this->use_sandbox_api
            && get_option('woocommerce_force_ssl_checkout') == 'no'
            && $this->enabled == 'yes'
        ) {
            echo '<div class="error"><p>'
                . sprintf(
                    __('%s sandbox testing is disabled and can performe live transactions'
                        . ' but the <a href="%s">force SSL option</a> is disabled; your checkout'
                        . ' is not secure! Please enable SSL and ensure your server has a valid SSL'
                        . ' certificate.', 'woothemes'),
                    $this->GATEWAY_NAME,
                    admin_url('admin.php?page=settings')
                )
                . '</p></div>';
        }
    }

    public function ckpg_init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'type'        => 'checkbox',
                'title'       => __('Enable/Disable', 'woothemes'),
                'label'       => __('Enable Credit Card Payment', 'woothemes'),
                'default'     => 'yes'
            ),
            'meses' => array(
                'type'        => 'checkbox',
                'title'       => __('Months without interest', 'woothemes'),
                'label'       => __('Enable months without interest', 'woothemes'),
                'default'     => 'no'
            ),
            '3_months_msi' => array(
                'type'        => 'checkbox',
                'label'       => __('3 Months', 'woothemes'),
                'default'     => 'no'
            ),
            '6_months_msi' => array(
                'type'        => 'checkbox',
                'label'       => __('6 Months', 'woothemes'),
                'default'     => 'no'
            ),
            '9_months_msi' => array(
                'type'        => 'checkbox',
                'label'       => __('9 Months', 'woothemes'),
                'default'     => 'no'
            ),
            '12_months_msi' => array(
                'type'        => 'checkbox',
                'label'       => __('12 Months', 'woothemes'),
                'default'     => 'no'
            ),
            '18_months_msi' => array(
                'type'        => 'checkbox',
                'label'       => __('18 Months ( Banamex )', 'woothemes'),
                'default'     => 'no'
            ),
            'amount_monthly_install' => array(
                'type'        => 'text',
                'title'       => __('Minimun Amount for Monthly Installments', 'woothemes'),
                'description' => __('Minimum amount for each installment from Conekta</br>
                - 300 MXN para 3 meses sin intereses</br>
                - 600 MXN para 6 meses sin intereses</br>
                - 900 MXN para 9 meses sin intereses</br>
                - 1200 MXN para 12 meses sin intereses</br>
                - 1800 MXN para 18 meses sin intereses</br>', 'woothemes'),
            ),
            'debug' => array(
                'type'        => 'checkbox',
                'title'       => __('Testing', 'woothemes'),
                'label'       => __('Turn on testing', 'woothemes'),
                'default'     => 'no'
            ),
            'iframe' => array(
                'type'        => 'checkbox',
                'title'       => __('Iframe', 'woothemes'),
                'label'       => __('Enable iframe (beta)', 'woothemes'),
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => __('Title', 'woothemes'),
                'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
                'default'     => __('Credit Card Payment', 'woothemes')
            ),
            'test_api_key' => array(
                'type'        => 'password',
                'title'       => __('Conekta API Test Private key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'test_publishable_key' => array(
                'type'        => 'text',
                'title'       => __('Conekta API Test Public key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'live_api_key' => array(
                'type'        => 'password',
                'title'       => __('Conekta API Live Private key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'live_publishable_key' => array(
                'type'        => 'text',
                'title'       => __('Conekta API Live Public key', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),
            'alternate_imageurl' => array(
                'type'        => 'text',
                'title'       => __('Alternate Image to display on checkout, use fullly qualified url, served via https', 'woothemes'),
                'default'     => __('', 'woothemes')
            ),


        );
    }

    public function admin_options()
    {
        include_once('templates/admin.php');
    }

    public function payment_fields()
    {
        if ($this->enable_iframe) {
            include_once('templates/payment.php');
        } else {
            include_once('templates/payment_legacy.php');
        }
    }
    public function ckpg_find_last_month() {

        $last_month_true = false;
        
        foreach (array_keys($this->lang_options['monthly_installments']) as $last_month ) {
            if( $this->settings[ $last_month .'_months_msi' ] == 'yes') {
                
                $last_month_true = $last_month;
            }
        }
        return $last_month_true;
    }
    public function ckpg_payment_fields()
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_script('conekta_js', 'https://s3.us-east-2.amazonaws.com/conektadirect/latest/index.js', '', '', true);
        wp_enqueue_script('tokenize', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/js/tokenize.js', '', '1.0', true); //check import convention

        //PCI
        $params = array(
            'public_key' => $this->publishable_key
        );

        wp_localize_script('tokenize', 'wc_conekta_params', $params);
    }

    protected function ckpg_send_to_conekta()
    {
        global $woocommerce;
        include_once('conekta_gateway_helper.php');
        \Conekta\Conekta::setApiKey($this->secret_key);
        \Conekta\Conekta::setApiVersion('2.0.0');
        \Conekta\Conekta::setPlugin($this->name);
        \Conekta\Conekta::setPluginVersion($this->version);
        \Conekta\Conekta::setLocale('es');



        //ALL $data VAR ASSIGNATION IS FREE OF VALIDATION
        $data             = ckpg_get_request_data($this->order);
        $amount           = $data['amount'];
        $items            = $this->order->get_items();
        $taxes            = $this->order->get_taxes();
        $line_items       = ckpg_build_line_items($items, parent::ckpg_get_version());
        $discount_lines   = ckpg_build_discount_lines($data);
        $shipping_lines   = ckpg_build_shipping_lines($data);
        $shipping_contact = ckpg_build_shipping_contact($data);
        $tax_lines        = ckpg_build_tax_lines($taxes);
        $customer_info    = ckpg_build_customer_info($data);
        $order_metadata   = ckpg_build_order_metadata($data);
        $order_details    = array(
            'currency'         => $data['currency'],
            'line_items'       => $line_items,
            'customer_info'    => $customer_info,
            'shipping_lines'   => $shipping_lines,
            'discount_lines'   => $discount_lines,
            'tax_lines'        => $tax_lines
        );

        if (!empty($shipping_contact)) {
            $order_details = array_merge($order_details, array('shipping_contact' => $shipping_contact));
        }


        if (!empty($order_metadata)) {
            $order_details = array_merge($order_details, array('metadata' => $order_metadata));
        }

        $order_details = ckpg_check_balance($order_details, $amount);

        try {
            $conekta_order_id = get_post_meta($this->order->get_id(), 'conekta-order-id', true);
            if (!empty($conekta_order_id)) {
                $order = \Conekta\Order::find($conekta_order_id);
                $order->update($order_details);
            } else {
                $order = \Conekta\Order::create($order_details);
            }
            //ORDER ID IS GENERATED BY RESPONSE
            update_post_meta($this->order->get_id(), 'conekta-order-id', $order->id);

            $charge_details = array(
                'payment_method' => array(
                    'type'     => 'card',
                    'token_id' => $data['token']
                ),
                'amount' => $amount
            );

            $monthly_installments = $data['monthly_installments'];
            if ($monthly_installments > 1) {
                $charge_details['payment_method']['monthly_installments'] = $monthly_installments;
            }

            $charge = $order->createCharge($charge_details);

            $this->transaction_id = $charge->id;
            if ($data['monthly_installments'] > 1) {
                update_post_meta($this->order->get_id(), 'meses-sin-intereses', $data['monthly_installments']);
            }
            update_post_meta($this->order->get_id(), 'transaction_id', $this->transaction_id);
            return true;
        } catch (\Conekta\Handler $e) {
            $description = $e->getMessage();
            global $wp_version;
            if (version_compare($wp_version, '4.1', '>=')) {
                wc_add_notice(__('Error: ', 'woothemes') . $description, $notice_type = 'error');
            } else {
                error_log('Gateway Error:' . $description . "\n");
                $woocommerce->add_error(__('Error: ', 'woothemes') . $description);
            }
            return false;
        }
    }

    protected function ckpg_mark_as_failed_payment()
    {
        $this->order->add_order_note(
            sprintf(
                "%s Credit Card Payment Failed : '%s'",
                $this->GATEWAY_NAME,
                $this->transaction_error_message
            )
        );
    }

    protected function ckpg_completeOrder()
    {
        global $woocommerce;

        if ($this->order->get_status() == 'completed')
            return;

        // adjust stock levels and change order status
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

    public function process_payment($order_id)
    {
        global $woocommerce;
        $this->order        = new WC_Order($order_id);
        if ($this->ckpg_send_to_conekta()) {
            $this->ckpg_completeOrder();

            $result = array(
                'result' => 'success',
                'redirect' => $this->get_return_url($this->order)
            );
            return $result;
        } else {
            $this->ckpg_mark_as_failed_payment();
            WC()->session->reload_checkout = true;
        }
    }

    /**
     * Checks if woocommerce has enabled available currencies for plugin
     *
     * @access public
     * @return bool
     */
    public function ckpg_validate_currency()
    {
        return in_array(get_woocommerce_currency(), $this->currencies);
    }

    public function ckpg_is_null_or_empty_string($string)
    {
        return (!isset($string) || trim($string) === '');
    }
}

function ckpg_conekta_card_add_gateway($methods)
{
    array_push($methods, 'WC_Conekta_Card_Gateway');
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'ckpg_conekta_card_add_gateway');
