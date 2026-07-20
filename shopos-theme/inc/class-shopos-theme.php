<?php
/**
 * ShopOS Theme main class.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the ShopOS child theme.
 */
final class ShopOS_Theme {

	/**
	 * Singleton instance.
	 *
	 * @var ShopOS_Theme|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @return ShopOS_Theme
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook registration.
	 */
	private function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'widgets_init', array( $this, 'register_widget_areas' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// ShopOS Line theme-owned WooCommerce surfaces — cart (§11-B surface 2)
		// + account (surface 3), and every later locate_template surface. ONE
		// shared filter, registered UNCONDITIONALLY so the callback census is
		// identical in both flag states (§11 Ruling 7.1); the per-surface flag
		// gate lives inside the callback. New surfaces add a claim arm to
		// woo_surface_enabled(), never a new registration (the §11.3 "one shared
		// arm" doctrine, applied to locate_template). Priority 10 / 3 args
		// matches WooCommerce's own `woocommerce_locate_template` signature.
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_woo_template' ), 10, 3 );
	}

	/**
	 * Once-per-request log guards for the shared woo locate-template filter.
	 * Static because ShopOS_Theme is a singleton (unlike the PLP loader, which
	 * is new-per-test); reset_woo_guards() is the test seam.
	 *
	 * @var bool
	 */
	private static $warned_woo_fonts_off = false;

	/**
	 * @var bool
	 */
	private static $logged_woo_template_missing = false;

	/**
	 * Whether the theme owns the cart page (decisions §11.4, §11-B surface 2).
	 *
	 * The single pinned flag-read path for the cart — the shared locate-template
	 * filter and the cart-asset enqueue both route through here. Spelled as an
	 * FQCN because the theme is unnamespaced (see chrome_enabled()). Core
	 * absent ⇒ hard false ⇒ the WooCommerce default cart (today's render).
	 *
	 * @return bool
	 */
	public static function cart_enabled() {
		return class_exists( '\ShopOS\Core\Core\Feature_Flags' )
			&& \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_cart' );
	}

	/**
	 * Whether the theme owns the My Account pages (decisions §11.4, §11-B
	 * surface 3). The single pinned flag-read path for account — same FQCN /
	 * Core-absent-hard-false contract as cart_enabled().
	 *
	 * @return bool
	 */
	public static function account_enabled() {
		return class_exists( '\ShopOS\Core\Core\Feature_Flags' )
			&& \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_account' );
	}

	/**
	 * Whether a WooCommerce template name is a `cart/*` template. Pure —
	 * unit-tested. The prefix guard keeps the shared filter from redirecting the
	 * PDP/PLP templates the theme also ships under `templates/woo/` (those
	 * belong to the `template_include` loaders, not `locate_template`): without
	 * it, a bare `templates/woo/<name>` existence check would collide with them.
	 *
	 * @param string $template_name WooCommerce template name, e.g. 'cart/cart.php'.
	 * @return bool
	 */
	public static function should_claim_cart_template( $template_name ) {
		return 0 === strpos( (string) $template_name, 'cart/' );
	}

	/**
	 * Whether a WooCommerce template name is a `myaccount/*` template. Pure —
	 * unit-tested (same prefix-guard rationale as should_claim_cart_template).
	 *
	 * @param string $template_name WooCommerce template name, e.g. 'myaccount/my-account.php'.
	 * @return bool
	 */
	public static function should_claim_account_template( $template_name ) {
		return 0 === strpos( (string) $template_name, 'myaccount/' );
	}

	/**
	 * Whether $template_name belongs to a theme-owned WooCommerce surface whose
	 * flag is on. The single claim arm — new surfaces slot in here, keeping one
	 * LITERAL is_enabled() call site per flag (via the *_enabled() pinned reads)
	 * so the bidirectional FeatureFlagsAdminTest scan still resolves each flag.
	 *
	 * @param string $template_name WooCommerce template name.
	 * @return bool
	 */
	private static function woo_surface_enabled( $template_name ) {
		if ( self::should_claim_cart_template( $template_name ) ) {
			return self::cart_enabled();
		}
		if ( self::should_claim_account_template( $template_name ) ) {
			return self::account_enabled();
		}
		return false;
	}

	/**
	 * Redirect WooCommerce templates to the theme's own copies when the owning
	 * surface's flag is on. Flag-off (or a non-theme-owned template) returns
	 * $template untouched ⇒ the WooCommerce default ⇒ byte-identical (§11
	 * Ruling 6).
	 *
	 * Resolves ONLY from `templates/woo/<name>` via get_stylesheet_directory(),
	 * never the auto-located `{theme}/woocommerce/` path (§11.3 — never
	 * resolvable by file presence). Templates the theme does not ship (e.g.
	 * `cart/mini-cart.php`, or a `myaccount/*` form we CSS-skin instead of fork)
	 * fail is_readable and fall through to the WooCommerce default, so the
	 * filter's blast radius is exactly the files we ship. Once-per-request
	 * Ruling-10 fonts warning + missing-template info log mirror the PLP loader.
	 *
	 * @param string $template      Path WooCommerce resolved.
	 * @param string $template_name Template name, e.g. 'cart/cart.php'.
	 * @param string $template_path Base template path (unused — signature parity).
	 * @return string
	 */
	public static function locate_woo_template( $template, $template_name, $template_path = '' ) {
		if ( ! self::woo_surface_enabled( $template_name ) ) {
			return $template;
		}

		// §11 Ruling 10: fonts must not flip between Elementor pages and
		// PHP-template pages — fonts_selfhost is a flip precondition. Core is
		// present here (a surface flag is on ⇒ Feature_Flags loaded ⇒ Logger too).
		if ( ! self::$warned_woo_fonts_off
			&& ! \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'fonts_selfhost' ) ) {
			self::$warned_woo_fonts_off = true;
			\ShopOS\Core\Core\Logger::log( 'A ShopOS Line theme-template flag is on while theme.fonts_selfhost is off — storefront fonts will differ between Elementor and template pages. Turn fonts_selfhost on first (decisions §11 Ruling 10).', 'warning' );
		}

		$file = get_stylesheet_directory() . '/templates/woo/' . $template_name;
		if ( ! is_readable( $file ) ) {
			if ( ! self::$logged_woo_template_missing ) {
				self::$logged_woo_template_missing = true;
				\ShopOS\Core\Core\Logger::log( 'A ShopOS Line surface flag is on but the active theme ships no templates/woo/' . $template_name . ' — rendering the WooCommerce default instead (decisions §11.3).', 'info' );
			}
			return $template;
		}

		return $file;
	}

	/**
	 * Test seam: reset the once-per-request woo locate-template log guards.
	 */
	public static function reset_woo_guards() {
		self::$warned_woo_fonts_off        = false;
		self::$logged_woo_template_missing = false;
	}

	/**
	 * Whether the theme owns the header/footer chrome (decisions §11.4, §11-B).
	 *
	 * The single pinned flag-read path for chrome — header.php, footer.php, the
	 * asset enqueue, and the footer widget area all route through here. Spelled
	 * as an FQCN because the theme is unnamespaced (a bare `Feature_Flags::class`
	 * would resolve to `\Feature_Flags` and read false forever). Core absent ⇒
	 * hard false ⇒ the parent Hello Elementor chrome (today's render).
	 *
	 * @return bool
	 */
	public static function chrome_enabled() {
		return class_exists( '\ShopOS\Core\Core\Feature_Flags' )
			&& \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_chrome' );
	}

	/**
	 * Footer widget area — registered only when the theme owns the chrome, so a
	 * flag-off store gains no new sidebar (surface stays byte-identical).
	 */
	public function register_widget_areas() {
		if ( ! self::chrome_enabled() ) {
			return;
		}
		register_sidebar(
			array(
				'name'          => __( 'ShopOS Footer', 'shopos-theme' ),
				'id'            => 'shopos-footer',
				'description'   => __( 'Widgets shown in the ShopOS theme footer (chrome flag on).', 'shopos-theme' ),
				'before_widget' => '<div class="shopos-chrome__widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="shopos-chrome__widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}

	/**
	 * Theme supports. Hello Elementor already declares most of these but we
	 * add our own so the theme is self-contained if Hello ever changes.
	 */
	public function setup() {
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'post-thumbnails' );

		if ( apply_filters( 'shopos_theme_add_woocommerce_support', true ) ) {
			add_theme_support( 'woocommerce' );
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
		}
	}

	/**
	 * Load text-domain for translations.
	 */
	public function load_textdomain() {
		load_child_theme_textdomain( 'shopos-theme', SHOPOS_THEME_PATH . '/languages' );
	}

	/**
	 * Enqueue theme stylesheets and scripts.
	 */
	public function enqueue_assets() {
		// ShopOS Line typography (decisions §11.4 row 3): serve the
		// self-hosted Heebo/Assistant/Rubik faces and drop the Elementor
		// kit's Google Fonts. Pinned read path per §11 Ruling 4, spelled as
		// an FQCN — the theme is unnamespaced, so a bare Feature_Flags::class
		// would resolve to \Feature_Flags and silently read false forever.
		// Core absent ⇒ hard false ⇒ today's kit-loaded fonts.
		if ( class_exists( '\ShopOS\Core\Core\Feature_Flags' )
			&& \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'fonts_selfhost' ) ) {
			wp_enqueue_style(
				'shopos-fonts',
				SHOPOS_THEME_ASSETS . '/css/shopos-fonts.css',
				array(),
				SHOPOS_THEME_VERSION
			);
			// Without this the kit still prints Google Fonts CSS and the
			// row's recorded value delta — fonts without the kit dependency
			// (§11.4 row 3) — silently isn't delivered.
			add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
		}

		// Hello Elementor registers its stylesheet at wp_enqueue_scripts:10
		// (we run at 20), but the parent enqueue can be absent — the
		// hello_elementor_enqueue_style filter / "hide theme style" setting
		// disables it, and a parent update could rename the handle. WordPress
		// silently skips a style whose dependency is unregistered, which would
		// drop the whole ShopOS CSS chain (tokens → theme → rtl + every inline
		// block riding the shopos-tokens handle), so only depend on the parent
		// handle when it actually exists (§11 Ruling 6.2).
		$parent_deps = wp_style_is( 'hello-elementor-theme-style', 'registered' )
			? array( 'hello-elementor-theme-style' )
			: array();

		wp_enqueue_style(
			'shopos-tokens',
			SHOPOS_THEME_ASSETS . '/css/shopos-tokens.css',
			$parent_deps,
			SHOPOS_THEME_VERSION
		);

		wp_enqueue_style(
			'shopos-theme',
			SHOPOS_THEME_ASSETS . '/css/shopos.css',
			array( 'shopos-tokens' ),
			SHOPOS_THEME_VERSION
		);

		if ( is_rtl() ) {
			wp_enqueue_style(
				'shopos-theme-rtl',
				SHOPOS_THEME_ASSETS . '/css/shopos-rtl.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
		}

		wp_enqueue_script(
			'shopos-theme',
			SHOPOS_THEME_ASSETS . '/js/shopos.js',
			array(),
			SHOPOS_THEME_VERSION,
			true
		);

		// ShopOS Line header/footer chrome (decisions §11.4, §11-B): skin-light,
		// token-driven styles + a small mobile-nav toggle. Only when the theme
		// owns the chrome, so a flag-off store is byte-identical.
		if ( self::chrome_enabled() ) {
			wp_enqueue_style(
				'shopos-chrome',
				SHOPOS_THEME_ASSETS . '/css/shopos-chrome.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
			wp_enqueue_script(
				'shopos-chrome',
				SHOPOS_THEME_ASSETS . '/js/shopos-chrome.js',
				array(),
				SHOPOS_THEME_VERSION,
				true
			);
		}

		// ShopOS Line cart page (decisions §11.4, §11-B surface 2): skin-light,
		// token-driven cart styles + defensive progressive-enhancement JS. Only
		// on the cart page when the theme owns it, so every other page — and a
		// flag-off store — loads zero new assets (§11.5 byte-identity).
		if ( self::cart_enabled() && function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_style(
				'shopos-cart',
				SHOPOS_THEME_ASSETS . '/css/shopos-cart.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
			wp_enqueue_script(
				'shopos-cart',
				SHOPOS_THEME_ASSETS . '/js/shopos-cart.js',
				array(),
				SHOPOS_THEME_VERSION,
				true
			);
		}

		// ShopOS Line My Account (decisions §11.4, §11-B surface 3): skin-light,
		// token-driven account styles (structural forks + CSS-skinned WC forms).
		// Only on the account page when the theme owns it — no JS (WC owns the
		// account form behaviour), zero new assets everywhere else.
		if ( self::account_enabled() && function_exists( 'is_account_page' ) && is_account_page() ) {
			wp_enqueue_style(
				'shopos-account',
				SHOPOS_THEME_ASSETS . '/css/shopos-account.css',
				array( 'shopos-theme' ),
				SHOPOS_THEME_VERSION
			);
		}
	}

	/**
	 * Add identifying body class so module CSS can scope to the theme.
	 *
	 * @param string[] $classes Existing classes.
	 * @return string[]
	 */
	public function add_body_class( $classes ) {
		$classes[] = 'shopos-theme';
		if ( is_rtl() ) {
			$classes[] = 'shopos-rtl';
		}
		return $classes;
	}
}
