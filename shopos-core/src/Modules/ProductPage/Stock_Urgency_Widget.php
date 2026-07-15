<?php
/**
 * Elementor widget for the ProductPage stock-urgency badge.
 *
 * A thin control shell over the module's already-shipped, tested
 * {@see Stock_Urgency::shortcode()} — the same low-stock scarcity badge the
 * `[shopos_stock_urgency]` shortcode prints, and the same one the designed PDP
 * auto-places via `woocommerce_single_product_summary`. Dragging this widget
 * onto a pre-takeover Elementor-built or Theme-Builder product page places the
 * badge explicitly where the summary hook never fires.
 *
 * All configuration (urgency threshold + wording) lives in the module's global
 * settings, so the widget has no per-instance controls. It carries no
 * style/script dependencies — {@see Stock_Urgency::enqueue()} already enqueues
 * them on every single-product page (the same path the shortcode relies on).
 *
 * `get_name()` is frozen at `shopos_stock_urgency` — never rename it.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use ShopOS\Core\Core\Elementor\Widget_Base;

/**
 * Widget.
 */
final class Stock_Urgency_Widget extends Widget_Base {

	/**
	 * Frozen widget id — referenced by saved Elementor documents.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'shopos_stock_urgency';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Stock Urgency', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-flash';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'stock', 'urgency', 'scarcity', 'low stock', 'shopos' );
	}

	/**
	 * No per-instance controls — the badge is driven entirely by the module's
	 * global settings. A single info note points the editor at them.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_info',
			array(
				'label' => __( 'Stock Urgency', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Configured globally under ShopOS → Product Page (urgency threshold + wording). This badge shows on a single product page when the selected variation is low in managed stock.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Delegate to the shortcode render. shortcode() returns fully-escaped markup
	 * (or '' off a product page / when no variation qualifies) and reads only
	 * module options + the current product, so a throwaway Module (the Search
	 * widget precedent) reaches it; Elementor reconstructs widget instances
	 * without our constructor args at render time.
	 */
	protected function render() {
		$html = ( new Stock_Urgency( new Module() ) )->shortcode();
		if ( '' === $html ) {
			if ( $this->is_elementor_edit_mode() ) {
				echo '<div class="shopos-pp-widget-empty">' . esc_html__( 'Stock-urgency badge — shows on product pages when a variation is low in managed stock.', 'shopos-core' ) . '</div>';
			}
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode() returns fully-escaped markup.
	}
}
