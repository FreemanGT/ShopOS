<?php
/**
 * Shop Filters shortcode — [freeman_shop_filters].
 *
 * Drops the filter panel into an Elementor shortcode/HTML element (decision
 * §5.4 — no Elementor widget). Server-renders the initial facet tree for the
 * current context so the first paint is correct and SEO-visible without JS, and
 * enqueues the front-end script that takes over on interaction. Wired whenever
 * the module is enabled (always-on since 1.12.26; Module::boot()).
 *
 * The render path touches WooCommerce / the query, so it is exercised by live
 * QA; the shortcode-tag registration is unit-tested.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\ShopFilters;

use Freeman\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode.
 */
final class Shortcode {

	const TAG          = 'freeman_shop_filters';
	const SCRIPT_HANDLE = 'freeman-core-shop-filters';
	const STYLE_HANDLE  = 'freeman-core-shop-filters';

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
	}

	/**
	 * Head-enqueue the panel stylesheet on every front-end page.
	 *
	 * The shortcode renders in the page body (an Elementor shortcode element),
	 * after wp_head has printed — a style first enqueued at render time prints
	 * in the footer, so the panel painted unstyled and snapped into place once
	 * the footer CSS arrived, on every page load / filter reload. Enqueueing
	 * here puts the CSS in <head> before first paint regardless of where (or
	 * whether) the panel renders; enqueue_assets() keeps its call as a dedupe
	 * no-op safety net for contexts that skip wp_enqueue_scripts.
	 */
	public function enqueue_style() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		$fs_base  = FREEMAN_CORE_PATH . 'src/Modules/ShopFilters/assets/';
		$url_base = FREEMAN_CORE_URL . 'src/Modules/ShopFilters/assets/';

		wp_enqueue_style(
			self::STYLE_HANDLE,
			Module_Base::pick_min_url( $fs_base, $url_base, 'css/shop-filters.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);
	}

	/**
	 * Render the filter panel.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = array() ) {
		$context_id = $this->context_category_id();
		$response   = ( new Query_Builder() )->query( $this->initial_request( $context_id ) );

		$this->enqueue_assets( $context_id );

		ob_start();

		/**
		 * Fires before the Shop Filters panel markup is rendered. Captured inside
		 * the shortcode output buffer, so any echo is contained in the panel.
		 *
		 * @since 1.12.11
		 *
		 * @param int $context_id Current product_cat term id (0 = shop).
		 */
		do_action( 'freeman_core/shop_filters/before_render', $context_id );

		$facets        = $response['facets'];
		$category_tree = $response['category_tree'];
		$price         = isset( $response['price'] ) ? $response['price'] : array();
		$flags         = isset( $response['flags'] ) ? $response['flags'] : array();
		$count         = $response['count'];
		$orderby       = Url_State::parse( $this->initial_request( $context_id ) )['orderby'];
		include $this->template_path( 'filters.php' );

		/**
		 * Fires after the Shop Filters panel markup is rendered.
		 *
		 * @since 1.12.11
		 *
		 * @param int $context_id Current product_cat term id (0 = shop).
		 */
		do_action( 'freeman_core/shop_filters/after_render', $context_id );

		$html = (string) ob_get_clean();

		/**
		 * Filter the rendered Shop Filters panel markup before it is returned.
		 *
		 * @since 1.12.11
		 *
		 * @param string $html       Panel HTML.
		 * @param int    $context_id Current product_cat term id (0 = shop).
		 */
		return (string) apply_filters( 'freeman_core/shop_filters/panel_html', $html, $context_id );
	}

	/**
	 * Current product_cat context (0 on the shop page or anywhere else).
	 *
	 * @return int
	 */
	private function context_category_id() {
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			return (int) get_queried_object_id();
		}
		return 0;
	}

	/**
	 * Build the initial request from the current URL so a deep-linked filtered
	 * page renders its selection on first paint.
	 *
	 * @param int $context_id Category context.
	 * @return array
	 */
	private function initial_request( $context_id ) {
		$request               = is_array( $_GET ) ? wp_unslash( $_GET ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request['context_id'] = (int) $context_id;

		// On a search-results page, scope the facets to the products matching the
		// query (Query_Builder uses this as the base universe).
		if ( function_exists( 'is_search' ) && is_search() ) {
			$request['search'] = get_search_query();
		}

		return $request;
	}

	/**
	 * Enqueue (and localise) the front-end script + the panel stylesheet.
	 *
	 * @param int $context_id Category context.
	 */
	private function enqueue_assets( $context_id ) {
		$fs_base  = FREEMAN_CORE_PATH . 'src/Modules/ShopFilters/assets/';
		$url_base = FREEMAN_CORE_URL . 'src/Modules/ShopFilters/assets/';
		$src      = Module_Base::pick_min_url( $fs_base, $url_base, 'js/shop-filters.js' );
		$style    = Module_Base::pick_min_url( $fs_base, $url_base, 'css/shop-filters.css' );

		wp_enqueue_style( self::STYLE_HANDLE, $style, array(), FREEMAN_CORE_VERSION );
		wp_enqueue_script( self::SCRIPT_HANDLE, $src, array(), FREEMAN_CORE_VERSION, true );
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'FreemanShopFilters',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => Ajax::ACTION,
				'nonce'     => wp_create_nonce( Ajax::NONCE ),
				'contextId' => (int) $context_id,
			)
		);
	}

	/**
	 * Absolute path to a module template.
	 *
	 * @param string $template Template filename.
	 * @return string
	 */
	private function template_path( $template ) {
		return FREEMAN_CORE_PATH . 'src/Modules/ShopFilters/templates/' . ltrim( $template, '/' );
	}
}
