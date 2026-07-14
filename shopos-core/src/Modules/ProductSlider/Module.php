<?php
/**
 * Product Slider module.
 *
 * Registers an Elementor widget that renders WooCommerce products as either
 * an editorial horizontal slider — drag-scroll with momentum, optional
 * card/page snap, progress bar — or a static grid, controlled per-instance
 * by the "Display as slider" toggle.
 *
 * Reuses the Category Slider's CSS + JS runtime: both widgets emit the same
 * `.cs[data-cs-snap]` skeleton, so a single drag/progress engine drives
 * both. Each slider item is a *standard* WooCommerce shop-loop entry
 * rendered via `wc_get_template_part( 'content', 'product' )`, so plugins
 * that target the default product grid (sale flash, wishlist, quick-view,
 * image-swap, ratings, etc.) light up the slider with no extra wiring.
 * Layered product-specific styles (sizing of the standard `.product` /
 * `.woocommerce-loop-product__title` / `.price` / `.button` / `.onsale`
 * inside the slider scope, plus `.cs-grid-mode`) live in this module's
 * own stylesheet.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductSlider;

use ShopOS\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * @return string
	 */
	public function id() {
		return 'product_slider';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Product Slider', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Editorial Elementor widget for WooCommerce products — drag-scroll slider or static grid, with price, sale badge, and add-to-cart.', 'shopos-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array(
			'woocommerce' => true,
			'elementor'   => true,
		);
	}

	/**
	 * @return array
	 */
	public function settings_schema() {
		return array();
	}

	/**
	 * Boot — register the widget with Elementor and enqueue assets only when
	 * the widget is present on a page (handled by Elementor's
	 * `elementor/frontend/before_enqueue_scripts` hook + the widget's own
	 * get_script_depends()).
	 */
	public function boot() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		// Editor preview also needs the assets.
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_style' ) );
		// Head-enqueue the stylesheets before first paint — see enqueue_front_styles().
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_styles' ), 20 );
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widget( $widgets_manager ) {
		$widgets_manager->register( new Widget( array(), array( 'shopos_module' => $this ) ) );
	}

	/**
	 * Register front-end stylesheets:
	 *   1. Shared `.cs-*` skeleton (same handle the Category Slider uses) —
	 *      registered defensively so this module works even if the Category
	 *      Slider module is disabled.
	 *   2. Product-specific overlay (`shopos-product-slider`) — price, cart
	 *      button, sale badge, grid-mode container.
	 */
	public function register_styles() {
		$slider_fs  = SHOPOS_CORE_PATH . 'src/Modules/CategorySlider/assets/';
		$slider_url = SHOPOS_CORE_URL . 'src/Modules/CategorySlider/assets/';

		if ( ! wp_style_is( 'shopos-core-category-slider', 'registered' ) ) {
			wp_register_style(
				'shopos-core-category-slider',
				Module_Base::pick_min_url( $slider_fs, $slider_url, 'css/category-slider.css' ),
				array(),
				SHOPOS_CORE_VERSION
			);
			// Deprecated handle alias — see CategorySlider/Module.php.
			if ( ! wp_style_is( 'shopos-category-slider', 'registered' ) ) {
				wp_register_style(
					'shopos-category-slider',
					false,
					array( 'shopos-core-category-slider' ),
					SHOPOS_CORE_VERSION
				);
			}
		}

		wp_register_style(
			'shopos-core-product-slider',
			$this->asset_min_url( 'css/product-slider.css' ),
			array( 'shopos-core-category-slider' ),
			SHOPOS_CORE_VERSION
		);
		// Deprecated handle alias — kept for one release cycle (1.9.x).
		// Removed in 2.0.0.
		if ( ! wp_style_is( 'shopos-product-slider', 'registered' ) ) {
			wp_register_style(
				'shopos-product-slider',
				false,
				array( 'shopos-core-product-slider' ),
				SHOPOS_CORE_VERSION
			);
		}
	}

	/**
	 * Register the shared slider runtime under its canonical handle. Points
	 * at the Category Slider module's JS file — both modules ship in the
	 * same plugin so the relative path is stable. Idempotent — see
	 * register_styles().
	 */
	public function register_scripts() {
		if ( wp_script_is( 'shopos-core-category-slider', 'registered' ) ) {
			return;
		}
		$slider_fs  = SHOPOS_CORE_PATH . 'src/Modules/CategorySlider/assets/';
		$slider_url = SHOPOS_CORE_URL . 'src/Modules/CategorySlider/assets/';
		wp_register_script(
			'shopos-core-category-slider',
			Module_Base::pick_min_url( $slider_fs, $slider_url, 'js/category-slider.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
		// Deprecated handle alias.
		if ( ! wp_script_is( 'shopos-category-slider', 'registered' ) ) {
			wp_register_script(
				'shopos-category-slider',
				false,
				array( 'shopos-core-category-slider' ),
				SHOPOS_CORE_VERSION,
				true
			);
		}
	}

	/**
	 * Head-enqueue both stylesheets on the front end.
	 *
	 * Elementor resolves the widget's get_style_depends() at widget render
	 * time — in the page body, after wp_head — so the CSS printed in the
	 * footer and the card grid painted as unstyled WooCommerce defaults
	 * before snapping into the slider design on every page load. Enqueueing
	 * in <head> removes that flash; Elementor's render-time enqueue becomes
	 * a handle-level no-op.
	 */
	public function enqueue_front_styles() {
		if ( is_admin() || is_feed() || ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$slider_fs  = SHOPOS_CORE_PATH . 'src/Modules/CategorySlider/assets/';
		$slider_url = SHOPOS_CORE_URL . 'src/Modules/CategorySlider/assets/';

		wp_enqueue_style(
			'shopos-core-category-slider',
			Module_Base::pick_min_url( $slider_fs, $slider_url, 'css/category-slider.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);
		wp_enqueue_style(
			'shopos-core-product-slider',
			$this->asset_min_url( 'css/product-slider.css' ),
			array( 'shopos-core-category-slider' ),
			SHOPOS_CORE_VERSION
		);
	}

	/**
	 * Editor needs both stylesheets so the preview renders correctly.
	 */
	public function enqueue_editor_style() {
		$slider_fs  = SHOPOS_CORE_PATH . 'src/Modules/CategorySlider/assets/';
		$slider_url = SHOPOS_CORE_URL . 'src/Modules/CategorySlider/assets/';

		wp_enqueue_style(
			'shopos-core-category-slider',
			Module_Base::pick_min_url( $slider_fs, $slider_url, 'css/category-slider.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);
		wp_enqueue_style(
			'shopos-core-product-slider',
			$this->asset_min_url( 'css/product-slider.css' ),
			array( 'shopos-core-category-slider' ),
			SHOPOS_CORE_VERSION
		);
	}
}
