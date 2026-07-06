/**
 * Product Page — designed layout runtime.
 *
 * Five small jobs, all progressive enhancement (the page is fully usable
 * with none of them):
 *
 * 1. Mobile sticky add-to-cart bar — slides up once the shopper scrolls
 *    past the summary; its CTA scrolls back to the real buy box. DEFERS
 *    entirely when VariationSwatches' own sticky bar is in the DOM
 *    (.etucart-sticky-bar) — two bars must never stack (the CSS carries a
 *    :has() belt for the same rule). While visible it toggles
 *    body.fm-pdp-sticky-active so the CSS reserves bottom space for it.
 * 2. Sticky-bar price sync — follows the picked variation via WooCommerce's
 *    found_variation / reset_data events.
 * 3. Gallery scroll-progress bar (mobile) — the gallery is a swipeable
 *    scroll-snap strip; a slim fill tracks its horizontal scroll position.
 * 4. Gallery click guard — the lightbox is disabled, so block the raw-image
 *    navigation on gallery image links; hover-zoom stays the interaction.
 * 5. Gallery click-to-swap (desktop thumbnail row only) — clicking a
 *    thumbnail trades places with the hero (first) slot (Wave 9.3 owner
 *    request). The swap moves attributes, not nodes, so WC's variation image
 *    update (which always targets the FIRST gallery image's .wp-post-image)
 *    keeps working.
 * 6. Variation image-swap feedback — a quick fade on the main gallery image
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

	function initGalleryClickGuard() {
		var gallery = document.querySelector( '.fm-pdp__gallery' );
		if ( ! gallery ) {
			return;
		}
		// The lightbox theme support is removed, so WC's PhotoSwipe init — the
		// only place it preventDefault()s the gallery <a href="full.jpg"> — never
		// runs. Without this a click would navigate to the raw image file; block
		// it so the hover-magnify (jquery.zoom) is the interaction.
		gallery.addEventListener( 'click', function ( e ) {
			var link = e.target && e.target.closest
				? e.target.closest( '.woocommerce-product-gallery__image a' )
				: null;
			if ( link ) {
				e.preventDefault();
			}
		} );
	}

	// Trade every attribute except `class` between two elements. Keeping the
	// classes in place preserves .wp-post-image on the main slot — the marker
	// WC's wc_variations_image_update targets — while src/srcset/data-* travel.
	function swapAttributes( a, b ) {
		if ( ! a || ! b ) {
			return;
		}
		var snapshot = function ( el ) {
			var out = {};
			Array.prototype.forEach.call( el.attributes, function ( attr ) {
				if ( 'class' !== attr.name ) {
					out[ attr.name ] = attr.value;
				}
			} );
			return out;
		};
		var attrsA = snapshot( a );
		var attrsB = snapshot( b );
		Object.keys( attrsA ).forEach( function ( name ) {
			a.removeAttribute( name );
		} );
		Object.keys( attrsB ).forEach( function ( name ) {
			b.removeAttribute( name );
		} );
		Object.keys( attrsB ).forEach( function ( name ) {
			a.setAttribute( name, attrsB[ name ] );
		} );
		Object.keys( attrsA ).forEach( function ( name ) {
			b.setAttribute( name, attrsA[ name ] );
		} );
	}

	function initGalleryClickSwap() {
		var wrapper = document.querySelector( '.fm-pdp__gallery .woocommerce-product-gallery__wrapper' );
		if ( ! wrapper || ! window.matchMedia ) {
			return;
		}
		var desktop = window.matchMedia( '(min-width: 1024px)' );

		wrapper.addEventListener( 'click', function ( e ) {
			// Mobile is a swipe carousel (one image at a time) — a
			// tap-teleport would only disorient; the swap is a desktop
			// thumbnail-row affordance (click a thumb → it becomes the hero).
			if ( ! desktop.matches || ! e.target || ! e.target.closest ) {
				return;
			}
			var clicked = e.target.closest( '.woocommerce-product-gallery__image' );
			var main = wrapper.querySelector( '.woocommerce-product-gallery__image' );
			if ( ! clicked || ! main || clicked === main ) {
				return;
			}

			swapAttributes( main.querySelector( 'img' ), clicked.querySelector( 'img' ) );
			swapAttributes( main.querySelector( 'a' ), clicked.querySelector( 'a' ) );
			// The wrapper divs keep their placement; only the thumb pointer
			// (WC reads it for variation resets) travels with the image.
			swapAttributes( main, clicked );

			[ main, clicked ].forEach( function ( el ) {
				el.classList.remove( 'fm-img-swap' );
				void el.offsetWidth;
				el.classList.add( 'fm-img-swap' );
			} );

			// The hover-zoom clone caches the pre-swap source — rebuild it.
			if ( typeof jQuery !== 'undefined' ) {
				jQuery( '.woocommerce-product-gallery' ).trigger( 'woocommerce_gallery_init_zoom' );
			}
		} );
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
		initGalleryClickGuard();
		initGalleryClickSwap();
		initImageSwapFeedback();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
