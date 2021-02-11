

jQuery(document).ready(function($){
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