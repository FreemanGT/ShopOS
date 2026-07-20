<?php
/**
 * ShopOS shared theme template loader (ShopOS Line, decisions §11.3).
 *
 * THE single theme-side `template_include` filter, priority 9999 — one
 * registration serving every theme-owned buy-path surface, forever: PLP now
 * (§11.4 row 5); §11-B surfaces (cart, account, chrome…) become new claim
 * arms inside maybe_load_template(), never new registrations and never a
 * per-surface priority arms race. It coexists with Core's permanent
 * ProductPage Template_Loader (also 9999) by construction: the claims are
 * disjoint — Core claims is_product() only, this loader never does — and
 * both return $template untouched otherwise, so relative ordering never
 * matters (owner ask 1, 2026-07-17).
 *
 * Registration is UNCONDITIONAL: flag-off behavior lives inside the
 * callback, never in whether it is hooked, so the QA callback census is
 * identical in both flag states (§11 Ruling 7.1).
 *
 * The per-surface context value (`context()`: '' | 'plp' | 'search') is §11.3's
 * replacement for the `$is_takeover` static pattern, going forward — Core's
 * private static is not retro-touched.
 *
 * Flag reads use the FQCN string '\ShopOS\Core\Core\Feature_Flags' behind
 * class_exists — the theme is unnamespaced, so a bare class reference would
 * resolve to \Feature_Flags and silently read false forever (the pinned read
 * path, §11 Ruling 4; see inc/class-shopos-theme.php). Core absent ⇒ every
 * flag is hard false ⇒ this loader is inert (the soft Core dependency).
 *
 * Templates resolve ONLY from `templates/woo/` via get_stylesheet_directory()
 * — deliberately no locate_template(), no parent-theme fallback, no
 * template-hierarchy discovery (§11.3: never resolvable by file presence).
 * Unlike the PDP rung there is no public-override rung: PDP's
 * `shopos/product_page/single-product.php` was a pre-existing public
 * contract (Hard Rule #2); PLP has none, and minting one would create a new
 * file-presence-adjacent permanent contract.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * The shared theme template loader.
 */
final class ShopOS_Theme_Template_Loader {

	/**
	 * Which theme-owned surface is driving the current request: '' when none,
	 * 'plp' when the archive template claimed it, 'search' when the
	 * search-results template did. §11.3's per-surface context value (the
	 * $is_takeover replacement, going forward).
	 *
	 * Static on purpose (it IS per-request global state); everything else is
	 * instance state — production constructs exactly one instance in
	 * functions.php, and tests construct fresh ones so the once-per-request
	 * Logger guards stay order-independent (Core Template_Loader precedent —
	 * deliberately NOT a singleton, which would leak guard state into tests).
	 *
	 * @var string
	 */
	private static $context = '';

	/**
	 * Once-per-request Logger guards. Instance properties, not statics, per
	 * the Core Template_Loader precedent: every Logger::log() is a DB option
	 * write, the callback can run more than once per request, and a fresh
	 * instance per unit test keeps log assertions order-independent.
	 *
	 * @var bool
	 */
	private $warned_fonts_off = false;

	/**
	 * @var bool
	 */
	private $logged_template_missing = false;

	/**
	 * Register hooks. One template_include registration, forever (§11.3);
	 * enqueue at 21 so the theme's prio-20 enqueue has registered the
	 * `shopos-theme` handle this stylesheet depends on.
	 */
	public function register() {
		add_filter( 'template_include', array( $this, 'maybe_load_template' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 21 );
	}

	/**
	 * The single shared template_include callback. One arm per theme-owned
	 * full-page surface (the §11.3 "one shared registration, a claim arm per
	 * surface" doctrine): PLP (§11.4 row 5) now, search-results (§11-B surface
	 * 4) alongside it. The arms are disjoint by construction — should_claim_plp()
	 * refuses any product archive carrying search, should_claim_search() claims
	 * exactly those — so their relative order never matters.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function maybe_load_template( $template ) {
		if ( ! function_exists( 'is_shop' ) ) {
			return $template; // WooCommerce absent.
		}

		$is_admin  = is_admin();
		$is_main   = is_main_query();
		$is_shop   = is_shop();
		$is_tax    = is_product_taxonomy();
		$is_search = is_search();
		$term      = self::request_search_term();

		// FQCN, never bare Feature_Flags::class — the theme is unnamespaced
		// (class-shopos-theme.php:76-80: a bare name silently reads false
		// forever). Each arm keeps its OWN literal is_enabled( 'theme',
		// '<feature>' ) call site: the bidirectional FeatureFlagsAdminTest scan
		// requires one LITERAL call per registry flag — a dynamic
		// is_enabled( 'theme', $feature ) is invisible to it and fails
		// test_registry_has_no_stale_entries. That contract is why the flag read
		// stays here and is NOT folded into the shared resolve() helper.
		if ( self::should_claim_plp( $is_admin, $is_main, $is_shop, $is_tax, $is_search, $term ) ) {
			if ( ! class_exists( '\ShopOS\Core\Core\Feature_Flags' )
				|| ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_plp' ) ) {
				return $template; // Flag off (or Core absent) = the current render.
			}
			return $this->resolve( $template, 'archive-product.php', 'plp', 'template_plp' );
		}

		if ( self::should_claim_search( $is_admin, $is_main, $is_shop, $is_tax, $is_search, $term ) ) {
			if ( ! class_exists( '\ShopOS\Core\Core\Feature_Flags' )
				|| ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_search' ) ) {
				return $template; // Flag off (or Core absent) = the current render.
			}
			return $this->resolve( $template, 'search-results.php', 'search', 'template_search' );
		}

		return $template;
	}

	/**
	 * Resolve a claimed surface to its theme template file, or fall back to the
	 * current render. Shared by every claim arm: the once-per-request fonts
	 * warning (§11 Ruling 10) and the missing-file fallback log (§11.3) live here
	 * so every surface behaves identically. Only the flag read stays per-arm (the
	 * FeatureFlagsAdminTest literal-call-site contract; see maybe_load_template).
	 *
	 * @param string $template Current resolved template (the fallback).
	 * @param string $filename templates/woo/ filename to resolve.
	 * @param string $context  Per-surface context() value set on success.
	 * @param string $flag     The surface flag name, for the log messages.
	 * @return string
	 */
	private function resolve( $template, $filename, $context, $flag ) {
		// §11 Ruling 10: fonts must not flip between Elementor pages and
		// PHP-template pages — fonts_selfhost is a flip precondition.
		if ( ! $this->warned_fonts_off
			&& ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'fonts_selfhost' ) ) {
			$this->warned_fonts_off = true;
			\ShopOS\Core\Core\Logger::log( 'theme.' . $flag . ' is on while theme.fonts_selfhost is off — storefront fonts will differ between Elementor and template pages. Turn fonts_selfhost on first (decisions §11 Ruling 10).', 'warning' );
		}

		$file = get_stylesheet_directory() . '/templates/woo/' . $filename;
		if ( ! is_readable( $file ) ) {
			if ( ! $this->logged_template_missing ) {
				$this->logged_template_missing = true;
				\ShopOS\Core\Core\Logger::log( 'theme.' . $flag . ' is on but the active theme ships no templates/woo/' . $filename . ' — rendering the current template instead (decisions §11.3).', 'info' );
			}
			return $template;
		}

		self::$context = $context;

		return $file;
	}

	/**
	 * Whether the PLP template claims this request. Pure — unit-tested.
	 *
	 * Claim = a product-archive main query (is_shop() or a product taxonomy),
	 * never search — with BOTH search guards (§11 Ruling 2): native product
	 * search is is_shop() AND is_search() simultaneously, while the live
	 * Elementor search-results page is a product archive where is_search() is
	 * FALSE and the term lives only in the request URL — the exact
	 * Results_Query::should_handle trigger, mirror-negated here. A product
	 * archive carrying any request search term belongs to the search surface
	 * (deferred to §11-B), never to this loader.
	 *
	 * @param bool   $is_admin            In wp-admin.
	 * @param bool   $is_main             Main query.
	 * @param bool   $is_shop             is_shop() (covers the product post-type archive).
	 * @param bool   $is_product_taxonomy is_product_taxonomy() (product_cat/tag, pa_*, custom — owner ask 5).
	 * @param bool   $is_search           is_search().
	 * @param string $request_term        Raw request search term (never the query var).
	 * @return bool
	 */
	public static function should_claim_plp( $is_admin, $is_main, $is_shop, $is_product_taxonomy, $is_search, $request_term ) {
		return ! $is_admin && $is_main
			&& ( $is_shop || $is_product_taxonomy )
			&& ! $is_search
			&& '' === trim( (string) $request_term );
	}

	/**
	 * Whether the search-results template claims this request (§11-B surface 4).
	 * Pure — unit-tested. The mirror-positive of should_claim_plp()'s search
	 * refusal: the SAME product-archive main query base ($is_shop covers the
	 * product post-type archive, which native product search — ?post_type=product&s= —
	 * still satisfies), but carrying search rather than refusing it. "Carrying
	 * search" is is_search() OR a request search term with is_search() false (the
	 * live Elementor search page, where the term reaches only the request URL —
	 * Results_Query::should_handle's trigger, §11 Ruling 2's binding carve-out).
	 *
	 * The base is deliberately NOT `|| $is_search`: a bare is_search() with no
	 * product archive (a generic post/page search, or the mixed ?s= WP search) is
	 * not a product-search surface — Results_Query refuses it too (its is_product
	 * gate) — so this template must not take it over with a product grid.
	 *
	 * Disjoint from should_claim_plp() by construction: on the shared
	 * (is_shop || is_product_taxonomy) base, "carries search" partitions the
	 * space, so the two arms never both fire (asserted in SearchTemplateTest).
	 *
	 * @param bool   $is_admin            In wp-admin.
	 * @param bool   $is_main             Main query.
	 * @param bool   $is_shop             is_shop() (covers the product post-type archive).
	 * @param bool   $is_product_taxonomy is_product_taxonomy() (product_cat/tag, pa_*, custom).
	 * @param bool   $is_search           is_search().
	 * @param string $request_term        Raw request search term (never the query var).
	 * @return bool
	 */
	public static function should_claim_search( $is_admin, $is_main, $is_shop, $is_product_taxonomy, $is_search, $request_term ) {
		if ( $is_admin || ! $is_main ) {
			return false;
		}
		if ( ! $is_shop && ! $is_product_taxonomy ) {
			return false;
		}
		return $is_search || '' !== trim( (string) $request_term );
	}

	/**
	 * The raw request search term — $_GET, not get_query_var('s'): on the
	 * live Elementor search page the term never reaches the query vars
	 * (Search module Results_Query.php precedent).
	 *
	 * PRESENCE-BASED on purpose (Ruling 2 is a law about the REQUEST, not
	 * the parsed value): an array payload (?s[]=x) or a value sanitization
	 * strips to nothing (?s=%3Cb%3E) still counts as "a request search term"
	 * — the sentinel keeps should_claim_plp() refusing. Stricter than
	 * Results_Query::should_handle (which sees '' for those shapes) is safe:
	 * refusing more can never wrongly claim the search surface, and neither
	 * surface claims those requests, so the current render stands.
	 *
	 * @return string '' when no term is present; the term (or a non-empty
	 *                sentinel for unparseable payloads) otherwise.
	 */
	public static function request_search_term() {
		if ( ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing decision.
			return '';
		}
		$raw = wp_unslash( $_GET['s'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_array( $raw ) ) {
			return '[array]';
		}
		$term = trim( sanitize_text_field( $raw ) );
		if ( '' !== $term ) {
			return $term;
		}
		return '' === trim( (string) $raw ) ? '' : '[unparseable]';
	}

	/**
	 * Which theme-owned surface is driving the current request.
	 *
	 * @return string '' | 'plp' | 'search'
	 */
	public static function context() {
		return self::$context;
	}

	/**
	 * PLP stylesheet — only when this loader claimed the request, so flag-off
	 * (and every non-claimed page) loads zero new assets (§11.5 byte-identity).
	 * wp_enqueue_scripts fires inside the claimed template's get_header(),
	 * AFTER template_include has run, so context() is authoritative here —
	 * one gate, no re-derivation, and the stylesheet can never load for a
	 * render this loader didn't claim (including the missing-file fallback).
	 */
	public function enqueue_assets() {
		if ( 'plp' === self::$context ) {
			wp_enqueue_style(
				'shopos-plp',
				SHOPOS_THEME_ASSETS . '/css/shopos-plp.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
		} elseif ( 'search' === self::$context ) {
			wp_enqueue_style(
				'shopos-search',
				SHOPOS_THEME_ASSETS . '/css/shopos-search.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
		}
	}

	/**
	 * Test seam: reset the per-request static context.
	 */
	public static function reset_context() {
		self::$context = '';
	}
}
