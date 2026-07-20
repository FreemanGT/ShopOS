<?php
declare(strict_types=1);

use ShopOS\Core\Core\Updater;
use PHPUnit\Framework\TestCase;

/**
 * Dashboard self-updater — the `auto_update_plugin` force seam.
 *
 * The manifest fetch / update injection are integration (network + the WP
 * update transient); this pins the pure decision the 1.49.0 auto-update
 * feature adds: shopos-core is forced to auto-update, every other plugin's
 * decision passes through untouched.
 *
 * @covers ShopOS\Core\Core\Updater::auto_update
 */
final class UpdaterTest extends TestCase {

	public static function setUpBeforeClass(): void {
		if ( ! defined( 'SHOPOS_CORE_BASENAME' ) ) {
			define( 'SHOPOS_CORE_BASENAME', 'shopos-core/shopos-core.php' );
		}
	}

	private function item( string $plugin ): object {
		return (object) array( 'plugin' => $plugin );
	}

	public function test_forces_auto_update_for_shopos_core(): void {
		$u = new Updater();
		// Forced true even when the per-plugin toggle would say false/null.
		$this->assertTrue( $u->auto_update( false, $this->item( SHOPOS_CORE_BASENAME ) ) );
		$this->assertTrue( $u->auto_update( null, $this->item( SHOPOS_CORE_BASENAME ) ) );
	}

	public function test_passes_other_plugins_through_untouched(): void {
		$u = new Updater();
		$this->assertFalse( $u->auto_update( false, $this->item( 'other/other.php' ) ), 'other plugin keeps its false' );
		$this->assertTrue( $u->auto_update( true, $this->item( 'other/other.php' ) ), 'other plugin keeps its true' );
		$this->assertNull( $u->auto_update( null, $this->item( 'other/other.php' ) ), 'other plugin null passes through' );
	}

	public function test_item_without_plugin_property_passes_through(): void {
		$u = new Updater();
		$this->assertFalse( $u->auto_update( false, (object) array() ) );
		$this->assertNull( $u->auto_update( null, 'not-an-object' ) );
	}
}
