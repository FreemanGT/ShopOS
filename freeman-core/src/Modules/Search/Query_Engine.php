<?php
/**
 * Search query engine — pure, $wpdb-free seams.
 *
 * Wave 1 only needs the write side: assembling a product's denormalised
 * searchable-text blob. The read side (the MATCH ... AGAINST score / where
 * builders that power the dropdown and results page) lands in Wave 2, alongside
 * the Search_Repository::search() method that actually calls them — building
 * them now would be dead code for a wave.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Query engine.
 */
final class Query_Engine {

	/**
	 * Build the denormalised searchable-text blob stored in `search_text`. Pure:
	 * the Indexer extracts the raw fields off the WC_Product / terms and hands
	 * them here, so the blob assembly is unit-testable without WordPress.
	 *
	 * Tags are stripped (descriptions arrive as HTML) and whitespace collapsed so
	 * FULLTEXT tokenises cleanly. No stemming / synonyms — that is AWS's moat and
	 * deliberately out of scope.
	 *
	 * @param string   $title             Product title.
	 * @param string   $sku               SKU.
	 * @param string[] $category_names    Category term names.
	 * @param string[] $tag_names         Tag term names.
	 * @param string   $short_description Short description (may contain HTML).
	 * @param string   $content           Long description (may contain HTML).
	 * @return string
	 */
	public static function build_search_text( $title, $sku, array $category_names, array $tag_names, $short_description, $content ) {
		$parts = array_merge(
			array( (string) $title, (string) $sku ),
			array_map( 'strval', $category_names ),
			array_map( 'strval', $tag_names ),
			array( (string) $short_description, (string) $content )
		);

		$text = strip_tags( implode( ' ', $parts ) );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( (string) $text );
	}
}
