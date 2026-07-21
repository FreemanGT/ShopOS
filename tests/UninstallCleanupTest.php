<?php
declare(strict_types=1);

use ShopOS\Core\Modules\RestockNotify\Module as RestockModule;
use ShopOS\Core\Modules\VariationSwatches\Module as SwatchesModule;
use ShopOS\Core\Modules\VariationSwatches\Color_Sampler;
use PHPUnit\Framework\TestCase;

/**
 * B-5 uninstall completeness: the two destructive cleanups that sit outside the
 * per-module option-prefix sweep — RestockNotify dropping its subscriber PII
 * table + the off-prefix notification-log option, and VariationSwatches
 * clearing its `_shopos_core_vs_sampled_color` post-meta. The core-owned option
 * deletes in uninstall.php are procedural (WP_UNINSTALL_PLUGIN) — live-QA.
 *
 * @covers \ShopOS\Core\Modules\RestockNotify\Module::on_uninstall
 * @covers \ShopOS\Core\Modules\VariationSwatches\Module::on_uninstall
 */
final class UninstallCleanupTest extends TestCase {

	private $original_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']             = array();
		$GLOBALS['fr_deleted_meta_keys'] = array();
		$this->original_wpdb           = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb']               = $this->recording_wpdb();
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	private function recording_wpdb() {
		return new class() {
			public $prefix = 'wp_';
			public $options = 'wp_options';
			public $queries = array();
			public function esc_like( $t ) { return $t; }
			public function prepare( $q, ...$a ) { return $q; }
			public function query( $q ) { $this->queries[] = $q; return 0; }
		};
	}

	public function test_restock_uninstall_drops_the_pii_table(): void {
		$GLOBALS['fr_opts']['shopos_restock_notification_log'] = array( 1, 2, 3 );

		( new RestockModule() )->on_uninstall();

		$dropped = false;
		foreach ( $GLOBALS['wpdb']->queries as $q ) {
			if ( false !== strpos( $q, 'DROP TABLE IF EXISTS `wp_shopos_restock_subscribers`' ) ) {
				$dropped = true;
			}
		}
		$this->assertTrue( $dropped, 'subscriber PII table must be dropped on uninstall' );
		$this->assertArrayNotHasKey( 'shopos_restock_notification_log', $GLOBALS['fr_opts'], 'off-prefix accumulator must be deleted' );
	}

	public function test_swatches_uninstall_clears_sampled_color_meta(): void {
		( new SwatchesModule() )->on_uninstall();

		$this->assertContains( Color_Sampler::META_KEY, $GLOBALS['fr_deleted_meta_keys'] );
		$this->assertSame( '_shopos_core_vs_sampled_color', Color_Sampler::META_KEY );
	}
}
