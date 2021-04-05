<?php
/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : http://cristinarandall.com/
 * License : http://cristinarandall.com/
 */
?>


<span class='payment-errors required'></span>
<p id="conektaBillingFormCashErrorMessage" style="display: none">Complete los datos de facturación antes de efectuar el pago.</p>
<div id="conektaIframeCashContainer" style="width: 100%;"></div>
<script>
    let order_btn_cash = document.getElementById("place_order");
    if(order_btn_cash && order_btn_cash.style.display != "none")
        order_btn_cash.style.display = "none";
</script>