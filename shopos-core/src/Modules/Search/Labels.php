<?php
/**
 * Search — storefront label resolver.
 *
 * Every user-facing string in the search shortcode form + the live dropdown is
 * overridable from the ShopOS → Search settings page, so the site owner can set
 * Hebrew (or any wording) without code (QuickView / ShopFilters Labels
 * precedent). Each label is stored under its own option
 * (`shopos_core_search_label_<key>`, the same name Settings_Hub writes for the
 * matching `label_<key>` field); an unset / blank option falls back to the
 * English default here.
 *
 * This map is the single source of truth: the module's settings_schema() builds
 * its text fields from defaults(), and the storefront resolves text through
 * get().
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\Search;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver. Resolution (option override → English default) lives in
 * Labels_Base::get(); this class only owns the prefix + canonical map.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_search_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'placeholder' => array(
				'label'   => __( 'Search field placeholder', 'shopos-core' ),
				'default' => __( 'Search products…', 'shopos-core' ),
			),
			'button'      => array(
				'label'   => __( 'Search button', 'shopos-core' ),
				'default' => __( 'Search', 'shopos-core' ),
			),
			'no_results'  => array(
				'label'   => __( 'No-results message', 'shopos-core' ),
				'default' => __( 'No products found', 'shopos-core' ),
			),
			'see_all'     => array(
				'label'   => __( 'See-all-results link', 'shopos-core' ),
				'default' => __( 'See all results', 'shopos-core' ),
			),
			'searching'   => array(
				'label'   => __( 'Searching message', 'shopos-core' ),
				'default' => __( 'Searching…', 'shopos-core' ),
			),
			'toggle'      => array(
				'label'   => __( 'Search toggle accessible name', 'shopos-core' ),
				'default' => __( 'Search', 'shopos-core' ),
			),
			'close'       => array(
				'label'   => __( 'Close-search accessible name', 'shopos-core' ),
				'default' => __( 'Close search', 'shopos-core' ),
			),
		);
	}
}
