jQuery(document).ready(function ($) {

    const METADATA_LIMIT = 12;
    var order_last_valid_selection = $("#woocommerce_conektaspei_order_metadata").val()
    $("#woocommerce_conektaspei_order_metadata").change(function (event) {
        let product_selected = $("#woocommerce_conektaspei_product_metadata").children("option:selected").length;
        let order_selected = $(this).children("option:selected").length;
        if (product_selected + order_selected > METADATA_LIMIT) {
            $(this).val(order_last_valid_selection);
        } else {
            $(this).siblings(".description").text(`More than one option can be chosen. (${order_selected} selected)`);
            order_last_valid_selection = $(this).val();
        }
    });
    var product_last_valid_selection = $("#woocommerce_conektaspei_product_metadata").val();
    $("#woocommerce_conektaspei_product_metadata").change(function (event) {
        let product_selected = $(this).children("option:selected").length;
        let order_selected = $("#woocommerce_conektaspei_order_metadata").children("option:selected").length;
        if (product_selected + order_selected > METADATA_LIMIT) {
            $(this).val(product_last_valid_selection);
        } else {
            $(this).siblings(".description").text(`More than one option can be chosen. (${product_selected} selected)`);
            product_last_valid_selection = $(this).val();
        }
    });

});