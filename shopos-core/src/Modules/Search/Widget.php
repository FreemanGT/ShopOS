<?php
/**
 * Elementor widget for ShopOS Search.
 *
 * A thin control shell over the module's already-shipped, tested
 * {@see Frontend::render_form()} — the same standalone product-search box the
 * `[shopos_search]` shortcode prints. Dragging this widget onto a page is
 * equivalent to placing the shortcode; the globally head-enqueued search
 * assets (search.css/search.js, enqueued on every front-end page by
 * {@see Frontend::enqueue()}) enhance it into the live dropdown, so the widget
 * declares no style/script dependencies of its own.
 *
 * `get_name()` is frozen at `shopos_search` — never rename it (a saved page
 * references the widget by this id).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use ShopOS\Core\Core\Elementor\Widget_Base;

/**
 * Widget.
 */
final class Widget extends Widget_Base {

	/**
	 * Frozen widget id — referenced by saved Elementor documents.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'shopos_search';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'ShopOS Search', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'search', 'product', 'find', 'shopos' );
	}

	/**
	 * Two optional text overrides. Both default to blank so the field falls
	 * through to the Search module's Labels default (the same fallback the
	 * shortcode uses); the resolved default shows as the input placeholder.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'       => __( 'Placeholder', 'shopos-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => Labels::get( 'placeholder' ),
				'description' => __( 'Leave blank to use the Search module default.', 'shopos-core' ),
			)
		);

		$this->add_control(
			'button',
			array(
				'label'       => __( 'Button text', 'shopos-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => Labels::get( 'button' ),
				'description' => __( 'Leave blank to use the Search module default.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Map saved widget settings to render_form() atts. Only non-empty overrides
	 * are passed, so a blank control falls through to the Labels default — the
	 * exact `shortcode_atts` fallback the `[shopos_search]` shortcode relies on.
	 * Pure — unit-tested.
	 *
	 * @param array<string,mixed> $s Settings from get_settings_for_display().
	 * @return array<string,string>
	 */
	public static function atts_from_settings( $s ) {
		$atts = array();
		foreach ( array( 'placeholder', 'button' ) as $key ) {
			if ( isset( $s[ $key ] ) && '' !== trim( (string) $s[ $key ] ) ) {
				$atts[ $key ] = (string) $s[ $key ];
			}
		}
		return $atts;
	}

	/**
	 * Delegate to the shortcode render. render_form() returns markup with every
	 * value already escaped and uses no module state, so a throwaway Module (the
	 * SearchFrontendTest idiom) reaches it; Elementor reconstructs widget
	 * instances without our constructor args at render time, so the owning
	 * module can't be injected.
	 */
	protected function render() {
		$atts = self::atts_from_settings( $this->get_settings_for_display() );
		echo ( new Frontend( new Module() ) )->render_form( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_form() returns fully-escaped markup.
	}
}
