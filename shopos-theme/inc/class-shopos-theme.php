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
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
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
