<?php
/**
 * Quick View — storefront label resolver.
 *
 * Every user-facing string in the quick-view trigger + drawer is overridable
 * from the ShopOS → Quick View settings page, so the site owner can set
 * Hebrew (or any wording) without code (§4.2 — English defaults,
 * locale-specific opt-in; ShopFilters Labels precedent). Each label is stored
 * under its own option (`shopos_core_quick_view_label_<key>`, the same name
 * Settings_Hub writes for the matching `label_<key>` field); an unset / blank
 * option falls back to the English default here.
 *
 * This map is the single source of truth: the module's settings_schema()
 * builds its text fields from defaults(), and the markup resolves text
 * through get().
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\QuickView;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver. Resolution (option override → English default) lives in
 * Labels_Base::get(); this class only owns the prefix + canonical map.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_quick_view_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'trigger'     => array(
				'label'   => __( 'Trigger button accessible name', 'shopos-core' ),
				'default' => __( 'Quick view', 'shopos-core' ),
			),
			'drawer_title' => array(
				'label'   => __( 'Drawer title', 'shopos-core' ),
				'default' => __( 'Quick view', 'shopos-core' ),
			),
			'close'       => array(
				'label'   => __( 'Close button label', 'shopos-core' ),
				'default' => __( 'Close', 'shopos-core' ),
			),
			'details'     => array(
				'label'   => __( 'Full-details link', 'shopos-core' ),
				'default' => __( 'View full details', 'shopos-core' ),
			),
			'loading'     => array(
				'label'   => __( 'Loading message', 'shopos-core' ),
				'default' => __( 'Loading…', 'shopos-core' ),
			),
			'error'       => array(
				'label'   => __( 'Error message', 'shopos-core' ),
				'default' => __( 'Could not load this product. Please try again.', 'shopos-core' ),
			),
		);
	}
}
