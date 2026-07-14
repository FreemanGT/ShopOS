<?php
/**
 * Legacy importer for shopos-infinite-scroll.
 *
 * The legacy plugin was purely front-end JS/CSS with no persisted options,
 * so import is effectively a no-op beyond detection + deactivation.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\InfiniteScroll;

use ShopOS\Core\Core\Base_Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Importer.
 */
final class Importer extends Base_Importer {

	const LEGACY_PLUGIN_FILE = 'shopos-infinite-scroll/shopos-infinite-scroll.php';

	/**
	 * Import legacy settings. Legacy plugin has none.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public function import() {
		return array(
			'ok'      => true,
			'message' => __( 'Infinite Scroll migrated (legacy plugin had no settings).', 'shopos-core' ),
		);
	}
}
