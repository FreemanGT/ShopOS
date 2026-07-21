<?php
/**
 * Side Cart — free-shipping progress-meter math.
 *
 * Pure and unit-tested. Given the cart subtotal and the free-shipping
 * threshold, returns the display state the body template renders: how much is
 * left, the fill percentage (0–100), whether the threshold is met, and whether
 * a meter should show at all (a store with no free-shipping threshold →
 * `active` false → the template omits the bar).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\SideCart;

defined( 'ABSPATH' ) || exit;

/**
 * Free-shipping meter.
 */
final class Meter {

	/**
	 * Compute the meter state.
	 *
	 * @param float $subtotal Cart subtotal (display value).
	 * @param float $min      Free-shipping threshold (0 = none).
	 * @return array{min:float,subtotal:float,remaining:float,percent:int,reached:bool,active:bool}
	 */
	public static function compute( $subtotal, $min ) {
		$subtotal = max( 0.0, (float) $subtotal );
		$min      = max( 0.0, (float) $min );

		if ( $min <= 0.0 ) {
			return array(
				'min'       => 0.0,
				'subtotal'  => $subtotal,
				'remaining' => 0.0,
				'percent'   => 100,
				'reached'   => true,
				'active'    => false,
			);
		}

		$reached   = $subtotal >= $min;
		$remaining = $reached ? 0.0 : round( $min - $subtotal, 2 );
		$percent   = $reached ? 100 : (int) floor( ( $subtotal / $min ) * 100 );
		$percent   = max( 0, min( 100, $percent ) );

		return array(
			'min'       => $min,
			'subtotal'  => $subtotal,
			'remaining' => $remaining,
			'percent'   => $percent,
			'reached'   => $reached,
			'active'    => true,
		);
	}
}
