<?php
/**
 * Plugin Name: ShopOS QA — template hook listener
 * Description: wp-env QA harness for ShopOS Line template PRs (decisions §11 Ruling 7.1). Copy into wp-content/mu-plugins/ for the QA window, remove after. Never ship to a store.
 *
 * Two jobs, both QA-window-only:
 *
 * 1. Hook-firing census (Ruling 7.1): on every front-end single-product or
 *    product-archive request it records, per checklist hook of that surface
 *    (pdp / plp), whether it fired and the full callback census (priority →
 *    callback names) as of shutdown — i.e. AFTER any takeover-time detaches —
 *    and writes the report to wp-content/shopos-qa-hook-report.json. Flag-on
 *    and flag-off runs must produce identical censuses. Counters register for
 *    the union of both surfaces (the mu-plugin loads before the query is
 *    parsed, so it cannot branch on conditionals at registration time); the
 *    report is scoped to the surface's own checklist at shutdown.
 *
 * 2. Loop-order determinism for render-diff runs (Ruling 7.3): WooCommerce
 *    shuffles related products on every request
 *    (`woocommerce_product_related_posts_shuffle` → shuffle()) and defaults
 *    upsells to orderby=rand, so two fetches of the SAME page differ and
 *    tools/render-diff.sh (which normalizes request tokens, never content)
 *    would flake. While this mu-plugin is installed both loops are pinned to
 *    a stable order — applied identically to both sides of every diff, so
 *    the zero-diff bar is meaningful.
 *
 * @package ShopOSQA
 */

defined( 'ABSPATH' ) || exit;

// ---- Job 2: pin request-varying render noise (must load for BOTH sides ----
// of every diff run). Three independent sources, all found the hard way:

// (a) Related-product SELECTION: WC shuffles the related-ids pool per request.
add_filter( 'woocommerce_product_related_posts_shuffle', '__return_false' );

// (b) Related/upsell DISPLAY order: independent of (a) — both loops default
// orderby=rand (related via wc_products_array_orderby on the output args,
// upsells via woocommerce_upsell_display's $orderby). Priority 10001 so the
// pin lands after any template-level args filter (the PDP template pins
// posts_per_page/columns at 9999 and deliberately leaves orderby alone).
add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		$args['orderby'] = 'title';
		$args['order']   = 'asc';
		return $args;
	},
	10001
);
add_filter(
	'woocommerce_upsells_orderby',
	static function () {
		return 'title';
	}
);

// (c) The quantity input id is uniqid()-generated per request — pin it to a
// deterministic per-product id.
add_filter(
	'woocommerce_quantity_input_args',
	static function ( $args, $product ) {
		$args['input_id'] = 'quantity_qa_' . ( $product instanceof WC_Product ? $product->get_id() : '0' );
		return $args;
	},
	10,
	2
);

// ---- Job 1: hook-firing census ----

/**
 * The per-surface checklist hooks. Actions are counted when they fire;
 * filters are counted when they run. Census (registered callbacks) is
 * captured for the surface's own list at shutdown.
 *
 * @param string $surface 'pdp' (single product) or 'plp' (product archive).
 * @return array
 */
function shopos_qa_checklist_hooks( $surface = 'pdp' ) {
	if ( 'plp' === $surface ) {
		return array(
			'actions' => array(
				'woocommerce_before_main_content',
				'woocommerce_shop_loop_header',
				'woocommerce_before_shop_loop',
				'woocommerce_shop_loop',
				'woocommerce_before_shop_loop_item',
				'woocommerce_before_shop_loop_item_title',
				'woocommerce_shop_loop_item_title',
				'woocommerce_after_shop_loop_item_title',
				'woocommerce_after_shop_loop_item',
				'woocommerce_after_shop_loop',
				'woocommerce_no_products_found',
				'woocommerce_after_main_content',
				'woocommerce_sidebar',
			),
			'filters' => array(),
		);
	}

	return array(
		'actions' => array(
			'woocommerce_before_single_product',
			'woocommerce_before_single_product_summary',
			'woocommerce_single_product_summary',
			'woocommerce_after_single_product_summary',
			'woocommerce_after_single_product',
		),
		'filters' => array(
			'woocommerce_product_tabs',
		),
	);
}

/**
 * Union of both surfaces' checklists, for counter registration.
 *
 * @return array
 */
function shopos_qa_all_hooks() {
	$pdp = shopos_qa_checklist_hooks( 'pdp' );
	$plp = shopos_qa_checklist_hooks( 'plp' );
	return array(
		'actions' => array_values( array_unique( array_merge( $pdp['actions'], $plp['actions'] ) ) ),
		'filters' => array_values( array_unique( array_merge( $pdp['filters'], $plp['filters'] ) ) ),
	);
}

$GLOBALS['shopos_qa_fired'] = array();

foreach ( shopos_qa_all_hooks()['actions'] as $shopos_qa_hook ) {
	add_action(
		$shopos_qa_hook,
		static function () use ( $shopos_qa_hook ) {
			$GLOBALS['shopos_qa_fired'][ $shopos_qa_hook ] = ( $GLOBALS['shopos_qa_fired'][ $shopos_qa_hook ] ?? 0 ) + 1;
		},
		-9999
	);
}
foreach ( shopos_qa_all_hooks()['filters'] as $shopos_qa_hook ) {
	add_filter(
		$shopos_qa_hook,
		static function ( $value ) use ( $shopos_qa_hook ) {
			$GLOBALS['shopos_qa_fired'][ $shopos_qa_hook ] = ( $GLOBALS['shopos_qa_fired'][ $shopos_qa_hook ] ?? 0 ) + 1;
			return $value;
		},
		-9999
	);
}

/**
 * Human-readable name for a hook callback.
 *
 * @param mixed $callback A $wp_filter callback entry's 'function'.
 * @return string
 */
function shopos_qa_callback_name( $callback ) {
	if ( is_string( $callback ) ) {
		return $callback;
	}
	if ( is_array( $callback ) && 2 === count( $callback ) ) {
		$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
		return $class . '::' . $callback[1];
	}
	if ( $callback instanceof Closure ) {
		return 'Closure';
	}
	return is_object( $callback ) ? get_class( $callback ) : gettype( $callback );
}

add_action(
	'shutdown',
	static function () {
		if ( is_admin() || ! function_exists( 'is_product' ) ) {
			return;
		}

		if ( is_product() ) {
			$surface = 'pdp';
		} elseif ( is_shop() || is_product_taxonomy() ) {
			$surface = 'plp';
		} else {
			return;
		}

		global $wp_filter;

		$hooks  = shopos_qa_checklist_hooks( $surface );
		$census = array();
		foreach ( array_merge( $hooks['actions'], $hooks['filters'] ) as $hook ) {
			$census[ $hook ] = array();
			if ( ! isset( $wp_filter[ $hook ] ) ) {
				continue;
			}
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $entry ) {
					$name = shopos_qa_callback_name( $entry['function'] );
					if ( 'Closure' === $name && -9999 === $priority ) {
						continue; // This harness's own counters.
					}
					$census[ $hook ][] = array(
						'priority' => $priority,
						'callback' => $name,
					);
				}
			}
		}

		$fired = array_intersect_key(
			$GLOBALS['shopos_qa_fired'],
			array_flip( array_merge( $hooks['actions'], $hooks['filters'] ) )
		);

		// One class_exists for all flag reads — a typo'd FQCN in a copy
		// wouldn't error (class_exists just returns false), it would report
		// that flag as false forever and misdirect a census debugging round.
		$has_flags = class_exists( '\ShopOS\Core\Core\Feature_Flags' );

		$report = array(
			'url'      => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			'surface'  => $surface,
			'template' => get_page_template_slug() ? get_page_template_slug() : ( $GLOBALS['template'] ?? '' ),
			'flags'    => array(
				'theme.template_pdp'   => $has_flags && \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_pdp' ),
				'theme.template_plp'   => $has_flags && \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'template_plp' ),
				'theme.fonts_selfhost' => $has_flags && \ShopOS\Core\Core\Feature_Flags::is_enabled( 'theme', 'fonts_selfhost' ),
			),
			'fired'    => $fired,
			'census'   => $census,
		);

		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- QA harness, wp-env only.
			WP_CONTENT_DIR . '/shopos-qa-hook-report.json',
			wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
		);
	},
	0
);
