<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ShopFilters\Ajax;
use ShopOS\Core\Modules\ShopFilters\Module;
use ShopOS\Core\Modules\ShopFilters\Shortcode;
use PHPUnit\Framework\TestCase;

/**
 * Frontend read-path wiring (Phase 6.3a): the shortcode and public AJAX
 * endpoint register on boot.
 *
 * @covers \ShopOS\Core\Modules\ShopFilters\Ajax
 * @covers \ShopOS\Core\Modules\ShopFilters\Shortcode
 * @covers \ShopOS\Core\Modules\ShopFilters\Module
 */
final class ShopFiltersFrontendTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']       = array();
		$GLOBALS['fr_hooks']      = array();
		$GLOBALS['fr_cron']       = array();
		$GLOBALS['fr_shortcodes'] = array();
	}

	public function test_ajax_register_attaches_both_public_actions(): void {
		( new Ajax() )->register();

		$this->assertNotFalse( has_action( 'wp_ajax_' . Ajax::ACTION ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_' . Ajax::ACTION ) );
	}

	public function test_shortcode_register_adds_the_tag(): void {
		( new Shortcode() )->register();

		$this->assertArrayHasKey( Shortcode::TAG, $GLOBALS['fr_shortcodes'] );
	}

	public function test_shortcode_register_head_enqueues_the_style(): void {
		// The panel CSS must load from wp_enqueue_scripts (<head>) — a style
		// first enqueued at shortcode-render time prints in the footer, so the
		// panel painted unstyled before snapping into place on every load.
		( new Shortcode() )->register();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts' ) );
	}

	public function test_boot_wires_frontend(): void {
		( new Module() )->boot();
		$this->assertArrayHasKey( Shortcode::TAG, $GLOBALS['fr_shortcodes'] );
		$this->assertNotFalse( has_action( 'wp_ajax_' . Ajax::ACTION ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_' . Ajax::ACTION ) );
	}
}
