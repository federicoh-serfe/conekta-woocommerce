const plansField = function() {
	Array.from(document.querySelectorAll("[class*=_subscription_plans_]")).forEach(element => {
		element.style.display = (document.getElementById('_is_subscription').checked) ? 'block' : 'none'
	});
}
document.getElementById('_is_subscription').onchange = plansField;
window.onload = function() {
	plansField();
}
let plans = conekta_product.plans;
let variants = conekta_product.variants;
let selection = {};

const updateVariableProducts = function(){
	jQuery("[class*=_subscription_plans_]").each(function(){
		let select = jQuery(this).find('select');
		let id = select.attr('id')
		let value = select.val();
		if(value){
			selection[id] = value;
		}
		jQuery(this).remove()
	})
	let variations = jQuery('.woocommerce_variation h3');
	jQuery('#_is_subscription').prop('disabled', (variations.length === 0 && !variants))
	variations.each(function() {
		let text_id = jQuery(this).find('strong').text();
		let product_name = jQuery('#title').val();
		let id = parseInt(text_id.substring(1));
		let variation_id = '_subscription_plans_' + id + '_field';
		let variation_name = [];
		jQuery(this).find('select').each(function(){
			variation_name.push(jQuery(this).val());
		})
		variation_name = variation_name.join(', ')
		let label = jQuery('<label></label>').attr('for', variation_id).text(product_name + ' - ' + variation_name);
		let select = jQuery('<select></select>').attr( 'name', variation_id ).attr( 'id', variation_id ).addClass( 'select' ).addClass( 'short' );
		Object.keys(plans).forEach( key => {
			select.append(jQuery('<option></option>').attr('value', key).text(plans[key]));
		})
		if (selection[variation_id])
			select.val(selection[variation_id])
		let span = jQuery('<span></span>').addClass( 'description' ).text(conekta_product.plans_desc);
		let variation = jQuery('<p></p>').addClass( variation_id ).addClass( 'form-field' )
		variation.append(label).append(select).append(span);
		jQuery('#conekta_subscriptions_inner').append(variation);
	})
}

const updateSimpleProducts = function(){
	jQuery("[class*=_subscription_plans_]").each(function(){
		let select = jQuery(this).find('select');
		let id = select.attr('id')
		let value = select.val();
		if(value){
			selection[id] = value;
		}
		jQuery(this).remove()
	})
	jQuery('#_is_subscription').prop('disabled', false)
	let product_name = jQuery('#title').val();
	let id = '_subscription_plans_field';
	let label = jQuery('<label></label>').attr('for', id ).text('Plan: ' + product_name);
	let select = jQuery('<select></select>').attr( 'name', id ).attr( 'id', id ).addClass( 'select' ).addClass( 'short' );
	Object.keys(plans).forEach( key => {
		select.append(jQuery('<option></option>').attr('value', key).text(plans[key]));
	})
	if (selection[id])
		select.val(selection[id])
	let span = jQuery('<span></span>').addClass( 'description' ).text(conekta_product.plans_desc);
	let product = jQuery('<p></p>').addClass( id ).addClass( 'form-field' )
	product.append(label).append(select).append(span);
	jQuery('#conekta_subscriptions_inner').append(product);
}

const containsAll = (arr1, arr2) => arr2.every(arr2Item => arr1.includes(arr2Item))
const sameMembers = (arr1, arr2) => containsAll(arr1, arr2) && containsAll(arr2, arr1);

const variantsMissing = function(){
	return jQuery('.woocommerce_variation h3').length === 0;
}

jQuery('.conekta_subscriptions_tab').click(function(){
	let type = jQuery('#product-type').val();
	if ( type === 'variable' ){
		if(variantsMissing())
			return;
		updateVariableProducts()
	} else if ( ['external', 'simple'].includes( type ) ) {
		updateSimpleProducts()
	}
	plansField();
})

jQuery('.save-variation-changes').click(function(){
	updateVariableProducts()
})

jQuery('#product-type').change(function(){
	let type = jQuery(this).val();
	if ( type === 'variable' ){
		if(variantsMissing())
			return;
		updateVariableProducts()
	} else if ( ['external', 'simple'].includes( type ) ) {
		updateSimpleProducts()
	}
	plansField();
})

jQuery('#title').change(function(){
	let new_title = jQuery(this).val()
	jQuery("[class*=_subscription_plans_]").find('label').each(function(){
		let current_text = jQuery(this).text();
		let change = '';
		if (current_text.startsWith('Plan: ')){
			change = current_text.concat(new_title)
		} else {
			splitted = current_text.split('-');
			splitted[0] = new_title + ' '
			change = splitted.join('-')
		}
		jQuery(this).text(change);
	})
})