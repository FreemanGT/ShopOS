<?php
/**
 * Search — storefront wiring for the live dropdown.
 *
 * Enqueues the dropdown JS/CSS and localizes the config the script needs. The
 * dropdown attaches to `input[type="search"], input[name="s"]` — which the
 * `[freeman_search]` shortcode field (and most native theme search boxes) match
 * — progressive enhancement, the native form still submits with JS off.
 *
 * Also provides a `[freeman_search]` shortcode that prints a standalone product
 * search box. It renders `input[name="s"]`, which the default selector already
 * matches, so the same dropdown JS enhances it with no extra config; JS-off it
 * submits a normal product search.
 *
 * Only constructed when the dropdown feature flag is on (Module::boot()).
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
final class Frontend {

	const HANDLE = 'freeman-core-search';

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
		add_shortcode( 'freeman_search', array( $this, 'render_form' ) );
	}

	/**
	 * `[freeman_search]` — a standalone product search box. Renders a native GET
	 * form to the home URL with `post_type=product`, so with JS off it performs a
	 * normal product search; the dropdown JS enhances it via the default selector
	 * (the field is `input[type="search"]` named `s`).
	 *
	 * Pure (string-returning) — unit-tested.
	 *
	 * @param array|string $atts Shortcode attributes: placeholder, button.
	 * @return string
	 */
	public function render_form( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'placeholder' => __( 'Search products…', 'freeman-core' ),
				'button'      => __( 'Search', 'freeman-core' ),
			),
			$atts,
			'freeman_search'
		);

		return '<form role="search" method="get" class="fc-search-form" action="' . esc_url( home_url( '/' ) ) . '">'
			. '<input type="search" class="fc-search-field" name="s" value="' . esc_attr( get_search_query() ) . '"'
			. ' placeholder="' . esc_attr( $atts['placeholder'] ) . '" aria-label="' . esc_attr( $atts['placeholder'] ) . '" autocomplete="off" />'
			. '<input type="hidden" name="post_type" value="product" />'
			. '<button type="submit" class="fc-search-submit">' . esc_html( $atts['button'] ) . '</button>'
			. '</form>';
	}

	/**
	 * Enqueue the dropdown assets on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/search.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/search.js' ),
			array(),
			FREEMAN_CORE_VERSION,
			true
		);

		wp_localize_script( self::HANDLE, 'FreemanSearch', $this->localized_payload() );
	}

	/**
	 * Build the FreemanSearch JS payload. Extracted so PHPUnit can assert the
	 * shape (InfiniteScroll / QuickView precedent).
	 *
	 * @return array<string,mixed>
	 */
	public function localized_payload() {
		return array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'action'   => Ajax::ACTION,
			'nonce'    => wp_create_nonce( Ajax::NONCE ),
			// The `[freeman_search]` box renders input[type=search][name=s]; the
			// dropdown attaches to that (and any native theme search field).
			'selector' => 'input[type="search"], input[name="s"]',
			'minChars' => (int) $this->module->get_option( 'min_chars', 2 ),
			'debounce' => (int) $this->module->get_option( 'debounce_ms', 200 ),
			'labels'   => array(
				'noResults' => __( 'No products found', 'freeman-core' ),
				'seeAll'    => __( 'See all results', 'freeman-core' ),
				'searching' => __( 'Searching…', 'freeman-core' ),
			),
		);
	}
}
