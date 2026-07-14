<?php
/**
 * Legacy importer for shopos-variation-swatches.
 *
 * The module re-uses the same option keys (`shopos_vs_shop_*`, `shopos_vs_pdp_*`)
 * so admins keep their existing settings. Import is therefore a detect-only
 * operation — we just advertise that the settings will be reused.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\VariationSwatches;

use ShopOS\Core\Core\Base_Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Importer.
 */
final class Importer extends Base_Importer {

	const LEGACY_PLUGIN_FILE = 'shopos-variation-swatches/shopos-variation-swatches.php';

	/**
	 * Import — nothing to copy because option keys are identical.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public function import() {
		return array(
			'ok'      => true,
			'message' => __( 'Variation Swatches settings preserved (option keys unchanged).', 'shopos-core' ),
		);
	}
}
