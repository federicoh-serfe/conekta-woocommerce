<?php
/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : http://cristinarandall.com/
 * License : http://cristinarandall.com/
 */
?>


<span class='payment-errors required'></span>
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
            'allowed_payment_methods' => array("bank_transfer"),
            'monthly_installments_enabled' => false,
            'monthly_installments_options' => array(),
            "on_demand_enabled" => false
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
    $checkout = WC()->checkout();
    $order_id = $checkout->create_order($checkout->get_posted_data());
    $this->current_order = new WC_Order($order_id);
    $order = \Conekta\Order::create($validOrderWithCheckout);
        
?>
<script type="text/javascript" src="https://pay.conekta.com/v1.0/js/conekta-checkout.min.js"></script>
<div id="conektaIframeBankContainer" style="height: 70rem; width: 100%;"></div>
<script>
    let order_spei_button = document.getElementById('place_order');
    if(order_spei_button)
        order_spei_button.style.display = "none";
    
    window.ConektaCheckoutComponents.Integration({
        targetIFrame: "#conektaIframeBankContainer",
        checkoutRequestId: "<?php echo $order->checkout['id'] ?>",
        publicKey: "<?php echo $this->publishable_key ?>",
        paymentMethods: ["BankTransfer"],
        options: {
            button: {
                buttonPayText:  `Pago de $${<?php echo WC()->cart->total ?>}`
            },
            paymentMethodInformation: {
                bankTransferText:  '',
                cashText: "<?php echo $this->settings['description']; ?>",
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
        onFinalizePayment: function(event){},
        onErrorPayment: function(event) {}
    })
</script>