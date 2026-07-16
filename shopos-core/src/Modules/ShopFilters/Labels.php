<?php
/**
 * Shop Filters — storefront label resolver.
 *
 * Every user-facing string in the filter panel is overridable from the
 * ShopOS → Shop Filters settings page, so the site owner can set Hebrew (or any
 * wording) without code (§4.2 — English defaults, locale-specific opt-in). Each
 * label is stored under its own option (`shopos_core_shop_filters_label_<key>`,
 * the same name Settings_Hub writes for the matching `label_<key>` field); an
 * unset / blank option falls back to the English default here, so an
 * untouched-settings site is byte-identical to before this phase.
 *
 * This map is the single source of truth: the module's settings_schema() builds
 * its text fields from defaults(), and the templates resolve text through get().
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ShopFilters;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver. Resolution (option override → English default) lives in
 * Labels_Base::get(); this class owns the prefix + canonical map, plus the
 * module-specific count_text() formatter.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_shop_filters_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 * The defaults reproduce the strings the templates shipped with in 6.3b.
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'toggle'         => array(
				'label'   => __( 'Mobile filter button', 'shopos-core' ),
				'default' => __( 'Filter sizes & prices', 'shopos-core' ),
			),
			'panel_title'    => array(
				'label'   => __( 'Drawer title', 'shopos-core' ),
				'default' => __( 'Filter', 'shopos-core' ),
			),
			'panel_aria'     => array(
				'label'   => __( 'Drawer accessible name', 'shopos-core' ),
				'default' => __( 'Filter products', 'shopos-core' ),
			),
			'close'          => array(
				'label'   => __( 'Close button label', 'shopos-core' ),
				'default' => __( 'Close', 'shopos-core' ),
			),
			'categories'     => array(
				'label'   => __( 'Categories heading', 'shopos-core' ),
				'default' => __( 'Categories', 'shopos-core' ),
			),
			'price'          => array(
				'label'   => __( 'Price heading', 'shopos-core' ),
				'default' => __( 'Price', 'shopos-core' ),
			),
			'sort'           => array(
				'label'   => __( 'Sort heading', 'shopos-core' ),
				'default' => __( 'Sort by', 'shopos-core' ),
			),
			'flags_heading'  => array(
				'label'   => __( 'Availability heading', 'shopos-core' ),
				'default' => __( 'Availability', 'shopos-core' ),
			),
			'onsale'         => array(
				'label'   => __( 'On-sale filter label', 'shopos-core' ),
				'default' => __( 'On sale', 'shopos-core' ),
			),
			'in_stock'       => array(
				'label'   => __( 'In-stock filter label', 'shopos-core' ),
				'default' => __( 'In stock', 'shopos-core' ),
			),
			'categories_aria' => array(
				'label'   => __( 'Categories accessible name', 'shopos-core' ),
				'default' => __( 'Product categories', 'shopos-core' ),
			),
			'clear_all'      => array(
				'label'   => __( 'Clear-all button', 'shopos-core' ),
				'default' => __( 'Clear all', 'shopos-core' ),
			),
			'apply'          => array(
				'label'   => __( 'Apply button (mobile)', 'shopos-core' ),
				'default' => __( 'Apply filters', 'shopos-core' ),
			),
			'clear'          => array(
				'label'   => __( 'Clear button (mobile)', 'shopos-core' ),
				'default' => __( 'Clear', 'shopos-core' ),
			),
			'count_singular' => array(
				'label'   => __( 'Result count, singular (use %d)', 'shopos-core' ),
				'default' => __( '%d product', 'shopos-core' ),
			),
			'count_plural'   => array(
				'label'   => __( 'Result count, plural (use %d)', 'shopos-core' ),
				'default' => __( '%d products', 'shopos-core' ),
			),
		);
	}

	/**
	 * Format the result-count line, choosing singular / plural by count.
	 *
	 * @param int $count Product count.
	 * @return string
	 */
	public static function count_text( $count ) {
		$count    = (int) $count;
		$template = ( 1 === $count ) ? self::get( 'count_singular' ) : self::get( 'count_plural' );
		return sprintf( $template, $count );
	}
}
