<?php
/**
 * Bundle Deals — owner-editable storefront wording.
 *
 * The option-backed resolver base (QuickView / ShopFilters / Search / Product
 * Page pattern): each key's English default is translatable via the textdomain
 * (so a Hebrew store gets Hebrew out of the box through the .mo), and any key
 * can be overridden per-store with the `shopos_core_bundle_deals_label_<key>`
 * option surfaced as a text field on the settings page.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Bundle Deals labels.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_bundle_deals_label_';

	/**
	 * Canonical label map: key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'heading_tiered'     => array(
				'label'   => __( 'Tiered heading', 'shopos-core' ),
				'default' => __( 'Buy more, save more', 'shopos-core' ),
			),
			'heading_bogo'       => array(
				'label'   => __( 'BOGO heading', 'shopos-core' ),
				'default' => __( 'Special offer', 'shopos-core' ),
			),
			'heading_curated'    => array(
				'label'   => __( 'Frequently-bought-together heading', 'shopos-core' ),
				'default' => __( 'Frequently bought together', 'shopos-core' ),
			),
			'heading_mixmatch'   => array(
				'label'   => __( 'Mix-&-match heading', 'shopos-core' ),
				'default' => __( 'Build your bundle', 'shopos-core' ),
			),
			'save'               => array(
				'label'   => __( '"Save" word', 'shopos-core' ),
				'default' => __( 'Save', 'shopos-core' ),
			),
			'you_save'           => array(
				'label'   => __( '"You save" (cart)', 'shopos-core' ),
				'default' => __( 'You save', 'shopos-core' ),
			),
			'add_bundle'         => array(
				'label'   => __( 'Add-bundle button', 'shopos-core' ),
				'default' => __( 'Add bundle to cart', 'shopos-core' ),
			),
			'mixmatch_remaining' => array(
				'label'   => __( 'Mix-&-match progress (%d = items left)', 'shopos-core' ),
				'default' => __( 'Add %d more to unlock the discount', 'shopos-core' ),
			),
			'mixmatch_unlocked'  => array(
				'label'   => __( 'Mix-&-match unlocked', 'shopos-core' ),
				'default' => __( 'Bundle discount unlocked!', 'shopos-core' ),
			),
			'bundle_price'       => array(
				'label'   => __( '"Bundle price" label', 'shopos-core' ),
				'default' => __( 'Bundle price', 'shopos-core' ),
			),
		);
	}
}
