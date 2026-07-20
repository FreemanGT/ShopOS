<?php
/**
 * Feature-flag helper. Reads `shopos_core_<module>_<feature>_enabled` options
 * with explicit boolean parsing so common option-store values
 * ('false', 'no', 'off', 0, '') resolve to false rather than to true via
 * PHP's lax `(bool) 'false'` truthiness.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Feature_Flags.
 */
final class Feature_Flags {

	/**
	 * Option name for a module/feature flag.
	 *
	 * @param string $module  Module slug.
	 * @param string $feature Feature slug.
	 * @return string
	 */
	public static function option_name( $module, $feature ) {
		return 'shopos_core_' . $module . '_' . $feature . '_enabled';
	}

	/**
	 * Whether a module/feature flag is enabled.
	 *
	 * @param string $module  Module slug, e.g. 'sliders'.
	 * @param string $feature Feature slug, e.g. 'advanced_controls'.
	 * @return bool
	 */
	public static function is_enabled( $module, $feature ) {
		$option = self::option_name( $module, $feature );
		$raw    = get_option( $option, false );

		// FILTER_VALIDATE_BOOLEAN handles '1'/'0', 'true'/'false', 'yes'/'no',
		// 'on'/'off' explicitly. FILTER_NULL_ON_FAILURE makes garbage strings
		// resolve to null, which we treat as false.
		$parsed  = filter_var( $raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		$enabled = ( true === $parsed );

		/**
		 * Filters whether a feature flag is enabled.
		 *
		 * Mirrors the dynamic-name pattern of WordPress's `option_{$option}`:
		 * listeners can target one specific flag without inspecting args.
		 *
		 * @since 1.10.14
		 *
		 * @param bool   $enabled Resolved flag state after option lookup + bool parse.
		 * @param string $module  Module slug passed to is_enabled().
		 * @param string $feature Feature slug passed to is_enabled().
		 */
		return (bool) apply_filters( "shopos_core/feature_flag/{$module}/{$feature}", $enabled, $module, $feature );
	}

	/**
	 * Whether a flag's effective state is being forced by a code-level filter
	 * on `shopos_core/feature_flag/{module}/{feature}` rather than by the DB
	 * option. Used by the admin Feature Flags page to disable a checkbox the
	 * UI cannot honour.
	 *
	 * @param string $module  Module slug.
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	public static function is_forced_by_filter( $module, $feature ) {
		return has_filter( "shopos_core/feature_flag/{$module}/{$feature}" ) !== false;
	}

	/**
	 * Canonical list of every feature flag the suite ships. Source of truth for
	 * the admin Feature Flags page and the `/docs/feature-flags.md` table.
	 *
	 * Each entry:
	 *   'module'      => string  slug passed to is_enabled()
	 *   'feature'     => string  slug passed to is_enabled()
	 *   'label'       => string  human title
	 *   'description' => string  one-line "what this gates"
	 *   'since'       => string  version the flag was introduced
	 *   'shared'      => bool    true when one flag gates several shipped sub-features
	 *
	 * Adding a flag to the codebase without listing it here is caught by
	 * FeatureFlagsAdminTest::test_registry_covers_every_flag_referenced_in_source().
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function registry() {
		return array(
			array(
				'module'      => 'variation_swatches',
				'feature'     => 'card_image_swap',
				'label'       => __( 'Variation Swatches — card image swap', 'shopos-core' ),
				'description' => __( 'On shop / archive pages, clicking a swatch swaps the product card image to the matching variation image — no navigation, no Quick View.', 'shopos-core' ),
				'since'       => '1.11.23',
				'shared'      => false,
			),
			array(
				'module'      => 'variation_swatches',
				'feature'     => 'image_swatches',
				'label'       => __( 'Variation Swatches — image swatches', 'shopos-core' ),
				'description' => __( 'Lets each attribute term use an uploaded image instead of a colour; adds the upload UI to the term-edit screen and renders image thumbnails in the shop picker and PDP buy box. Image wins over colour when both are set.', 'shopos-core' ),
				'since'       => '1.11.24',
				'shared'      => false,
			),
			array(
				'module'      => 'variation_swatches',
				'feature'     => 'tooltip',
				'label'       => __( 'Variation Swatches — hover tooltip', 'shopos-core' ),
				'description' => __( 'Shows a CSS hover tooltip on colour and image swatches; the text defaults to the term name and is overridable per term from the term-edit screen.', 'shopos-core' ),
				'since'       => '1.11.25',
				'shared'      => false,
			),
			array(
				'module'      => 'variation_swatches',
				'feature'     => 'auto_color',
				'label'       => __( 'Variation Swatches — auto colour sampler', 'shopos-core' ),
				'description' => __( 'Samples each variation image to a representative hex (cached as post-meta, refreshed on save, pre-warmed via WP-Cron) and uses it as the swatch colour when no manual term colour is set. One toggle drives both the sampler pipeline and the render-path fallback.', 'shopos-core' ),
				'since'       => '1.11.27',
				'shared'      => true,
			),
			array(
				'module'      => 'variation_swatches',
				'feature'     => 'bundle_compat',
				'label'       => __( 'Variation Swatches — WPC Bundles / FBT compatibility', 'shopos-core' ),
				'description' => __( "Forwards all buy-box form fields to WooCommerce's add-to-cart endpoint so WPC Product Bundles and WPC Frequently-Bought-Together hidden inputs reach the cart; exposes a JS flag for the runtime.", 'shopos-core' ),
				'since'       => '1.11.40',
				'shared'      => false,
			),
			array(
				'module'      => 'design',
				'feature'     => 'panel',
				'label'       => __( 'Design panel', 'shopos-core' ),
				'description' => __( 'Adds a ShopOS → Design admin page to set a store accent + a few core design tokens (colours, corner radius) that flow to every module. Off = no page and no CSS; even on, only tokens you change are emitted.', 'shopos-core' ),
				'since'       => '1.35.0',
				'shared'      => false,
			),
			array(
				'module'      => 'perf',
				'feature'     => 'probe',
				'label'       => __( 'Perf probe — diagnostic response headers', 'shopos-core' ),
				'description' => __( 'When on, storefront requests carrying ?shopos_perf=1 respond with X-ShopOS-Queries / X-ShopOS-Render-Ms / X-ShopOS-Mem-MB headers, read by the tools/perf-budget.php per-template budget check. Off = no listeners, no headers. Numbers only — nothing sensitive.', 'shopos-core' ),
				'since'       => '1.38.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'fonts_selfhost',
				'label'       => __( 'Theme — self-hosted storefront fonts', 'shopos-core' ),
				'description' => __( 'When on, the ShopOS theme serves Heebo / Assistant / Rubik from its own @font-face files (woff2) and suppresses the Elementor kit\'s Google Fonts, so storefront typography no longer depends on the kit loading. Off = today\'s kit-loaded fonts, byte-identical. Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme active; without Core the theme reads this as hard false.', 'shopos-core' ),
				'since'       => '1.42.2',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_pdp',
				'label'       => __( 'Theme — product page template (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the designed product page renders from the ShopOS theme\'s own copy (templates/woo/single-product.php) instead of the Core module copy — the same page, owned by the theme (decisions §11.4 row 4). Off = the Core ProductPage template renders as today, byte-identical. Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme active and the Product Page module enabled (the module toggle stays the outer kill-switch); turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and template pages.', 'shopos-core' ),
				'since'       => '1.43.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_plp',
				'label'       => __( 'Theme — product listing template (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the shop page and product-taxonomy archives render from the ShopOS theme\'s classic template (templates/woo/archive-product.php) via the shared theme loader instead of the Elementor archive template — the first Elementor-to-PHP conversion (decisions §11.4 row 5). Never claims search results (Ruling 2). Off = the current archive render, byte-identical. Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme at 1.14.0+ AND, when ShopOS Digital is active, Digital 1.7.7+ (older Digital forces no_found_rows on archive queries and the classic grid renders empty); without Core (or on an older theme) it is inert; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and template pages.', 'shopos-core' ),
				'since'       => '1.44.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_chrome',
				'label'       => __( 'Theme — header/footer chrome (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the ShopOS theme renders its own classic header (logo, primary menu, search, cart) and footer (menu, widgets, copyright) instead of the Elementor Theme Builder / Hello Elementor chrome — the first §11-B deferred surface (decisions §11.4). Off = the theme\'s header.php / footer.php pass through to the Hello Elementor parent chrome, byte-identical (Ruling 6). Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme active; without Core the theme reads this as hard false; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and chrome pages.', 'shopos-core' ),
				'since'       => '1.47.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_cart',
				'label'       => __( 'Theme — cart page template (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the whole cart page renders from the ShopOS theme\'s own classic templates (templates/woo/cart/*.php — cart, empty cart, totals, shipping rows, shipping calculator, proceed-to-checkout, cross-sells) instead of the WooCommerce defaults, the second §11-B deferred surface (decisions §11.4). Redirected via a flag-gated woocommerce_locate_template filter, never by file presence (§11.3) — Off = the current WooCommerce cart render, byte-identical (Ruling 6), with every cart hook/nonce still firing. Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Only affects the shortcode ([woocommerce_cart]) cart: stores using the Cart block render their own markup and need a per-store block→shortcode content-migration under Hard Rule #3 (Ruling 9). Needs the ShopOS theme active; without Core the theme reads this as hard false; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and template pages.', 'shopos-core' ),
				'since'       => '1.48.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_account',
				'label'       => __( 'Theme — My Account pages (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the WooCommerce My Account pages render from the ShopOS theme\'s own classic templates (templates/woo/myaccount/*.php — the account shell, navigation, dashboard, orders, view-order, downloads, payment-methods, addresses) instead of the WooCommerce defaults, the third §11-B deferred surface (decisions §11.4). Redirected via the same flag-gated woocommerce_locate_template filter as the cart, never by file presence (§11.3). The auth/payment form templates (login, edit-account, add-payment-method, password reset) are deliberately NOT forked — they are skin-styled via CSS so their nonces and gateway fields stay WooCommerce-owned. Off = the current WooCommerce account render, byte-identical (Ruling 6), with every account hook/nonce still firing. Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Only affects the [woocommerce_my_account] shortcode account. Needs the ShopOS theme active; without Core the theme reads this as hard false; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and template pages.', 'shopos-core' ),
				'since'       => '1.50.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'template_search',
				'label'       => __( 'Theme — search results template (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the product search-results page renders from the ShopOS theme\'s classic template (templates/woo/search-results.php) via the shared theme loader instead of the Elementor archive template — the fourth §11-B deferred surface (decisions §11.4, §11 Ruling 2 search carve-out). Claims exactly the product-archive main query that carries a search term (the mirror-positive of the PLP loader\'s search refusal); it never touches a generic post/page search. The Search module\'s Results_Query already supplies the engine-ranked results, so the template only reskins the surrounding archive. Off = the current search render, byte-identical (Ruling 6). Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme active; without Core (or on an older theme) it is inert; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and template pages.', 'shopos-core' ),
				'since'       => '1.51.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'style_checkout',
				'label'       => __( 'Theme — checkout skin (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, the ShopOS theme enqueues its own checkout stylesheet (assets/css/shopos-checkout.css) on the checkout page so the buy path is on-brand, the fifth §11-B deferred surface (decisions §11.4). Unlike the cart/account surfaces this forks NO templates — checkout is the most gateway/nonce-heavy surface, so the My Account CSS-skin doctrine is applied to the whole page: WooCommerce keeps ownership of every checkout field, nonce, and payment gateway, and the theme only restyles them (Ruling 9, resolved-as-moot 2026-07-20). Because it is skin-only it works on BOTH the shortcode ([woocommerce_checkout]) and the block checkout, so no per-store content-migration is ever required. Off = the current checkout render with no extra assets, byte-identical (Ruling 6). Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Needs the ShopOS theme active; without Core the theme reads this as hard false; turn on theme.fonts_selfhost first (Ruling 10) or fonts differ between Elementor and checkout pages.', 'shopos-core' ),
				'since'       => '1.52.0',
				'shared'      => false,
			),
			array(
				'module'      => 'theme',
				'feature'     => 'style_emails',
				'label'       => __( 'Theme — transactional email skin (ShopOS Line)', 'shopos-core' ),
				'description' => __( 'When on, ShopOS Core restyles the WooCommerce transactional emails (new order, processing, completed, invoice, password reset, …) to the ShopOS Line brand — the sixth and final §11-B deferred surface (decisions §11.4). Unlike every sibling surface this is Core-side, not theme-side: WooCommerce emails send from cron / webhook / REST contexts where the active theme may not be ShopOS Line, so a theme-level email override would vanish (decisions §11 line 304). Skin-only like the checkout surface — it hooks woocommerce_email_styles and appends email-safe CSS (literal hex/px values; no CSS custom properties, @media, or logical properties, which email clients drop) that WooCommerce inlines onto the markup via Emogrifier; it forks NO email templates, so there is no WooCommerce email @version to chase, and WooCommerce keeps ownership of every email template, header, and footer (Core only appends brand styles, never replacing WooCommerce\'s). Off = the WooCommerce default email styling with nothing appended, byte-identical (Ruling 6). Permanent kill-switch (decisions §11 Ruling 4) — exempt from graduation sweeps. Core-only: unlike the other §11-B surfaces it does NOT need the ShopOS theme active. This closes §11-B.', 'shopos-core' ),
				'since'       => '1.53.0',
				'shared'      => false,
			),
		);
	}
}
