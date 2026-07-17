<?php
declare(strict_types=1);

use ShopOS\Core\Core\Design;
use PHPUnit\Framework\TestCase;

/**
 * The Design panel's pure seams: resolving saved options to a `--shopos-ui-*`
 * override map (preset base + individual-colour override + radius), and building
 * the inline `:root{}` CSS with defence-in-depth value validation. The admin
 * render + front-end enqueue are integration/live-QA.
 *
 * @covers \ShopOS\Core\Core\Design
 */
final class DesignPanelTest extends TestCase {

	/* -------- resolve_values -------- */

	public function test_empty_options_resolve_to_nothing(): void {
		// Default accent contributes nothing and blank colours inherit, so an
		// untouched panel emits no override even when the flag is on.
		$this->assertSame( array(), Design::resolve_values( array() ) );
		$this->assertSame( array(), Design::resolve_values( array( 'accent' => 'default' ) ) );
	}

	public function test_accent_preset_paints_the_accent_token(): void {
		$vars = Design::resolve_values( array( 'accent' => 'terracotta' ) );
		$this->assertSame( '#b5532a', $vars['--shopos-ui-palette-gold'] );
	}

	public function test_unknown_accent_falls_back_to_default(): void {
		$this->assertSame( array(), Design::resolve_values( array( 'accent' => 'bogus' ) ) );
	}

	public function test_individual_colour_overrides_preset(): void {
		$vars = Design::resolve_values( array( 'accent' => 'terracotta', 'col_gold' => '#123456' ) );
		$this->assertSame( '#123456', $vars['--shopos-ui-palette-gold'] );
	}

	public function test_each_curated_colour_maps_to_its_token(): void {
		$vars = Design::resolve_values(
			array(
				'col_ink'       => '#111111',
				'col_paper'     => '#fefefe',
				'col_hairline'  => '#dddddd',
			)
		);
		$this->assertSame( '#111111', $vars['--shopos-ui-palette-ink'] );
		$this->assertSame( '#fefefe', $vars['--shopos-ui-palette-paper'] );
		$this->assertSame( '#dddddd', $vars['--shopos-ui-palette-hairline'] );
	}

	public function test_invalid_hex_is_dropped(): void {
		$vars = Design::resolve_values( array( 'col_ink' => 'notahex' ) );
		$this->assertArrayNotHasKey( '--shopos-ui-palette-ink', $vars );
	}

	public function test_radius_emits_px_within_bounds(): void {
		$vars = Design::resolve_values( array( 'radius' => '8' ) );
		$this->assertSame( '8px', $vars['--shopos-ui-radius-md'] );
	}

	public function test_radius_out_of_bounds_is_dropped(): void {
		$this->assertArrayNotHasKey( '--shopos-ui-radius-md', Design::resolve_values( array( 'radius' => '999' ) ) );
		$this->assertArrayNotHasKey( '--shopos-ui-radius-md', Design::resolve_values( array( 'radius' => '' ) ) );
		$this->assertArrayNotHasKey( '--shopos-ui-radius-md', Design::resolve_values( array( 'radius' => 'abc' ) ) );
	}

	/* -------- build_css -------- */

	public function test_build_css_empty_map_returns_empty_string(): void {
		$this->assertSame( '', Design::build_css( array() ) );
	}

	public function test_build_css_wraps_declarations_in_root(): void {
		$css = Design::build_css( array( '--shopos-ui-palette-ink' => '#111111' ) );
		$this->assertSame( ':root{--shopos-ui-palette-ink:#111111;}', $css );
	}

	public function test_build_css_rejects_unsafe_value(): void {
		// A value that could break out of the declaration is dropped entirely.
		$this->assertSame( '', Design::build_css( array( '--shopos-ui-palette-ink' => 'red;}body{display:none' ) ) );
	}

	public function test_build_css_rejects_non_shopos_var_name(): void {
		$this->assertSame( '', Design::build_css( array( '--evil' => '#111111' ) ) );
	}

	/* -------- end-to-end pure path -------- */

	public function test_resolve_then_build_produces_scoped_block(): void {
		$css = Design::build_css( Design::resolve_values( array( 'accent' => 'forest', 'radius' => '6' ) ) );
		$this->assertStringContainsString( '--shopos-ui-palette-gold:#3f7a4b;', $css );
		$this->assertStringContainsString( '--shopos-ui-radius-md:6px;', $css );
		$this->assertStringStartsWith( ':root{', $css );
	}

	/* -------- boot wiring (1.44.1 regression pin) -------- */

	public function test_submenu_registers_after_the_parent_menu(): void {
		// The Settings Hub registers the parent `shopos` menu at the default
		// priority 10 and Design boots before the hub in Plugin::boot(), so a
		// submenu registered at <= 10 files its page hook under `admin_page_*`
		// and the page 403s (the 1.44.1 bug). Pin the after-parent priority.
		$hooks_before           = $GLOBALS['fr_hooks'] ?? array();
		$GLOBALS['fr_hooks']    = array();
		$GLOBALS['fr_is_admin'] = true;
		try {
			( new Design() )->boot();
			$entries = array();
			foreach ( $GLOBALS['fr_hooks']['admin_menu'] ?? array() as $h ) {
				if ( is_array( $h['cb'] ) && $h['cb'][0] instanceof Design && 'register_menu' === $h['cb'][1] ) {
					$entries[] = $h;
				}
			}
			$this->assertNotEmpty( $entries, 'Design registers its submenu on admin_menu when is_admin' );
			foreach ( $entries as $h ) {
				$this->assertGreaterThan( 10, $h['priority'], 'submenu must register after the parent shopos menu (priority 10)' );
			}
		} finally {
			unset( $GLOBALS['fr_is_admin'] );
			$GLOBALS['fr_hooks'] = $hooks_before;
		}
	}

	/* -------- kit_slots (§11 Ruling 8 — Core-option default, filterable, no UI) -------- */

	public function test_kit_slots_default_to_the_hardcoded_style_kit_slots(): void {
		unset( $GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] );
		$this->assertSame( array( 'body' => 'sk_type_12', 'display' => 'sk_type_2' ), Design::kit_slots() );
		$this->assertSame( Design::KIT_SLOT_DEFAULTS, Design::kit_slots() );
	}

	public function test_kit_slots_option_overrides_and_merges_with_defaults(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] = array( 'body' => 'sk_type_3' );
		$this->assertSame( array( 'body' => 'sk_type_3', 'display' => 'sk_type_2' ), Design::kit_slots() );
		unset( $GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] );
	}

	public function test_kit_slots_filter_overrides_the_option(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] = array( 'body' => 'sk_type_3' );
		add_filter(
			'shopos_core/theme/kit_slots',
			static function ( $slots ) {
				$slots['body'] = 'sk_type_9';
				return $slots;
			}
		);
		$this->assertSame( 'sk_type_9', Design::kit_slots()['body'] );
		unset( $GLOBALS['fr_opts']['shopos_core_theme_kit_slots'], $GLOBALS['fr_hooks']['shopos_core/theme/kit_slots'] );
	}

	public function test_kit_slots_sanitises_unsafe_values_back_to_defaults(): void {
		// Slot ids are interpolated into inline CSS, so anything outside
		// [a-z0-9_] is stripped; an emptied slot falls back to its default.
		$GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] = array(
			'body'    => 'sk_type_5;}body{display:none',
			'display' => ');url(evil',
		);
		$this->assertSame( array( 'body' => 'sk_type_5bodydisplaynone', 'display' => 'urlevil' ), Design::kit_slots() );
		$GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] = array( 'body' => '<<<>>>', 'display' => 42 );
		$this->assertSame( Design::KIT_SLOT_DEFAULTS, Design::kit_slots() );
		unset( $GLOBALS['fr_opts']['shopos_core_theme_kit_slots'] );
	}
}
