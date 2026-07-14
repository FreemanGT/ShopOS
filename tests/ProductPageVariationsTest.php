<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductPage\Variations;
use PHPUnit\Framework\TestCase;

/**
 * The shared per-request variations memo (Wave 9.3 perf): Coupon_Notice and
 * Stock_Urgency both read the current product's available variations — the
 * memo collapses their two independent get_available_variations() sweeps to
 * one per product per request. Asserted with a counting product double; the
 * live objects read is integration (needs WC).
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Variations
 */
final class ProductPageVariationsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Variations::reset();
	}

	/**
	 * A product double counting its get_available_variations() calls.
	 *
	 * @param int $id Product id.
	 * @return object
	 */
	private function counting_product( int $id ) {
		return new class( $id ) {
			public $calls = 0;

			private $id;

			public function __construct( $id ) {
				$this->id = $id;
			}

			public function get_id() {
				return $this->id;
			}

			public function get_available_variations( $mode = 'array' ) {
				$this->calls++;
				return array( 'v-of-' . $this->id . '-' . $mode );
			}
		};
	}

	public function test_second_read_hits_the_memo(): void {
		$product = $this->counting_product( 7 );

		$first  = Variations::objects( $product );
		$second = Variations::objects( $product );

		$this->assertSame( 1, $product->calls, 'one enumeration per product per request' );
		$this->assertSame( array( 'v-of-7-objects' ), $first, 'the light objects read is requested' );
		$this->assertSame( $first, $second );
	}

	public function test_distinct_products_memoize_independently(): void {
		$a = $this->counting_product( 1 );
		$b = $this->counting_product( 2 );

		$this->assertSame( array( 'v-of-1-objects' ), Variations::objects( $a ) );
		$this->assertSame( array( 'v-of-2-objects' ), Variations::objects( $b ) );
		Variations::objects( $a );

		$this->assertSame( 1, $a->calls );
		$this->assertSame( 1, $b->calls );
	}

	public function test_same_product_id_via_a_fresh_object_still_hits_the_memo(): void {
		$first  = $this->counting_product( 5 );
		$second = $this->counting_product( 5 );

		Variations::objects( $first );
		Variations::objects( $second );

		$this->assertSame( 1, $first->calls );
		$this->assertSame( 0, $second->calls, 'keyed by product id — the set is data-derived, not instance-derived' );
	}

	public function test_non_variable_shapes_return_empty(): void {
		$plain = new class() {
			public function get_id() {
				return 9;
			}
		};

		$this->assertSame( array(), Variations::objects( $plain ) );
	}
}
