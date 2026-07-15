<?php
declare(strict_types=1);

use ShopOS\Core\Core\Cache;
use PHPUnit\Framework\TestCase;

/**
 * The object-cache facade. Verifies the two backends behave identically at the
 * get/set/delete contract, that the transient fallback keys off the caller's key
 * verbatim (byte-identical to pre-abstraction code), and that the kill-switch
 * filter forces the transient path even when an external cache is present.
 *
 * @covers \ShopOS\Core\Core\Cache
 */
final class CacheTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_transients']  = array();
		$GLOBALS['fr_object_cache'] = array();
		$GLOBALS['fr_ext_cache']   = false;
	}

	protected function tearDown(): void {
		// Drop any kill-switch filters a test registered so they can't leak.
		unset( $GLOBALS['fr_hooks']['shopos_core/cache/use_object_cache'] );
		$GLOBALS['fr_ext_cache'] = false;
		parent::tearDown();
	}

	/* -------- transient fallback (no object cache) -------- */

	public function test_transient_path_round_trips(): void {
		$this->assertFalse( Cache::get( 'k_missing', 'grp' ) );

		$this->assertTrue( Cache::set( 'k1', array( 'a' => 1 ), 300, 'grp' ) );
		$this->assertSame( array( 'a' => 1 ), Cache::get( 'k1', 'grp' ) );

		Cache::delete( 'k1', 'grp' );
		$this->assertFalse( Cache::get( 'k1', 'grp' ) );
	}

	public function test_transient_path_uses_key_verbatim_ignoring_group(): void {
		// Byte-identical guarantee: the transient option row is the caller's key,
		// with no group prefix — a store with no object cache sees no change.
		Cache::set( 'shopos_core_sf_q_abc', 'v', 60, 'shopos_shop_filters' );
		$this->assertArrayHasKey( 'shopos_core_sf_q_abc', $GLOBALS['fr_transients'] );
		// The group is not folded into the transient name.
		$this->assertArrayNotHasKey( 'shopos_shop_filters_shopos_core_sf_q_abc', $GLOBALS['fr_transients'] );
	}

	public function test_transient_path_miss_returns_false_like_get_transient(): void {
		// Facet cache relies on `false` (not null) so its `is_array()` guard holds.
		$this->assertFalse( Cache::get( 'nope' ) );
	}

	/* -------- object-cache backend -------- */

	public function test_object_cache_path_round_trips_and_bypasses_transients(): void {
		$GLOBALS['fr_ext_cache'] = true;

		$this->assertFalse( Cache::get( 'k1', 'grp' ) );
		Cache::set( 'k1', 42, 300, 'grp' );
		$this->assertSame( 42, Cache::get( 'k1', 'grp' ) );

		// Routed to the object cache, not the options/transient table.
		$this->assertArrayHasKey( 'grp' . "\0" . 'k1', $GLOBALS['fr_object_cache'] );
		$this->assertSame( array(), $GLOBALS['fr_transients'] );

		Cache::delete( 'k1', 'grp' );
		$this->assertFalse( Cache::get( 'k1', 'grp' ) );
	}

	public function test_object_cache_groups_are_isolated(): void {
		$GLOBALS['fr_ext_cache'] = true;
		Cache::set( 'same', 'A', 0, 'g1' );
		Cache::set( 'same', 'B', 0, 'g2' );
		$this->assertSame( 'A', Cache::get( 'same', 'g1' ) );
		$this->assertSame( 'B', Cache::get( 'same', 'g2' ) );
	}

	public function test_object_cache_stores_falsey_value_distinct_from_miss(): void {
		// wp_cache's $found out-param lets a legitimately-stored 0 differ from a
		// miss — the transient path can't, but the object path should.
		$GLOBALS['fr_ext_cache'] = true;
		Cache::set( 'zero', 0, 0, 'grp' );
		$this->assertSame( 0, Cache::get( 'zero', 'grp' ) );
	}

	/* -------- kill switch -------- */

	public function test_filter_forces_transient_path_even_with_object_cache(): void {
		$GLOBALS['fr_ext_cache'] = true;
		add_filter( 'shopos_core/cache/use_object_cache', '__return_false' );

		Cache::set( 'k1', 'v', 60, 'grp' );
		// Landed in transients despite the external cache being "present".
		$this->assertArrayHasKey( 'k1', $GLOBALS['fr_transients'] );
		$this->assertSame( array(), $GLOBALS['fr_object_cache'] );
		$this->assertSame( 'v', Cache::get( 'k1', 'grp' ) );
	}
}
