<?php
declare(strict_types=1);

use ShopOS\Core\Modules\BundleDeals\Bundle_Config;
use PHPUnit\Framework\TestCase;

/**
 * Bundle Deals storage normaliser — the pure whitelist-and-coerce seam that
 * guards the price rules the engine reads (the Facet_Config::sanitize model).
 *
 * @covers \ShopOS\Core\Modules\BundleDeals\Bundle_Config
 */
final class BundleConfigTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
	}

	public function test_drops_rows_with_an_unknown_type(): void {
		$out = Bundle_Config::sanitize(
			array(
				array( 'type' => 'tiered' ),
				array( 'type' => 'nonsense' ),
				array( 'type' => 'bogo' ),
			)
		);
		$this->assertCount( 2, $out );
		$this->assertSame( array( 'tiered', 'bogo' ), array_column( $out, 'type' ) );
	}

	public function test_auto_generates_a_missing_id(): void {
		$out = Bundle_Config::sanitize( array( array( 'type' => 'tiered' ) ) );
		$this->assertSame( 'b_1', $out[0]['id'] );
	}

	public function test_keeps_and_slugifies_a_supplied_id(): void {
		$out = Bundle_Config::sanitize( array( array( 'type' => 'tiered', 'id' => 'Summer Sale!' ) ) );
		$this->assertSame( 'SummerSale', $out[0]['id'] );
	}

	public function test_coerces_scalars_and_scope(): void {
		$out = Bundle_Config::sanitize(
			array(
				array(
					'type'     => 'tiered',
					'title'    => 'Deal',
					'enabled'  => '1',
					'priority' => '5',
					'scope'    => array(
						'products'           => array( '10', 10, 'x', 0, 20 ),
						'categories'         => '3,4,4',
						'exclude_categories' => array( 7 ),
					),
				),
			)
		);
		$b = $out[0];
		$this->assertSame( 'Deal', $b['title'] );
		$this->assertTrue( $b['enabled'] );
		$this->assertSame( 5, $b['priority'] );
		$this->assertSame( array( 10, 20 ), $b['scope']['products'], 'deduped, intified, zeros/junk dropped' );
		$this->assertSame( array( 3, 4 ), $b['scope']['categories'], 'comma string parsed + deduped' );
		$this->assertSame( array( 7 ), $b['scope']['exclude_categories'] );
		$this->assertSame( array(), $b['scope']['tags'] );
	}

	public function test_tiers_are_sorted_and_clamped(): void {
		$out = Bundle_Config::sanitize(
			array(
				array(
					'type'  => 'tiered',
					'tiers' => array(
						array( 'min' => 5, 'kind' => 'percent', 'amount' => 150 ),
						array( 'min' => 2, 'kind' => 'fixed', 'amount' => 10 ),
						array( 'min' => 0 ), // dropped (min < 1).
					),
				),
			)
		);
		$tiers = $out[0]['tiers'];
		$this->assertCount( 2, $tiers );
		$this->assertSame( 2, $tiers[0]['min'], 'sorted ascending' );
		$this->assertSame( 5, $tiers[1]['min'] );
		$this->assertSame( 100.0, $tiers[1]['amount'], 'percent clamped to 100' );
	}

	public function test_bogo_defaults_and_clamps(): void {
		$out = Bundle_Config::sanitize(
			array( array( 'type' => 'bogo', 'bogo' => array( 'buy' => 0, 'get' => -3, 'discount' => 250 ) ) )
		);
		$bogo = $out[0]['bogo'];
		$this->assertSame( 1, $bogo['buy'] );
		$this->assertSame( 1, $bogo['get'] );
		$this->assertSame( 100.0, $bogo['discount'] );
	}

	public function test_mixmatch_fixed_price_amount_not_clamped_to_100(): void {
		$out = Bundle_Config::sanitize(
			array( array( 'type' => 'mixmatch', 'mixmatch' => array( 'need' => 3, 'kind' => 'fixed_price', 'amount' => 99 ) ) )
		);
		$mm = $out[0]['mixmatch'];
		$this->assertSame( 'fixed_price', $mm['kind'] );
		$this->assertSame( 99.0, $mm['amount'], 'a fixed bundle price is not a percentage' );
		$this->assertSame( 3, $mm['need'] );
	}

	public function test_active_returns_enabled_ordered_by_priority_then_id(): void {
		update_option(
			Bundle_Config::OPTION,
			array(
				array( 'id' => 'b', 'type' => 'tiered', 'enabled' => true, 'priority' => 1 ),
				array( 'id' => 'a', 'type' => 'tiered', 'enabled' => true, 'priority' => 0 ),
				array( 'id' => 'c', 'type' => 'tiered', 'enabled' => false, 'priority' => 0 ),
			)
		);
		$active = Bundle_Config::active();
		$this->assertSame( array( 'a', 'b' ), array_column( $active, 'id' ), 'disabled dropped, ordered' );
	}

	public function test_all_keeps_disabled_rows(): void {
		update_option(
			Bundle_Config::OPTION,
			array( array( 'id' => 'a', 'type' => 'tiered', 'enabled' => false ) )
		);
		$this->assertCount( 1, Bundle_Config::all() );
	}
}
