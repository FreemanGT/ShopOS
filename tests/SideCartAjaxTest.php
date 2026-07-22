<?php
declare(strict_types=1);

use ShopOS\Core\Modules\SideCart\Ajax;
use ShopOS\Core\Modules\SideCart\Module;
use PHPUnit\Framework\TestCase;

/**
 * Side Cart endpoint: the op whitelist and public registration. The cart
 * mutations (apply/remove coupon, remove/restore item) run against WC()->cart
 * and are exercised by live QA.
 *
 * @covers \ShopOS\Core\Modules\SideCart\Ajax
 */
final class SideCartAjaxTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks'] = array();
	}

	private function ajax(): Ajax {
		return new Ajax( new Module() );
	}

	/**
	 * @dataProvider ops
	 * @param mixed $raw
	 */
	public function test_sanitize_op( $raw, string $expected ): void {
		$this->assertSame( $expected, $this->ajax()->sanitize_op( $raw ) );
	}

	public static function ops(): array {
		return array(
			'refresh'                => array( 'refresh', 'refresh' ),
			'apply_coupon'           => array( 'apply_coupon', 'apply_coupon' ),
			'remove_coupon'          => array( 'remove_coupon', 'remove_coupon' ),
			'remove_item'            => array( 'remove_item', 'remove_item' ),
			'restore_item'           => array( 'restore_item', 'restore_item' ),
			'uppercase is folded'    => array( 'APPLY_COUPON', 'apply_coupon' ),
			'surrounding space trims' => array( '  remove_item  ', 'remove_item' ),
			'unknown verb rejected'  => array( 'delete_everything', '' ),
			'empty string rejected'  => array( '', '' ),
			'array rejected'         => array( array( 'refresh' ), '' ),
			'null rejected'          => array( null, '' ),
		);
	}

	public function test_register_wires_public_action(): void {
		$this->ajax()->register();

		$this->assertNotFalse( has_action( 'wp_ajax_shopos_core_side_cart' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_shopos_core_side_cart' ) );
	}
}
