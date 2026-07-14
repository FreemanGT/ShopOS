<?php
/**
 * Shop Filters — on-sale / in-stock flag facet template.
 *
 * Renders the boolean flags (On sale, In stock) as checkboxes carrying the
 * `shopos-sf__flag` class + a `data-shopos-sf-flag` param name, so the reload
 * controller serialises them to the top-level `onsale=1` / `in_stock=1` params
 * (NOT the filter_ prefix — Url_State parses these as flags, not taxonomies).
 * Counts come from wc_product_meta_lookup (see Query_Builder). Expects:
 *   - array $flags : ['onsale'=>['count','selected'], 'in_stock'=>[…]] (keys present only when shown).
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

use ShopOS\Core\Modules\ShopFilters\Labels;

/** @var array $flags */
$sf_flags = isset( $flags ) && is_array( $flags ) ? $flags : array();
if ( empty( $sf_flags ) ) {
	return;
}

$sf_flag_labels = array(
	'onsale'   => Labels::get( 'onsale' ),
	'in_stock' => Labels::get( 'in_stock' ),
);
?>
<fieldset class="shopos-sf__facet shopos-sf__facet--flags" data-shopos-sf-facet="flags">
	<legend class="shopos-sf__facet-title"><?php echo esc_html( Labels::get( 'flags_heading' ) ); ?></legend>
	<ul class="shopos-sf__terms">
		<?php foreach ( $sf_flags as $sf_key => $sf_flag ) : ?>
			<li class="shopos-sf__term">
				<label>
					<input
						type="checkbox"
						class="shopos-sf__checkbox shopos-sf__flag"
						data-shopos-sf-flag="<?php echo esc_attr( $sf_key ); ?>"
						value="1"
						<?php checked( ! empty( $sf_flag['selected'] ) ); ?>
					/>
					<span class="shopos-sf__term-label"><?php echo esc_html( $sf_flag_labels[ $sf_key ] ); ?></span>
					<span class="shopos-sf__term-count">(<?php echo esc_html( (string) (int) $sf_flag['count'] ); ?>)</span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>
</fieldset>
