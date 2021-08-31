const checkFields = function(e, type) {
  let valid = true;
  switch (e.name) {
    case `${type}_first_name`: {
        valid = !!e.value;
        break;
    }
    case `${type}_last_name`: {
        valid = !!e.value;
        break;
    }
    case `${type}_address_1`: {
        valid = !!e.value;
        break;
    }
    case `${type}_city`: {
        valid = !!e.value;
        break;
    }
    case `${type}_country`: {
        valid = !!e.value;
        break;
    }
    case `${type}_state`: {
        valid = !!e.value;
        /*&& states.includes(e.value);*/ break;
    }
    case `${type}_postcode`: {
        valid = !!e.value;
        break;
    }
    case `${type}_phone`: {
        valid =
        !!e.value &&
        0 == e.value.replaceAll(/[\s\#0-9_\-\+\/\(\)\.]/g, "").trim().length;
        break;
    }
    case `${type}_email`: {
        valid = !!e.value && /\S+@\S+\.\S+/.test(e.value);
        break;
    }
  }
  return valid;
}

const validate_checkout = function () {
  let valid = true;
  let customerData = Array.from(
      billing_form_card.querySelectorAll("input,select")
  );
  let has_shipping = jQuery('#ship-to-different-address-checkbox').is(":checked");
  customerData.forEach((e) => {
      if (!valid) return;
      valid = checkFields(e, "billing")
      if (valid && has_shipping) {
        valid = checkFields(e, "shipping")
      }
  });
  if (valid) {
        let billing_phone = jQuery("#billing_phone");
        let billing_first_name = jQuery("#billing_first_name");
        let billing_last_name = jQuery("#billing_last_name");
        let billing_email = jQuery("#billing_email");
        let type = "billing"
        if (jQuery('#ship-to-different-address-checkbox').is(":checked")) {
          type = "shipping"
        }
        let shipping_first_name = (type == "billing") ? billing_first_name : jQuery("#shipping_first_name");
        let shipping_last_name = (type == "billing") ? billing_last_name : jQuery("#shipping_last_name");
        let country = jQuery(`#${type}_country`);
        let postcode = jQuery(`#${type}_postcode`);
        let address_1 = jQuery(`#${type}_address_1`);
        let address_2 = jQuery(`#${type}_address_2`);
        let company = jQuery(`#${type}_company`);
        let state = jQuery(`#${type}_state`);
        let city = jQuery(`#${type}_city`);
        let postBody = {
            action: "ckpg_create_order",  
            phone: billing_phone.val(),
            firstName: billing_first_name.val(),
            lastName: billing_last_name.val(),
            city: city.val(),
            email: billing_email.val(),
            country: country.val(),
            postcode: postcode.val(),
            address_1: address_1.val(),
            address_2: address_2.val(),
            company: company.val(),
            state: state.val(),
            shipping_first_name: shipping_first_name.val(),
            shipping_last_name: shipping_last_name.val()
        }
        let error_container = document.getElementById("conektaBillingFormErrorMessage");
        error_container.innerText = tokenize.loading_text
        error_container.style.display = "block";
        let container = document.getElementById("conektaIframeContainer");
        container.style.display = "none";
        jQuery.post(
            tokenize.ajaxurl,
            postBody,
            function (response) {
                if(response.error){
                    error_container.innerText = response.error
                }else{
                    container.innerHTML = '';
                    container.style.display = "block";
                    container.style.height = "90rem";
                    error_container.style.display = "none";
                    window.ConektaCheckoutComponents.Integration({
                        targetIFrame: `#conektaIframeContainer`,
                        checkoutRequestId: response.checkout_id,
                        publicKey: response.key,
                        paymentMethods: ["Cash", "Card", "BankTransfer"],
                        options: {
                        button: {
                            buttonPayText: `Pago de $${response.price}`,
                        },
                        paymentMethodInformation: {
                            bankTransferText: response.spei_text,
                            cashText: response.cash_text,
                            display: true,
                        },
                        theme: "default", // 'blue' | 'dark' | 'default' | 'green' | 'red'
                        styles: {
                            fontSize: "baseline", // 'baseline' | 'compact'
                            inputType: "rounded", // 'basic' | 'rounded' | 'line'
                            buttonType: "sharp", // 'basic' | 'rounded' | 'sharp'
                        },
                        },
                        onFinalizePayment: function (order) {
                          let method = (order && order.charge && order.charge.payment_method) ? order.charge.payment_method.type : null;
                          let charge_id = (order && order.charge) ? order.charge.id : null;
                          let form = jQuery('form[name=checkout]');
                          form.append(jQuery('<input type="hidden" name="conekta_payment_method" id="conekta_payment_method" />').val(method));
                          form.append(jQuery('<input type="hidden" name="charge_id" id="charge_id" />').val(charge_id));
                          switch(method){
                            case "oxxo": 
                              form.append(jQuery('<input type="hidden" name="reference" id="reference" />').val(order.reference)); break;
                            case "spei": 
                              form.append(jQuery('<input type="hidden" name="clabe" id="clabe" />').val(order.reference)); break;
                          }
                          document.getElementById("place_order").click();
                        },
                    });
                }
            }
        )
        .error(function (e) {
          console.error(e)
          error_container.innerText = "ERROR - TRY RELOADING"
        });
    } else {
        let error_container = document.getElementById("conektaBillingFormErrorMessage");
        error_container.style.display = "block";
        error_container.innerText = tokenize.enter_customer_details
        let container = document.getElementById("conektaIframeContainer");
        container.style.display = "none";
    }
};
let billing_form_card = document.getElementById("customer_details");
