<?php
/**
 * Shop Filters — panel template.
 *
 * Server-rendered filter panel. On desktop it renders inline (sidebar); on
 * mobile the same markup becomes an off-canvas drawer opened by the toggle
 * button — the front-end controller defers navigation until "Apply" on mobile,
 * while desktop navigates on each change (reload transport). Expects in scope:
 *   - array $facets        : shaped facets[] (see Query_Builder::shape_facets()).
 *   - array $category_tree : nested product_cat tree (Category_Tree::build()).
 *   - int   $count         : current filtered product count.
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

use ShopOS\Core\Modules\ShopFilters\Labels;
use ShopOS\Core\Modules\ShopFilters\Url_State;
use ShopOS\Core\Modules\ShopFilters\Module;

/** @var array $facets */
/** @var array $category_tree */
/** @var array $price */
/** @var string $orderby */
/** @var int $count */

// Effective sort selection: the URL's orderby, else the configured default.
$sf_current_sort = '' !== (string) $orderby ? (string) $orderby : (string) get_option( 'shopos_core_shop_filters_default_sort', '' );

// Panel style (ShopOS → Shop Filters). 'classic' renders today's checkbox
// layout verbatim; 'refined' adds the `shopos-sf--refined` modifier the CSS/JS
// key the pill/accordion/show-more/scroll treatment off — classic markup is
// otherwise unchanged.
$sf_style = ( 'refined' === get_option( 'shopos_core_shop_filters_filter_style', 'classic' ) ) ? ' shopos-sf--refined' : '';
?>
<div class="shopos-sf<?php echo esc_attr( $sf_style ); ?>" data-shopos-sf>
	<button type="button" class="shopos-sf__toggle shopos-ui-btn shopos-ui-btn--ghost shopos-ui-btn--block" data-shopos-sf-toggle aria-expanded="false" aria-controls="shopos-sf-panel">
		<?php echo esc_html( Labels::get( 'toggle' ) ); ?>
	</button>

	<div class="shopos-sf__overlay" data-shopos-sf-overlay></div>

	<div class="shopos-sf__panel" id="shopos-sf-panel" data-shopos-sf-panel role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( Labels::get( 'panel_aria' ) ); ?>">
		<div class="shopos-sf__panel-head">
			<span class="shopos-sf__panel-title"><?php echo esc_html( Labels::get( 'panel_title' ) ); ?></span>
			<button type="button" class="shopos-sf__close" data-shopos-sf-close aria-label="<?php echo esc_attr( Labels::get( 'close' ) ); ?>">&times;</button>
		</div>

		<?php
		$sf_chips = array();
		foreach ( (array) $facets as $sf_facet ) {
			foreach ( (array) ( $sf_facet['terms'] ?? array() ) as $sf_term ) {
				if ( ! empty( $sf_term['selected'] ) ) {
					$sf_chips[] = array(
						'taxonomy' => (string) $sf_facet['taxonomy'],
						'slug'     => (string) $sf_term['slug'],
						'label'    => (string) $sf_term['label'],
					);
				}
			}
		}
		?>
		<div class="shopos-sf__chips" data-shopos-sf-chips aria-live="polite">
			<?php foreach ( $sf_chips as $sf_chip ) : ?>
				<button
					type="button"
					class="shopos-sf__chip"
					data-shopos-sf-taxonomy="<?php echo esc_attr( $sf_chip['taxonomy'] ); ?>"
					data-shopos-sf-slug="<?php echo esc_attr( $sf_chip['slug'] ); ?>"
				><?php echo esc_html( $sf_chip['label'] ); ?> <span class="shopos-sf__chip-x" aria-hidden="true">&times;</span></button>
			<?php endforeach; ?>
			<?php if ( ! empty( $sf_chips ) ) : ?>
				<button type="button" class="shopos-sf__clear shopos-ui-btn shopos-ui-btn--link" data-shopos-sf-clear><?php echo esc_html( Labels::get( 'clear_all' ) ); ?></button>
			<?php endif; ?>
		</div>

		<p class="shopos-sf__count" data-shopos-sf-count>
			<?php echo esc_html( Labels::count_text( (int) $count ) ); ?>
		</p>

		<div class="shopos-sf__sort">
			<label class="shopos-sf__sort-label" for="shopos-sf-sort"><?php echo esc_html( Labels::get( 'sort' ) ); ?></label>
			<select id="shopos-sf-sort" class="shopos-sf__sort-select shopos-ui-select" data-shopos-sf-sort>
				<?php foreach ( Url_State::orderby_whitelist() as $sf_orderby ) : ?>
					<option value="<?php echo esc_attr( $sf_orderby ); ?>" <?php selected( $sf_current_sort, $sf_orderby ); ?>><?php echo esc_html( Module::orderby_label( $sf_orderby ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php include __DIR__ . '/facet-price.php'; ?>

		<?php include __DIR__ . '/facet-flags.php'; ?>

		<?php include __DIR__ . '/facet-category-tree.php'; ?>

		<form class="shopos-sf__facets" data-shopos-sf-facets>
			<?php
			foreach ( (array) $facets as $facet ) {
				if ( 'color' === ( $facet['type'] ?? '' ) ) {
					include __DIR__ . '/facet-color.php';
				} else {
					include __DIR__ . '/facet-checkbox.php';
				}
			}
			?>
		</form>

		<div class="shopos-sf__actions">
			<button type="button" class="shopos-sf__apply shopos-ui-btn" data-shopos-sf-apply><?php echo esc_html( Labels::get( 'apply' ) ); ?></button>
			<button type="button" class="shopos-sf__clear-mobile shopos-ui-btn shopos-ui-btn--ghost" data-shopos-sf-clear-mobile><?php echo esc_html( Labels::get( 'clear' ) ); ?></button>
		</div>
	</div>
</div>
