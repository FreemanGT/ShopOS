<?php
declare(strict_types=1);

use ShopOS\Core\Admin\Dashboard;
use PHPUnit\Framework\TestCase;

/**
 * The dashboard search's pure seam: Dashboard::settings_index() — one
 * jump-to-setting row per schema entry, deep-linking to the module settings
 * page with the option name as the URL fragment (fields render with
 * id="<option_name>", so the fragment is the anchor). The search box render,
 * card filtering, and the inline JS are integration/live-QA.
 *
 * @covers \ShopOS\Core\Admin\Dashboard
 */
final class DashboardSearchTest extends TestCase {

	/** Minimal module double: id + label + schema + Module_Base-shaped option_name(). */
	private function module( string $id, string $label, array $schema ): object {
		return new class( $id, $label, $schema ) {
			private $id;
			private $label;
			private $schema;
			public function __construct( $id, $label, $schema ) {
				$this->id     = $id;
				$this->label  = $label;
				$this->schema = $schema;
			}
			public function id() {
				return $this->id;
			}
			public function label() {
				return $this->label;
			}
			public function settings_schema() {
				return $this->schema;
			}
			public function option_name( $key ) {
				return 'shopos_core_' . $this->id . '_' . $key;
			}
		};
	}

	public function test_one_row_per_schema_entry_and_schema_less_modules_are_skipped(): void {
		$index = Dashboard::settings_index(
			array(
				$this->module( 'search', 'Search', array(
					'max_results' => array( 'label' => 'Max results', 'section' => 'Dropdown' ),
					'show_price'  => array( 'label' => 'Show price', 'section' => 'Dropdown' ),
				) ),
				$this->module( 'hover_swap', 'Hover Swap', array() ), // no schema — no rows.
			)
		);

		$this->assertCount( 2, $index );
		$this->assertSame( 'Max results', $index[0]['s'] );
		$this->assertSame( 'Search', $index[0]['m'] );
		$this->assertSame( 'Dropdown', $index[0]['c'] );
	}

	public function test_url_deep_links_to_the_settings_page_with_the_option_fragment(): void {
		$index = Dashboard::settings_index(
			array(
				$this->module( 'search', 'Search', array(
					'max_results' => array( 'label' => 'Max results' ),
				) ),
			)
		);

		$this->assertSame(
			'https://example.test/wp-admin/admin.php?page=shopos-search#shopos_core_search_max_results',
			$index[0]['u']
		);
	}

	public function test_label_falls_back_to_the_setting_key(): void {
		$index = Dashboard::settings_index(
			array(
				$this->module( 'search', 'Search', array(
					'max_results' => array(), // no label, no section.
				) ),
			)
		);

		$this->assertSame( 'max_results', $index[0]['s'] );
		$this->assertSame( '', $index[0]['c'] );
	}

	public function test_haystack_matches_on_label_key_section_and_module(): void {
		$index = Dashboard::settings_index(
			array(
				$this->module( 'search', 'Search', array(
					'max_results' => array( 'label' => 'Max results', 'section' => 'Dropdown' ),
				) ),
			)
		);

		$h = $index[0]['h'];
		$this->assertStringContainsString( 'Max results', $h );
		$this->assertStringContainsString( 'max_results', $h );
		$this->assertStringContainsString( 'Dropdown', $h );
		$this->assertStringContainsString( 'Search', $h );
	}

	public function test_index_over_the_real_registry_shape_is_json_safe(): void {
		// Every row must be a flat string map — the render embeds it verbatim
		// as JSON; a non-scalar would mean a schema entry leaked structure in.
		$index = Dashboard::settings_index(
			array(
				$this->module( 'search', 'Search', array(
					'max_results' => array( 'label' => 'Max results', 'section' => 'Dropdown', 'choices' => array( 'a' => 'b' ) ),
				) ),
			)
		);

		foreach ( $index[0] as $value ) {
			$this->assertIsString( $value );
		}
		$this->assertNotFalse( json_encode( $index ) );
	}
}
