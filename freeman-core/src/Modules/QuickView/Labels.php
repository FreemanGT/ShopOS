<?php
/**
 * Quick View — storefront label resolver.
 *
 * Every user-facing string in the quick-view trigger + drawer is overridable
 * from the Freeman → Quick View settings page, so the site owner can set
 * Hebrew (or any wording) without code (§4.2 — English defaults,
 * locale-specific opt-in; ShopFilters Labels precedent). Each label is stored
 * under its own option (`freeman_core_quick_view_label_<key>`, the same name
 * Settings_Hub writes for the matching `label_<key>` field); an unset / blank
 * option falls back to the English default here.
 *
 * This map is the single source of truth: the module's settings_schema()
 * builds its text fields from defaults(), and the markup resolves text
 * through get().
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\QuickView;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver.
 */
final class Labels {

	const OPTION_PREFIX = 'freeman_core_quick_view_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'trigger'     => array(
				'label'   => __( 'Trigger button accessible name', 'freeman-core' ),
				'default' => __( 'Quick view', 'freeman-core' ),
			),
			'drawer_title' => array(
				'label'   => __( 'Drawer title', 'freeman-core' ),
				'default' => __( 'Quick view', 'freeman-core' ),
			),
			'close'       => array(
				'label'   => __( 'Close button label', 'freeman-core' ),
				'default' => __( 'Close', 'freeman-core' ),
			),
			'details'     => array(
				'label'   => __( 'Full-details link', 'freeman-core' ),
				'default' => __( 'View full details', 'freeman-core' ),
			),
			'loading'     => array(
				'label'   => __( 'Loading message', 'freeman-core' ),
				'default' => __( 'Loading…', 'freeman-core' ),
			),
			'error'       => array(
				'label'   => __( 'Error message', 'freeman-core' ),
				'default' => __( 'Could not load this product. Please try again.', 'freeman-core' ),
			),
		);
	}

	/**
	 * Resolve a label by short key. Returns the saved override when non-empty,
	 * otherwise the English default.
	 *
	 * @param string $key Short key (e.g. 'close').
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
