<?php
/**
 * Side Cart — storefront wiring.
 *
 * Head-enqueues the drawer assets (FOUC-free — the 1.24.11 precedent), prints
 * the drawer shell once in the footer on every front-end page (so an add-to-cart
 * from anywhere can open it), and registers the drawer body as a WooCommerce
 * cart fragment so stores already refreshing fragments keep it live without a
 * round-trip.
 *
 * Only constructed when the module is enabled (Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\SideCart;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
final class Frontend {

	const HANDLE = 'shopos-core-side-cart';

	/**
	 * The selector the drawer body lives at — also the WC cart-fragment key, so
	 * the shell container and the fragment stay in lockstep.
	 */
	const BODY_SELECTOR = '.shopos-side-cart__body';

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_footer', array( $this, 'render_drawer_shell' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragments' ) );
	}

	/**
	 * Head-enqueue the drawer assets on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/side-cart.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/side-cart.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);

		wp_localize_script( self::HANDLE, 'ShopOSSideCart', $this->localized_payload() );
	}

	/**
	 * Build the ShopOSSideCart JS payload. Extracted so PHPUnit can assert the
	 * shape (InfiniteScroll precedent).
	 *
	 * @return array<string,mixed>
	 */
	public function localized_payload() {
		return array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'action'           => Ajax::ACTION,
			'nonce'            => wp_create_nonce( Ajax::NONCE ),
			'bodySelector'     => self::BODY_SELECTOR,
			// Cart links that should open the drawer instead of navigating.
			'cartLinkSelectors' => 'a.shopos-cart-link, a.cart-contents, a.wc-block-mini-cart__button, .widget_shopping_cart a.button.wc-forward',
			'labels'           => array(
				'loading' => Labels::get( 'loading' ),
				'error'   => Labels::get( 'error' ),
				'removed' => Labels::get( 'removed' ),
				'undo'    => Labels::get( 'undo' ),
			),
		);
	}

	/**
	 * WooCommerce cart-fragment: keep the drawer body current on stores that
	 * refresh fragments after add-to-cart.
	 *
	 * @param array<string,string> $fragments Existing fragments.
	 * @return array<string,string>
	 */
	public function cart_fragments( $fragments ) {
		if ( ! is_array( $fragments ) ) {
			$fragments = array();
		}
		$fragments[ self::BODY_SELECTOR ] = $this->body_container_html( $this->module->render_body() );

		/**
		 * Filter the side-cart fragment map.
		 *
		 * @since 1.55.0
		 * @param array<string,string> $fragments Fragment selector => HTML.
		 * @param Module               $module    Owning module.
		 */
		return (array) apply_filters( 'shopos_core/side_cart/fragments', $fragments, $this->module );
	}

	/**
	 * Print the drawer shell once in the footer (front end only), with the body
	 * pre-rendered so first paint carries the current cart.
	 */
	public function render_drawer_shell() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		echo $this->drawer_shell_html( $this->module->render_body() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts + a rendered template.
	}

	/**
	 * The body container wrapper — the exact element WC's fragment refresh
	 * replaces (its selector is BODY_SELECTOR). Pure.
	 *
	 * @param string $body_html Rendered drawer body.
	 * @return string
	 */
	public function body_container_html( $body_html ) {
		return '<div class="shopos-side-cart__body" data-shopos-sc-body aria-live="polite">' . $body_html . '</div>';
	}

	/**
	 * Drawer shell markup. Pure — unit-tested (the body is passed in).
	 *
	 * @param string $body_html Rendered drawer body.
	 * @return string
	 */
	public function drawer_shell_html( $body_html = '' ) {
		$heading = Labels::get( 'heading' );
		$close   = Labels::get( 'close' );

		return '<div class="shopos-side-cart woocommerce" id="shopos-side-cart" aria-hidden="true">'
			. '<div class="shopos-side-cart__overlay" data-shopos-sc-close></div>'
			. '<aside class="shopos-side-cart__panel" role="dialog" aria-modal="true" aria-label="' . esc_attr( $heading ) . '" tabindex="-1">'
			. '<header class="shopos-side-cart__head">'
			. '<h2 class="shopos-side-cart__title">' . esc_html( $heading ) . '</h2>'
			. '<button type="button" class="shopos-side-cart__close shopos-ui-iconbtn" data-shopos-sc-close aria-label="' . esc_attr( $close ) . '">'
			. '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2 2L12 12M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>'
			. '</button>'
			. '</header>'
			. $this->body_container_html( $body_html )
			. '</aside>'
			. '</div>';
	}
}
