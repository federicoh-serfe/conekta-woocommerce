const plansField = function() {
	document.getElementById('_subscription_plans').parentElement.style.display = (document.getElementById('_is_subscription').checked) ? 'block' : 'none'
}
document.getElementById('_is_subscription').onchange = plansField;
window.onload = function() {
	plansField();
}