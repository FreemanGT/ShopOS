<?php
declare(strict_types=1);

use ShopOS\Core\Modules\BundleDeals\Targeting;
use PHPUnit\Framework\TestCase;

/**
 * Bundle Deals scope resolver — the pure set-logic seam (`matches_terms`).
 *
 * @covers \ShopOS\Core\Modules\BundleDeals\Targeting
 */
final class BundleTargetingTest extends TestCase {

	private function scope( array $overrides = array() ): array {
		return array(
			'products'           => $overrides['products'] ?? array(),
			'categories'         => $overrides['categories'] ?? array(),
			'tags'               => $overrides['tags'] ?? array(),
			'exclude_categories' => $overrides['exclude_categories'] ?? array(),
		);
	}

	public function test_empty_scope_matches_nothing(): void {
		$this->assertFalse( Targeting::matches_terms( 5, array( 1, 2 ), array( 3 ), $this->scope() ) );
	}

	public function test_matches_by_product_id(): void {
		$this->assertTrue( Targeting::matches_terms( 5, array(), array(), $this->scope( array( 'products' => array( 5 ) ) ) ) );
		$this->assertFalse( Targeting::matches_terms( 6, array(), array(), $this->scope( array( 'products' => array( 5 ) ) ) ) );
	}

	public function test_matches_by_category(): void {
		$this->assertTrue( Targeting::matches_terms( 5, array( 12 ), array(), $this->scope( array( 'categories' => array( 12 ) ) ) ) );
	}

	public function test_matches_by_tag(): void {
		$this->assertTrue( Targeting::matches_terms( 5, array(), array( 30 ), $this->scope( array( 'tags' => array( 30 ) ) ) ) );
	}

	public function test_exclusion_wins_over_a_category_match(): void {
		$scope = $this->scope(
			array(
				'categories'         => array( 12 ),
				'exclude_categories' => array( 99 ),
			)
		);
		$this->assertFalse(
			Targeting::matches_terms( 5, array( 12, 99 ), array(), $scope ),
			'a product in an excluded category never matches'
		);
	}

	public function test_exclusion_wins_over_a_direct_product_id_match(): void {
		$scope = $this->scope(
			array(
				'products'           => array( 5 ),
				'exclude_categories' => array( 99 ),
			)
		);
		$this->assertFalse( Targeting::matches_terms( 5, array( 99 ), array(), $scope ) );
	}
}
