<?php
/**
 * Shared base for ShopOS Elementor widgets.
 *
 * Hosts the setting-coercion + environment helpers and the default widget
 * category that the ShopOS slider widgets (Category Slider, Product Slider)
 * previously each declared inline. New widgets should extend this instead of
 * `\Elementor\Widget_Base` so they inherit the shared helpers rather than
 * re-forking them.
 *
 * Behaviour is identical to the per-widget copies this replaces: the extracted
 * helpers were byte-identical across the two widgets. `get_term_options()` is
 * parameterised with a `product_cat` default so the Category Slider's no-arg
 * call is unchanged and the Product Slider's `product_cat` / `product_tag`
 * calls keep working. `ids_array()` is intentionally NOT hosted here — the two
 * widgets' copies differ (the Product Slider additionally splits comma/space
 * separated strings), so each keeps its own to preserve exact behaviour.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base every ShopOS Elementor widget extends.
 */
abstract class Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Default Elementor panel categories. ShopOS widgets surface under the
	 * dedicated ShopOS category first (see {@see Category}), then the
	 * WooCommerce + General panels — the latter kept so existing widget
	 * placements never vanish.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( Category::SLUG, 'woocommerce-elements', 'general' );
	}

	/**
	 * Build a term-id => name map for SELECT2 controls. Capped at 200 to keep
	 * the editor responsive on stores with very large taxonomies.
	 *
	 * @param string $taxonomy Taxonomy to enumerate. Defaults to product_cat.
	 * @return array<int,string>
	 */
	protected function get_term_options( $taxonomy = 'product_cat' ) {
		if ( ! function_exists( 'taxonomy_exists' ) || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		$opts = array();
		foreach ( $terms as $t ) {
			$opts[ (int) $t->term_id ] = $t->name;
		}
		return $opts;
	}

	/**
	 * Read a SLIDER control as int. Elementor stores sliders as
	 * ['size' => N, 'unit' => '...']; older saved values may still be plain
	 * scalars, so both shapes are handled.
	 *
	 * @param mixed $raw
	 * @param int   $default
	 * @return int
	 */
	protected function slider_int( $raw, $default ) {
		if ( is_array( $raw ) && isset( $raw['size'] ) && '' !== $raw['size'] ) {
			return (int) $raw['size'];
		}
		if ( is_scalar( $raw ) && '' !== $raw ) {
			return (int) $raw;
		}
		return $default;
	}

	/**
	 * Float variant for SLIDER controls that allow fractional steps — notably
	 * `per_view_mobile` (1.4 = one card with a peek of the next).
	 *
	 * @param mixed $raw
	 * @param float $default
	 * @return float
	 */
	protected function slider_float( $raw, $default ) {
		if ( is_array( $raw ) && isset( $raw['size'] ) && '' !== $raw['size'] ) {
			return (float) $raw['size'];
		}
		if ( is_scalar( $raw ) && '' !== $raw ) {
			return (float) $raw;
		}
		return $default;
	}

	/**
	 * Are we rendering inside the Elementor editor preview?
	 *
	 * @return bool
	 */
	protected function is_elementor_edit_mode() {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::$instance;
		if ( ! $plugin || empty( $plugin->editor ) ) {
			return false;
		}
		return (bool) $plugin->editor->is_edit_mode();
	}

	/**
	 * Resolve the Direction control to the actual direction string.
	 *
	 * @param string $setting auto|ltr|rtl
	 * @return string ltr|rtl
	 */
	protected function resolve_direction( $setting ) {
		if ( 'rtl' === $setting ) {
			return 'rtl';
		}
		if ( 'ltr' === $setting ) {
			return 'ltr';
		}
		return ( function_exists( 'is_rtl' ) && is_rtl() ) ? 'rtl' : 'ltr';
	}
}
