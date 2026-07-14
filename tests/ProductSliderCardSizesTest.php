<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductSlider\Widget;
use PHPUnit\Framework\TestCase;

/**
 * The card thumbnail `sizes` attribute (pure column math). Without it, the
 * `large` loop-thumbnail request shipped WordPress's default viewport-wide
 * sizes attr, so every card downloaded the biggest srcset candidate — the
 * scoped wp_calculate_image_sizes filter and the actual byte savings are
 * live QA.
 *
 * @covers \ShopOS\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderCardSizesTest extends TestCase {

	/**
	 * @dataProvider sizes_cases
	 */
	public function test_card_sizes_attr( $per_view, $per_view_tablet, $per_view_mobile, string $expected ): void {
		$this->assertSame( $expected, Widget::card_sizes_attr( $per_view, $per_view_tablet, $per_view_mobile ) );
	}

	public static function sizes_cases(): array {
		// [desktop cols, tablet cols, mobile cols, expected sizes attr]
		return array(
			'default grid 4/3/2'      => array( 4, 3, 2, '(max-width: 640px) 50vw, (max-width: 1024px) 34vw, 350px' ),
			'three-up, slider peek'   => array( 3, 2, 1.4, '(max-width: 640px) 72vw, (max-width: 1024px) 50vw, 467px' ),
			'two-up all around'       => array( 2, 2, 2, '(max-width: 640px) 50vw, (max-width: 1024px) 50vw, 700px' ),
			'single mobile column'    => array( 4, 3, 1, '(max-width: 640px) 100vw, (max-width: 1024px) 34vw, 350px' ),
			'zero cols floor to one'  => array( 0, 0, 0, '(max-width: 640px) 100vw, (max-width: 1024px) 100vw, 1400px' ),
		);
	}
}
