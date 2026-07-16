<?php
/**
 * Product Page — storefront label resolver.
 *
 * Every user-facing string the module prints on the product page is
 * overridable from the ShopOS → Product Page settings page, so the site
 * owner can set Hebrew (or any wording) without code (§4.2 — English
 * defaults, locale-specific opt-in; QuickView / ShopFilters / Search Labels
 * precedent). Each label is stored under its own option
 * (`shopos_core_product_page_label_<key>`, the same name Settings_Hub
 * writes for the matching `label_<key>` field); an unset / blank option
 * falls back to the English default here.
 *
 * This map is the single source of truth: the module's settings_schema()
 * builds its text fields from defaults(), and the markup resolves text
 * through get().
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductPage;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver. Resolution (option override → English default) lives in
 * Labels_Base::get(); this class only owns the prefix + canonical map.
 *
 * Unlike QuickView/ShopFilters/Search, the module's settings_schema() does
 * NOT use Module_Base::label_fields(): its label loop routes each key to a
 * per-prefix settings section, words descriptions differently ("Leave blank
 * to use the default: %s") and special-cases the empty-default trust lines —
 * none of which label_fields() reproduces. The custom loop stays.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_product_page_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * `{count}` in urgency_units_left is replaced with the variation's stock
	 * quantity at render time.
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'coupon_intro'       => array(
				'label'   => __( 'Coupon notice — intro (before the code)', 'shopos-core' ),
				'default' => __( 'Enter coupon code', 'shopos-core' ),
			),
			'coupon_outro'       => array(
				'label'   => __( 'Coupon notice — outro (before the price)', 'shopos-core' ),
				'default' => __( 'and the product will cost you:', 'shopos-core' ),
			),
			'urgency_last_unit'  => array(
				'label'   => __( 'Stock urgency — last unit', 'shopos-core' ),
				'default' => __( 'Last one in stock', 'shopos-core' ),
			),
			'urgency_units_left' => array(
				'label'   => __( 'Stock urgency — few units left ({count} = quantity)', 'shopos-core' ),
				'default' => __( 'Only {count} left in stock', 'shopos-core' ),
			),
			// Trust line (designed layout): empty default = the item is hidden,
			// so the line only appears once the owner writes the wording.
			'trust_shipping'     => array(
				'label'   => __( 'Trust line — shipping (empty = hidden)', 'shopos-core' ),
				'default' => '',
			),
			'trust_returns'      => array(
				'label'   => __( 'Trust line — returns (empty = hidden)', 'shopos-core' ),
				'default' => '',
			),
		);
	}
}
