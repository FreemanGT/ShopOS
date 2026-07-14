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
		wp_enqueue_style(
			'shopos-tokens',
			SHOPOS_THEME_ASSETS . '/css/shopos-tokens.css',
			array( 'hello-elementor-theme-style' ),
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
