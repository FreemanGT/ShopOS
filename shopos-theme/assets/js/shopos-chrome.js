/**
 * ShopOS Line chrome — mobile nav toggle (theme.template_chrome).
 *
 * Progressive enhancement: the nav is a plain menu without JS; this only wires
 * the hamburger to show/hide it on small screens and keeps aria-expanded in
 * sync. No dependencies.
 */
( function () {
	'use strict';

	function init() {
		var toggle = document.querySelector( '.shopos-chrome__toggle' );
		var nav = document.getElementById( 'shopos-chrome-nav' );
		if ( ! toggle || ! nav ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var open = nav.getAttribute( 'data-open' ) === 'true';
			nav.setAttribute( 'data-open', open ? 'false' : 'true' );
			toggle.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
		} );

		// Close on Escape for keyboard users.
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && nav.getAttribute( 'data-open' ) === 'true' ) {
				nav.setAttribute( 'data-open', 'false' );
				toggle.setAttribute( 'aria-expanded', 'false' );
				toggle.focus();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
