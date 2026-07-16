<?php
declare(strict_types=1);

use ShopOS\Core\Core\CLI;
use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * The `wp shopos` command's pure seams (target→module mapping, flag-arg /
 * state parsing, flags-list row building) plus the flags command's
 * option-write behaviour against the recording WP_CLI stub. The reindex
 * loop itself drives the modules' already-tested Indexer::reindex_batch()
 * and is exercised end-to-end in live-QA, not here.
 *
 * @covers \ShopOS\Core\Core\CLI
 */
final class CliTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		$GLOBALS['fr_cli']   = array();
	}

	/* -------- register() -------- */

	public function test_register_is_a_noop_without_the_wp_cli_constant(): void {
		CLI::register();
		$this->assertArrayNotHasKey( 'commands', $GLOBALS['fr_cli'] );
	}

	/* -------- reindex target mapping -------- */

	public function test_reindex_targets_map_to_module_ids(): void {
		$this->assertSame( 'search', CLI::reindex_module_id( 'search' ) );
		$this->assertSame( 'shop_filters', CLI::reindex_module_id( 'shop-filters' ) );
	}

	public function test_unknown_reindex_targets_map_to_null(): void {
		$this->assertNull( CLI::reindex_module_id( 'shop_filters' ) ); // module id, not the CLI spelling.
		$this->assertNull( CLI::reindex_module_id( 'bogus' ) );
		$this->assertNull( CLI::reindex_module_id( '' ) );
	}

	public function test_reindex_command_errors_on_unknown_target(): void {
		( new CLI() )->reindex( array( 'bogus' ) );
		$this->assertStringContainsString( 'Unknown index', $GLOBALS['fr_cli']['error'][0] );
	}

	/* -------- flag-arg parsing -------- */

	public function test_parse_flag_accepts_a_registry_flag(): void {
		$this->assertSame( array( 'design', 'panel' ), CLI::parse_flag( 'design.panel' ) );
	}

	public function test_parse_flag_rejects_unknown_and_malformed_input(): void {
		$this->assertNull( CLI::parse_flag( 'design.bogus' ) );
		$this->assertNull( CLI::parse_flag( 'design' ) );
		$this->assertNull( CLI::parse_flag( 'design.' ) );
		$this->assertNull( CLI::parse_flag( '.panel' ) );
		$this->assertNull( CLI::parse_flag( '' ) );
	}

	public function test_parse_state_is_strict_on_off(): void {
		$this->assertTrue( CLI::parse_state( 'on' ) );
		$this->assertFalse( CLI::parse_state( 'off' ) );
		$this->assertNull( CLI::parse_state( '1' ) );
		$this->assertNull( CLI::parse_state( 'true' ) );
		$this->assertNull( CLI::parse_state( '' ) );
	}

	/* -------- flags list -------- */

	public function test_flag_rows_cover_the_whole_registry(): void {
		$rows = CLI::flag_rows();
		$this->assertCount( count( Feature_Flags::registry() ), $rows );
		foreach ( $rows as $row ) {
			$this->assertMatchesRegularExpression( '/^[a-z0-9_]+\.[a-z0-9_]+$/', $row['flag'] );
			$this->assertContains( $row['enabled'], array( 'on', 'off' ) );
			$this->assertContains( $row['forced_by_filter'], array( 'yes', 'no' ) );
		}
	}

	public function test_flag_rows_reflect_the_saved_option(): void {
		update_option( Feature_Flags::option_name( 'design', 'panel' ), '1' );
		$row = $this->row_for( 'design.panel' );
		$this->assertSame( 'on', $row['enabled'] );
		$this->assertSame( 'no', $row['forced_by_filter'] );
	}

	public function test_flag_rows_show_the_filter_forced_effective_state(): void {
		update_option( Feature_Flags::option_name( 'design', 'panel' ), '1' );
		add_filter( 'shopos_core/feature_flag/design/panel', '__return_false' );
		$row = $this->row_for( 'design.panel' );
		$this->assertSame( 'off', $row['enabled'] ); // effective, not the raw option.
		$this->assertSame( 'yes', $row['forced_by_filter'] );
	}

	public function test_flags_list_emits_one_table_of_all_flags(): void {
		( new CLI() )->flags( array( 'list' ) );
		$table = $GLOBALS['fr_cli']['tables'][0];
		$this->assertSame( 'table', $table['format'] );
		$this->assertSame( array( 'flag', 'enabled', 'forced_by_filter', 'since' ), $table['fields'] );
		$this->assertCount( count( Feature_Flags::registry() ), $table['items'] );
	}

	/* -------- flags set -------- */

	public function test_flags_set_writes_the_admin_page_option_shape(): void {
		( new CLI() )->flags( array( 'set', 'design.panel', 'on' ) );
		$this->assertSame( '1', $GLOBALS['fr_opts']['shopos_core_design_panel_enabled'] );
		$this->assertStringContainsString( 'Effective state: on', $GLOBALS['fr_cli']['success'][0] );
		$this->assertArrayNotHasKey( 'warning', $GLOBALS['fr_cli'] );

		( new CLI() )->flags( array( 'set', 'design.panel', 'off' ) );
		$this->assertSame( '0', $GLOBALS['fr_opts']['shopos_core_design_panel_enabled'] );
	}

	public function test_flags_set_rejects_an_unknown_flag_without_writing(): void {
		( new CLI() )->flags( array( 'set', 'design.bogus', 'on' ) );
		$this->assertStringContainsString( 'Unknown flag', $GLOBALS['fr_cli']['error'][0] );
		$this->assertSame( array(), $GLOBALS['fr_opts'] );
	}

	public function test_flags_set_rejects_a_bad_state_without_writing(): void {
		( new CLI() )->flags( array( 'set', 'design.panel', 'yes' ) );
		$this->assertStringContainsString( 'on', $GLOBALS['fr_cli']['error'][0] );
		$this->assertSame( array(), $GLOBALS['fr_opts'] );
	}

	public function test_flags_set_warns_when_a_filter_forces_the_flag(): void {
		add_filter( 'shopos_core/feature_flag/design/panel', '__return_false' );
		( new CLI() )->flags( array( 'set', 'design.panel', 'on' ) );
		$this->assertSame( '1', $GLOBALS['fr_opts']['shopos_core_design_panel_enabled'] );
		$this->assertStringContainsString( 'filter is active', $GLOBALS['fr_cli']['warning'][0] );
		$this->assertStringContainsString( 'Effective state: off', $GLOBALS['fr_cli']['success'][0] );
	}

	public function test_flags_unknown_action_errors(): void {
		( new CLI() )->flags( array( 'bogus' ) );
		$this->assertStringContainsString( 'Unknown subcommand', $GLOBALS['fr_cli']['error'][0] );
	}

	/* -------- blueprint (decisions §10) -------- */

	public function test_blueprint_name_prefers_the_explicit_arg_then_the_file_stem(): void {
		$this->assertSame( 'flagship', CLI::blueprint_name( '/tmp/whatever.json', ' flagship ' ) );
		$this->assertSame( 'flagship', CLI::blueprint_name( '/stores/flagship.json', '' ) );
		$this->assertSame( 'flagship.v2', CLI::blueprint_name( 'flagship.v2.json', '' ) );
		$this->assertSame( 'flagship', CLI::blueprint_name( 'flagship', '' ) );
		$this->assertSame( '.hidden', CLI::blueprint_name( '.hidden', '' ) );
	}

	public function test_blueprint_unknown_action_errors(): void {
		( new CLI() )->blueprint( array( 'bogus', 'x.json' ) );
		$this->assertStringContainsString( 'Unknown subcommand', $GLOBALS['fr_cli']['error'][0] );
	}

	public function test_blueprint_requires_a_file_path(): void {
		( new CLI() )->blueprint( array( 'export' ) );
		$this->assertStringContainsString( 'Missing file path', $GLOBALS['fr_cli']['error'][0] );
	}

	public function test_blueprint_export_writes_a_valid_blueprint_file(): void {
		update_option( 'shopos_core_design_accent', 'forest' );
		$file = sys_get_temp_dir() . '/shopos-bp-' . uniqid() . '.json';

		( new CLI() )->blueprint( array( 'export', $file, 'flagship' ) );

		$this->assertStringContainsString( 'Exported blueprint "flagship"', $GLOBALS['fr_cli']['success'][0] );
		$decoded = json_decode( (string) file_get_contents( $file ), true );
		$this->assertSame( 'flagship', $decoded['blueprint']['name'] );
		$this->assertSame( 'forest', $decoded['options']['shopos_core_design_accent'] );
		$this->assertTrue( \ShopOS\Core\Core\Blueprint::validate( $decoded )['ok'] );
		unlink( $file );
	}

	public function test_blueprint_diff_and_import_error_on_unreadable_or_invalid_files(): void {
		( new CLI() )->blueprint( array( 'diff', '/nonexistent/x.json' ) );
		$this->assertStringContainsString( 'Cannot read', $GLOBALS['fr_cli']['error'][0] );

		$file = tempnam( sys_get_temp_dir(), 'shopos-bp-' );
		file_put_contents( $file, 'not json' );
		( new CLI() )->blueprint( array( 'import', $file ) );
		$this->assertStringContainsString( 'not valid JSON', $GLOBALS['fr_cli']['error'][1] );
		$this->assertArrayNotHasKey( 'shopos_core_settings_backups', $GLOBALS['fr_opts'] );

		file_put_contents( $file, (string) json_encode( array( 'version' => 1 ) ) );
		( new CLI() )->blueprint( array( 'import', $file ) );
		$this->assertStringContainsString( 'missing_field:exported_at', $GLOBALS['fr_cli']['error'][2] );
		unlink( $file );
	}

	/* -------- helpers -------- */

	/** Find one flag_rows() row by its module.feature key. */
	private function row_for( string $flag ): array {
		foreach ( CLI::flag_rows() as $row ) {
			if ( $flag === $row['flag'] ) {
				return $row;
			}
		}
		$this->fail( "No row for {$flag}" );
	}
}
