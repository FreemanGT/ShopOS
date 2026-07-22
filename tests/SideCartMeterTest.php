<?php
declare(strict_types=1);

use ShopOS\Core\Modules\SideCart\Meter;
use PHPUnit\Framework\TestCase;

/**
 * Free-shipping meter math: remaining amount, fill percent (0–100),
 * threshold-met flag, and the "no threshold → inactive" case.
 *
 * @covers \ShopOS\Core\Modules\SideCart\Meter
 */
final class SideCartMeterTest extends TestCase {

	/**
	 * @dataProvider cases
	 */
	public function test_compute( float $subtotal, float $min, array $expected ): void {
		$state = Meter::compute( $subtotal, $min );

		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, $state[ $key ], "field {$key}" );
		}
	}

	public static function cases(): array {
		return array(
			'no threshold → inactive, treated as reached' => array(
				40.0, 0.0,
				array( 'active' => false, 'reached' => true, 'percent' => 100, 'remaining' => 0.0 ),
			),
			'empty cart, threshold set' => array(
				0.0, 100.0,
				array( 'active' => true, 'reached' => false, 'percent' => 0, 'remaining' => 100.0 ),
			),
			'halfway' => array(
				50.0, 100.0,
				array( 'active' => true, 'reached' => false, 'percent' => 50, 'remaining' => 50.0 ),
			),
			'almost there floors the percent' => array(
				99.5, 100.0,
				array( 'active' => true, 'reached' => false, 'percent' => 99, 'remaining' => 0.5 ),
			),
			'exactly met' => array(
				100.0, 100.0,
				array( 'active' => true, 'reached' => true, 'percent' => 100, 'remaining' => 0.0 ),
			),
			'over the threshold clamps to 100 / 0' => array(
				150.0, 100.0,
				array( 'active' => true, 'reached' => true, 'percent' => 100, 'remaining' => 0.0 ),
			),
			'negative subtotal clamps to 0' => array(
				-10.0, 100.0,
				array( 'active' => true, 'reached' => false, 'percent' => 0, 'remaining' => 100.0 ),
			),
			'negative threshold clamps to inactive' => array(
				40.0, -5.0,
				array( 'active' => false, 'reached' => true, 'percent' => 100 ),
			),
		);
	}
}
