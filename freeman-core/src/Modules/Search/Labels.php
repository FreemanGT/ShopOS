<?php
/**
 * Search — storefront label resolver.
 *
 * Every user-facing string in the search shortcode form + the live dropdown is
 * overridable from the Freeman → Search settings page, so the site owner can set
 * Hebrew (or any wording) without code (QuickView / ShopFilters Labels
 * precedent). Each label is stored under its own option
 * (`freeman_core_search_label_<key>`, the same name Settings_Hub writes for the
 * matching `label_<key>` field); an unset / blank option falls back to the
 * English default here.
 *
 * This map is the single source of truth: the module's settings_schema() builds
 * its text fields from defaults(), and the storefront resolves text through
 * get().
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver.
 */
final class Labels {

	const OPTION_PREFIX = 'freeman_core_search_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'placeholder' => array(
				'label'   => __( 'Search field placeholder', 'freeman-core' ),
				'default' => __( 'Search products…', 'freeman-core' ),
			),
			'button'      => array(
				'label'   => __( 'Search button', 'freeman-core' ),
				'default' => __( 'Search', 'freeman-core' ),
			),
			'no_results'  => array(
				'label'   => __( 'No-results message', 'freeman-core' ),
				'default' => __( 'No products found', 'freeman-core' ),
			),
			'see_all'     => array(
				'label'   => __( 'See-all-results link', 'freeman-core' ),
				'default' => __( 'See all results', 'freeman-core' ),
			),
			'searching'   => array(
				'label'   => __( 'Searching message', 'freeman-core' ),
				'default' => __( 'Searching…', 'freeman-core' ),
			),
			'toggle'      => array(
				'label'   => __( 'Search toggle accessible name', 'freeman-core' ),
				'default' => __( 'Search', 'freeman-core' ),
			),
			'close'       => array(
				'label'   => __( 'Close-search accessible name', 'freeman-core' ),
				'default' => __( 'Close search', 'freeman-core' ),
			),
		);
	}

	/**
	 * Resolve a label by short key. Returns the saved override when non-empty,
	 * otherwise the English default.
	 *
	 * @param string $key Short key (e.g. 'placeholder').
	 * @return string
	 */
	public static function get( $key ) {
		$key      = (string) $key;
		$defaults = self::defaults();
		$default  = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ]['default'] : '';

		$value = (string) get_option( self::OPTION_PREFIX . $key, '' );
		return '' !== trim( $value ) ? $value : $default;
	}
}
