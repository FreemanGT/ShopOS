<?php
/**
 * Page Transitions module.
 *
 * Smooths the full-page reloads the storefront's result surfaces run on
 * (Shop Filters reload transport, product search submits, grid pagination)
 * with two composable layers, chosen by the owner from a live comparison
 * demo (2026-07-14):
 *
 *   A. A loading overlay — the moment a filter / search / pagination
 *      interaction starts a navigation, the page dims under a scrim with a
 *      spinner card, so the wait has instant feedback in every browser.
 *      Exposed as `window.FreemanPageTransitions.show()` so other modules
 *      (ShopFilters `navigate()`) can trigger it without a hard dependency.
 *
 *   B. Cross-document View Transitions — `@view-transition{navigation:auto}`
 *      makes supporting browsers (Chrome/Edge/Safari) cross-fade the old
 *      page into the new one on every same-origin navigation instead of
 *      flashing. Pure CSS progressive enhancement; Firefox ignores it.
 *
 * Both honour prefers-reduced-motion (the fade is disabled, the overlay
 * stays as static feedback). The module-enable toggle is the kill-switch;
 * a new module is absent from `freeman_core_modules` → off by default.
 *
 * The overlay/trigger behaviour is JS+CSS (live-QA); the enqueue wiring is
 * unit-tested.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\PageTransitions;

use Freeman\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	const HANDLE = 'freeman-core-page-transitions';

	/**
	 * @return string
	 */
	public function id() {
		return 'page_transitions';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Page Transitions', 'freeman-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Smooth page reloads: a loading overlay on filter / search / pagination interactions, plus a cross-fade between pages in supporting browsers.', 'freeman-core' );
	}

	/**
	 * No dependencies — the overlay and fade are generic front-end behaviour.
	 *
	 * @return array
	 */
	public function dependencies() {
		return array();
	}

	/**
	 * @return array
	 */
	public function settings_schema() {
		return array(
			'loading_label' => array(
				'label'       => __( 'Loading overlay text', 'freeman-core' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Shown under the spinner while the next page loads. Leave blank for the English default (Loading…).', 'freeman-core' ),
			),
		);
	}

	/**
	 * Boot hooks.
	 */
	public function boot() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'print_expect_link' ), 1 );
		add_action( 'wp_footer', array( $this, 'print_ready_marker' ), 9999 );
	}

	/**
	 * Print a render-blocking `rel=expect` link (Chrome 124+; other browsers
	 * ignore it). Without it the cross-document fade captures the new page at
	 * its FIRST paint — often before the product grid has rendered — so the
	 * animation landed on a mostly-white frame and the content popped in
	 * after. Blocking the first paint until the end-of-body marker (below)
	 * is parsed makes the fade land on real content; during the wait the
	 * browser keeps showing the frozen old page (dimmed under the overlay).
	 */
	public function print_expect_link() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		echo '<link rel="expect" href="#fpt-ready" blocking="render">' . "\n";
	}

	/**
	 * The end-of-body marker `print_expect_link()` waits for. Parsing it
	 * releases the render block; if anything strips it, the block is
	 * released at end-of-parse anyway (spec behaviour) — no hang risk.
	 */
	public function print_ready_marker() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		echo '<div id="fpt-ready" hidden></div>' . "\n";
	}

	/**
	 * Enqueue assets on the front end (skipping admin/feed). The CSS must be
	 * in <head>: `@view-transition` opts the *document* in, and the overlay
	 * styles have to exist before the first interaction.
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->asset_min_url( 'css/page-transitions.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->asset_min_url( 'js/page-transitions.js' ),
			array(),
			FREEMAN_CORE_VERSION,
			true
		);

		wp_localize_script( self::HANDLE, 'FreemanPTConfig', $this->localized_payload() );
	}

	/**
	 * Build the FreemanPTConfig JS payload. Extracted so PHPUnit can assert
	 * the shape (Search / QuickView precedent). A blank setting falls back to
	 * a locale-aware default (He/En — the VariationSwatches Labels pattern).
	 *
	 * @return array<string,string>
	 */
	public function localized_payload() {
		$label = trim( (string) $this->get_option( 'loading_label', '' ) );
		if ( '' === $label ) {
			$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
			$label  = ( 0 === strpos( (string) $locale, 'he' ) ) ? 'טוען תוצאות…' : 'Loading…';
		}

		return array( 'label' => $label );
	}
}
