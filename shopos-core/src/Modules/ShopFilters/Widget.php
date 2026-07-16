<?php
/**
 * Elementor widget for the Shop Filters panel.
 *
 * A thin shell over the module's already-shipped, tested
 * {@see Shortcode::render()} — the same faceted filter panel the
 * `[shopos_shop_filters]` shortcode prints. Dragging this widget onto a shop /
 * category / product-archive template is equivalent to placing the shortcode in
 * an Elementor HTML/shortcode element (decision §5.4's original delivery), now
 * as a first-class draggable widget in the ShopOS panel category. Reverses the
 * §5.4 "no Elementor widget in v1" scoping (owner-approved 2026-07-15); the
 * shortcode stays as-is (Hard Rule #2).
 *
 * Context (current category / search term) is resolved inside render() from the
 * main-query conditional tags exactly as the shortcode resolves it, so the
 * widget inherits the shortcode's already-in-production archive behaviour and
 * adds no new query-context handling of its own. All configuration (facet
 * matrix, panel wording, panel style) lives in the module's global settings, so
 * the widget has no per-instance controls; it carries no style/script
 * dependencies — {@see Shortcode::render()} enqueues them itself (and the panel
 * stylesheet is head-enqueued on every front-end page).
 *
 * `get_name()` is frozen at `shopos_shop_filters` — never rename it (a saved
 * page references the widget by this id).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ShopFilters;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use ShopOS\Core\Core\Elementor\Widget_Base;

/**
 * Widget.
 */
final class Widget extends Widget_Base {

	/**
	 * Frozen widget id — referenced by saved Elementor documents.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'shopos_shop_filters';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Shop Filters', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-filter';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'filter', 'facet', 'shop', 'category', 'shopos' );
	}

	/**
	 * No per-instance controls — the panel is driven entirely by the current
	 * page context + the module's global settings. A single info note points the
	 * editor at them.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_info',
			array(
				'label' => __( 'Shop Filters', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Configured globally under ShopOS → Shop Filters (facet configuration, panel wording + panel style). The panel adapts to the current page — the shop page shows all facets, a category page scopes to that category, and a search page scopes to the results.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Delegate to the shortcode render. render() returns fully-escaped panel
	 * markup, resolves its own context and enqueues its own assets, and registers
	 * no deferred hooks — so a throwaway Shortcode (the Search widget precedent)
	 * reaches it safely; Elementor reconstructs widget instances without our
	 * constructor args at render time.
	 */
	protected function render() {
		echo ( new Shortcode() )->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns fully-escaped markup.
	}
}
