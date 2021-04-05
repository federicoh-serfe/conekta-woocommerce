<?php
 /*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://www.conekta.io/es/docs/plugins/woocommerce
 */
?>
<div class="clear"></div>
<span style="width: 100%; float: left; color: red;" class='payment-errors required'></span>
<?php $order_correct = (((float) WC()->cart->total) >= parent::MINIMUM_ORDER_AMOUNT);?>
    <p id="conektaBillingFormErrorMessage"><?php echo ($order_correct) ? $this->lang_options["enter_customer_details"] : $this->lang_options["order_too_little"].parent::MINIMUM_ORDER_AMOUNT.' $'?></p>
<?php if ($order_correct) : ?>
    <div id="conektaIframeContainer" style="width: 100%;"></div>
<?php endif ?>
<script>
    let order_btn_card = document.getElementById("place_order");
    if(order_btn_card && order_btn_card.style.display != "none")
        order_btn_card.style.display = "none";
</script>
<div class="clear"></div> 
