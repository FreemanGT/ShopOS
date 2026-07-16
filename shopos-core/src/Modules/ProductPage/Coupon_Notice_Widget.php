<?php
/**
 * Elementor widget for the ProductPage coupon-price notice.
 *
 * A thin control shell over the module's already-shipped, tested
 * {@see Coupon_Notice::shortcode()} — the same "enter coupon X and pay Y"
 * notice the `[shopos_discounted_price]` shortcode prints, and the same one the
 * designed PDP auto-places via `woocommerce_single_product_summary`. Dragging
 * this widget onto a pre-takeover Elementor-built or Theme-Builder product page
 * places the notice explicitly where the summary hook never fires.
 *
 * All configuration (coupon code + percent + wording) lives in the module's
 * global settings, so the widget has no per-instance controls. It carries no
 * style/script dependencies — {@see Coupon_Notice::enqueue()} already enqueues
 * them on every single-product page (the same path the shortcode relies on).
 *
 * `get_name()` is frozen at `shopos_discounted_price` — never rename it.
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
final class Coupon_Notice_Widget extends Widget_Base {

	/**
	 * Frozen widget id — referenced by saved Elementor documents.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'shopos_discounted_price';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Coupon Price', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-price-tag';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'coupon', 'discount', 'price', 'notice', 'shopos' );
	}

	/**
	 * No per-instance controls — the notice is driven entirely by the module's
	 * global settings. A single info note points the editor at them.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_info',
			array(
				'label' => __( 'Coupon Price', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Configured globally under ShopOS → Product Page (coupon code + percent + wording). This notice shows on a single product page while a live WooCommerce coupon with that code exists.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Delegate to the shortcode render. shortcode() returns fully-escaped markup
	 * (or '' off a product page / when no live coupon qualifies) and reads only
	 * module options + the current product, so a throwaway Module (the Search
	 * widget precedent) reaches it; Elementor reconstructs widget instances
	 * without our constructor args at render time.
	 */
	protected function render() {
		$html = ( new Coupon_Notice( new Module() ) )->shortcode();
		if ( '' === $html ) {
			if ( $this->is_elementor_edit_mode() ) {
				echo '<div class="shopos-pp-widget-empty">' . esc_html__( 'Coupon-price notice — shows on product pages while a live coupon with the configured code exists.', 'shopos-core' ) . '</div>';
			}
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode() returns fully-escaped markup.
	}
}
