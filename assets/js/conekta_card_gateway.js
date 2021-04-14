jQuery(document).ready(function ($) {

    $( 'input#woocommerce_conektacard_meses' ).change(function() {
        if ( $( this ).is( ':checked' ) ) {
            $( '#woocommerce_conektacard_3_months_msi' ).closest( 'tr' ).show();
            $( '#woocommerce_conektacard_6_months_msi' ).closest( 'tr' ).show();
            $( '#woocommerce_conektacard_9_months_msi' ).closest( 'tr' ).show();
            $( '#woocommerce_conektacard_12_months_msi' ).closest( 'tr' ).show();
            $( '#woocommerce_conektacard_18_months_msi' ).closest( 'tr' ).show();
            $( '#woocommerce_conektacard_amount_monthly_install' ).closest( 'tr' ).show();


        } else {
            $( '#woocommerce_conektacard_3_months_msi' ).closest( 'tr' ).hide();
            $( '#woocommerce_conektacard_6_months_msi' ).closest( 'tr' ).hide();
            $( '#woocommerce_conektacard_9_months_msi' ).closest( 'tr' ).hide();
            $( '#woocommerce_conektacard_12_months_msi' ).closest( 'tr' ).hide();
            $( '#woocommerce_conektacard_18_months_msi' ).closest( 'tr' ).hide();
            $( '#woocommerce_conektacard_amount_monthly_install' ).closest( 'tr' ).hide();

        }
    }).change();

    var type = $("#woocommerce_conektacard_expiration_time :selected").val();
    $("#woocommerce_conektacard_expiration_time").change(function(){
        type = $(this).children("option:selected").val();
    });
    $('#woocommerce_conektacard_expiration').change(function(){
        var currentValue = parseInt($('#woocommerce_conektacard_expiration').val())
        if( currentValue<1 || !$.isNumeric(currentValue)){
            $('#woocommerce_conektacard_expiration').val(1)
        }else{
            if(type=="hours"){
                if(currentValue > 23) $('#woocommerce_conektacard_expiration').val(23)
            }else{
                if(currentValue > 31) $('#woocommerce_conektacard_expiration').val(31)
            }
        }
    });

    const METADATA_LIMIT = 12;
    var order_last_valid_selection = $("#woocommerce_conektacard_order_metadata").val();
    $("#woocommerce_conektacard_order_metadata").change(function (event) {
        let product_selected = $("#woocommerce_conektacard_product_metadata").children("option:selected").length;
        let order_selected = $(this).children("option:selected").length;
        if (product_selected + order_selected > METADATA_LIMIT) {
            $(this).val(order_last_valid_selection);
        } else {
            $(this).siblings(".description").text(`More than one option can be chosen. (${order_selected} selected)`);
            order_last_valid_selection = $(this).val();
        }
    });
    var product_last_valid_selection = $("#woocommerce_conektacard_product_metadata").val();
    $("#woocommerce_conektacard_product_metadata").change(function (event) {
        let product_selected = $(this).children("option:selected").length;
        let order_selected = $("#woocommerce_conektacard_order_metadata").children("option:selected").length;
        if (product_selected + order_selected > METADATA_LIMIT) {
            $(this).val(product_last_valid_selection);
        } else {
            $(this).siblings(".description").text(`More than one option can be chosen. (${product_selected} selected)`);
            product_last_valid_selection = $(this).val();
        }
    });

});