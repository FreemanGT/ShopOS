<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductSlider\Widget;
use PHPUnit\Framework\TestCase;

/**
 * Slicing a listener-composed include list (pure). The query_args listeners
 * (Search engine, Shop Filters) inject FULL id sets so composing a search with
 * a facet selection intersects whole sets — pre-1.24.10 the Search listener
 * injected one page slice, Shop Filters intersected against ~10 ids instead of
 * the whole match set, and a filtered search rendered blank while the facet
 * panel counted the real total. The widget slices the final composed list —
 * the current page for a paginating grid (plus the real page count), or the
 * widget's own cap for a slider row / fixed-source grid. The fetch-path wiring
 * (pre/post filter include comparison) and pagination render are live QA.
 *
 * @covers \ShopOS\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderConstrainedIncludeTest extends TestCase {

	/**
	 * @dataProvider paginating_cases
	 */
	public function test_paginating_grid_slices_by_page_and_reports_pages( array $include, int $paged, int $per_page, array $expected_include, int $expected_pages ): void {
		$out = Widget::slice_constrained_include( $include, true, $paged, $per_page, 12 );

		$this->assertSame( $expected_include, $out['include'] );
		$this->assertSame( $expected_pages, $out['pages'] );
	}

	public static function paginating_cases(): array {
		// [composed include, paged, per_page, expected slice, expected page count]
		return array(
			'page 1, order kept'      => array( array( 9, 4, 7, 2, 5 ), 1, 2, array( 9, 4 ), 3 ),
			'page 2'                  => array( array( 9, 4, 7, 2, 5 ), 2, 2, array( 7, 2 ), 3 ),
			'last partial page'       => array( array( 9, 4, 7, 2, 5 ), 3, 2, array( 5 ), 3 ),
			'page past the end = [0]' => array( array( 9, 4, 7 ), 4, 2, array( 0 ), 2 ),
			'whole set fits page 1'   => array( array( 9, 4, 7 ), 1, 10, array( 9, 4, 7 ), 1 ),
			'paged floors to 1'       => array( array( 9, 4 ), 0, 2, array( 9, 4 ), 1 ),
			'per_page floors to 1'    => array( array( 9, 4 ), 1, 0, array( 9 ), 2 ),
			'zero ids filtered first' => array( array( 0, 9, 0, 4 ), 1, 2, array( 9, 4 ), 1 ),
		);
	}

	public function test_paginating_grid_no_matches_is_zero_sentinel_one_page(): void {
		// [0] keeps wc_get_products constrained (an empty include would mean
		// "no constraint" and render the whole catalog); one empty page.
		$out = Widget::slice_constrained_include( array(), true, 1, 12, 12 );

		$this->assertSame( array( 0 ), $out['include'] );
		$this->assertSame( 1, $out['pages'] );
	}

	public function test_non_paginating_caps_at_widget_limit_without_page_count(): void {
		// A slider row / fixed-source grid shows the top of the composed set,
		// bounded by the widget's own product limit; it has no pagination UI.
		$out = Widget::slice_constrained_include( array( 9, 4, 7, 2, 5 ), false, 1, 2, 3 );

		$this->assertSame( array( 9, 4, 7 ), $out['include'] );
		$this->assertNull( $out['pages'] );
	}

	public function test_non_paginating_smaller_set_than_cap_passes_through(): void {
		$out = Widget::slice_constrained_include( array( 9, 4 ), false, 1, 12, 12 );

		$this->assertSame( array( 9, 4 ), $out['include'] );
		$this->assertNull( $out['pages'] );
	}

	public function test_non_paginating_no_matches_is_zero_sentinel(): void {
		$out = Widget::slice_constrained_include( array(), false, 1, 12, 12 );

		$this->assertSame( array( 0 ), $out['include'] );
		$this->assertNull( $out['pages'] );
	}
}
