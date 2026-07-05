/**
 * Product Page — designed layout runtime.
 *
 * Four small jobs, all progressive enhancement (the page is fully usable
 * with none of them):
 *
 * 1. Mobile sticky add-to-cart bar — slides up once the shopper scrolls
 *    past the summary; its CTA scrolls back to the real buy box. DEFERS
 *    entirely when VariationSwatches' own sticky bar is in the DOM
 *    (.etucart-sticky-bar) — two bars must never stack (the CSS carries a
 *    :has() belt for the same rule).
 * 2. Sticky-bar price sync — follows the picked variation via WooCommerce's
 *    found_variation / reset_data events.
 * 3. Gallery dot rail (mobile) — the editorial gallery is a scroll-snap
 *    strip; dots reflect and drive the scroll position.
 * 4. Variation image-swap feedback — a quick fade on the main gallery image
 *    when WC swaps its source (reduced-motion collapses it in CSS).
 */
( function () {
	'use strict';

	function initStickyBar() {
		var bar = document.querySelector( '[data-fm-sticky-bar]' );
		if ( ! bar ) {
			return;
		}

		// VariationSwatches owns the mobile sticky role on its products.
		if ( document.querySelector( '.etucart-sticky-bar' ) ) {
			return;
		}

		var summary = document.querySelector( '.fm-pdp__summary' );
		if ( ! summary || ! ( 'IntersectionObserver' in window ) ) {
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

		// Price follows the picked variation (server-rendered price_html in
		// WC's found_variation payload).
		var priceEl = bar.querySelector( '.fm-pdp__sticky-price' );
		if ( priceEl && typeof jQuery !== 'undefined' ) {
			var baseHtml = priceEl.innerHTML;
			jQuery( document.body )
				.on( 'found_variation', function ( e, variation ) {
					if ( variation && variation.price_html ) {
						priceEl.innerHTML = variation.price_html;
					}
				} )
				.on( 'reset_data', function () {
					priceEl.innerHTML = baseHtml;
				} );
		}
	}

	function initGalleryDots() {
		var gallery = document.querySelector( '.fm-pdp__gallery' );
		var strip = gallery && gallery.querySelector( '.woocommerce-product-gallery__wrapper' );
		if ( ! strip ) {
			return;
		}
		var slides = strip.querySelectorAll( '.woocommerce-product-gallery__image' );
		if ( slides.length < 2 ) {
			return;
		}

		var rail = document.createElement( 'div' );
		rail.className = 'fm-pdp__gallery-dots';
		var dots = [];
		Array.prototype.forEach.call( slides, function ( slide, i ) {
			var dot = document.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'fm-pdp__gallery-dot' + ( 0 === i ? ' is-active' : '' );
			dot.setAttribute( 'aria-label', String( i + 1 ) + ' / ' + String( slides.length ) );
			dot.addEventListener( 'click', function () {
				var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
				slide.scrollIntoView( { behavior: reduce ? 'auto' : 'smooth', block: 'nearest', inline: 'center' } );
			} );
			rail.appendChild( dot );
			dots.push( dot );
		} );
		strip.parentNode.insertBefore( rail, strip.nextSibling );

		var ticking = false;
		strip.addEventListener(
			'scroll',
			function () {
				if ( ticking ) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame( function () {
					ticking = false;
					var index = Math.round( Math.abs( strip.scrollLeft ) / Math.max( 1, strip.clientWidth ) );
					index = Math.min( index, dots.length - 1 );
					dots.forEach( function ( dot, i ) {
						dot.classList.toggle( 'is-active', i === index );
					} );
				} );
			},
			{ passive: true }
		);
	}

	function initImageSwapFeedback() {
		if ( typeof jQuery === 'undefined' ) {
			return;
		}
		var main = document.querySelector( '.fm-pdp__gallery .woocommerce-product-gallery__image' );
		if ( ! main ) {
			return;
		}
		jQuery( document.body ).on( 'found_variation reset_data', function () {
			main.classList.remove( 'fm-img-swap' );
			// Force a restart so consecutive picks re-fire the animation.
			void main.offsetWidth;
			main.classList.add( 'fm-img-swap' );
		} );
		main.addEventListener( 'animationend', function () {
			main.classList.remove( 'fm-img-swap' );
		} );
	}

	function init() {
		initStickyBar();
		initGalleryDots();
		initImageSwapFeedback();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
