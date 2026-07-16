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
 * The per-surface context value (`context()`: '' | 'plp') is §11.3's
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
	 * Singleton instance.
	 *
	 * @var ShopOS_Theme_Template_Loader|null
	 */
	private static $instance = null;

	/**
	 * Which theme-owned surface is driving the current request: '' when none,
	 * 'plp' when the archive template claimed it. §11.3's per-surface context
	 * value (the $is_takeover replacement, going forward).
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
	 * Returns the singleton.
	 *
	 * @return ShopOS_Theme_Template_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

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
	 * The single shared template_include callback.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function maybe_load_template( $template ) {
		if ( ! function_exists( 'is_shop' ) ) {
			return $template; // WooCommerce absent.
		}

		if ( ! self::should_claim_plp(
			is_admin(),
			is_main_query(),
			is_shop(),
			is_product_taxonomy(),
			is_search(),
			self::request_search_term()
		) ) {
			return $template;
		}

		if ( ! class_exists( '\ShopOS\Core\Core\Feature_Flags' )
			|| ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_plp' ) ) {
			return $template; // Flag off (or Core absent) = the current render.
		}

		// §11 Ruling 10: fonts must not flip between Elementor pages and
		// PHP-template pages — fonts_selfhost is a flip precondition.
		if ( ! $this->warned_fonts_off
			&& ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'fonts_selfhost' ) ) {
			$this->warned_fonts_off = true;
			\ShopOS\Core\Core\Logger::log( 'theme.template_plp is on while theme.fonts_selfhost is off — storefront fonts will differ between Elementor and template pages. Turn fonts_selfhost on first (decisions §11 Ruling 10).', 'warning' );
		}

		$file = get_stylesheet_directory() . '/templates/woo/archive-product.php';
		if ( ! is_readable( $file ) ) {
			if ( ! $this->logged_template_missing ) {
				$this->logged_template_missing = true;
				\ShopOS\Core\Core\Logger::log( 'theme.template_plp is on but the active theme ships no templates/woo/archive-product.php — rendering the current template instead (decisions §11.3).', 'info' );
			}
			return $template;
		}

		self::$context = 'plp';

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
	 * The raw request search term — $_GET, not get_query_var('s'): on the
	 * live Elementor search page the term never reaches the query vars
	 * (Search module Results_Query.php precedent).
	 *
	 * @return string
	 */
	public static function request_search_term() {
		if ( ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing decision.
			return '';
		}
		return trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Which theme-owned surface is driving the current request.
	 *
	 * @return string '' | 'plp'
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
		if ( 'plp' !== self::$context ) {
			return;
		}

		wp_enqueue_style(
			'shopos-plp',
			SHOPOS_THEME_ASSETS . '/css/shopos-plp.css',
			array( 'shopos-theme' ),
			SHOPOS_THEME_VERSION
		);
	}

	/**
	 * Test seam: reset the per-request static context.
	 */
	public static function reset_context() {
		self::$context = '';
	}
}
