/**
 * Product Page — low-stock urgency badge: per-variation reveal.
 *
 * The badge shell ships hidden with a variation-id => message map on
 * data-fm-urgency. On WooCommerce's `found_variation` the badge shows the
 * picked variation's message (or hides when it has none); `reset_data`
 * hides it — the original snippet's behaviour, minus the inline tags.
 */
( function () {
	'use strict';

	function init() {
		if ( typeof jQuery === 'undefined' ) {
			return;
		}
		var boxes = document.querySelectorAll( '.fm-stock-urgency[data-fm-urgency]' );
		if ( ! boxes.length ) {
			return;
		}

		Array.prototype.forEach.call( boxes, function ( box ) {
			var textEl = box.querySelector( '[data-fm-urgency-text]' );
			if ( ! textEl ) {
				return;
			}
			var map;
			try {
				map = JSON.parse( box.getAttribute( 'data-fm-urgency' ) || '{}' );
			} catch ( e ) {
				return;
			}

			// WC triggers these on the variations form; they bubble to body.
			jQuery( document.body )
				.on( 'found_variation', function ( e, variation ) {
					if ( variation && map[ variation.variation_id ] ) {
						textEl.textContent = map[ variation.variation_id ];
						box.removeAttribute( 'hidden' );
					} else {
						box.setAttribute( 'hidden', '' );
					}
				} )
				.on( 'reset_data', function () {
					box.setAttribute( 'hidden', '' );
				} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
