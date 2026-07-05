<?php
/**
 * Search public AJAX endpoint — the live dropdown.
 *
 * Action `freeman_core_search_query` (admin-AJAX, mirroring Shop Filters — no
 * REST). Public: logged-in + logged-out, guarded by a nonce + a per-IP rate
 * limit. Wired whenever the module is enabled (always-on since 1.21.0;
 * Module::boot()).
 *
 * The handler echoes JSON built from live WC products, so it is live-QA; the
 * ranked id query it delegates to (Query_Engine pure SQL) is unit-tested.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

use Freeman\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Public AJAX handler.
 */
final class Ajax {

	const ACTION = 'freeman_core_search_query';
	const NONCE  = 'freeman_core_search_query';
	const LIMIT  = 8;

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @param Module $module Owning module (for the dropdown display settings).
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Register the public query action (logged-in + logged-out).
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_query' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle_query' ) );
	}

	/**
	 * Handle a search query: validate, run, send JSON.
	 */
	public function handle_query() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );

		if ( ! Security::rate_limit( 'search_query', 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'freeman-core' ) ), 429 );
		}

		$request = Security::sanitize_recursive( wp_unslash( $_REQUEST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term    = ( is_array( $request ) && isset( $request['q'] ) ) ? (string) $request['q'] : '';

		if ( ! self::term_meets_min_chars( $term, (int) $this->module->get_option( 'min_chars', 2 ) ) ) {
			// The client already gates keystrokes on minChars; enforcing it here
			// keeps a crafted short request from running the broad LIKE fallback
			// scan. Empty success (not an error) — same shape the dropdown shows
			// for "no results".
			wp_send_json_success( array( 'items' => array(), 'more_url' => '' ) );
		}

		wp_send_json_success(
			array(
				'items'    => $this->build_results( $term ),
				'more_url' => $this->more_url( $term ),
			)
		);
	}

	/**
	 * Whether a term is long enough to query (pure seam). Multibyte-aware so a
	 * two-letter Hebrew term counts as 2, not 4.
	 *
	 * @param string $term Search term.
	 * @param int    $min  Configured minimum characters.
	 * @return bool
	 */
	public static function term_meets_min_chars( $term, $min ) {
		return mb_strlen( trim( (string) $term ) ) >= max( 1, (int) $min );
	}

	/**
	 * Map the ranked product ids to dropdown items. price_html is server-rendered
	 * so currency / locale formatting is correct and the client stays dumb.
	 *
	 * @param string $term Search term.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_results( $term ) {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			return array();
		}
		$limit = max( 1, min( 20, (int) $this->module->get_option( 'max_results', self::LIMIT ) ) );
		$ids   = ( new Search_Repository() )->search( $term, $limit, true );
		$items = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$image_id = $product->get_image_id();
			$items[]  = array(
				'id'         => (int) $id,
				'title'      => $product->get_name(),
				'url'        => get_permalink( $id ),
				'price_html' => $product->get_price_html(),
				'sku'        => $product->get_sku(),
				'image'      => $image_id
					? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
					: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			);
		}
		return $items;
	}

	/**
	 * The native "see all results" URL the dropdown's footer row links to.
	 *
	 * @param string $term Search term.
	 * @return string
	 */
	private function more_url( $term ) {
		return add_query_arg(
			array(
				's'         => rawurlencode( $term ),
				'post_type' => 'product',
			),
			home_url( '/' )
		);
	}
}
