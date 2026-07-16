<?php
declare(strict_types=1);

use ShopOS\Core\Core\Module_Base;
use ShopOS\Core\Modules\Search\Labels as SearchLabels;
use PHPUnit\Framework\TestCase;

/**
 * Minimal concrete module so the abstract base can be instantiated.
 */
final class _LabelFieldsModuleFixture extends Module_Base {
	public function label() {
		return 'Fixture';
	}
	public function description() {
		return '';
	}
	public function id() {
		return 'fixture';
	}
	public function boot() {}
}

/**
 * Module_Base::label_fields() — the shared `label_<key>` text-field builder
 * extracted from the QuickView / ShopFilters / Search settings loops.
 *
 * @covers \ShopOS\Core\Core\Module_Base::label_fields
 */
final class ModuleBaseLabelFieldsTest extends TestCase {

	private function module(): _LabelFieldsModuleFixture {
		return new _LabelFieldsModuleFixture();
	}

	public function test_builds_a_text_field_per_label_with_empty_default(): void {
		$fields = $this->module()->label_fields(
			array(
				'alpha' => array( 'label' => 'Alpha label', 'default' => 'Alpha' ),
				'beta'  => array( 'label' => 'Beta label', 'default' => 'Beta' ),
			),
			'Intro sentence.'
		);

		$this->assertSame( array( 'label_alpha', 'label_beta' ), array_keys( $fields ) );
		$this->assertSame(
			array(
				'label'       => 'Alpha label',
				'type'        => 'text',
				'default'     => '',
				'description' => 'Intro sentence. Default: Alpha',
			),
			$fields['label_alpha']
		);
		// Intro only prefixes the first field.
		$this->assertSame( 'Default: Beta', $fields['label_beta']['description'] );
	}

	public function test_empty_intro_yields_no_prefix_on_first_field(): void {
		$fields = $this->module()->label_fields(
			array( 'alpha' => array( 'label' => 'Alpha label', 'default' => 'Alpha' ) ),
			''
		);
		$this->assertSame( 'Default: Alpha', $fields['label_alpha']['description'] );
	}

	public function test_empty_defaults_map_yields_no_fields(): void {
		$this->assertSame( array(), $this->module()->label_fields( array() ) );
	}

	/**
	 * Byte-identity guarantee: the helper reproduces the exact hand-rolled
	 * loop the modules shipped pre-adoption (replaced in 1.40.0), driven by
	 * real Search\Labels data. LabelsAdoptionTest asserts the same parity
	 * against each adopted module's live settings_schema().
	 */
	public function test_output_is_byte_identical_to_the_shipped_loop(): void {
		$defaults = SearchLabels::defaults();
		$intro    = 'Search wording — leave a field blank to use its English default.';

		// The exact loop Search/QuickView/ShopFilters ship in settings_schema().
		$replica = array();
		$first   = true;
		foreach ( $defaults as $key => $def ) {
			$desc = sprintf( 'Default: %s', $def['default'] );
			if ( $first ) {
				$desc = $intro . ' ' . $desc;
			}
			$replica[ 'label_' . $key ] = array(
				'label'       => $def['label'],
				'type'        => 'text',
				'default'     => '',
				'description' => $desc,
			);
			$first = false;
		}

		$this->assertSame( $replica, $this->module()->label_fields( $defaults, $intro ) );
	}
}
