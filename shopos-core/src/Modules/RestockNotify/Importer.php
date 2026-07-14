<?php
/**
 * Legacy importer for restock-notify.
 *
 * The module reuses the legacy option keys (`shopos_restock_*`) and table name
 * (`{prefix}shopos_restock_subscribers`) so subscribers and settings are preserved when
 * the legacy plugin is deactivated. Import is therefore a detect-only step.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\RestockNotify;

use ShopOS\Core\Core\Base_Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Importer.
 */
final class Importer extends Base_Importer {

	const LEGACY_PLUGIN_FILE = 'restock-notify/restock-notify.php';

	/**
	 * Import — options + table are adopted as-is.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public function import() {
		return array(
			'ok'      => true,
			'message' => __( 'Restock Notify subscribers and settings adopted in place.', 'shopos-core' ),
		);
	}
}
