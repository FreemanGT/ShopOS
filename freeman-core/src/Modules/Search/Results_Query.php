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
 * Freeman ProductSlider grids: a "current query" ProductSlider doesn't treat a
 * search as an archive, so it renders source `all` (every product) through its
 * `freeman_core/product_slider/query_args` filter — we hook that and inject the
 * engine matches (constrain_slider_query), which is how the engine reaches the
 * grid on stores that render search results with the widget rather than the
 * theme's native loop.
 *
 * The WP adapters touch WP_Query / $wpdb (integration / live QA); the gating +
 * id planning are pure statics, unit-tested.
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
		add_filter( 'freeman_core/product_slider/query_args', array( $this, 'constrain_slider_query' ), 10, 2 );
		add_filter( 'freeman_core/shop_filters/search_product_ids', array( $this, 'supply_engine_ids' ), 10, 2 );
	}

	/* -----------------------------------------------------------------
	 * Pure seams (unit-tested)
	 * ----------------------------------------------------------------- */

	/**
	 * Whether the engine should drive this query. We deliberately do NOT gate on
	 * is_search(): on this store the search results page is rendered by an
	 * Elementor product-archive template whose main query carries post_type
	 * `product` but NOT the `s` query var (the term lives only in the URL), so
	 * is_search() is false and $q->get('s') is empty there. The trigger is instead
	 * "a product main query + a search term in the request" — which also covers
	 * the plain native search. A product query with no request term (the ordinary
	 * shop archive) is left alone.
	 *
	 * @param bool   $is_admin   In wp-admin.
	 * @param bool   $is_main    Main query.
	 * @param bool   $is_product Product post type / archive.
	 * @param string $term       Request search term.
	 * @param bool   $has_data   Index has rows.
	 * @return bool
	 */
	public static function should_handle( $is_admin, $is_main, $is_product, $term, $has_data ) {
		return ! $is_admin && $is_main && $is_product && '' !== trim( (string) $term ) && (bool) $has_data;
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

	/* -----------------------------------------------------------------
	 * WP adapters (integration / live QA)
	 * ----------------------------------------------------------------- */

	/**
	 * pre_get_posts: take over the front-end product main query when the URL
	 * carries a search term — including an Elementor product-archive page that
	 * never set the `s` query var on its main query (so the term is read from the
	 * request, not the query). Constraining the main query fixes both the grid
	 * content and the pagination (max_num_pages follows the engine match count).
	 *
	 * @param \WP_Query $q Query.
	 */
	public function apply( $q ) {
		$this->active = false;
		if ( ! $q instanceof \WP_Query ) {
			return;
		}
		$term = $this->request_search_term();

		if ( ! self::should_handle( is_admin(), $q->is_main_query(), $this->is_product_query( $q ), $term, $this->repo->has_data() ) ) {
			return;
		}

		$q->set( 'post__in', self::plan_ids( $this->repo->search( $term, -1, true ) ) );
		$q->set( 'orderby', 'post__in' );
		$this->active = true;
	}

	/**
	 * The search term from the request (where it actually lives — the main query
	 * may not carry it as the `s` var on an archive-template render). Sanitised.
	 *
	 * @return string
	 */
	private function request_search_term() {
		if ( ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		return trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether a query targets products — by post type or as the product archive.
	 *
	 * @param \WP_Query $q Query.
	 * @return bool
	 */
	private function is_product_query( $q ) {
		$post_type = $q->get( 'post_type' );
		if ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
			return true;
		}
		return $q->is_post_type_archive( 'product' );
	}

	/**
	 * Whether a ProductSlider widget on a search page should be constrained to the
	 * engine matches. Two cases reflect the live search:
	 *
	 * - `current_query` (any display mode): the widget stands in for the archive
	 *   query, so a search must narrow it. (On a search it falls through to the
	 *   `all` code path and reaches the `query_args` filter we hook here.)
	 * - `all` in *grid* mode: an Elementor archive template's products grid is
	 *   frequently configured with the fixed `all products` source rather than
	 *   `current query`; in grid mode it is the results grid, so a search must
	 *   narrow it too — otherwise the whole catalog renders (the live arba4 bug).
	 *
	 * Other fixed sources (`featured`/`category`/`tag`/`related`/`manual`) and a
	 * slider-mode `all` carousel are intentional curations and left untouched.
	 *
	 * @param string $source  Widget `source` setting.
	 * @param bool   $is_grid Widget renders as a grid (vs slider).
	 * @return bool
	 */
	public static function should_constrain_slider( $source, $is_grid ) {
		if ( 'current_query' === $source ) {
			return true;
		}
		return 'all' === $source && (bool) $is_grid;
	}

	/**
	 * Constrain a Freeman ProductSlider widget's query to the engine ids on a
	 * search page, by injecting them through its own
	 * `freeman_core/product_slider/query_args` filter. Scoped by
	 * should_constrain_slider() (current-query widgets, plus the grid-mode
	 * all-products archive grid). The term is read from the request (the main
	 * query may not carry `s` on an Elementor archive render, and $wp_query is
	 * swapped during the widget render).
	 *
	 * @param array $args     wc_get_products() args.
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public function constrain_slider_query( $args, $settings ) {
		if ( ! is_array( $args ) || ! is_array( $settings ) ) {
			return $args;
		}
		$is_grid = ( ( $settings['display_mode'] ?? 'slider' ) === 'grid' );
		if ( ! self::should_constrain_slider( (string) ( $settings['source'] ?? '' ), $is_grid ) ) {
			return $args;
		}
		$term = $this->request_search_term();
		if ( '' === $term || ! $this->repo->has_data() ) {
			return $args;
		}

		// wc_get_products() uses `include` (+ orderby=include to keep rank). [0]
		// on a no-match forces an empty grid (engine authoritative once indexed).
		$args['include'] = self::plan_ids( $this->repo->search( $term, -1, true ) );
		$args['orderby'] = 'include';
		$args['limit']   = -1;
		return $args;
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
		return $this->repo->search( (string) $term, -1, true );
	}
}
