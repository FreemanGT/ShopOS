/**
 * Product Page — coupon-price notice: live variation price.
 *
 * The notice ships a per-variation discounted-price map (server-rendered
 * wc_price() HTML) on data-shopos-ui-coupon-prices. On WooCommerce's
 * `found_variation` the shown price swaps to the picked variation's entry;
 * `reset_data` restores the base (minimum) price. Products without a map
 * (simple products) never register listeners.
 *
 * Progressive enhancement: without jQuery (WooCommerce ships it wherever the
 * variations form exists) the notice simply keeps its server-rendered price.
 */
( function () {
	'use strict';

	function init() {
		if ( typeof jQuery === 'undefined' ) {
			return;
		}
		var boxes = document.querySelectorAll( '.shopos-ui-coupon-notice[data-shopos-ui-coupon-prices]' );
		if ( ! boxes.length ) {
			return;
		}

		Array.prototype.forEach.call( boxes, function ( box ) {
			var priceEl = box.querySelector( '[data-shopos-ui-coupon-price]' );
			if ( ! priceEl ) {
				return;
			}
			var map;
			try {
				map = JSON.parse( box.getAttribute( 'data-shopos-ui-coupon-prices' ) || '{}' );
			} catch ( e ) {
				return;
			}
			var baseHtml = priceEl.innerHTML;

			// WC triggers these on the variations form; they bubble to body.
			jQuery( document.body )
				.on( 'found_variation', function ( e, variation ) {
					if ( variation && map[ variation.variation_id ] ) {
						priceEl.innerHTML = map[ variation.variation_id ];
					}
				} )
				.on( 'reset_data', function () {
					priceEl.innerHTML = baseHtml;
				} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
