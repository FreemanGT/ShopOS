<?php
declare(strict_types=1);

use ShopOS\Core\Core\Labels_Base;
use ShopOS\Core\Modules\ProductPage\Labels as ProductPageLabels;
use ShopOS\Core\Modules\ProductPage\Module as ProductPageModule;
use ShopOS\Core\Modules\QuickView\Labels as QuickViewLabels;
use ShopOS\Core\Modules\QuickView\Module as QuickViewModule;
use ShopOS\Core\Modules\Search\Labels as SearchLabels;
use ShopOS\Core\Modules\Search\Module as SearchModule;
use ShopOS\Core\Modules\ShopFilters\Labels as ShopFiltersLabels;
use ShopOS\Core\Modules\ShopFilters\Module as ShopFiltersModule;
use PHPUnit\Framework\TestCase;

/**
 * Labels_Base / label_fields() adoption (1.40.0) — byte-identity proof.
 *
 * QuickView, ShopFilters and Search dropped their hand-rolled `get()` for
 * Labels_Base and swapped their settings_schema() label loops for
 * Module_Base::label_fields(). This test pins the refactor as a pure one:
 * the resolver behaves exactly as before, and the schema label block is
 * byte-identical to the loop each module shipped pre-adoption.
 *
 * ProductPage adopted the resolver in 1.41.0 — resolver only: its sectioned
 * settings loop is not label_fields()-compatible and stays hand-rolled
 * (pinned below: per-key sections + "Leave blank…" wording + empty-default
 * trust-line descriptions survive the adoption untouched).
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Labels
 * @covers \ShopOS\Core\Modules\QuickView\Labels
 * @covers \ShopOS\Core\Modules\ShopFilters\Labels
 * @covers \ShopOS\Core\Modules\Search\Labels
 */
final class LabelsAdoptionTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
	}

	/**
	 * The exact hand-rolled loop the three modules shipped pre-adoption
	 * (kept verbatim as the parity oracle).
	 *
	 * @param array<string,array{label:string,default:string}> $defaults Label map.
	 * @param string                                           $intro    First-field intro sentence.
	 * @return array<string,array>
	 */
	private function replica( array $defaults, string $intro ): array {
		$schema = array();
		$first  = true;
		foreach ( $defaults as $key => $def ) {
			$desc = sprintf( 'Default: %s', $def['default'] );
			if ( $first ) {
				$desc = $intro . ' ' . $desc;
			}
			$schema[ 'label_' . $key ] = array(
				'label'       => $def['label'],
				'type'        => 'text',
				'default'     => '',
				'description' => $desc,
			);
			$first = false;
		}
		return $schema;
	}

	public function test_adopted_labels_classes_extend_labels_base(): void {
		$this->assertTrue( is_subclass_of( QuickViewLabels::class, Labels_Base::class ) );
		$this->assertTrue( is_subclass_of( ShopFiltersLabels::class, Labels_Base::class ) );
		$this->assertTrue( is_subclass_of( SearchLabels::class, Labels_Base::class ) );
		$this->assertTrue( is_subclass_of( ProductPageLabels::class, Labels_Base::class ) );
	}

	public function test_quick_view_schema_is_byte_identical_to_the_pre_adoption_loop(): void {
		$expected = $this->replica(
			QuickViewLabels::defaults(),
			'Quick-view wording — leave a field blank to use its English default.'
		);
		// QuickView's schema is the label block alone.
		$this->assertSame( $expected, ( new QuickViewModule() )->settings_schema() );
	}

	public function test_shop_filters_schema_label_block_is_byte_identical_to_the_pre_adoption_loop(): void {
		$expected = $this->replica(
			ShopFiltersLabels::defaults(),
			'Filter panel wording — leave a field blank to use its English default.'
		);
		$schema = ( new ShopFiltersModule() )->settings_schema();
		// The label block leads the schema, in map order, followed by the
		// module's other fields — exactly as pre-adoption.
		$this->assertSame( $expected, array_slice( $schema, 0, count( $expected ), true ) );
	}

	public function test_search_schema_label_block_is_byte_identical_to_the_pre_adoption_loop(): void {
		$expected = $this->replica(
			SearchLabels::defaults(),
			'Search wording — leave a field blank to use its English default.'
		);
		$schema = ( new SearchModule() )->settings_schema();
		$this->assertSame( $expected, array_slice( $schema, 0, count( $expected ), true ) );
	}

	public function test_inherited_get_resolves_via_each_subclass_prefix(): void {
		// Default when unset.
		$this->assertSame( 'Quick view', QuickViewLabels::get( 'trigger' ) );
		$this->assertSame( 'Clear all', ShopFiltersLabels::get( 'clear_all' ) );
		$this->assertSame( 'Search products…', SearchLabels::get( 'placeholder' ) );

		// Saved override wins, under the module's own option prefix.
		update_option( 'shopos_core_quick_view_label_trigger', 'תצוגה מהירה' );
		update_option( 'shopos_core_shop_filters_label_clear_all', 'נקה הכל' );
		update_option( 'shopos_core_search_label_placeholder', 'חיפוש מוצרים…' );
		$this->assertSame( 'תצוגה מהירה', QuickViewLabels::get( 'trigger' ) );
		$this->assertSame( 'נקה הכל', ShopFiltersLabels::get( 'clear_all' ) );
		$this->assertSame( 'חיפוש מוצרים…', SearchLabels::get( 'placeholder' ) );

		// Whitespace-only override falls back to the default.
		update_option( 'shopos_core_search_label_button', '   ' );
		$this->assertSame( 'Search', SearchLabels::get( 'button' ) );

		// count_text() still rides the inherited resolver.
		$this->assertSame( '1 product', ShopFiltersLabels::count_text( 1 ) );
		$this->assertSame( '5 products', ShopFiltersLabels::count_text( 5 ) );
	}

	public function test_product_page_inherited_get_resolves_via_its_prefix(): void {
		// Default when unset.
		$this->assertSame( 'Enter coupon code', ProductPageLabels::get( 'coupon_intro' ) );
		// Saved override wins under the module's own prefix.
		update_option( 'shopos_core_product_page_label_coupon_intro', 'הזינו קוד קופון' );
		$this->assertSame( 'הזינו קוד קופון', ProductPageLabels::get( 'coupon_intro' ) );
		// Whitespace-only override falls back.
		update_option( 'shopos_core_product_page_label_urgency_last_unit', '   ' );
		$this->assertSame( 'Last one in stock', ProductPageLabels::get( 'urgency_last_unit' ) );
		// Intentionally-empty default (trust lines) stays empty when unset.
		$this->assertSame( '', ProductPageLabels::get( 'trust_shipping' ) );
	}

	public function test_product_page_sectioned_label_loop_is_untouched(): void {
		$schema = ( new ProductPageModule() )->settings_schema();
		foreach ( ProductPageLabels::defaults() as $key => $def ) {
			$field = $schema[ 'label_' . $key ];
			$this->assertSame( 'text', $field['type'] );
			$this->assertSame( '', $field['default'] );
			// The per-key section routing label_fields() cannot produce.
			$this->assertArrayHasKey( 'section', $field );
			// The module's own description wording, not label_fields()'s.
			$expected = '' === $def['default']
				? 'Shown under the add-to-cart button on the designed layout; leave empty to hide.'
				: sprintf( 'Leave blank to use the default: %s', $def['default'] );
			$this->assertSame( $expected, $field['description'] );
		}
		$this->assertSame( 'Trust line (designed layout)', $schema['label_trust_shipping']['section'] );
	}
}
