<?php
declare(strict_types=1);

use Freeman\Core\Modules\ProductSlider\Module;
use PHPUnit\Framework\TestCase;

/**
 * Boot wiring — the widget stylesheets must be head-enqueued on the front end
 * (wp_enqueue_scripts). Relying only on Elementor resolving the widget's
 * get_style_depends() enqueues them at widget render time, which prints the
 * CSS in the footer → the card grid painted as unstyled WooCommerce defaults
 * before snapping into the slider design on every page load.
 *
 * @covers \Freeman\Core\Modules\ProductSlider\Module
 */
final class ProductSliderModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_boot_head_enqueues_the_front_styles(): void {
		( new Module() )->boot();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts' ) );
		// Elementor's own registration path stays wired (editor + render dedupe).
		$this->assertNotFalse( has_action( 'elementor/frontend/after_register_styles' ) );
	}
}
