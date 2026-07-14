<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductPage\Module;
use ShopOS\Core\Modules\ProductPage\Stock_Urgency;
use PHPUnit\Framework\TestCase;

/**
 * Stock urgency pure seams: the stock-row => message map (band edges,
 * unmanaged/empty rows, the {count} placeholder, a custom ceiling), the
 * badge shell markup, and the urgency_max setting clamp. The variation
 * objects read is integration (needs WC) — live-QA.
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Stock_Urgency
 */
final class ProductPageStockUrgencyTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	private function row( int $id, ?int $qty, bool $managing = true ): array {
		return array(
			'id'       => $id,
			'managing' => $managing,
			'qty'      => $qty,
		);
	}

	public function test_quantity_one_gets_the_last_unit_wording(): void {
		$messages = Stock_Urgency::messages( array( $this->row( 11, 1 ) ), 5, 'Last one', 'Only {count} left' );

		$this->assertSame( array( 11 => 'Last one' ), $messages );
	}

	public function test_low_quantities_get_the_count_wording(): void {
		$messages = Stock_Urgency::messages(
			array( $this->row( 11, 3 ), $this->row( 12, 5 ) ),
			5,
			'Last one',
			'Only {count} left'
		);

		$this->assertSame(
			array(
				11 => 'Only 3 left',
				12 => 'Only 5 left',
			),
			$messages
		);
	}

	/**
	 * @dataProvider provider_skipped_rows
	 */
	public function test_rows_outside_the_band_are_skipped( array $row ): void {
		$this->assertSame( array(), Stock_Urgency::messages( array( $row ), 5, 'last', '{count}' ) );
	}

	public static function provider_skipped_rows(): array {
		return array(
			'zero stock'      => array( array( 'id' => 11, 'managing' => true, 'qty' => 0 ) ),
			'above ceiling'   => array( array( 'id' => 11, 'managing' => true, 'qty' => 6 ) ),
			'unmanaged stock' => array( array( 'id' => 11, 'managing' => false, 'qty' => 2 ) ),
			'null quantity'   => array( array( 'id' => 11, 'managing' => true, 'qty' => null ) ),
			'missing id'      => array( array( 'id' => 0, 'managing' => true, 'qty' => 2 ) ),
		);
	}

	public function test_custom_ceiling_widens_the_band(): void {
		$messages = Stock_Urgency::messages( array( $this->row( 11, 6 ) ), 8, 'last', 'Only {count} left' );

		$this->assertSame( array( 11 => 'Only 6 left' ), $messages );
	}

	public function test_badge_html_ships_hidden_with_the_message_map(): void {
		$json = (string) wp_json_encode( array( 11 => 'Last one' ) );
		$html = Stock_Urgency::badge_html( $json );

		$this->assertStringContainsString( 'shopos-ui-stock-urgency', $html );
		$this->assertStringContainsString( ' hidden>', $html, 'shell must print hidden until a variation is picked' );
		$this->assertStringContainsString( 'data-shopos-ui-urgency=', $html );
		$this->assertStringContainsString( 'data-shopos-ui-urgency-text', $html, 'the text element must be JS-addressable' );
	}

	public function test_max_units_defaults_and_clamps(): void {
		$urgency = new Stock_Urgency( new Module() );

		$this->assertSame( 5, $urgency->max_units(), 'unset option falls back to the schema default' );

		$GLOBALS['fr_opts']['shopos_core_product_page_urgency_max'] = '8';
		$this->assertSame( 8, $urgency->max_units() );

		$GLOBALS['fr_opts']['shopos_core_product_page_urgency_max'] = '0';
		$this->assertSame( 5, $urgency->max_units(), 'a non-positive setting falls back to the snippet default' );
	}
}
