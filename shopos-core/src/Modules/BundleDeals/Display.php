<?php
/**
 * Bundle Deals — storefront markup.
 *
 * Every method is pure and returns a fully-escaped HTML string (the
 * Coupon_Notice / Stock_Urgency "pure, unit-tested markup" convention), so the
 * whole visual surface is testable without WordPress. `Frontend` resolves the
 * live product/bundle data and feeds it in; trusted, pre-formatted fragments
 * (a `wc_price()` string, an image tag) are documented as such at each seam.
 *
 * Class hooks hang off `.shopos-ui-bundle*`; all colour / spacing / radius come
 * from the `--shopos-ui-*` design tokens with literal fallbacks, and layout
 * uses logical properties so the RTL storefront is correct for free.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Pure display helpers.
 */
final class Display {

	/**
	 * Wrap one bundle offer in the shared card chrome.
	 *
	 * @param string $type    Bundle type slug (for the modifier class).
	 * @param string $heading Card heading (plain text).
	 * @param string $inner   Inner HTML (trusted — built by the methods below).
	 * @param string $attr    Optional extra root attributes (already escaped).
	 * @return string
	 */
	public static function card( $type, $heading, $inner, $attr = '' ) {
		$type = preg_replace( '/[^a-z0-9_\-]/', '', (string) $type );

		return '<section class="shopos-ui-bundle shopos-ui-bundle--' . esc_attr( $type ) . '"' . $attr . '>'
			. ( '' !== (string) $heading ? '<h3 class="shopos-ui-bundle__heading">' . esc_html( $heading ) . '</h3>' : '' )
			. '<div class="shopos-ui-bundle__body">' . $inner . '</div>'
			. '</section>';
	}

	/**
	 * Volume/tiered discount table.
	 *
	 * @param array<int,array{label:string,value:string,active:bool}> $rows Tier rows.
	 * @return string
	 */
	public static function tier_rows( array $rows ) {
		$html = '<ul class="shopos-ui-bundle__tiers">';
		foreach ( $rows as $row ) {
			$active = ! empty( $row['active'] ) ? ' is-active' : '';
			$html  .= '<li class="shopos-ui-bundle__tier' . $active . '">'
				. '<span class="shopos-ui-bundle__tier-qty">' . esc_html( (string) ( $row['label'] ?? '' ) ) . '</span>'
				. '<span class="shopos-ui-bundle__tier-save">' . esc_html( (string) ( $row['value'] ?? '' ) ) . '</span>'
				. '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * A single BOGO/offer line.
	 *
	 * @param string $text Offer sentence (plain text).
	 * @return string
	 */
	public static function offer_line( $text ) {
		return '<p class="shopos-ui-bundle__offer">'
			. '<span class="shopos-ui-bundle__offer-badge" aria-hidden="true">%</span>'
			. '<span class="shopos-ui-bundle__offer-text">' . esc_html( (string) $text ) . '</span>'
			. '</p>';
	}

	/**
	 * Mix-&-match progress bar + status line. The fill never fully empties
	 * (min resting width) so the control reads as a progress affordance even at
	 * zero — the PDP gallery-progress convention.
	 *
	 * @param float  $ratio    Completion ratio 0..1.
	 * @param string $message  Status line (plain text).
	 * @param bool   $unlocked Whether the threshold is met.
	 * @return string
	 */
	public static function progress( $ratio, $message, $unlocked ) {
		$pct   = max( 8.0, min( 100.0, (float) $ratio * 100 ) );
		$state = $unlocked ? ' is-unlocked' : '';

		return '<div class="shopos-ui-bundle__progress' . $state . '">'
			. '<div class="shopos-ui-bundle__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) round( $pct ) ) . '">'
			. '<span class="shopos-ui-bundle__fill" style="inline-size:' . esc_attr( (string) round( $pct, 2 ) ) . '%"></span>'
			. '</div>'
			. '<p class="shopos-ui-bundle__status" data-shopos-bundle-status>' . esc_html( (string) $message ) . '</p>'
			. '</div>';
	}

	/**
	 * Frequently-bought-together box: the item list (each with a checkbox,
	 * thumbnail and price) plus the combined-price add button.
	 *
	 * @param array<int,array{id:int,name:string,thumb:string,price:string}> $items Set items (thumb + price are trusted HTML).
	 * @param string $total_html Combined price HTML (trusted, from wc_price()).
	 * @param string $add_label  Add-button label (plain text).
	 * @param string $data_attr  Escaped data-* attributes carrying the id list + nonce.
	 * @return string
	 */
	public static function fbt( array $items, $total_html, $add_label, $data_attr = '' ) {
		$list = '<ul class="shopos-ui-bundle__set">';
		foreach ( $items as $item ) {
			$id    = (int) ( $item['id'] ?? 0 );
			$list .= '<li class="shopos-ui-bundle__set-item">'
				. '<label class="shopos-ui-bundle__set-label">'
				. '<input type="checkbox" class="shopos-ui-bundle__set-check" value="' . esc_attr( (string) $id ) . '" checked />'
				. '<span class="shopos-ui-bundle__set-thumb">' . ( (string) ( $item['thumb'] ?? '' ) ) . '</span>'
				. '<span class="shopos-ui-bundle__set-name">' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</span>'
				. '<span class="shopos-ui-bundle__set-price">' . ( (string) ( $item['price'] ?? '' ) ) . '</span>'
				. '</label>'
				. '</li>';
		}
		$list .= '</ul>';

		return '<div class="shopos-ui-bundle__fbt"' . $data_attr . '>'
			. $list
			. '<div class="shopos-ui-bundle__fbt-foot">'
			. '<span class="shopos-ui-bundle__fbt-total" data-shopos-bundle-total>' . $total_html . '</span>'
			. '<button type="button" class="shopos-ui-bundle__add" data-shopos-bundle-add>' . esc_html( (string) $add_label ) . '</button>'
			. '</div>'
			. '</div>';
	}

	/**
	 * The cart-line "you save" savings tag.
	 *
	 * @param string $amount_html Saved amount HTML (trusted, from wc_price()).
	 * @param string $label       "You save" label (plain text).
	 * @return string
	 */
	public static function savings_tag( $amount_html, $label ) {
		return '<span class="shopos-ui-bundle-save">'
			. esc_html( (string) $label ) . ' ' . $amount_html
			. '</span>';
	}
}
