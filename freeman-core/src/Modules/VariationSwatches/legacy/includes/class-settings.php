<?php
/**
 * Variation Swatches settings — option-key registry + static read helpers.
 *
 * As of Wave 2.2 / 4g (freeman-core 1.11.45) the editable settings UI moved
 * to **Freeman → Variation Swatches** (the Settings_Hub page, stored under
 * `freeman_core_variation_swatches_*` and read via {@see
 * \Freeman\Core\Modules\VariationSwatches\Settings_Reader}). Through 1.23.1 a
 * vestigial WooCommerce → Settings → Products → "Shop swatches" sub-section
 * was still registered (rendering only a "settings have moved" notice) so the
 * old `?section=etucart_vs_shop_pick` URL kept resolving.
 *
 * As of 1.23.2 that sub-section is removed entirely — `register()` no longer
 * hooks the WooCommerce settings filters, so the "Shop swatches" tab and its
 * moved notice no longer appear. This is an owner-approved override of Hard
 * Rule #2 (removing an admin section URL); no data is affected — the `OPT_*`
 * constants and the static read helpers (`bool`, `max_visible`,
 * `excluded_category_ids`, `should_apply_on_current_archive`) are unchanged
 * and still delegate reads to `Settings_Reader`, and the historic
 * `etucart_vs_*` option keys are never deleted (§4.5 zero-downtime decision).
 *
 * Hard Rule #3: this `legacy/` edit was owner-approved.
 *
 * @package EtucartVariationSwatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Etucart_VS_Settings' ) ) :

class Etucart_VS_Settings {

	/** Individual option keys, one per field. */
	public const OPT_ENABLED             = 'etucart_vs_shop_enabled';
	public const OPT_MAX_VISIBLE         = 'etucart_vs_shop_max_visible';
	public const OPT_SHOW_PRICE          = 'etucart_vs_shop_show_price';
	public const OPT_APPLY_SHOP          = 'etucart_vs_shop_apply_shop';
	public const OPT_APPLY_CATEGORY      = 'etucart_vs_shop_apply_category';
	public const OPT_APPLY_TAG           = 'etucart_vs_shop_apply_tag';
	public const OPT_APPLY_SEARCH        = 'etucart_vs_shop_apply_search';
	public const OPT_APPLY_RELATED       = 'etucart_vs_shop_apply_related';
	public const OPT_EXCLUDED_CATEGORIES = 'etucart_vs_shop_excluded_categories';

	/** Single-product buy box options (new in 1.6.1). */
	public const OPT_PDP_HIDE_OOS        = 'etucart_vs_pdp_hide_oos';

	/** Archive / shop-grid OOS hiding (new in 1.6.6). */
	public const OPT_SHOP_HIDE_OOS       = 'etucart_vs_shop_hide_oos';

	/** Archive / shop-grid: skip pre-selecting any variation (new in 1.7.4). */
	public const OPT_SHOP_NO_PRESELECT   = 'etucart_vs_shop_no_preselect';

	/** Archive / shop-grid: hide the attribute label row e.g. "Size:" (1.7.8). */
	public const OPT_SHOP_HIDE_ATTR_LABELS = 'etucart_vs_shop_hide_attr_labels';

	/** Archive / shop-grid: hide the "selected option" text row (1.7.9). */
	public const OPT_SHOP_HIDE_SELECTED    = 'etucart_vs_shop_hide_selected';

	/** Archive / shop-grid: show name + price only, no buy UI (1.23.2). */
	public const OPT_SHOP_NAMES_PRICE_ONLY = 'etucart_vs_shop_names_price_only';

	/**
	 * Boot hook, called from Etucart_VS_Plugin. As of 1.23.2 the WooCommerce
	 * → Settings → Products → "Shop swatches" sub-section is no longer
	 * registered (removed — owner-approved Hard Rule #2 override), so there is
	 * nothing to wire here. Kept as a no-op because the plugin bootstrap still
	 * calls it and the OPT_* constants / static read helpers below remain the
	 * module's option contract.
	 */
	public function register(): void {
	}

	public static function bool( string $option_key, string $default = 'yes' ): bool {
		// Most flags default to 'yes' (the feature's core toggle is OPT_ENABLED).
		// A few fields want to default OFF (e.g. OPT_SHOW_PRICE, which is opt-in).
		// Callers pass 'no' for those.
		return 'yes' === \Freeman\Core\Modules\VariationSwatches\Settings_Reader::get( $option_key, $default );
	}

	public static function max_visible(): int {
		$v = absint( \Freeman\Core\Modules\VariationSwatches\Settings_Reader::get( self::OPT_MAX_VISIBLE, 5 ) );
		if ( $v < 1 )  $v = 1;
		if ( $v > 50 ) $v = 50;
		return $v;
	}

	public static function excluded_category_ids(): array {
		$raw = \Freeman\Core\Modules\VariationSwatches\Settings_Reader::get( self::OPT_EXCLUDED_CATEGORIES, [] );
		if ( ! is_array( $raw ) ) {
			return [];
		}
		return array_values( array_filter( array_map( 'absint', $raw ) ) );
	}

	/**
	 * Should the compact picker render on the *current* archive request?
	 */
	public static function should_apply_on_current_archive(): bool {
		if ( ! self::bool( self::OPT_ENABLED ) ) {
			return false;
		}
		if ( is_admin() ) {
			return false;
		}
		if ( function_exists( 'is_shop' ) && is_shop() && self::bool( self::OPT_APPLY_SHOP ) ) {
			return true;
		}
		if ( function_exists( 'is_product_category' ) && is_product_category() && self::bool( self::OPT_APPLY_CATEGORY ) ) {
			return true;
		}
		if ( function_exists( 'is_product_tag' ) && is_product_tag() && self::bool( self::OPT_APPLY_TAG ) ) {
			return true;
		}
		if ( function_exists( 'is_search' ) && is_search() && self::bool( self::OPT_APPLY_SEARCH ) ) {
			return true;
		}
		// Cart / checkout / account are never product loops — keep them off.
		if ( ( function_exists( 'is_cart' )         && is_cart() )
			|| ( function_exists( 'is_checkout' )    && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() )
		) {
			return false;
		}
		// Single-product page: any loop rendered here is Related / Upsells /
		// Cross-sells / "Recently viewed" etc. Controlled by its own toggle so
		// a shop owner can disable just the PDP loops if they want to.
		if ( is_singular( 'product' ) ) {
			return self::bool( self::OPT_APPLY_RELATED );
		}
		// Any other loop context (shop fallback, shortcode grid on a page, an
		// Elementor products widget, block-based grid on home, etc.).
		return true;
	}

}

endif; // class_exists Etucart_VS_Settings
