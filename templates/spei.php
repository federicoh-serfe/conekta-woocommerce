<?php
/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : http://cristinarandall.com/
 * License : http://cristinarandall.com/
 */
?>


<span class='payment-errors required'></span>

<script type="text/javascript" src="https://pay.conekta.com/v1.0/js/conekta-checkout.min.js"></script>
<p id="conektaBillingFormSpeiErrorMessage" style="display: none">Complete los datos de facturación antes de efectuar el pago.</p>
<div id="conektaIframeBankContainer" style="width: 100%;"></div>
<script>
    let order_btn_spei = document.getElementById("place_order");
    if(order_btn_spei && order_btn_spei.style.display != "none")
        order_btn_spei.style.display = "none";
</script>
