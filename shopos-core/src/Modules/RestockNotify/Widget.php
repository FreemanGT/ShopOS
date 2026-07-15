<?php
/**
 * Elementor widget for the RestockNotify back-in-stock form.
 *
 * A thin control shell over the module's already-shipped, tested
 * `[restock_notify]` shortcode — the same back-in-stock subscribe form the
 * shortcode prints, and the same one the module's `auto_inject` strategies
 * place via the WooCommerce single-product hooks. Dragging this widget onto an
 * Elementor-built or Theme-Builder product page places the form explicitly
 * where those summary/variation hooks never fire (a hook-bypassing PDP).
 *
 * Unlike the Search / ProductPage widgets, this one delegates via
 * {@see do_shortcode()} rather than constructing the delegate class directly:
 * {@see Frontend::__construct()} registers deferred hooks (`wp_footer`,
 * `woocommerce_get_stock_html`) when `auto_inject` is on, and a throwaway
 * `new Frontend()` — a distinct object, so not deduped by WordPress' unique
 * callback id — would double-register them and inject the form a second time.
 * `do_shortcode()` invokes the booted instance's already-registered callback:
 * no new hooks, and it shares the class-level per-product dedup guard so it
 * never doubles a form the auto-inject path already rendered this request.
 *
 * `get_name()` is frozen at `shopos_restock_notify` — never rename it (a saved
 * page references the widget by this id).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\RestockNotify;

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
		return 'shopos_restock_notify';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Restock Notify', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-mail';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'restock', 'back in stock', 'notify', 'waitlist', 'stock', 'shopos' );
	}

	/**
	 * One optional product-id override (blank → the shortcode auto-detects the
	 * current product, exactly as `[restock_notify]` does) plus an info note:
	 * the form's heading/wording lives in the Restock Notify admin, not per
	 * instance.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Restock Notify', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'shopos-core' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'Leave blank to use the current product (the same auto-detection the shortcode uses).', 'shopos-core' ),
			)
		);

		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Form heading, button text and wording are configured under Restock Notify. The form shows on a product page when the product (or every variation) is out of stock.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build the `[restock_notify]` shortcode string from saved settings. A
	 * positive `product_id` override is passed through as the `product_id` att;
	 * a blank/zero value yields the bare shortcode so the handler auto-detects
	 * the current product. Pure — unit-tested.
	 *
	 * @param array<string,mixed> $s Settings from get_settings_for_display().
	 * @return string
	 */
	public static function shortcode_from_settings( $s ) {
		$product_id = isset( $s['product_id'] ) ? absint( $s['product_id'] ) : 0;
		if ( $product_id > 0 ) {
			return '[restock_notify product_id="' . $product_id . '"]';
		}
		return '[restock_notify]';
	}

	/**
	 * Delegate to the registered `[restock_notify]` shortcode via do_shortcode()
	 * — see the class docblock for why this widget doesn't construct Frontend
	 * directly. The shortcode returns fully-escaped markup, or an HTML comment
	 * (`<!-- RSN: … -->`) when no product is detected; in the Elementor editor
	 * (no product context) that comment is invisible, so a placeholder is shown
	 * instead.
	 */
	protected function render() {
		$html = do_shortcode( self::shortcode_from_settings( $this->get_settings_for_display() ) );

		if ( '' === trim( $html ) || 0 === strpos( ltrim( $html ), '<!-- RSN:' ) ) {
			if ( $this->is_elementor_edit_mode() ) {
				echo '<div class="shopos-pp-widget-empty">' . esc_html__( 'Restock Notify form — shows on a product page when the product is out of stock.', 'shopos-core' ) . '</div>';
			}
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_shortcode() returns the shortcode's fully-escaped markup.
	}
}
