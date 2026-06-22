<?php
/**
 * Search results page — drives the native (theme-styled) product search grid
 * from the engine.
 *
 * On a front-end product search, the engine decides which products match and in
 * what relevance order; the theme's existing search template renders them, with
 * pagination, for free. No custom search.php.
 *
 * Coexistence with Shop Filters: this sets `post__in` (engine order) at
 * pre_get_posts priority 5; Shop Filters' apply_instock_post_in() runs at 20 and
 * *intersects into* an existing post__in while preserving the first array's
 * order, so facet filtering narrows within the engine's result set and the
 * relevance order survives. We also neutralise WP's native `s` LIKE (via
 * posts_search) so it doesn't AND-narrow our set, while leaving `s` set so
 * is_search() stays true and Shop Filters' search-page logic still engages. The
 * facet panel's base universe is fed engine ids via the additive
 * `freeman_core/shop_filters/search_product_ids` filter.
 *
 * The pre_get_posts adapter touches WP_Query / $wpdb (integration / live QA);
 * the gating + id planning are pure statics, unit-tested.
 *
 * Only constructed when the results feature flag is on (Module::boot()).
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Results query.
 */
final class Results_Query {

	/**
	 * @var Search_Repository
	 */
	private $repo;

	/**
	 * Whether we took over the current main query — gates posts_search.
	 *
	 * @var bool
	 */
	private $active = false;

	/**
	 * @param Search_Repository|null $repo Repository (injected for tests).
	 */
	public function __construct( Search_Repository $repo = null ) {
		$this->repo = $repo ? $repo : new Search_Repository();
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'pre_get_posts', array( $this, 'apply' ), 5 );
		add_filter( 'posts_search', array( $this, 'neutralize_search' ), 10, 2 );
		add_filter( 'the_posts', array( $this, 'enforce_on_search' ), 99999, 2 );
		add_filter( 'freeman_core/shop_filters/search_product_ids', array( $this, 'supply_engine_ids' ), 10, 2 );
	}

	/* -----------------------------------------------------------------
	 * Pure seams (unit-tested)
	 * ----------------------------------------------------------------- */

	/**
	 * Whether the engine should drive this query. Product search only — a generic
	 * theme search (no product post type) spans pages/posts and is left to WP.
	 *
	 * @param bool   $is_admin   In wp-admin.
	 * @param bool   $is_main    Main query.
	 * @param bool   $is_search  Search query.
	 * @param bool   $is_product Product post type.
	 * @param string $term       Search term.
	 * @param bool   $has_data   Index has rows.
	 * @return bool
	 */
	public static function should_handle( $is_admin, $is_main, $is_search, $is_product, $term, $has_data ) {
		return ! $is_admin && $is_main && $is_search && $is_product && '' !== trim( (string) $term ) && (bool) $has_data;
	}

	/**
	 * The post__in value for an engine id list. A genuine no-match becomes [0]
	 * (WP treats an empty post__in as "no constraint", so we force zero results —
	 * the engine is authoritative once the index has data).
	 *
	 * @param int[] $ids Engine ids.
	 * @return int[]
	 */
	public static function plan_ids( array $ids ) {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		return empty( $ids ) ? array( 0 ) : $ids;
	}

	/**
	 * Filter a fetched post list to the engine ids, in engine-rank order. Posts
	 * not in the id set are dropped; the result follows the id order. Pure.
	 *
	 * @param array $posts Post objects (or ids).
	 * @param int[] $ids   Engine ids, best-first.
	 * @return array
	 */
	public static function order_posts_by_ids( array $posts, array $ids ) {
		$rank = array();
		$i    = 0;
		foreach ( array_map( 'intval', $ids ) as $id ) {
			if ( ! isset( $rank[ $id ] ) ) {
				$rank[ $id ] = $i++;
			}
		}
		$kept = array();
		foreach ( $posts as $post ) {
			$pid = is_object( $post ) ? (int) ( $post->ID ?? 0 ) : (int) $post;
			if ( isset( $rank[ $pid ] ) ) {
				$kept[ $rank[ $pid ] ] = $post;
			}
		}
		ksort( $kept );
		return array_values( $kept );
	}

	/* -----------------------------------------------------------------
	 * WP adapters (integration / live QA)
	 * ----------------------------------------------------------------- */

	/**
	 * pre_get_posts: take over a front-end product search main query.
	 *
	 * @param \WP_Query $q Query.
	 */
	public function apply( $q ) {
		$this->active = false;
		if ( ! $q instanceof \WP_Query ) {
			return;
		}
		$post_type  = $q->get( 'post_type' );
		$is_product = ( 'product' === $post_type ) || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
		$term       = (string) $q->get( 's' );

		if ( ! self::should_handle( is_admin(), $q->is_main_query(), $q->is_search(), $is_product, $term, $this->repo->has_data() ) ) {
			return;
		}

		$q->set( 'post__in', self::plan_ids( $this->repo->search( trim( $term ), -1 ) ) );
		$q->set( 'orderby', 'post__in' );
		$this->active = true;
	}

	/**
	 * the_posts safety net (priority 99999): constrain + reorder the final product
	 * list on a search to the engine's ids. This catches the grid even when it is
	 * rendered by a query that bypasses the main query — on this store Elementor
	 * swaps $wp_query, so the pre_get_posts/is_main_query() path above misses the
	 * real grid while this runs on whatever query actually produces the posts
	 * (the same reason Shop Filters enforces on the_posts for AWS). Scoped to a
	 * product query carrying a search term.
	 *
	 * @param array     $posts Posts from the query.
	 * @param \WP_Query $q     Query.
	 * @return array
	 */
	public function enforce_on_search( $posts, $q ) {
		if ( is_admin() || ! $q instanceof \WP_Query || empty( $posts ) ) {
			return $posts;
		}
		$term = (string) $q->get( 's' );
		if ( '' === trim( $term ) ) {
			return $posts;
		}
		$post_type  = $q->get( 'post_type' );
		$is_product = ( 'product' === $post_type ) || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
		if ( ! $is_product || ! $this->repo->has_data() ) {
			return $posts;
		}
		return self::order_posts_by_ids( $posts, $this->repo->search( trim( $term ), -1 ) );
	}

	/**
	 * posts_search: blank out WP's native search WHERE for the query we took over,
	 * so its title/content LIKE doesn't narrow our engine post__in. Scoped to the
	 * main query (secondary queries keep their own search).
	 *
	 * @param string    $search Search SQL.
	 * @param \WP_Query $q      Query.
	 * @return string
	 */
	public function neutralize_search( $search, $q ) {
		if ( $this->active && $q instanceof \WP_Query && $q->is_main_query() ) {
			return '';
		}
		return $search;
	}

	/**
	 * Feed Shop Filters' facet base universe the engine's ranked ids, so the panel
	 * counts match the engine-driven grid. Falls through to the native ids when
	 * the index has no data.
	 *
	 * @param int[]  $ids  Native WP product-search ids.
	 * @param string $term Search term.
	 * @return int[]
	 */
	public function supply_engine_ids( $ids, $term ) {
		if ( ! $this->repo->has_data() ) {
			return $ids;
		}
		return $this->repo->search( (string) $term, -1 );
	}
}
