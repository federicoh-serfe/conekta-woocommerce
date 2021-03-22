<?php
 /*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://www.conekta.io/es/docs/plugins/woocommerce
 */
?>
<div class="clear"></div>
<span style="width: 100%; float: left; color: red;" class='payment-errors required'></span>
<?php 
    $customer_id = parent::ckpg_get_conekta_metadata(get_current_user_id(), parent::CONEKTA_CUSTOMER_ID);
    $sources = parent::ckpg_get_conekta_metadata(get_current_user_id(), parent::CONEKTA_PAYMENT_SOURCES_ID);
    if(!empty($customer_id)){
        $customer = \Conekta\Customer::find($customer_id); 
    }else{
        $validCustomer = [
            'name' => (empty(WC()->session->customer['first_name']) || empty(WC()->session->customer['last_name'])) ? "NEW CUSTOMER" :sprintf('%s %s', WC()->session->customer['first_name'], WC()->session->customer['last_name']),
            'email' => (empty(WC()->session->customer['email'])) ? "new_customer@mail.com" : WC()->session->customer['email'],
            'phone' => (empty(WC()->session->customer['phone'])) ? "0000000000" : sanitize_text_field(WC()->session->customer['phone'])
        ];

        $customer = \Conekta\Customer::create($validCustomer);
    }

    if(!empty( $sources )){
        $sources = explode(',',$sources);
    }

    $line_items = array();

    foreach (WC()->cart->cart_contents as $element){
        $sub_total   = floatval($element['line_subtotal']) * 1000;
        $unit_price   = $sub_total / floatval($element['quantity']);
        $unit_price  = intval(round($unit_price / 10), 2);
        $version = parent::ckpg_get_version();
        $line_items[] = array(
            'name'=> $element['data']->get_name(),
            'unit_price'=> $unit_price,
            'quantity'=> $element['quantity'],
            'sku'=> $element['data']->get_sku()
        );
    }

    $allowed_installments = array();
    if($this->enable_meses){
        $total = (float) WC()->cart->total;
        foreach (array_keys($this->lang_options['monthly_installments']) as $month ) {
            if(!empty($this->settings['amount_monthly_install'])){
                $elegible = $total >= (int) $this->settings['amount_monthly_install'];
            }else{
                switch( $month ) {
                    case 3 : $elegible = $total >= 300; break;
                    case 6 : $elegible = $total >= 600; break;
                    case 9 : $elegible = $total >= 900; break;
                    case 12 : $elegible = $total >= 1200; break;
                    case 18 : $elegible = $total >= 1800; break;
                }
            }
            if($month <= $this->ckpg_find_last_month() && $elegible){
                $allowed_installments[] = $month;
            }
        }
    }

    $validOrderWithCheckout = array(
        'line_items'=> $line_items,
        'shipping_lines' => array(
            array("amount" => 0)
        ),
        'shipping_contact'=> array(
            "phone" => (strlen($customer['phone']) > 10) ? '0000000000' : $customer['phone'],
            "receiver" => $customer['name'],
            "address" => array(
              "street1" => (empty(WC()->session->customer['shipping_address_1'])) ? 'New Street 123' : WC()->session->customer['shipping_address_1'],
              "country" => (empty(WC()->session->customer['shipping_country'])) ? 'AA' : WC()->session->customer['shipping_country'],
              "postal_code" => (empty(WC()->session->customer['shipping_postcode'])) ? '00000' : WC()->session->customer['shipping_postcode']
            )
        ),
        'checkout' => array(
            'allowed_payment_methods' => array("card"),
            'monthly_installments_enabled' => !empty($allowed_installments),
            'monthly_installments_options' => $allowed_installments,
            "on_demand_enabled" => ($this->enable_save_card == 'yes')
        ),
        'customer_info' => array(
            'customer_id'   =>  $customer['id'],
            'name' =>  $customer['name'],    
            'email' => $customer['email'],    
            'phone' => (strlen($customer['phone']) > 10) ? '0000000000' : $customer['phone']
        ),
        'currency'    => 'mxn',
        'metadata'    => array('test' => 'extra info')
    );
    if ((float) WC()->cart->total >= self::MINIMUM_CARD_PAYMENT ){
        $checkout = WC()->checkout();
        $order_id = $checkout->create_order($checkout->get_posted_data());
        $this->current_order = new WC_Order($order_id);
        $order = \Conekta\Order::create($validOrderWithCheckout);
    }
        
?>
<?php if ((float) WC()->cart->total >= self::MINIMUM_CARD_PAYMENT ) : ?>
    <script type="text/javascript" src="https://cdn.conekta.io/js/latest/conekta.js"></script>
    <script type="text/javascript" src="https://pay.conekta.com/v1.0/js/conekta-checkout.min.js"></script>
    <div id="conektaIframeContainer" style="height: 90rem; width: 100%;"></div>
    <script>
        //Conekta.setPublishableKey("<?php echo $this->publishable_key ?>");
        let order_button = document.getElementById('place_order');
        if(order_button)
            order_button.style.display = "none";
        /*
        let form_card = document.querySelector('form.checkout,form#order_review');
        Conekta.token.create({
            "card": {
            "number": "4242424242424242",
            "name": "Fulanito Pérez",
            "exp_year": "2020",
            "exp_month": "12",
            "cvc": "123"
            },
            "checkout": {"returns_control_on": ​"Token"}
        }, 
            (tok) => {console.log("TOK", tok)}, (err) => {console.log("ERROR", err)});
        */
        window.ConektaCheckoutComponents.Integration({
            targetIFrame: "#conektaIframeContainer",
            checkoutRequestId: "<?php echo $order->checkout['id'] ?>",
            publicKey: "<?php echo $this->publishable_key ?>",
            paymentMethods: ["Card"],
            options: {
                button: {
                    buttonPayText:  `Pago de $${<?php echo WC()->cart->total ?>}`
                },
                paymentMethodInformation: {
                    bankTransferText:  '',
                    cashText: "",
                    display: true,
                },
                theme: 'default', // 'blue' | 'dark' | 'default' | 'green' | 'red'
                styles: {
                    fontSize: 'baseline', // 'baseline' | 'compact'
                    inputType: 'rounded', // 'basic' | 'rounded' | 'line'
                    buttonType: 'sharp' // 'basic' | 'rounded' | 'sharp'
                }
            },
            onCreateTokenSucceeded: function (token) {},
            onCreateTokenError: function (error) {},
            onFinalizePayment: function(event){
                order_button.click()
            },
            onErrorPayment: function(event) {}
        })
    </script>
<?php else : ?>
    <p class="form-row">
        No se pueden hacer pagos con tarjeta para montos menores a $<?php echo self::MINIMUM_CARD_PAYMENT ?>.
    </p>
<?php endif ?>
<div class="clear"></div> 