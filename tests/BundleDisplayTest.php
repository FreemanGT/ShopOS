<?php
declare(strict_types=1);

use ShopOS\Core\Modules\BundleDeals\Display;
use PHPUnit\Framework\TestCase;

/**
 * Bundle Deals storefront markup — the pure, escaped HTML seams.
 *
 * @covers \ShopOS\Core\Modules\BundleDeals\Display
 */
final class BundleDisplayTest extends TestCase {

	public function test_card_wraps_and_escapes_heading(): void {
		$html = Display::card( 'tiered', '<b>Deal</b>', '<i>inner</i>' );
		$this->assertStringContainsString( 'shopos-ui-bundle--tiered', $html );
		$this->assertStringContainsString( '&lt;b&gt;Deal&lt;/b&gt;', $html );
		$this->assertStringContainsString( '<i>inner</i>', $html, 'trusted inner passes through' );
	}

	public function test_card_sanitises_the_type_modifier(): void {
		$html = Display::card( 'bad type"', 'H', '' );
		$this->assertStringContainsString( 'shopos-ui-bundle--badtype', $html );
	}

	public function test_tier_rows_marks_active(): void {
		$html = Display::tier_rows(
			array(
				array( 'label' => '3+', 'value' => '-10%', 'active' => false ),
				array( 'label' => '5+', 'value' => '-20%', 'active' => true ),
			)
		);
		$this->assertSame( 1, substr_count( $html, 'is-active' ) );
		$this->assertStringContainsString( '3+', $html );
		$this->assertStringContainsString( '-20%', $html );
	}

	public function test_progress_never_empties_and_flags_unlocked(): void {
		$zero = Display::progress( 0.0, 'add 3', false );
		$this->assertStringContainsString( 'inline-size:8%', $zero, 'resting fill floor' );
		$this->assertStringNotContainsString( 'is-unlocked', $zero );

		$done = Display::progress( 1.0, 'unlocked', true );
		$this->assertStringContainsString( 'inline-size:100%', $done );
		$this->assertStringContainsString( 'is-unlocked', $done );
		$this->assertStringContainsString( 'aria-valuenow="100"', $done );
	}

	public function test_fbt_renders_checkboxes_and_escapes_names(): void {
		$html = Display::fbt(
			array(
				array( 'id' => 5, 'name' => 'A & B', 'thumb' => '<img>', 'price' => '<span>10</span>' ),
			),
			'<span>total</span>',
			'Add bundle'
		);
		$this->assertStringContainsString( 'value="5"', $html );
		$this->assertStringContainsString( 'A &amp; B', $html );
		$this->assertStringContainsString( '<img>', $html, 'trusted thumb passes through' );
		$this->assertStringContainsString( 'data-shopos-bundle-add', $html );
	}

	public function test_savings_tag(): void {
		$html = Display::savings_tag( '<span>₪10</span>', 'You save' );
		$this->assertStringContainsString( 'shopos-ui-bundle-save', $html );
		$this->assertStringContainsString( 'You save', $html );
		$this->assertStringContainsString( '<span>₪10</span>', $html );
	}
}
