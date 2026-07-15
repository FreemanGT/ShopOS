<?php
/**
 * ShopOS Elementor panel category.
 *
 * Registers a dedicated "ShopOS" category in the Elementor editor's widget
 * panel so ShopOS widgets group together instead of scattering across the
 * WooCommerce / General panels. Widgets opt in via their `get_categories()`
 * (see {@see \ShopOS\Core\Core\Elementor\Widget_Base}); this class only
 * declares the category itself.
 *
 * Wired once from Plugin::boot(). The `elementor/elements/categories_registered`
 * hook only fires when Elementor is loaded, so registering the listener is a
 * no-op on stores without Elementor.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The ShopOS widget-panel category registrar.
 */
final class Category {

	/**
	 * The category slug widgets reference in get_categories().
	 *
	 * @var string
	 */
	const SLUG = 'shopos';

	/**
	 * Wire the category into the Elementor editor.
	 */
	public function boot() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register' ) );
	}

	/**
	 * Declare the ShopOS category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register( $elements_manager ) {
		$elements_manager->add_category(
			self::SLUG,
			array(
				'title' => __( 'ShopOS', 'shopos-core' ),
				'icon'  => 'eicon-woocommerce',
			)
		);
	}
}
