<?php
declare(strict_types=1);

use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 1 — theme-owned header/footer chrome (theme.template_chrome).
 *
 * The chrome render is procedural (header.php / footer.php: require-parent
 * passthrough when off, ShopOS markup when on) — integration/live-QA per
 * tools/qa/chrome-template.md. This pins the flag seam every consumer routes
 * through (`ShopOS_Theme::chrome_enabled()` — header, footer, asset enqueue,
 * footer widget area), the frozen flag name, the registry entry, and the
 * on-disk templates/assets.
 *
 * ShopOS Line theme CI lane (decisions §11-B).
 *
 * @covers ShopOS_Theme::chrome_enabled
 * @group theme
 */
final class ThemeChromeTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/../shopos-theme/inc/class-shopos-theme.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	private function theme_dir(): string {
		return realpath( __DIR__ . '/../shopos-theme' );
	}

	public function test_flag_name_is_frozen(): void {
		$this->assertSame(
			'shopos_core_theme_template_chrome_enabled',
			Feature_Flags::option_name( 'theme', 'template_chrome' )
		);
	}

	public function test_chrome_disabled_by_default(): void {
		$this->assertFalse( ShopOS_Theme::chrome_enabled() );
	}

	public function test_chrome_enabled_when_flag_option_set(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_chrome_enabled'] = '1';
		$this->assertTrue( ShopOS_Theme::chrome_enabled() );
	}

	public function test_chrome_off_for_zero_value(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_chrome_enabled'] = '0';
		$this->assertFalse( ShopOS_Theme::chrome_enabled() );
	}

	public function test_registry_exposes_the_chrome_flag(): void {
		$found = null;
		foreach ( Feature_Flags::registry() as $entry ) {
			if ( 'theme' === $entry['module'] && 'template_chrome' === $entry['feature'] ) {
				$found = $entry;
				break;
			}
		}
		$this->assertNotNull( $found, 'theme/template_chrome must be in the flag registry.' );
		$this->assertFalse( $found['shared'], 'Chrome is a permanent per-store kill-switch.' );
	}

	public function test_chrome_templates_and_assets_exist(): void {
		$dir = $this->theme_dir();
		$this->assertFileExists( $dir . '/header.php' );
		$this->assertFileExists( $dir . '/footer.php' );
		$this->assertFileExists( $dir . '/assets/css/shopos-chrome.css' );
		$this->assertFileExists( $dir . '/assets/js/shopos-chrome.js' );
	}
}
