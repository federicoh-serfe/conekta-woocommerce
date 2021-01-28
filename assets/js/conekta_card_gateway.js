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


});