<?php
 /*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://www.conekta.io/es/docs/plugins/woocommerce
 */
?>
<div class="clear"></div>
<span style="width: 100%; float: left; color: red;" class='payment-errors required'></span>
<p id="conektaBillingFormErrorMessage" style="display: none">Complete los datos de facturación antes de efectuar el pago.</p>
<div id="conektaIframeContainer" style="width: 100%;"></div>
<script>
    let order_btn_card = document.getElementById("place_order");
    if(order_btn_card && order_btn_card.style.display != "none")
        order_btn_card.style.display = "none";
</script>
<div class="clear"></div> 