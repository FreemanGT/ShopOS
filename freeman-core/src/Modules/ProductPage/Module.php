<?php
/**
 * Product Page module.
 *
 * A designed WooCommerce single-product page plus two conversion widgets,
 * each behind its own feature flag (default off — Hard Rule #1):
 *
 * - `freeman_core_product_page_layout_enabled` — full PDP template takeover
 *   (`template_include`, wins over the Elementor Pro single-product location):
 *   token-driven responsive layout with the native WC gallery (zoom /
 *   lightbox / thumbnail slider), sticky summary column, accordion product
 *   sections, restyled upsells / related, and a mobile sticky add-to-cart
 *   bar. The summary renders the standard `woocommerce_single_product_summary`
 *   stack, so VariationSwatches / RestockNotify / structured data light up
 *   unaided — and so both widgets below auto-place without shortcodes.
 *
 * - `freeman_core_product_page_coupon_notice_enabled` — "enter coupon X and
 *   pay Y" notice under the price. Coupon code + percent are owner settings;
 *   the notice renders only while a live (existing, unexpired) WC coupon
 *   with that code exists, and follows the picked variation's price live.
 *
 * - `freeman_core_product_page_stock_urgency_enabled` — low-stock scarcity
 *   badge for variable products, driven by the picked variation's managed
 *   stock quantity.
 *
 * Both widgets also register shortcodes (`[freeman_discounted_price]`,
 * `[freeman_stock_urgency]`, plus legacy aliases `[discounted_price]` /
 * `[stock_urgency]`) because the pre-takeover Elementor-built product page
 * renders widgets directly and never fires the summary hook stack.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\ProductPage;

use Freeman\Core\Core\Feature_Flags;
use Freeman\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * @return string
	 */
	public function id() {
		return 'product_page';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Product Page', 'freeman-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Designed single-product page (template takeover) plus a coupon-price notice and a low-stock urgency badge, each behind its own feature flag.', 'freeman-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' => true );
	}

	/**
	 * Settings schema — coupon configuration, urgency threshold, and one text
	 * field per storefront string (Labels precedent from ShopFilters 6.3c).
	 *
	 * @return array
	 */
	public function settings_schema() {
		$coupon_section  = __( 'Coupon notice', 'freeman-core' );
		$urgency_section = __( 'Stock urgency', 'freeman-core' );

		$schema = array(
			'coupon_code'    => array(
				'label'       => __( 'Coupon code', 'freeman-core' ),
				'type'        => 'text',
				'default'     => '',
				'section'     => $coupon_section,
				'description' => __( 'The WooCommerce coupon code to advertise (e.g. arba50). The notice only shows while a coupon with this exact code exists and has not expired — deleted or expired coupon hides the notice automatically.', 'freeman-core' ),
			),
			'coupon_percent' => array(
				'label'       => __( 'Discount percent', 'freeman-core' ),
				'type'        => 'number',
				'default'     => 0,
				'section'     => $coupon_section,
				'description' => __( 'The percentage the advertised price is reduced by (1–99). Used to compute the shown price; keep it matching the coupon itself.', 'freeman-core' ),
			),
			'urgency_max'    => array(
				'label'       => __( 'Show urgency up to (units)', 'freeman-core' ),
				'type'        => 'number',
				'default'     => 5,
				'section'     => $urgency_section,
				'description' => __( 'The badge shows while the picked variation has between 1 and this many units in managed stock.', 'freeman-core' ),
			),
		);

		$sections = array(
			'coupon_'  => $coupon_section,
			'urgency_' => $urgency_section,
			'trust_'   => __( 'Trust line (designed layout)', 'freeman-core' ),
		);
		foreach ( Labels::defaults() as $key => $def ) {
			$section = __( 'Wording', 'freeman-core' );
			foreach ( $sections as $prefix => $name ) {
				if ( 0 === strpos( $key, $prefix ) ) {
					$section = $name;
					break;
				}
			}
			$description = '' === $def['default']
				? __( 'Shown under the add-to-cart button on the designed layout; leave empty to hide.', 'freeman-core' )
				/* translators: %s: the English default wording for this field. */
				: sprintf( __( 'Leave blank to use the default: %s', 'freeman-core' ), $def['default'] );
			$schema[ 'label_' . $key ] = array(
				'label'       => $def['label'],
				'type'        => 'text',
				'default'     => '',
				'section'     => $section,
				'description' => $description,
			);
		}

		return $schema;
	}

	/**
	 * Boot — each surface sits behind its own feature flag so flag-off
	 * leaves no template override, no storefront markup and no assets.
	 */
	public function boot() {
		if ( Feature_Flags::is_enabled( 'product_page', 'coupon_notice' ) ) {
			( new Coupon_Notice( $this ) )->register();
		}
		if ( Feature_Flags::is_enabled( 'product_page', 'stock_urgency' ) ) {
			( new Stock_Urgency( $this ) )->register();
		}
		if ( Feature_Flags::is_enabled( 'product_page', 'layout' ) ) {
			( new Template_Loader( $this ) )->register();
		}
	}
}
