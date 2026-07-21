/**
 * Side Cart drawer — progressive enhancement.
 *
 * Opens on WooCommerce's `added_to_cart` event and when a cart link is clicked;
 * every mutation (coupon apply/remove, item remove/restore) posts to the single
 * side-cart endpoint, which returns the freshly rendered body. No jQuery
 * required for the drawer itself; the WC `added_to_cart` hook is bound through
 * jQuery only when it is present (that is the event WC fires).
 */
( function () {
	'use strict';

	var CFG = window.ShopOSSideCart || {};
	var root, panel, body;

	function qs( sel, ctx ) {
		return ( ctx || document ).querySelector( sel );
	}

	function open() {
		if ( ! root ) {
			return;
		}
		root.setAttribute( 'aria-hidden', 'false' );
		document.documentElement.classList.add( 'shopos-sc-open' );
		if ( panel ) {
			panel.focus();
		}
	}

	function close() {
		if ( ! root ) {
			return;
		}
		root.setAttribute( 'aria-hidden', 'true' );
		document.documentElement.classList.remove( 'shopos-sc-open' );
	}

	function isOpen() {
		return root && 'false' === root.getAttribute( 'aria-hidden' );
	}

	/**
	 * POST an op to the endpoint and swap the body with the returned HTML.
	 *
	 * @param {Object} params Extra params (op, coupon, cart_item_key).
	 * @return {Promise}
	 */
	function request( params ) {
		if ( ! CFG.ajaxUrl || ! body ) {
			return Promise.resolve();
		}
		root.classList.add( 'is-busy' );

		var data = new URLSearchParams();
		data.append( 'action', CFG.action );
		data.append( '_ajax_nonce', CFG.nonce );
		Object.keys( params || {} ).forEach( function ( k ) {
			data.append( k, params[ k ] );
		} );

		return fetch( CFG.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data.toString()
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( res && res.success && res.data && 'string' === typeof res.data.html ) {
					// The endpoint returns the inner body markup; the fragment
					// wrapper stays put so its bindings persist.
					body.innerHTML = res.data.html;
				}
			} )
			.catch( function () {
				/* Network error — leave the current body in place. */
			} )
			.then( function () {
				root.classList.remove( 'is-busy' );
			} );
	}

	function refresh() {
		return request( { op: 'refresh' } );
	}

	// --- Delegated interactions ------------------------------------------
	function onClick( e ) {
		var el = e.target.closest ? e.target.closest( '[data-shopos-sc-close],[data-shopos-sc-remove],[data-shopos-sc-restore],[data-shopos-sc-remove-coupon]' ) : null;

		// Open on a cart link — before the closest() check so links anywhere work.
		if ( CFG.cartLinkSelectors ) {
			var link = e.target.closest ? e.target.closest( CFG.cartLinkSelectors ) : null;
			if ( link ) {
				e.preventDefault();
				open();
				refresh();
				return;
			}
		}

		if ( ! el ) {
			return;
		}

		if ( el.hasAttribute( 'data-shopos-sc-close' ) ) {
			e.preventDefault();
			close();
		} else if ( el.hasAttribute( 'data-shopos-sc-remove' ) ) {
			e.preventDefault();
			request( { op: 'remove_item', cart_item_key: el.getAttribute( 'data-shopos-sc-remove' ) } );
		} else if ( el.hasAttribute( 'data-shopos-sc-restore' ) ) {
			e.preventDefault();
			request( { op: 'restore_item', cart_item_key: el.getAttribute( 'data-shopos-sc-restore' ) } );
		} else if ( el.hasAttribute( 'data-shopos-sc-remove-coupon' ) ) {
			e.preventDefault();
			request( { op: 'remove_coupon', coupon: el.getAttribute( 'data-shopos-sc-remove-coupon' ) } );
		}
	}

	function onSubmit( e ) {
		var form = e.target.closest ? e.target.closest( '[data-shopos-sc-coupon-form]' ) : null;
		if ( ! form ) {
			return;
		}
		e.preventDefault();
		var input = qs( '[data-shopos-sc-coupon]', form );
		var code = input ? input.value.trim() : '';
		if ( code ) {
			request( { op: 'apply_coupon', coupon: code } );
		}
	}

	function onKeydown( e ) {
		if ( 'Escape' === e.key && isOpen() ) {
			close();
		}
	}

	function init() {
		root = qs( '#shopos-side-cart' );
		if ( ! root ) {
			return;
		}
		panel = qs( '.shopos-side-cart__panel', root );
		body = qs( CFG.bodySelector || '.shopos-side-cart__body', root );

		document.addEventListener( 'click', onClick );
		document.addEventListener( 'submit', onSubmit );
		document.addEventListener( 'keydown', onKeydown );

		// WC fires `added_to_cart` via jQuery on document.body after an AJAX add.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart', function () {
				open();
				refresh();
			} );
		}

		// Expose a tiny API for other modules (e.g. ProductSlider) to open us.
		window.ShopOSSideCartOpen = function () {
			open();
			refresh();
		};
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
