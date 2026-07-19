<?php
/**
 * Elementor widget for the Bundle Deals product-page block.
 *
 * A thin shell over the module's shortcode: the same offer block the
 * `[shopos_bundle_deals]` shortcode prints and the designed PDP auto-places via
 * `woocommerce_single_product_summary`. Dropping this widget onto an
 * Elementor-built or Theme-Builder product page places the block explicitly
 * where the summary hook never fires.
 *
 * All configuration lives in the module's global settings + the bundle builder,
 * so the widget has no per-instance controls. It carries no asset dependencies —
 * {@see Frontend::enqueue()} already loads them on every single-product page.
 *
 * `get_name()` is frozen at `shopos_bundle_deals` — never rename it.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use ShopOS\Core\Core\Elementor\Widget_Base;

/**
 * Widget.
 */
final class Bundle_Deals_Widget extends Widget_Base {

	/**
	 * Frozen widget id — referenced by saved Elementor documents.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'shopos_bundle_deals';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Bundle Deals', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-related';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'bundle', 'deal', 'discount', 'bogo', 'shopos' );
	}

	/**
	 * No per-instance controls — a single info note points at the global config.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_info',
			array(
				'label' => __( 'Bundle Deals', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Bundles are configured under ShopOS → Bundle Deals. This block shows the offers that apply to the current product.', 'shopos-core' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Delegate to the shortcode render (fully-escaped markup, '' off a product
	 * page). A throwaway Module reaches it — Elementor rebuilds widget instances
	 * without our constructor args at render time (the Coupon_Notice precedent).
	 */
	protected function render() {
		$html = ( new Frontend( new Module() ) )->shortcode();
		if ( '' === $html ) {
			if ( $this->is_elementor_edit_mode() ) {
				echo '<div class="shopos-pp-widget-empty">' . esc_html__( 'Bundle Deals — shows the bundles that apply to the current product.', 'shopos-core' ) . '</div>';
			}
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode() returns fully-escaped markup.
	}
}
