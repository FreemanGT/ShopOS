/**
 * ShopOS Line — cart page (theme.template_cart, §11-B surface 2).
 *
 * Defensive progressive enhancement ONLY. WooCommerce's own cart.js owns qty
 * change-detection, coupon/remove AJAX and cart fragments; this never
 * duplicates or fights it. It adds one thing WC's non-AJAX cart POST lacks: a
 * busy state on submit so a double-click can't double-post (apply-coupon /
 * update-cart / calc-shipping all full-page-reload). Guarded so it silently
 * no-ops if the markup isn't present. Enqueued only on the theme-owned cart.
 */
( function () {
	'use strict';

	var cart = document.querySelector( '.shopos-cart' );
	if ( ! cart ) {
		return;
	}

	// One busy-guard per form: on submit, disable the submit controls and flag
	// the form so CSS can dim it. The page reloads on success, clearing state;
	// a validation bounce re-renders the form fresh, so no manual reset needed.
	cart.addEventListener(
		'submit',
		function ( e ) {
			var form = e.target;
			if ( ! ( form instanceof HTMLFormElement ) || form.classList.contains( 'is-busy' ) ) {
				return;
			}
			form.classList.add( 'is-busy' );
			var buttons = form.querySelectorAll( 'button[type="submit"], input[type="submit"]' );
			for ( var i = 0; i < buttons.length; i++ ) {
				buttons[ i ].setAttribute( 'aria-disabled', 'true' );
			}
		},
		// Capture phase so we flag before WC's own submit handlers run; we never
		// preventDefault, so WC's handling (and the normal POST) is untouched.
		true
	);
} )();
