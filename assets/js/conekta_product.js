const plansField = function() {
	Array.from(document.querySelectorAll("[class*=_subscription_plans_]")).forEach(element => {
		element.style.display = (document.getElementById('_is_subscription').checked) ? 'block' : 'none'
	});
}
document.getElementById('_is_subscription').onchange = plansField;
window.onload = function() {
	plansField();
}