<?php
declare(strict_types=1);

use ShopOS\Core\Modules\BundleDeals\Pricing;
use PHPUnit\Framework\TestCase;

/**
 * The Bundle Deals discount engine — the money-critical, WC-free math seam.
 * Covers all four bundle types, blended partial-quantity pricing, conflict
 * resolution (best-wins, never-stacked), recalc idempotence, and the
 * never-raise / never-below-zero clamps.
 *
 * @covers \ShopOS\Core\Modules\BundleDeals\Pricing
 */
final class BundlePricingTest extends TestCase {

	/**
	 * Build a line map entry.
	 */
	private function line( int $product_id, int $qty, float $base ): array {
		return array( 'product_id' => $product_id, 'qty' => $qty, 'base' => $base );
	}

	private function tiered( string $id, array $tiers, int $priority = 0 ): array {
		return array( 'id' => $id, 'type' => 'tiered', 'priority' => $priority, 'tiers' => $tiers );
	}

	/* ---------------- discount_unit + blend primitives ---------------- */

	public function test_discount_unit_percent(): void {
		$this->assertEqualsWithDelta( 90.0, Pricing::discount_unit( 100.0, 'percent', 10 ), 0.0001 );
	}

	public function test_discount_unit_fixed(): void {
		$this->assertEqualsWithDelta( 75.0, Pricing::discount_unit( 100.0, 'fixed', 25 ), 0.0001 );
	}

	public function test_discount_unit_never_below_zero(): void {
		$this->assertSame( 0.0, Pricing::discount_unit( 40.0, 'fixed', 100 ) );
	}

	public function test_discount_unit_never_raises_price(): void {
		// A negative amount would raise the price — clamp to base.
		$this->assertSame( 100.0, Pricing::discount_unit( 100.0, 'fixed', -20 ) );
	}

	public function test_blend_two_of_three_free(): void {
		// qty 3, one free (d=1, disc=0) → (2*10 + 0) / 3 = 6.6667.
		$this->assertEqualsWithDelta( 6.6667, Pricing::blend( 10.0, 3, 1, 0.0 ), 0.001 );
	}

	public function test_blend_clamps_d_to_qty(): void {
		$this->assertSame( 0.0, Pricing::blend( 10.0, 2, 5, 0.0 ) );
	}

	/* ---------------- pick_tier ---------------- */

	public function test_pick_tier_returns_highest_met(): void {
		$tiers = array(
			array( 'min' => 3, 'kind' => 'percent', 'amount' => 10.0 ),
			array( 'min' => 5, 'kind' => 'percent', 'amount' => 20.0 ),
		);
		$this->assertSame( 20.0, Pricing::pick_tier( $tiers, 6 )['amount'] );
		$this->assertSame( 10.0, Pricing::pick_tier( $tiers, 4 )['amount'] );
		$this->assertNull( Pricing::pick_tier( $tiers, 2 ) );
	}

	/* ---------------- tiered ---------------- */

	public function test_tiered_below_threshold_no_discount(): void {
		$bundles = array( $this->tiered( 'b1', array( array( 'min' => 3, 'kind' => 'percent', 'amount' => 10.0 ) ) ) );
		$lines   = array( 'k1' => $this->line( 1, 2, 100.0 ) );
		$out     = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );

		$this->assertSame( array(), $out );
	}

	public function test_tiered_applies_highest_met_tier_across_lines(): void {
		$bundles = array(
			$this->tiered(
				'b1',
				array(
					array( 'min' => 3, 'kind' => 'percent', 'amount' => 10.0 ),
					array( 'min' => 5, 'kind' => 'percent', 'amount' => 20.0 ),
				)
			),
		);
		// Combined qty across the two lines = 3+3 = 6 → the 20% tier.
		$lines = array(
			'k1' => $this->line( 1, 3, 100.0 ),
			'k2' => $this->line( 2, 3, 50.0 ),
		);
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2' ) ) );

		$this->assertEqualsWithDelta( 80.0, $out['k1']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 40.0, $out['k2']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 60.0, $out['k1']['saved'], 0.001 ); // (100-80)*3
	}

	/* ---------------- bogo ---------------- */

	public function test_bogo_buy2_get1_free_blends_unit(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'bogo', 'priority' => 0, 'bogo' => array( 'buy' => 2, 'get' => 1, 'discount' => 100.0 ) ),
		);
		$lines = array( 'k1' => $this->line( 1, 3, 10.0 ) );
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );

		// 3 units, one free → line total 20 → unit 6.667.
		$this->assertEqualsWithDelta( 6.6667, $out['k1']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 10.0, $out['k1']['saved'], 0.01 );
	}

	public function test_bogo_discounts_cheapest_units_first(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'bogo', 'priority' => 0, 'bogo' => array( 'buy' => 1, 'get' => 1, 'discount' => 100.0 ) ),
		);
		// buy1get1 on qty 2 total → 1 free unit, applied to the cheaper line.
		$lines = array(
			'k1' => $this->line( 1, 1, 100.0 ),
			'k2' => $this->line( 2, 1, 20.0 ),
		);
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2' ) ) );

		$this->assertArrayNotHasKey( 'k1', $out, 'the pricier unit stays full price' );
		$this->assertSame( 0.0, $out['k2']['unit'], 'the cheaper unit is the free one' );
	}

	public function test_bogo_incomplete_group_no_discount(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'bogo', 'priority' => 0, 'bogo' => array( 'buy' => 2, 'get' => 1, 'discount' => 100.0 ) ),
		);
		$lines = array( 'k1' => $this->line( 1, 2, 10.0 ) ); // need 3 for a group.
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );

		$this->assertSame( array(), $out );
	}

	/* ---------------- curated ---------------- */

	public function test_curated_requires_full_set(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'curated', 'priority' => 0, 'curated' => array( 'products' => array( 1, 2 ), 'kind' => 'percent', 'amount' => 10.0 ) ),
		);
		// Only product 1 present → no discount.
		$lines = array( 'k1' => $this->line( 1, 1, 100.0 ) );
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );
		$this->assertSame( array(), $out );
	}

	public function test_curated_percent_discounts_one_of_each(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'curated', 'priority' => 0, 'curated' => array( 'products' => array( 1, 2 ), 'kind' => 'percent', 'amount' => 10.0 ) ),
		);
		$lines = array(
			'k1' => $this->line( 1, 1, 100.0 ),
			'k2' => $this->line( 2, 1, 50.0 ),
		);
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2' ) ) );

		$this->assertEqualsWithDelta( 90.0, $out['k1']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 45.0, $out['k2']['unit'], 0.001 );
	}

	public function test_curated_fixed_total_split_proportionally(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'curated', 'priority' => 0, 'curated' => array( 'products' => array( 1, 2 ), 'kind' => 'fixed', 'amount' => 30.0 ) ),
		);
		// Set value 100+50=150; ₪30 off split 2:1 → 20 off k1, 10 off k2.
		$lines = array(
			'k1' => $this->line( 1, 1, 100.0 ),
			'k2' => $this->line( 2, 1, 50.0 ),
		);
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2' ) ) );

		$this->assertEqualsWithDelta( 80.0, $out['k1']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 40.0, $out['k2']['unit'], 0.001 );
	}

	/* ---------------- mixmatch ---------------- */

	public function test_mixmatch_percent_needs_threshold(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'mixmatch', 'priority' => 0, 'mixmatch' => array( 'need' => 3, 'kind' => 'percent', 'amount' => 15.0 ) ),
		);
		$lines = array( 'k1' => $this->line( 1, 2, 100.0 ) ); // only 2 < 3.
		$this->assertSame( array(), Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) ) );

		$lines2 = array( 'k1' => $this->line( 1, 3, 100.0 ) );
		$out    = Pricing::compute( $bundles, $lines2, array( 'b1' => array( 'k1' ) ) );
		$this->assertEqualsWithDelta( 85.0, $out['k1']['unit'], 0.001 );
	}

	public function test_mixmatch_fixed_price_prices_cheapest_need_units(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'mixmatch', 'priority' => 0, 'mixmatch' => array( 'need' => 3, 'kind' => 'fixed_price', 'amount' => 99.0 ) ),
		);
		// Pick any 3 for 99 → each selected unit 33.
		$lines = array( 'k1' => $this->line( 1, 3, 50.0 ) );
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );

		$this->assertEqualsWithDelta( 33.0, $out['k1']['unit'], 0.001 );
	}

	public function test_mixmatch_fixed_price_scales_varied_units_proportionally(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'mixmatch', 'priority' => 0, 'mixmatch' => array( 'need' => 3, 'kind' => 'fixed_price', 'amount' => 45.0 ) ),
		);
		// 3 distinct-price units (10/20/30 = 60) for 45 → factor 0.75 → 7.5/15/22.5.
		$lines = array(
			'k1' => $this->line( 1, 1, 10.0 ),
			'k2' => $this->line( 2, 1, 20.0 ),
			'k3' => $this->line( 3, 1, 30.0 ),
		);
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2', 'k3' ) ) );

		$this->assertEqualsWithDelta( 7.5, $out['k1']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 15.0, $out['k2']['unit'], 0.001 );
		$this->assertEqualsWithDelta( 22.5, $out['k3']['unit'], 0.001 );
		$total = $out['k1']['unit'] + $out['k2']['unit'] + $out['k3']['unit'];
		$this->assertEqualsWithDelta( 45.0, $total, 0.001, 'the selected set sums to the target price' );
	}

	public function test_mixmatch_fixed_price_no_discount_when_target_above_cost(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'mixmatch', 'priority' => 0, 'mixmatch' => array( 'need' => 3, 'kind' => 'fixed_price', 'amount' => 99.0 ) ),
		);
		// 3 units cost 60; "for 99" would raise → no discount.
		$lines = array(
			'k1' => $this->line( 1, 1, 10.0 ),
			'k2' => $this->line( 2, 1, 20.0 ),
			'k3' => $this->line( 3, 1, 30.0 ),
		);
		$this->assertSame( array(), Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1', 'k2', 'k3' ) ) ) );
	}

	public function test_mixmatch_fixed_price_never_raises(): void {
		$bundles = array(
			array( 'id' => 'b1', 'type' => 'mixmatch', 'priority' => 0, 'mixmatch' => array( 'need' => 2, 'kind' => 'fixed_price', 'amount' => 200.0 ) ),
		);
		// 200 for 2 = 100/unit, but base is 10 → never raise, so no reduction.
		$lines = array( 'k1' => $this->line( 1, 2, 10.0 ) );
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ) ) );

		$this->assertSame( array(), $out );
	}

	/* ---------------- conflict resolution + idempotence ---------------- */

	public function test_best_discount_wins_never_stacks(): void {
		$bundles = array(
			$this->tiered( 'b1', array( array( 'min' => 1, 'kind' => 'percent', 'amount' => 10.0 ) ), 0 ),
			$this->tiered( 'b2', array( array( 'min' => 1, 'kind' => 'percent', 'amount' => 25.0 ) ), 1 ),
		);
		$lines = array( 'k1' => $this->line( 1, 1, 100.0 ) );
		$out   = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'k1' ), 'b2' => array( 'k1' ) ) );

		$this->assertSame( 'b2', $out['k1']['bundle_id'], 'the larger discount wins' );
		$this->assertEqualsWithDelta( 75.0, $out['k1']['unit'], 0.001, 'discounts do not stack' );
	}

	public function test_compute_is_idempotent(): void {
		$bundles = array( $this->tiered( 'b1', array( array( 'min' => 2, 'kind' => 'percent', 'amount' => 10.0 ) ) ) );
		$lines   = array( 'k1' => $this->line( 1, 2, 100.0 ) );
		$part    = array( 'b1' => array( 'k1' ) );

		$first  = Pricing::compute( $bundles, $lines, $part );
		$second = Pricing::compute( $bundles, $lines, $part );

		$this->assertEquals( $first, $second, 'repeated calc passes must be identical (recalc-safe)' );
	}

	public function test_missing_participation_line_is_skipped(): void {
		$bundles = array( $this->tiered( 'b1', array( array( 'min' => 1, 'kind' => 'percent', 'amount' => 10.0 ) ) ) );
		$lines   = array( 'k1' => $this->line( 1, 1, 100.0 ) );
		// Participation names a stale key not in $lines.
		$out = Pricing::compute( $bundles, $lines, array( 'b1' => array( 'gone', 'k1' ) ) );

		$this->assertArrayHasKey( 'k1', $out );
		$this->assertArrayNotHasKey( 'gone', $out );
	}
}
