<?php
/**
 * Product Page — storefront label resolver.
 *
 * Every user-facing string the module prints on the product page is
 * overridable from the Freeman → Product Page settings page, so the site
 * owner can set Hebrew (or any wording) without code (§4.2 — English
 * defaults, locale-specific opt-in; QuickView / ShopFilters / Search Labels
 * precedent). Each label is stored under its own option
 * (`freeman_core_product_page_label_<key>`, the same name Settings_Hub
 * writes for the matching `label_<key>` field); an unset / blank option
 * falls back to the English default here.
 *
 * This map is the single source of truth: the module's settings_schema()
 * builds its text fields from defaults(), and the markup resolves text
 * through get().
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver.
 */
final class Labels {

	const OPTION_PREFIX = 'freeman_core_product_page_label_';

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
				'label'   => __( 'Coupon notice — intro (before the code)', 'freeman-core' ),
				'default' => __( 'Enter coupon code', 'freeman-core' ),
			),
			'coupon_outro'       => array(
				'label'   => __( 'Coupon notice — outro (before the price)', 'freeman-core' ),
				'default' => __( 'and the product will cost you:', 'freeman-core' ),
			),
			'urgency_last_unit'  => array(
				'label'   => __( 'Stock urgency — last unit', 'freeman-core' ),
				'default' => __( 'Last one in stock', 'freeman-core' ),
			),
			'urgency_units_left' => array(
				'label'   => __( 'Stock urgency — few units left ({count} = quantity)', 'freeman-core' ),
				'default' => __( 'Only {count} left in stock', 'freeman-core' ),
			),
			// Trust line (designed layout): empty default = the item is hidden,
			// so the line only appears once the owner writes the wording.
			'trust_shipping'     => array(
				'label'   => __( 'Trust line — shipping (empty = hidden)', 'freeman-core' ),
				'default' => '',
			),
			'trust_returns'      => array(
				'label'   => __( 'Trust line — returns (empty = hidden)', 'freeman-core' ),
				'default' => '',
			),
		);
	}

	/**
	 * Resolve a label by short key. Returns the saved override when non-empty,
	 * otherwise the English default.
	 *
	 * @param string $key Short key (e.g. 'coupon_intro').
	 * @return string
	 */
	public static function get( $key ) {
		$key      = (string) $key;
		$defaults = self::defaults();
		$default  = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ]['default'] : '';

		$value = (string) get_option( self::OPTION_PREFIX . $key, '' );
		return '' !== trim( $value ) ? $value : $default;
	}
}
