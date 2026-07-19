<?php
declare(strict_types=1);

use ShopOS\Core\Core\Blueprint;
use ShopOS\Core\Core\Design;
use ShopOS\Core\Core\Feature_Flags;
use ShopOS\Core\Core\Plugin;
use ShopOS\Core\Core\Settings_Tools;
use ShopOS\Core\Modules\BundleDeals\Bundle_Config;
use ShopOS\Core\Modules\ProductPage\Labels as Product_Page_Labels;
use ShopOS\Core\Modules\QuickView\Labels as Quick_View_Labels;
use ShopOS\Core\Modules\Search\Labels as Search_Labels;
use ShopOS\Core\Modules\ShopFilters\Facet_Config;
use ShopOS\Core\Modules\ShopFilters\Labels as Shop_Filters_Labels;
use PHPUnit\Framework\TestCase;

/**
 * Store Blueprint (decisions §10): the curated registry-derived key set, the
 * strict validator, the per-surface normalisers (modules merge-by-id, flag
 * '1'/'0' shape, facet matrix reuse, design token rules), unchanged-skip
 * idempotence, and the shared-backup apply flow. The CLI glue around it is
 * covered in CliTest; file I/O + the live registry/index lookups in live-QA.
 *
 * @covers \ShopOS\Core\Core\Blueprint
 */
final class BlueprintTest extends TestCase {

	private const KNOWN_MODULES = array( 'quick_view', 'search', 'shop_filters' );

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	/** A minimal valid envelope around the given options. */
	private function envelope( array $options, array $block_overrides = array() ): array {
		return array(
			'version'     => 1,
			'exported_at' => '2026-07-16T00:00:00+00:00',
			'site_url'    => 'https://example.test',
			'blueprint'   => array_merge(
				array( 'format' => 1, 'name' => 'test', 'generator' => 'shopos-core ' . Plugin::VERSION ),
				$block_overrides
			),
			'options'     => $options,
		);
	}

	/* -------- key_set() -------- */

	public function test_key_set_is_composed_from_the_code_registries(): void {
		$keys = Blueprint::key_set();

		$label_count = count( Quick_View_Labels::defaults() ) + count( Shop_Filters_Labels::defaults() )
			+ count( Search_Labels::defaults() ) + count( Product_Page_Labels::defaults() );
		// 1 modules + flags + labels + 1 facet + 1 bundle + design (accent + colours + radius).
		$this->assertCount(
			1 + count( Feature_Flags::registry() ) + $label_count + 1 + 1 + ( 1 + count( Design::colour_fields() ) + 1 ),
			$keys
		);

		$this->assertSame( 'modules', $keys['shopos_core_modules'] );
		$this->assertSame( 'flag', $keys['shopos_core_design_panel_enabled'] );
		$this->assertSame( 'label', $keys['shopos_core_quick_view_label_close'] );
		$this->assertSame( 'facet', $keys[ Facet_Config::OPTION ] );
		$this->assertSame( 'bundle', $keys[ Bundle_Config::OPTION ] );
		$this->assertSame( 'design', $keys['shopos_core_design_accent'] );
		$this->assertSame( 'design', $keys['shopos_core_design_radius'] );
	}

	public function test_key_set_excludes_runtime_state(): void {
		$keys = Blueprint::key_set();
		foreach ( array( 'shopos_core_log', 'shopos_core_boot_failures', 'shopos_core_settings_backups', 'shopos_core_onboarded' ) as $runtime ) {
			$this->assertArrayNotHasKey( $runtime, $keys );
		}
	}

	/* -------- export_payload() -------- */

	public function test_export_covers_every_curated_key_with_portable_defaults(): void {
		update_option( 'shopos_core_quick_view_label_close', 'סגירה' );
		update_option( 'shopos_core_design_panel_enabled', '1' );

		$envelope = Blueprint::export_payload( 'flagship' );

		$this->assertSame( 1, $envelope['version'] );
		$this->assertSame( array( 'format' => 1, 'name' => 'flagship', 'generator' => 'shopos-core ' . Plugin::VERSION ), $envelope['blueprint'] );
		$this->assertEqualsCanonicalizing( array_keys( Blueprint::key_set() ), array_keys( $envelope['options'] ) );

		// Saved values export verbatim; absent options export as semantic defaults.
		$this->assertSame( 'סגירה', $envelope['options']['shopos_core_quick_view_label_close'] );
		$this->assertSame( '1', $envelope['options']['shopos_core_design_panel_enabled'] );
		$this->assertSame( '0', $envelope['options']['shopos_core_perf_probe_enabled'] );
		$this->assertSame( '', $envelope['options']['shopos_core_search_label_placeholder'] );
		$this->assertSame( 'default', $envelope['options']['shopos_core_design_accent'] );
		$this->assertSame( array(), $envelope['options']['shopos_core_modules'] );
		$this->assertSame( array(), $envelope['options'][ Facet_Config::OPTION ] );
	}

	public function test_export_is_a_valid_wave_03_envelope(): void {
		$check = ( new Settings_Tools() )->validate_envelope( Blueprint::export_payload( 'x' ) );
		$this->assertTrue( $check['ok'], 'Blueprint files must stay importable through ShopOS → Tools: ' . $check['reason'] );
	}

	public function test_export_round_trips_through_validate(): void {
		$check = Blueprint::validate( Blueprint::export_payload( 'x' ) );
		$this->assertTrue( $check['ok'], $check['reason'] );
		$this->assertSame( array(), $check['warnings'] );
	}

	/* -------- validate() -------- */

	public function test_validate_rejects_a_plain_tools_export_without_the_blueprint_block(): void {
		$envelope = $this->envelope( array() );
		unset( $envelope['blueprint'] );
		$this->assertSame( 'missing_field:blueprint', Blueprint::validate( $envelope )['reason'] );
	}

	public function test_validate_rejects_structural_problems(): void {
		$this->assertSame( 'not_an_object', Blueprint::validate( 'nope' )['reason'] );
		$this->assertSame( 'unsupported_version:2', Blueprint::validate( array_merge( $this->envelope( array() ), array( 'version' => 2 ) ) )['reason'] );
		$this->assertSame( 'unsupported_format:2', Blueprint::validate( $this->envelope( array(), array( 'format' => 2 ) ) )['reason'] );
		$this->assertSame( 'invalid_name', Blueprint::validate( $this->envelope( array(), array( 'name' => ' ' ) ) )['reason'] );
		$this->assertSame( 'options_not_an_object', Blueprint::validate( array_merge( $this->envelope( array() ), array( 'options' => 'x' ) ) )['reason'] );
	}

	public function test_validate_rejects_keys_outside_the_curated_set(): void {
		// Allowed by the Tools prefix allowlist, but not a Blueprint surface.
		$result = Blueprint::validate( $this->envelope( array( 'shopos_core_log' => array() ) ) );
		$this->assertSame( 'unexpected_key:shopos_core_log', $result['reason'] );
	}

	public function test_validate_rejects_invalid_values_per_surface(): void {
		$cases = array(
			array( 'shopos_core_modules', 'not-a-map' ),
			// A module-map typo must fail loudly, never coerce to "off" (the
			// flag surface's unambiguous-boolean rule).
			array( 'shopos_core_modules', array( 'search' => 'enabled' ) ),
			array( 'shopos_core_design_panel_enabled', 'banana' ),
			array( Facet_Config::OPTION, array( array( 'enabled' => true ) ) ), // row missing taxonomy.
			array( 'shopos_core_design_accent', 'neon' ),
			array( 'shopos_core_design_col_gold', 'red' ), // not a hex colour.
			array( 'shopos_core_design_radius', '99' ),
			// Non-scalar design values reject without an Array-to-string warning.
			array( 'shopos_core_design_radius', array( 8 ) ),
			array( 'shopos_core_design_col_gold', array( '#fff' ) ),
		);
		foreach ( $cases as list( $key, $bad ) ) {
			$result = Blueprint::validate( $this->envelope( array( $key => $bad ) ) );
			$this->assertSame( 'invalid_value:' . $key, $result['reason'], $key );
		}
	}

	public function test_validate_accepts_loose_but_unambiguous_flag_values(): void {
		foreach ( array( '1', 1, true, 'true', '0', 0, false, 'off' ) as $value ) {
			$result = Blueprint::validate( $this->envelope( array( 'shopos_core_design_panel_enabled' => $value ) ) );
			$this->assertTrue( $result['ok'], var_export( $value, true ) );
		}
	}

	public function test_validate_warns_when_the_generator_is_newer(): void {
		$result = Blueprint::validate( $this->envelope( array(), array( 'generator' => 'shopos-core 99.0.0' ) ) );
		$this->assertTrue( $result['ok'] );
		$this->assertStringContainsString( 'newer core', $result['warnings'][0] );
	}

	public function test_generator_is_newer_is_tolerant_of_garbage(): void {
		$this->assertTrue( Blueprint::generator_is_newer( 'shopos-core 99.0.0' ) );
		$this->assertFalse( Blueprint::generator_is_newer( 'shopos-core ' . Plugin::VERSION ) );
		$this->assertFalse( Blueprint::generator_is_newer( 'hand-written' ) );
		$this->assertFalse( Blueprint::generator_is_newer( null ) );
	}

	/* -------- normalize_value() per surface -------- */

	public function test_modules_merge_by_id_preserves_unlisted_modules(): void {
		$current = array( 'quick_view' => true, 'search' => true );
		list( $merged, $warnings ) = Blueprint::normalize_value(
			'shopos_core_modules', 'modules',
			array( 'search' => false, 'shop_filters' => '1' ),
			$current, self::KNOWN_MODULES, null
		);
		$this->assertSame( array( 'quick_view' => true, 'search' => false, 'shop_filters' => true ), $merged );
		$this->assertSame( array(), $warnings );
	}

	public function test_modules_unknown_to_this_core_are_dropped_with_a_warning(): void {
		list( $merged, $warnings ) = Blueprint::normalize_value(
			'shopos_core_modules', 'modules',
			array( 'hover_board' => true, 'search' => true ),
			array(), self::KNOWN_MODULES, null
		);
		$this->assertSame( array( 'search' => true ), $merged );
		$this->assertStringContainsString( '"hover_board" is unknown', $warnings[0] );
	}

	public function test_flags_normalise_to_the_admin_page_string_shape(): void {
		foreach ( array( true, 'true', 1, '1', 'yes' ) as $on ) {
			list( $value ) = Blueprint::normalize_value( 'shopos_core_design_panel_enabled', 'flag', $on, false, array(), null );
			$this->assertSame( '1', $value );
		}
		foreach ( array( false, 'false', 0, '0', 'off' ) as $off ) {
			list( $value ) = Blueprint::normalize_value( 'shopos_core_design_panel_enabled', 'flag', $off, false, array(), null );
			$this->assertSame( '0', $value );
		}
	}

	public function test_labels_are_text_sanitised(): void {
		list( $value ) = Blueprint::normalize_value( 'shopos_core_search_label_button', 'label', 'חיפוש', false, array(), null );
		$this->assertSame( 'חיפוש', $value );
		list( $value ) = Blueprint::normalize_value( 'shopos_core_search_label_button', 'label', null, false, array(), null );
		$this->assertSame( '', $value );
	}

	public function test_facet_rows_reuse_the_module_normaliser_and_recompute_type(): void {
		$rows = array(
			array( 'taxonomy' => 'product_cat', 'type' => 'checkbox', 'enabled' => 1, 'order' => '3', 'hide_on_categories' => array( '7', '7', '0', 42 ) ),
			array( 'taxonomy' => 'pa_color', 'enabled' => false, 'order' => 1 ),
		);
		list( $config, $warnings ) = Blueprint::normalize_value( Facet_Config::OPTION, 'facet', $rows, false, array(), array( 'product_cat', 'pa_color' ) );

		$this->assertSame(
			array(
				array( 'taxonomy' => 'product_cat', 'type' => 'category', 'enabled' => true, 'order' => 3, 'hide_on_categories' => array( 7, 42 ) ),
				array( 'taxonomy' => 'pa_color', 'type' => 'checkbox', 'enabled' => false, 'order' => 1, 'hide_on_categories' => array() ),
			),
			$config
		);
		$this->assertSame( array(), $warnings );
	}

	public function test_facet_taxonomies_missing_from_the_index_are_kept_with_a_warning(): void {
		$rows = array( array( 'taxonomy' => 'pa_size', 'enabled' => true, 'order' => 0 ) );

		list( $config, $warnings ) = Blueprint::normalize_value( Facet_Config::OPTION, 'facet', $rows, false, array(), array( 'product_cat' ) );
		$this->assertSame( 'pa_size', $config[0]['taxonomy'], 'kept, not dropped — a fresh store indexes later' );
		$this->assertStringContainsString( 'not in this store\'s filter index yet', $warnings[0] );

		// Unknown index (null) means no advisory at all.
		list( , $warnings ) = Blueprint::normalize_value( Facet_Config::OPTION, 'facet', $rows, false, array(), null );
		$this->assertSame( array(), $warnings );
	}

	public function test_bundle_rows_reuse_the_module_normaliser(): void {
		$rows = array(
			array( 'type' => 'tiered', 'id' => 'x', 'enabled' => 1, 'tiers' => array( array( 'min' => 3, 'kind' => 'percent', 'amount' => 150 ) ) ),
			array( 'type' => 'nonsense' ), // dropped by the sanitiser.
		);
		list( $config, $warnings ) = Blueprint::normalize_value( Bundle_Config::OPTION, 'bundle', $rows, false, array(), null );

		$this->assertCount( 1, $config, 'the unknown-type row is dropped' );
		$this->assertSame( 'tiered', $config[0]['type'] );
		$this->assertSame( 100.0, $config[0]['tiers'][0]['amount'], 'percent clamped' );
		$this->assertSame( array(), $warnings );
	}

	public function test_validate_rejects_a_bundle_row_without_a_type(): void {
		$this->assertFalse( Blueprint::value_is_valid( 'bundle', Bundle_Config::OPTION, array( array( 'title' => 'x' ) ) ) );
		$this->assertTrue( Blueprint::value_is_valid( 'bundle', Bundle_Config::OPTION, array( array( 'type' => 'bogo' ) ) ) );
	}

	public function test_design_values_normalise_to_the_admin_shapes(): void {
		list( $value ) = Blueprint::normalize_value( 'shopos_core_design_radius', 'design', 8, false, array(), null );
		$this->assertSame( '8', $value );
		list( $value ) = Blueprint::normalize_value( 'shopos_core_design_radius', 'design', '', false, array(), null );
		$this->assertSame( '', $value );
		list( $value ) = Blueprint::normalize_value( 'shopos_core_design_col_gold', 'design', ' #b5532a ', false, array(), null );
		$this->assertSame( '#b5532a', $value );
	}

	/* -------- values_equal() -------- */

	public function test_values_equal_bridges_scalar_type_drift_but_not_content(): void {
		$this->assertTrue( Blueprint::values_equal( 1, '1' ) );
		$this->assertTrue( Blueprint::values_equal( array( 'a' => true ), array( 'a' => true ) ) );
		$this->assertFalse( Blueprint::values_equal( '1', '0' ) );
		$this->assertFalse( Blueprint::values_equal( array( 'a' => true ), array( 'a' => true, 'b' => true ) ) );
		$this->assertFalse( Blueprint::values_equal( array( 'a', 'b' ), array( 'b', 'a' ) ) );
		$this->assertTrue( Blueprint::values_equal( false, '' ) ); // absent option == semantic-default '' — a skip, not a write.
		$this->assertFalse( Blueprint::values_equal( false, array() ) );
	}

	/* -------- diff_rows() -------- */

	public function test_diff_marks_changed_keys_write_and_matching_keys_skip(): void {
		update_option( 'shopos_core_quick_view_label_close', 'Close it' );

		$diff = Blueprint::diff_rows(
			$this->envelope(
				array(
					'shopos_core_quick_view_label_close' => 'Close it',   // matches.
					'shopos_core_search_label_button'    => 'Find',       // differs (absent locally).
				)
			),
			self::KNOWN_MODULES,
			null
		);

		$by_key = array_column( $diff['rows'], null, 'option' );
		$this->assertSame( 'skip', $by_key['shopos_core_quick_view_label_close']['action'] );
		$this->assertSame( 'write', $by_key['shopos_core_search_label_button']['action'] );
		$this->assertSame( 'Find', $by_key['shopos_core_search_label_button']['blueprint'] );
		$this->assertSame(
			array( 'shopos_core_quick_view_label_close' => 'Close it' ),
			$GLOBALS['fr_opts'],
			'diff must never write (only the setUp seed may be present)'
		);
	}

	/* -------- apply() -------- */

	public function test_apply_writes_normalised_values_and_backs_up_first(): void {
		update_option( 'shopos_core_modules', array( 'quick_view' => true ) );
		$fired = array();
		add_action( 'shopos_core/blueprint/before_apply', function () use ( &$fired ) { $fired[] = 'before'; } );
		add_action( 'shopos_core/blueprint/after_apply', function () use ( &$fired ) { $fired[] = 'after'; } );

		$result = Blueprint::apply(
			$this->envelope(
				array(
					'shopos_core_modules'              => array( 'search' => true ),
					'shopos_core_design_panel_enabled' => 'true',
					'shopos_core_design_accent'        => 'forest',
				)
			),
			self::KNOWN_MODULES,
			null
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'test', $result['name'] );
		$this->assertSame( 3, $result['written'] );
		$this->assertSame( 0, $result['skipped'] );
		$this->assertSame( array( 'quick_view' => true, 'search' => true ), $GLOBALS['fr_opts']['shopos_core_modules'] );
		$this->assertSame( '1', $GLOBALS['fr_opts']['shopos_core_design_panel_enabled'] );
		$this->assertSame( 'forest', $GLOBALS['fr_opts']['shopos_core_design_accent'] );

		// Shared rolling-5 backup store, pre-apply state, 'blueprint' source.
		$backups = $GLOBALS['fr_opts'][ Settings_Tools::OPTION_BACKUPS ];
		$this->assertSame( 'blueprint', $backups[0]['source'] );
		$this->assertSame( array( 'quick_view' => true ), $backups[0]['options']['shopos_core_modules'] );
		$this->assertNotNull( $result['backup_at'] );

		$this->assertSame( array( 'before', 'after' ), $fired );
	}

	public function test_apply_is_idempotent_on_reapply(): void {
		$envelope = $this->envelope(
			array(
				'shopos_core_design_accent'         => 'plum',
				'shopos_core_quick_view_label_close' => 'Dismiss',
			)
		);

		$first = Blueprint::apply( $envelope, self::KNOWN_MODULES, null );
		$this->assertSame( array( 2, 0 ), array( $first['written'], $first['skipped'] ) );

		$second = Blueprint::apply( $envelope, self::KNOWN_MODULES, null );
		$this->assertTrue( $second['ok'], 'unchanged values must skip, not halt' );
		$this->assertSame( array( 0, 2 ), array( $second['written'], $second['skipped'] ) );
	}

	public function test_apply_rejects_invalid_files_with_zero_writes_and_no_backup(): void {
		$result = Blueprint::apply( $this->envelope( array( 'shopos_core_design_accent' => 'neon' ) ), self::KNOWN_MODULES, null );
		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'invalid_value:shopos_core_design_accent', $result['reason'] );
		$this->assertSame( 0, $result['written'] );
		$this->assertArrayNotHasKey( Settings_Tools::OPTION_BACKUPS, $GLOBALS['fr_opts'] );
		$this->assertArrayNotHasKey( 'shopos_core_design_accent', $GLOBALS['fr_opts'] );
	}

	public function test_apply_warns_when_an_applied_flag_is_forced_by_a_filter(): void {
		add_filter( 'shopos_core/feature_flag/design/panel', '__return_false' );

		$result = Blueprint::apply(
			$this->envelope( array( 'shopos_core_design_panel_enabled' => '1' ) ),
			self::KNOWN_MODULES,
			null
		);

		$this->assertSame( '1', $GLOBALS['fr_opts']['shopos_core_design_panel_enabled'] );
		$warnings = implode( ' | ', $result['warnings'] );
		$this->assertStringContainsString( 'shopos_core/feature_flag/design/panel', $warnings );
	}

	/* -------- stringify() -------- */

	public function test_stringify_renders_compact_single_lines(): void {
		$this->assertSame( 'plum', Blueprint::stringify( 'plum' ) );
		$this->assertSame( '(empty)', Blueprint::stringify( '' ) );
		$this->assertSame( '{"a":true}', Blueprint::stringify( array( 'a' => true ) ) );
		$this->assertSame( 60, strlen( Blueprint::stringify( str_repeat( 'x', 200 ) ) ) );
	}
}
