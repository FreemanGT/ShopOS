<?php
/**
 * Search indexer.
 *
 * Keeps the full-text index fresh with minimal impact: an event-driven dirty
 * queue (WC product/stock lifecycle hooks → 30s debounced drain) plus a
 * periodic reconciliation sweep that re-indexes products modified since the
 * last sweep, batched and self-chaining so one tick never runs long. The sweep
 * is scheduled via Action Scheduler when WooCommerce makes it available,
 * falling back to wp-cron — never a hard dependency.
 *
 * Mechanics mirror the Shop Filters indexer; the difference is the payload:
 * one denormalised searchable-text row per product instead of taxonomy rows.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Indexer.
 */
final class Indexer {

	const QUEUE_OPTION     = 'freeman_core_search_dirty_queue';
	const WATERMARK_OPTION = 'freeman_core_search_last_sweep_gmt';
	const LAST_RUN_OPTION  = 'freeman_core_search_last_run_gmt';
	const DRAIN_HOOK       = 'freeman_core_search_drain_queue';
	const RECONCILE_HOOK   = 'freeman_core_search_reconcile';
	const CRON_SCHEDULE    = 'freeman_search_5min';
	const AS_GROUP         = 'freeman-search';
	const BATCH_SIZE       = 50;
	const SWEEP_INTERVAL   = 300; // 5 minutes.
	const DEBOUNCE_DELAY   = 10;  // Seconds after a product change before its reindex drains. Low for near-live search on a fast-moving shop; still batched/background so bulk edits collapse to one reindex per product.

	/**
	 * Index storage.
	 *
	 * @var Search_Repository
	 */
	private $repo;

	/**
	 * Constructor.
	 *
	 * @param Search_Repository|null $repo Repository (injected for tests).
	 */
	public function __construct( Search_Repository $repo = null ) {
		$this->repo = $repo ? $repo : new Search_Repository();
	}

	/* -----------------------------------------------------------------
	 * Wiring
	 * ----------------------------------------------------------------- */

	/**
	 * Register the WC lifecycle listeners + cron callbacks. Only called from
	 * Module::boot() when the indexer feature flag is on.
	 *
	 * ponytail: no term-rename hooks (set_object_terms / edited_term). A category
	 * or tag rename doesn't bump a product's modified date, so the 5-min sweep
	 * won't auto-catch it — the admin "Reindex all" button covers that rare case.
	 * Add term hooks only if rename-staleness proves to matter in practice.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_update_product', array( $this, 'handle_product_event' ), 20, 1 );
		add_action( 'woocommerce_new_product', array( $this, 'handle_product_event' ), 20, 1 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'handle_product_event' ), 20, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'handle_product_event' ), 20, 1 );
		add_action( 'before_delete_post', array( $this, 'handle_product_event' ), 20, 1 );
		add_action( 'wp_trash_post', array( $this, 'handle_product_event' ), 20, 1 );

		add_action( self::DRAIN_HOOK, array( $this, 'drain_queue' ) );
		add_action( self::RECONCILE_HOOK, array( $this, 'run_reconcile' ) );
	}

	/**
	 * Ensure the recurring reconcile sweep is scheduled (idempotent). Prefers
	 * Action Scheduler; falls back to a custom-recurrence wp-cron event.
	 */
	public function ensure_scheduled() {
		if ( function_exists( 'as_schedule_recurring_action' ) && function_exists( 'as_next_scheduled_action' ) ) {
			if ( ! as_next_scheduled_action( self::RECONCILE_HOOK ) ) {
				as_schedule_recurring_action( time() + self::SWEEP_INTERVAL, self::SWEEP_INTERVAL, self::RECONCILE_HOOK, array(), self::AS_GROUP );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::RECONCILE_HOOK ) ) {
			wp_schedule_event( time() + self::SWEEP_INTERVAL, self::CRON_SCHEDULE, self::RECONCILE_HOOK );
		}
	}

	/**
	 * Tear down all scheduling + the queue (deactivation / uninstall).
	 */
	public function unschedule() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::RECONCILE_HOOK );
		}
		wp_clear_scheduled_hook( self::RECONCILE_HOOK );
		wp_clear_scheduled_hook( self::DRAIN_HOOK );
		delete_option( self::QUEUE_OPTION );
	}

	/* -----------------------------------------------------------------
	 * Dirty queue (pure, option-backed)
	 * ----------------------------------------------------------------- */

	/**
	 * Route a WC lifecycle event to the dirty queue. Accepts an id or a
	 * WC_Product (or variation, whose parent is queued). Non-products are
	 * ignored, so the broad before_delete_post / wp_trash_post hooks don't
	 * pollute the queue with pages, orders, etc.
	 *
	 * @param int|\WC_Product $arg Event payload.
	 */
	public function handle_product_event( $arg ) {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			return;
		}
		$product = $arg instanceof \WC_Product ? $arg : ( is_numeric( $arg ) ? wc_get_product( (int) $arg ) : null );
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$id = $product instanceof \WC_Product_Variation ? $product->get_parent_id() : $product->get_id();
		$this->mark_dirty( (int) $id );
	}

	/**
	 * Add a product id to the dirty queue and ensure the debounced drain is
	 * scheduled. Keyed by id so repeated touches during a bulk import collapse
	 * to one reindex.
	 *
	 * @param int $product_id Product id.
	 */
	public function mark_dirty( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return;
		}
		$queue                = $this->get_dirty_queue();
		$queue[ $product_id ] = true;
		update_option( self::QUEUE_OPTION, $queue, false );

		if ( ! wp_next_scheduled( self::DRAIN_HOOK ) ) {
			wp_schedule_single_event( time() + self::DEBOUNCE_DELAY, self::DRAIN_HOOK );
		}
	}

	/**
	 * Current dirty queue as an id => true map.
	 *
	 * @return array<int,bool>
	 */
	public function get_dirty_queue() {
		$queue = get_option( self::QUEUE_OPTION, array() );
		return is_array( $queue ) ? $queue : array();
	}

	/**
	 * Drain the queue — reindex each queued product, then clear it.
	 */
	public function drain_queue() {
		$queue = $this->get_dirty_queue();
		delete_option( self::QUEUE_OPTION );
		if ( empty( $queue ) || ! class_exists( '\\WooCommerce' ) ) {
			return;
		}
		foreach ( array_keys( $queue ) as $product_id ) {
			$this->reindex_product( (int) $product_id );
		}
		$this->flush_runtime_cache();
	}

	/* -----------------------------------------------------------------
	 * Reconciliation sweep (integration)
	 * ----------------------------------------------------------------- */

	/**
	 * Re-index products modified since the last sweep, oldest first, one batch
	 * per tick. Advances a watermark and self-chains a near-term follow-up while
	 * batches keep coming, so the initial full index and large catalogues make
	 * progress without ever running long.
	 */
	public function run_reconcile() {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			return;
		}

		update_option( self::LAST_RUN_OPTION, gmdate( 'Y-m-d H:i:s' ), false );

		$since = (string) get_option( self::WATERMARK_OPTION, '' );

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => self::BATCH_SIZE,
			'orderby'        => 'modified',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);
		if ( '' !== $since ) {
			$args['date_query'] = array(
				array(
					'column'    => 'post_modified_gmt',
					'after'     => $since,
					'inclusive' => false,
				),
			);
		}

		$query = new \WP_Query( $args );
		if ( empty( $query->posts ) ) {
			update_option( self::WATERMARK_OPTION, gmdate( 'Y-m-d H:i:s' ), false );
			return;
		}

		$last_modified = $since;
		foreach ( $query->posts as $product_id ) {
			$this->reindex_product( (int) $product_id );
			$post = get_post( $product_id );
			if ( $post && ! empty( $post->post_modified_gmt ) ) {
				$last_modified = $post->post_modified_gmt;
			}
		}
		update_option( self::WATERMARK_OPTION, $last_modified, false );
		$this->flush_runtime_cache();

		if ( count( $query->posts ) === self::BATCH_SIZE ) {
			$this->chain_reconcile();
		}
	}

	/**
	 * Queue an immediate follow-up sweep while batches keep coming.
	 */
	private function chain_reconcile() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::RECONCILE_HOOK, array(), self::AS_GROUP );
			return;
		}
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::RECONCILE_HOOK );
	}

	/* -----------------------------------------------------------------
	 * Per-product indexing (integration)
	 * ----------------------------------------------------------------- */

	/**
	 * Rebuild a single product's index row.
	 *
	 * @param int|\WC_Product $product_or_id Product or id.
	 */
	public function reindex_product( $product_or_id ) {
		$product = $product_or_id instanceof \WC_Product ? $product_or_id : wc_get_product( $product_or_id );

		if ( ! $product instanceof \WC_Product ) {
			if ( is_numeric( $product_or_id ) ) {
				$this->repo->delete_product( (int) $product_or_id );
			}
			return;
		}

		$pid = (int) $product->get_id();

		if ( ! $this->is_indexable( $product ) ) {
			$this->repo->delete_product( $pid );
			return;
		}

		$search_text = Query_Engine::build_search_text(
			$product->get_name(),
			$product->get_sku(),
			$this->term_names( $pid, 'product_cat' ),
			$this->term_names( $pid, 'product_tag' ),
			$product->get_short_description(),
			$product->get_description()
		);

		$this->repo->upsert(
			$pid,
			(string) $product->get_sku(),
			(string) $product->get_name(),
			$search_text,
			$product->is_in_stock() ? 1 : 0
		);
	}

	/**
	 * Term names for a product in one taxonomy (for the searchable blob).
	 *
	 * @param int    $pid      Product id.
	 * @param string $taxonomy Taxonomy.
	 * @return string[]
	 */
	private function term_names( $pid, $taxonomy ) {
		$terms = get_the_terms( $pid, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$names = array();
		foreach ( $terms as $term ) {
			if ( isset( $term->name ) ) {
				$names[] = (string) $term->name;
			}
		}
		return $names;
	}

	/**
	 * Whether a product belongs in the search index — published and visible in
	 * search. Unlike the Shop Filters index (which mirrors the shop grid), this
	 * includes 'search'-only products and excludes 'catalog'-only ones, matching
	 * WooCommerce's own search visibility.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	private function is_indexable( $product ) {
		if ( 'publish' !== $product->get_status() ) {
			return false;
		}
		return in_array( $product->get_catalog_visibility(), array( 'visible', 'search' ), true );
	}

	/* -----------------------------------------------------------------
	 * Full rebuild (used by the admin tool)
	 * ----------------------------------------------------------------- */

	/**
	 * Re-index one offset-paged batch of all products. Returns the number
	 * processed so the caller can advance the offset / show progress.
	 *
	 * @param int $offset     Offset.
	 * @param int $batch_size Batch size.
	 * @return int Products processed.
	 */
	public function reindex_batch( $offset, $batch_size ) {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			return 0;
		}
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => (int) $batch_size,
				'offset'         => (int) $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$count = count( $query->posts );
		foreach ( $query->posts as $product_id ) {
			$this->reindex_product( (int) $product_id );
		}

		// An empty batch means the full reindex reached the end of the catalogue;
		// park the watermark + last-run at "now" so the periodic sweep treats the
		// index as current and doesn't re-process everything just rebuilt.
		if ( 0 === $count ) {
			$now = gmdate( 'Y-m-d H:i:s' );
			update_option( self::WATERMARK_OPTION, $now, false );
			update_option( self::LAST_RUN_OPTION, $now, false );
		}

		$this->flush_runtime_cache();
		return $count;
	}

	/**
	 * Total published products (the denominator for the reindex progress bar).
	 *
	 * @return int
	 */
	public function count_products() {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			return 0;
		}
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Repository accessor (for the admin tool's index stats).
	 *
	 * @return Search_Repository
	 */
	public function repository() {
		return $this->repo;
	}

	/**
	 * Flush the per-request object cache between batches to keep memory flat.
	 */
	private function flush_runtime_cache() {
		if ( function_exists( 'wp_cache_flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}
	}
}
