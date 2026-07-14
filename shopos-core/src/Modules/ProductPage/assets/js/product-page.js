/**
 * Product Page — designed layout runtime.
 *
 * Five small jobs, all progressive enhancement (the page is fully usable
 * with none of them):
 *
 * 1. Mobile sticky add-to-cart bar — slides up once the shopper scrolls
 *    past the summary; its CTA scrolls back to the real buy box. DEFERS
 *    entirely when VariationSwatches' own sticky bar is in the DOM
 *    (.shopos-sticky-bar) — two bars must never stack (the CSS carries a
 *    :has() belt for the same rule). While visible it toggles
 *    body.fm-pdp-sticky-active so the CSS reserves bottom space for it.
 * 2. Sticky-bar price sync — follows the picked variation via WooCommerce's
 *    found_variation / reset_data events.
 * 3. Gallery scroll-progress bar — the gallery is a swipeable scroll-snap
 *    strip at every breakpoint; a slim fill tracks its scroll position.
 * 4. Gallery interaction — a capture-phase click handler that kills any
 *    lightbox (WC PhotoSwipe / theme / Elementor) and the raw-image
 *    navigation (the carousel is swipe/drag-only, a click opens nothing),
 *    plus mouse click-drag-to-scroll on desktop (which has no touch swipe).
 * 5. Variation image-swap feedback — a quick fade on the main gallery image
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
		if ( document.querySelector( '.shopos-sticky-bar' ) ) {
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
				// Reserve bottom space only while our bar is the active one
				// (this whole init early-returns on VS products, so VS's own
				// reservation stays the single source — no double padding).
				document.body.classList.toggle( 'fm-pdp-sticky-active', passed );
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

	function initGalleryProgress() {
		var gallery = document.querySelector( '.fm-pdp__gallery' );
		var strip = gallery && gallery.querySelector( '.woocommerce-product-gallery__wrapper' );
		if ( ! strip ) {
			return;
		}
		var slides = strip.querySelectorAll( '.woocommerce-product-gallery__image' );
		if ( slides.length < 2 ) {
			return;
		}

		var track = document.createElement( 'div' );
		track.className = 'fm-pdp__gallery-progress';
		var fill = document.createElement( 'div' );
		fill.className = 'fm-pdp__gallery-progress__fill';
		track.appendChild( fill );
		strip.parentNode.insertBefore( track, strip.nextSibling );

		function update() {
			// scrollWidth − clientWidth (not clientWidth multiples) is gap-proof,
			// and Math.abs(scrollLeft) keeps it correct under RTL's negative scroll.
			var max = strip.scrollWidth - strip.clientWidth;
			var fraction = max > 0 ? Math.min( Math.abs( strip.scrollLeft ) / max, 1 ) : 0;
			// Floor at a small resting fill so the bar reads as a progress
			// indicator before the shopper scrolls (owner request).
			fraction = Math.max( fraction, 0.14 );
			fill.style.inlineSize = ( fraction * 100 ) + '%';
		}
		update();

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
					update();
				} );
			},
			{ passive: true }
		);
	}

	function initGalleryInteraction() {
		var wrapper = document.querySelector( '.fm-pdp__gallery .woocommerce-product-gallery__wrapper' );
		if ( ! wrapper ) {
			return;
		}

		// Capture-phase click kill: runs before — and prevents — any lightbox
		// handler (WC PhotoSwipe, the theme's, Elementor's), whichever is bound
		// and wherever it bubbles from, plus the raw <a href="full.jpg">
		// navigation. The gallery is a swipe/drag carousel, so a click never
		// opens anything. Hover-magnify (jquery.zoom) binds mousemove, not
		// click, so it's untouched.
		wrapper.addEventListener(
			'click',
			function ( e ) {
				if ( e.target && e.target.closest && e.target.closest( '.woocommerce-product-gallery__image' ) ) {
					e.preventDefault();
					e.stopPropagation();
				}
			},
			true
		);

		// Desktop has no touch swipe, so drag the strip with the mouse.
		var desktop = window.matchMedia ? window.matchMedia( '(min-width: 1024px)' ) : null;
		if ( ! desktop || ! desktop.matches || ! ( 'PointerEvent' in window ) ) {
			return;
		}

		var down = false;
		var startX = 0;
		var startScroll = 0;

		wrapper.addEventListener( 'pointerdown', function ( e ) {
			if ( 'touch' === e.pointerType ) {
				return; // native touch scroll already handles it
			}
			down = true;
			startX = e.clientX;
			startScroll = wrapper.scrollLeft;
			wrapper.classList.add( 'is-grabbing' );
			try {
				wrapper.setPointerCapture( e.pointerId );
			} catch ( err ) {}
		} );

		wrapper.addEventListener( 'pointermove', function ( e ) {
			if ( ! down ) {
				return;
			}
			// scrollLeft = startScroll − delta makes the strip follow the
			// pointer in both LTR and RTL (scrollLeft is a viewport offset, so
			// the sign convention is direction-agnostic here).
			wrapper.scrollLeft = startScroll - ( e.clientX - startX );
		} );

		function endDrag() {
			if ( ! down ) {
				return;
			}
			down = false;
			wrapper.classList.remove( 'is-grabbing' ); // re-engages scroll-snap → settles on the nearest slide
		}
		wrapper.addEventListener( 'pointerup', endDrag );
		wrapper.addEventListener( 'pointercancel', endDrag );
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
		initGalleryProgress();
		initGalleryInteraction();
		initImageSwapFeedback();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
