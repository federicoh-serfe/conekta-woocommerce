

jQuery(document).ready(function($){
    const METADATA_LIMIT = 12
    var order_last_valid_selection = $('#woocommerce_conektaoxxopay_order_metadata').val();
    $('#woocommerce_conektaoxxopay_order_metadata').change(function(event){
        let product_selected = $('#woocommerce_conektaoxxopay_product_metadata').children("option:selected").length
        let order_selected = $(this).children("option:selected").length
        if(product_selected + order_selected > METADATA_LIMIT){
            $(this).val(order_last_valid_selection);
        }else{
            $(this).siblings('.description').text(`More than one option can be chosen. (${order_selected} selected)`)
            order_last_valid_selection = $(this).val();
        } 
    })
    var product_last_valid_selection = $('#woocommerce_conektaoxxopay_product_metadata').val();
    $('#woocommerce_conektaoxxopay_product_metadata').change(function(event){
        let product_selected = $(this).children("option:selected").length
        let order_selected = $('#woocommerce_conektaoxxopay_order_metadata').children("option:selected").length
        if(product_selected + order_selected > METADATA_LIMIT){
            $(this).val(product_last_valid_selection);
        }else{
            $(this).siblings('.description').text(`More than one option can be chosen. (${product_selected} selected)`)
            product_last_valid_selection = $(this).val();
        }      
    })
    
    var type = $("#woocommerce_conektaoxxopay_expiration_time :selected").val();
    
    $("#woocommerce_conektaoxxopay_expiration_time").change(function(){
        type = $(this).children("option:selected").val();
    });

    $('#woocommerce_conektaoxxopay_expiration').change(function(){

        var currentValue = parseInt($('#woocommerce_conektaoxxopay_expiration').val())

        if( currentValue<1 || !$.isNumeric(currentValue)){
            $('#woocommerce_conektaoxxopay_expiration').val(1)
        }else{
            if(type=="hours"){
                if(currentValue > 23) $('#woocommerce_conektaoxxopay_expiration').val(23)
            }else{
                if(currentValue > 31) $('#woocommerce_conektaoxxopay_expiration').val(31)
            }

        }

    });

  });