<?php
declare(strict_types=1);

use Freeman\Core\Modules\ShopFilters\Indexer;
use PHPUnit\Framework\TestCase;

/**
 * Dirty-queue mechanics (pure, option-backed). The per-product reindex and the
 * reconcile sweep touch WooCommerce / $wpdb and are integration / live QA.
 *
 * @covers \Freeman\Core\Modules\ShopFilters\Indexer
 */
final class ShopFiltersIndexerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
		$GLOBALS['fr_cron'] = array();
	}

	public function test_mark_dirty_queues_and_dedupes(): void {
		$indexer = new Indexer();
		$indexer->mark_dirty( 5 );
		$indexer->mark_dirty( 7 );
		$indexer->mark_dirty( 5 ); // Duplicate collapses.

		$this->assertSame( array( 5, 7 ), array_keys( $indexer->get_dirty_queue() ) );
	}

	public function test_mark_dirty_schedules_drain_once(): void {
		$indexer = new Indexer();
		$indexer->mark_dirty( 5 );
		$indexer->mark_dirty( 7 );

		$this->assertArrayHasKey( Indexer::DRAIN_HOOK, $GLOBALS['fr_cron'] );
		$this->assertCount( 1, $GLOBALS['fr_cron'][ Indexer::DRAIN_HOOK ] );
	}

	public function test_mark_dirty_ignores_non_positive_ids(): void {
		$indexer = new Indexer();
		$indexer->mark_dirty( 0 );
		$indexer->mark_dirty( -3 );

		$this->assertSame( array(), $indexer->get_dirty_queue() );
		$this->assertArrayNotHasKey( Indexer::DRAIN_HOOK, $GLOBALS['fr_cron'] );
	}

	public function test_drain_clears_queue(): void {
		$indexer = new Indexer();
		$indexer->mark_dirty( 5 );
		// No \WooCommerce class in the test env, so the reindex loop is skipped,
		// but the queue is still cleared (the batch is considered drained).
		$indexer->drain_queue();

		$this->assertSame( array(), $indexer->get_dirty_queue() );
	}

	public function test_bump_rev_increments_the_index_revision(): void {
		// The revision is the invalidation segment of Query_Builder's facet
		// cache key; each index-write batch advances it. (The drain/reconcile
		// call sites need WooCommerce + WP_Query and are integration.)
		$indexer = new Indexer();

		$this->assertSame( 0, (int) get_option( Indexer::REV_OPTION, 0 ) );
		$indexer->bump_rev();
		$indexer->bump_rev();
		$this->assertSame( 2, (int) get_option( Indexer::REV_OPTION, 0 ) );
	}

	public function test_maybe_bump_rev_debounces_within_window(): void {
		// A4: the high-frequency drain path must not retire the whole facet cache
		// on every order — bumps collapse within REV_DEBOUNCE seconds.
		$indexer = new Indexer();

		$indexer->maybe_bump_rev(); // REV_AT starts at 0 → first bump goes through.
		$this->assertSame( 1, (int) get_option( Indexer::REV_OPTION, 0 ) );

		$indexer->maybe_bump_rev(); // Within the window → absorbed.
		$this->assertSame( 1, (int) get_option( Indexer::REV_OPTION, 0 ) );

		// Age the last-bump timestamp past the debounce window.
		update_option( Indexer::REV_AT_OPTION, time() - Indexer::REV_DEBOUNCE - 1, false );
		$indexer->maybe_bump_rev();
		$this->assertSame( 2, (int) get_option( Indexer::REV_OPTION, 0 ) );
	}
}
