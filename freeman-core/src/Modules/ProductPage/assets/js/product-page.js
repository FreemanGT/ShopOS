/**
 * Product Page — designed layout runtime.
 *
 * One job: the mobile sticky add-to-cart bar. The bar slides up once the
 * shopper scrolls past the summary (buy box) and its CTA scrolls back to
 * it — the actual add-to-cart stays the real form (VariationSwatches or the
 * standard WC form), so no purchase logic is duplicated here. Everything
 * else on the page (accordion, gallery, zoom, lightbox) is native
 * <details> / WooCommerce behaviour.
 *
 * Reveal/hide uses IntersectionObserver; without it (or without the bar in
 * the DOM) nothing runs — the page is fully usable either way.
 */
( function () {
	'use strict';

	function init() {
		var bar = document.querySelector( '[data-fm-sticky-bar]' );
		var summary = document.querySelector( '.fm-pdp__summary' );
		if ( ! bar || ! summary || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		// Leave `hidden` (display:none) until the observer owns visibility so
		// the bar never flashes during load; from here the transform + the
		// is-visible class animate it.
		bar.removeAttribute( 'hidden' );

		var observer = new IntersectionObserver(
			function ( entries ) {
				var entry = entries[ 0 ];
				// Show only once the summary has scrolled up out of view —
				// not while it still sits below the fold on page load.
				var passed = ! entry.isIntersecting && entry.boundingClientRect.bottom < 0;
				bar.classList.toggle( 'is-visible', passed );
			},
			{ threshold: 0 }
		);
		observer.observe( summary );

		var cta = bar.querySelector( '[data-fm-sticky-cta]' );
		if ( cta ) {
			cta.addEventListener( 'click', function () {
				var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
				summary.scrollIntoView( { behavior: reduce ? 'auto' : 'smooth', block: 'start' } );
			} );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
