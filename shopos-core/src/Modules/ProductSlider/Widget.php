<?php
/**
 * Elementor widget for the Product Slider.
 *
 * Mirrors the Category Slider's `.cs-*` slider chrome (drag, momentum,
 * arrows, progress bar, RTL, per-breakpoint cards-per-view) but each
 * slider item is a *standard* WooCommerce shop loop entry rendered via
 * `wc_get_template_part( 'content', 'product' )`. The emitted markup —
 * `<li class="product type-product post-X status-publish ..."> ... </li>`
 * with the full `woocommerce_before_shop_loop_item` /
 * `woocommerce_before_shop_loop_item_title` / `woocommerce_shop_loop_item_title`
 * / `woocommerce_after_shop_loop_item_title` / `woocommerce_after_shop_loop_item`
 * hook stack — is identical to a default product grid, so any plugin
 * that targets the WC archive (sale flash customisers, wishlist buttons,
 * quick-view modals, image-swap on hover, ratings overrides, etc.) lights
 * up the slider with no extra wiring.
 *
 * The "Display as" toggle switches the outer container between a
 * draggable `<ul class="cs-track products">` and a static
 * `<ul class="cs-grid products">`; the `cs-card` class is added to each
 * `<li>` via the `post_class` filter so the shared flex-basis /
 * scroll-snap / drag-suppression rules apply unchanged.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductSlider;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Widget.
 */
final class Widget extends Widget_Base {

	/**
	 * Page count of the composed id constraint, when a query_args listener
	 * (the Search engine and/or Shop Filters) supplied the grid's id set this
	 * request. The listeners inject FULL match sets — composition (intersecting
	 * a search with a facet selection) has to happen on whole sets, so the
	 * widget, which owns pagination, slices the final list to the current page
	 * and remembers the real page count here for render()'s paginate_links.
	 * Null when no listener constrained the query (genuine archives read the
	 * main query's max_num_pages instead).
	 *
	 * @var int|null
	 */
	private $constrained_grid_pages = null;

	public function get_name() {
		return 'shopos_product_slider';
	}

	public function get_title() {
		return __( 'ShopOS Product Slider', 'shopos-core' );
	}

	public function get_icon() {
		return 'eicon-woocommerce';
	}

	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	public function get_keywords() {
		return array( 'product', 'products', 'slider', 'woocommerce', 'carousel', 'grid', 'shopos' );
	}

	public function get_style_depends() {
		return array( 'shopos-core-category-slider', 'shopos-core-product-slider' );
	}

	public function get_script_depends() {
		return array( 'shopos-core-category-slider' );
	}

	protected function register_controls() {
		// ── Content ────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'   => __( 'Eyebrow', 'shopos-core' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Featured', 'shopos-core' ),
			)
		);

		$this->add_control(
			'headline',
			array(
				'label'   => __( 'Headline', 'shopos-core' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'New arrivals.', 'shopos-core' ),
			)
		);

		$this->add_control(
			'headline_mute',
			array(
				'label'   => __( 'Headline subtext', 'shopos-core' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Hand-picked for the season.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();

		// ── Query ──────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => __( 'Max products', 'shopos-core' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array( '' => array( 'min' => 1, 'max' => 48, 'step' => 1 ) ),
				'default'     => array( 'unit' => '', 'size' => 12 ),
				'description' => __( 'Ignored when Source is "Current query (archive)" in Grid mode — there the widget shows the full archive page and paginates.', 'shopos-core' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order by', 'shopos-core' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => __( 'Date', 'shopos-core' ),
					'price'      => __( 'Price', 'shopos-core' ),
					'popularity' => __( 'Popularity', 'shopos-core' ),
					'rating'     => __( 'Rating', 'shopos-core' ),
					'menu_order' => __( 'Menu order', 'shopos-core' ),
					'title'      => __( 'Title', 'shopos-core' ),
					'rand'       => __( 'Random', 'shopos-core' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'shopos-core' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'DESC',
				'toggle'  => false,
				'options' => array(
					'ASC'  => array( 'title' => __( 'Ascending', 'shopos-core' ),  'icon' => 'eicon-arrow-up' ),
					'DESC' => array( 'title' => __( 'Descending', 'shopos-core' ), 'icon' => 'eicon-arrow-down' ),
				),
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Source', 'shopos-core' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'           => __( 'All published products', 'shopos-core' ),
					'featured'      => __( 'Featured only', 'shopos-core' ),
					'on_sale'       => __( 'On-sale only', 'shopos-core' ),
					'category'      => __( 'By category', 'shopos-core' ),
					'tag'           => __( 'By tag', 'shopos-core' ),
					'manual'        => __( 'Manual selection', 'shopos-core' ),
					'current_query' => __( 'Current query (archive)', 'shopos-core' ),
					'related'       => __( 'Related products (single)', 'shopos-core' ),
				),
			)
		);

		$category_options = $this->get_term_options( 'product_cat' );
		$this->add_control(
			'categories',
			array(
				'label'       => __( 'Categories', 'shopos-core' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $category_options,
				'default'     => array(),
				'condition'   => array( 'source' => 'category' ),
			)
		);

		$tag_options = $this->get_term_options( 'product_tag' );
		$this->add_control(
			'tags',
			array(
				'label'       => __( 'Tags', 'shopos-core' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $tag_options,
				'default'     => array(),
				'condition'   => array( 'source' => 'tag' ),
			)
		);

		$this->add_control(
			'include_ids',
			array(
				'label'       => __( 'Product IDs', 'shopos-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated product IDs. Order is preserved.', 'shopos-core' ),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'exclude_ids',
			array(
				'label'       => __( 'Exclude product IDs', 'shopos-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated product IDs to exclude.', 'shopos-core' ),
			)
		);

		$this->add_control(
			'hide_free',
			array(
				'label'        => __( 'Hide free products', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hide_out_of_stock',
			array(
				'label'        => __( 'Hide out-of-stock', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'When off, the WooCommerce global "Hide out of stock items from the catalog" setting still applies.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();

		// ── Layout ─────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'per_view',
			array(
				'label'      => __( 'Cards per view', 'shopos-core' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array( '' => array( 'min' => 2, 'max' => 6, 'step' => 1 ) ),
				'default'    => array( 'unit' => '', 'size' => 4 ),
			)
		);

		$this->add_control(
			'per_view_tablet',
			array(
				'label'      => __( 'Cards per view (tablet)', 'shopos-core' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array( '' => array( 'min' => 2, 'max' => 6, 'step' => 1 ) ),
				'default'    => array( 'unit' => '', 'size' => 3 ),
			)
		);

		$this->add_control(
			'per_view_mobile',
			array(
				'label'       => __( 'Cards per view (mobile)', 'shopos-core' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array( '' => array( 'min' => 1, 'max' => 3, 'step' => 0.1 ) ),
				'default'     => array( 'unit' => '', 'size' => 1.4 ),
				'description' => __( 'Fractional values let the next card peek (e.g. 1.4 = one full card with ~30% of the next showing).', 'shopos-core' ),
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'shopos-core' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 48, 'step' => 2 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
			)
		);

		$this->add_control(
			'card_height',
			array(
				'label'       => __( 'Image height', 'shopos-core' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 180, 'max' => 480, 'step' => 10 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 320 ),
				'description' => __( 'Height of the image area. Meta and cart button stack below at natural height.', 'shopos-core' ),
			)
		);

		$this->end_controls_section();

		// ── Card style (Style tab) ────────────────────────────────────────
		$this->start_controls_section(
			'section_card_style',
			array(
				'label' => __( 'Card style', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'shape',
			array(
				'label'   => __( 'Image shape', 'shopos-core' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'soft',
				'toggle'  => false,
				'options' => array(
					'soft' => array( 'title' => __( 'Soft', 'shopos-core' ), 'icon' => 'eicon-frame-expand' ),
					'rect' => array( 'title' => __( 'Rect', 'shopos-core' ), 'icon' => 'eicon-square' ),
				),
			)
		);

		$this->add_control(
			'show_cart',
			array(
				'label'        => __( 'Show add-to-cart button', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_sale_badge',
			array(
				'label'        => __( 'Show sale badge', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( "Toggles WooCommerce's standard sale flash on each card. Plugins that replace the sale flash respect this toggle automatically.", 'shopos-core' ),
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'     => __( 'Accent color', 'shopos-core' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .cs' => '--cs-accent: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'ring_color',
			array(
				'label'       => __( 'Hover ring color', 'shopos-core' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'description' => __( 'Outline drawn around a card on hover.', 'shopos-core' ),
				'selectors'   => array(
					'{{WRAPPER}} .cs' => '--cs-ring-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'typo_heading',
			array(
				'label'     => __( 'Typography', 'shopos-core' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'eyebrow_typography',
				'label'    => __( 'Eyebrow', 'shopos-core' ),
				'selector' => '{{WRAPPER}} .cs-eyebrow',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'headline_typography',
				'label'    => __( 'Headline', 'shopos-core' ),
				'selector' => '{{WRAPPER}} .cs-headline',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'label'    => __( 'Product name', 'shopos-core' ),
				'selector' => '{{WRAPPER}} .cs.cs-products .woocommerce-loop-product__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'label'    => __( 'Price', 'shopos-core' ),
				'selector' => '{{WRAPPER}} .cs.cs-products .cs-card.product .price',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => __( 'Product name color', 'shopos-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cs.cs-products .woocommerce-loop-product__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price color', 'shopos-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cs.cs-products .price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ── Behavior ───────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_behavior',
			array(
				'label' => __( 'Behavior', 'shopos-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'display_mode',
			array(
				'label'       => __( 'Display as', 'shopos-core' ),
				'type'        => Controls_Manager::CHOOSE,
				'default'     => 'slider',
				'toggle'      => false,
				'options'     => array(
					'slider' => array( 'title' => __( 'Slider', 'shopos-core' ), 'icon' => 'eicon-slider-push' ),
					'grid'   => array( 'title' => __( 'Grid', 'shopos-core' ),   'icon' => 'eicon-gallery-grid' ),
				),
				'description' => __( 'Slider = draggable horizontal row with progress bar. Grid = static layout (no drag, no arrows).', 'shopos-core' ),
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'       => __( 'Direction', 'shopos-core' ),
				'type'        => Controls_Manager::CHOOSE,
				'default'     => 'auto',
				'toggle'      => false,
				'options'     => array(
					'auto' => array( 'title' => __( 'Auto (follow site)', 'shopos-core' ), 'icon' => 'eicon-cog' ),
					'ltr'  => array( 'title' => __( 'Force LTR', 'shopos-core' ),         'icon' => 'eicon-arrow-right' ),
					'rtl'  => array( 'title' => __( 'Force RTL', 'shopos-core' ),         'icon' => 'eicon-arrow-left' ),
				),
				'description' => __( 'RTL flips arrows, drag direction, and progress bar.', 'shopos-core' ),
				'condition'   => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'snap',
			array(
				'label'     => __( 'Scroll snap', 'shopos-core' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'none',
				'toggle'    => false,
				'options'   => array(
					'none' => array( 'title' => __( 'Free-scroll', 'shopos-core' ), 'icon' => 'eicon-ellipsis-h' ),
					'card' => array( 'title' => __( 'Per card', 'shopos-core' ),    'icon' => 'eicon-frame-minimize' ),
					'page' => array( 'title' => __( 'Per page', 'shopos-core' ),    'icon' => 'eicon-frame-expand' ),
				),
				'condition' => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'mouse_drag',
			array(
				'label'        => __( 'Enable mouse drag', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( 'Drag cards left or right to scroll the row. Clicks still navigate normally — drag only engages after a deliberate horizontal motion.', 'shopos-core' ),
				'condition'    => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show arrow buttons', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'display_mode' => 'slider' ),
			)
		);

		// Advanced controls — always-on since 1.23.0 (the advanced_controls
		// flag graduated). show_progress stays in place as a back-compat
		// alias — the indicator control supersedes it when set.
		// Advanced controls inherit display_mode=slider so they're hidden
		// in grid mode (autoplay/indicator have no meaning without slider
		// chrome); the render path also gates on $is_slider.
		$this->add_control(
			'indicator',
			array(
				'label'   => __( 'Indicator', 'shopos-core' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'progress',
				'toggle'  => false,
				'options' => array(
					'progress' => array( 'title' => __( 'Progress bar', 'shopos-core' ), 'icon' => 'eicon-slider-push' ),
					'dots'     => array( 'title' => __( 'Pagination dots', 'shopos-core' ), 'icon' => 'eicon-ellipsis-h' ),
					'none'     => array( 'title' => __( 'Hidden', 'shopos-core' ),         'icon' => 'eicon-ban' ),
				),
				'description' => __( 'Replaces the legacy "Show progress bar" toggle. When unset on pre-existing widgets, the legacy value is honored.', 'shopos-core' ),
				'condition'   => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'show_progress',
			array(
				'label'        => __( 'Show progress bar', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
				'condition'    => array( 'display_mode' => 'slider' ),
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'      => __( 'Autoplay delay (ms)', 'shopos-core' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 1000, 'max' => 15000, 'step' => 500 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 5000 ),
				'condition'  => array(
					'display_mode' => 'slider',
					'autoplay'     => 'yes',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Loop', 'shopos-core' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'When the autoplay reaches the end, smoothly wrap back to the start. Drag-past-end-wraps is intentionally out of scope.', 'shopos-core' ),
				'condition'    => array(
					'display_mode' => 'slider',
					'autoplay'     => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build a term-id => name map for SELECT2 controls. Capped at 200 to
	 * keep the editor responsive on stores with very large taxonomies.
	 *
	 * @param string $taxonomy
	 * @return array<int,string>
	 */
	private function get_term_options( $taxonomy ) {
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
	 * Coerce a setting into a list of positive integers.
	 *
	 * @param mixed $raw
	 * @return int[]
	 */
	private function ids_array( $raw ) {
		if ( empty( $raw ) ) {
			return array();
		}
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/[\s,]+/', $raw );
		}
		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}
		$out = array();
		foreach ( $raw as $v ) {
			$id = (int) $v;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Read a SLIDER control as int (handles both array+scalar shapes).
	 *
	 * @param mixed $raw
	 * @param int   $default
	 * @return int
	 */
	private function slider_int( $raw, $default ) {
		if ( is_array( $raw ) && isset( $raw['size'] ) && '' !== $raw['size'] ) {
			return (int) $raw['size'];
		}
		if ( is_scalar( $raw ) && '' !== $raw ) {
			return (int) $raw;
		}
		return $default;
	}

	/**
	 * Float variant for SLIDER controls that allow fractional steps —
	 * notably `per_view_mobile` (1.4 = one card with peek of next).
	 *
	 * @param mixed $raw
	 * @param float $default
	 * @return float
	 */
	private function slider_float( $raw, $default ) {
		if ( is_array( $raw ) && isset( $raw['size'] ) && '' !== $raw['size'] ) {
			return (float) $raw['size'];
		}
		if ( is_scalar( $raw ) && '' !== $raw ) {
			return (float) $raw;
		}
		return $default;
	}

	/**
	 * @return bool
	 */
	private function is_elementor_edit_mode() {
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
	 * @param string $setting
	 * @return string ltr|rtl
	 */
	private function resolve_direction( $setting ) {
		if ( 'rtl' === $setting ) {
			return 'rtl';
		}
		if ( 'ltr' === $setting ) {
			return 'ltr';
		}
		return ( function_exists( 'is_rtl' ) && is_rtl() ) ? 'rtl' : 'ltr';
	}

	/**
	 * Run the product query.
	 *
	 * @param array $s Settings.
	 * @return \WC_Product[]
	 */
	private function fetch_products( $s ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$limit  = max( 1, $this->slider_int( $s['limit'] ?? null, 12 ) );
		$source = $s['source'] ?? 'all';

		// Current archive query — read the *canonical* main query when on a
		// product archive. In grid mode the widget stands in for the archive
		// "Products" grid, so it renders the whole current page (already
		// bounded by WooCommerce's products-per-page) and lets render() emit
		// pagination for the rest of the catalog. In slider mode the row stays
		// bounded by the widget's "Max products" cap — paginating a draggable
		// row is meaningless. We read $wp_the_query (via main_query()), not the
		// global $wp_query, because an Elementor Theme Builder archive template
		// swaps $wp_query for its own loop while the widget renders — which
		// nulls out the is_shop()/is_tax() tags and the posts/max_num_pages we
		// need. $wp_the_query survives that swap.
		if ( 'current_query' === $source ) {
			$is_grid = ( ( $s['display_mode'] ?? 'slider' ) === 'grid' );
			$main    = $this->main_query();
			// A product *search* rendered through an archive template is not a
			// plain archive: the template hands us an unconstrained main query (the
			// search engine's match constraint lives elsewhere and may not reach
			// this query object), so reading its posts here would render the whole
			// catalog. Fall through to the standard wc_get_products() path instead,
			// where the search-results filter (shopos_core/product_slider/query_args)
			// narrows the grid to the current page of matches (and the
			// grid_max_pages filter below supplies the matching page count).
			// Genuine archives (shop / category / tag) still read the main query
			// directly.
			$main_is_search = ( $main instanceof \WP_Query && $main->is_search() );
			if ( self::should_use_archive( $main_is_search, $this->is_product_archive( $main ) ) ) {
				return $this->collect_archive_products( $main, $is_grid, $limit );
			}
			$source = 'all';
		}

		// Related products — only meaningful on a single product page.
		if ( 'related' === $source ) {
			$current_id = (int) get_the_ID();
			if ( is_singular( 'product' ) && $current_id > 0 && function_exists( 'wc_get_related_products' ) ) {
				$related_ids = wc_get_related_products( $current_id, $limit );
				if ( empty( $related_ids ) ) {
					return array();
				}
				$out = array();
				foreach ( $related_ids as $id ) {
					$prod = wc_get_product( $id );
					if ( $prod instanceof \WC_Product && $prod->is_visible() ) {
						$out[] = $prod;
					}
				}
				return $out;
			}
			$source = 'all';
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => $s['orderby'] ?? 'date',
			'order'   => ( ( $s['order'] ?? 'DESC' ) === 'ASC' ) ? 'ASC' : 'DESC',
			'return'  => 'objects',
		);

		// Stock visibility — explicit toggle wins; otherwise respect the
		// WC global option so the slider matches the rest of the catalog.
		if ( ( $s['hide_out_of_stock'] ?? '' ) === 'yes' ) {
			$args['stock_status'] = 'instock';
		} elseif ( function_exists( 'get_option' ) && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['stock_status'] = 'instock';
		}

		// Hide free products.
		if ( ( $s['hide_free'] ?? '' ) === 'yes' ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			);
		}

		if ( 'manual' === $source ) {
			$ids = $this->ids_array( $s['include_ids'] ?? '' );
			if ( empty( $ids ) ) {
				return array();
			}
			$args['include'] = $ids;
			$args['orderby'] = 'post__in';
			$args['limit']   = count( $ids );
		} elseif ( 'featured' === $source ) {
			$args['featured'] = true;
		} elseif ( 'on_sale' === $source ) {
			$args['on_sale'] = true;
		} elseif ( 'category' === $source ) {
			$cat_ids = $this->ids_array( $s['categories'] ?? array() );
			if ( empty( $cat_ids ) ) {
				return array();
			}
			$slugs = array();
			foreach ( $cat_ids as $id ) {
				$term = get_term( $id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$slugs[] = $term->slug;
				}
			}
			if ( empty( $slugs ) ) {
				return array();
			}
			$args['category'] = $slugs;
		} elseif ( 'tag' === $source ) {
			$tag_ids = $this->ids_array( $s['tags'] ?? array() );
			if ( empty( $tag_ids ) ) {
				return array();
			}
			$slugs = array();
			foreach ( $tag_ids as $id ) {
				$term = get_term( $id, 'product_tag' );
				if ( $term && ! is_wp_error( $term ) ) {
					$slugs[] = $term->slug;
				}
			}
			if ( empty( $slugs ) ) {
				return array();
			}
			$args['tag'] = $slugs;
		}

		$exclude = $this->ids_array( $s['exclude_ids'] ?? '' );
		if ( ! empty( $exclude ) ) {
			$args['exclude'] = $exclude;
		}

		$pre_filter_include = ( isset( $args['include'] ) && is_array( $args['include'] ) ) ? $args['include'] : null;

		/**
		 * Filter the `wc_get_products()` args used by the Product Slider widget.
		 *
		 * Only fires on the standard query path. The early-return branches for
		 * `current_query` (read $wp_query directly) and `related` (delegate to
		 * `wc_get_related_products()`) bypass this filter — those code paths
		 * never build `$args`.
		 *
		 * @since 1.11.1
		 *
		 * @param array $args     Args about to be passed to `wc_get_products()`.
		 * @param array $settings Resolved widget settings.
		 */
		$args = (array) apply_filters( 'shopos_core/product_slider/query_args', $args, $s );

		// A listener injected (or reshaped) an id constraint — the Search
		// engine's matches, Shop Filters' in-stock selection, or their
		// intersection. Listeners supply FULL sets: composing a search with a
		// facet selection is only correct on whole sets (pre-1.24.10 the Search
		// listener injected one page slice, so Shop Filters intersected against
		// 10 ids instead of ~300 and a filtered search rendered blank while the
		// facet panel counted the real total). Pagination is this widget's job:
		// slice the composed list to the current page (paginating current-query
		// grids) or the widget's own cap (sliders / fixed-source grids), before
		// wc_get_products() hydrates anything.
		$post_filter_include = ( isset( $args['include'] ) && is_array( $args['include'] ) ) ? $args['include'] : null;
		if ( null !== $post_filter_include && $post_filter_include !== $pre_filter_include ) {
			$paginates = ( ( $s['display_mode'] ?? 'slider' ) === 'grid' ) && 'current_query' === ( $s['source'] ?? '' );
			$paged     = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
			$sliced    = self::slice_constrained_include(
				$post_filter_include,
				$paginates,
				$paged,
				$this->grid_per_page(),
				$limit
			);

			$args['include'] = $sliced['include'];
			$args['limit']   = count( $sliced['include'] );
			if ( null !== $sliced['pages'] ) {
				$this->constrained_grid_pages = $sliced['pages'];
			}
		}

		// Popularity / rating / price: bypass `wc_get_products()`'s INNER JOIN
		// on the sort meta. WC translates `orderby=popularity` → `meta_value_num`
		// on `total_sales`, which drops every product that has never been sold
		// (no postmeta row) and ties the rest at 0 — making the result
		// indistinguishable from `orderby=date`. Same trap for `rating`
		// (`_wc_average_rating`, no reviews) and `price` (`_price`, e.g.
		// "price on request" / call-for-quote products with no price set).
		// See `fetch_products_by_meta_orderby()` for the two-pass workaround.
		if ( in_array( $args['orderby'] ?? '', array( 'popularity', 'rating', 'price' ), true ) ) {
			return $this->fetch_products_by_meta_orderby( $args );
		}

		$products = wc_get_products( $args );
		if ( ! is_array( $products ) ) {
			return array();
		}
		// Drop hidden + non-purchasable-and-not-viewable. wc_get_products
		// already filters by status; this catches catalog_visibility="hidden".
		return array_values(
			array_filter(
				$products,
				static function ( $p ) {
					return $p instanceof \WC_Product && $p->is_visible();
				}
			)
		);
	}

	/**
	 * Slice a listener-composed include list to what this widget renders (pure).
	 *
	 * A paginating grid gets the current page's window plus the real page count
	 * derived from the FULL composed set; anything else (a slider row, a
	 * fixed-source grid with no pagination UI) is capped at the widget's own
	 * product limit with no page count. An empty window — no matches, or a page
	 * past the end — becomes [0], wc_get_products' "no results" (an empty
	 * include list would mean "no constraint" and render the whole catalog).
	 *
	 * @param int[] $include   Composed id constraint, in final render order.
	 * @param bool  $paginates Whether this widget paginates (current-query grid).
	 * @param int   $paged     1-based current page.
	 * @param int   $per_page  Products per grid page.
	 * @param int   $cap       The widget's own product limit (non-paginating).
	 * @return array{include: int[], pages: int|null}
	 */
	public static function slice_constrained_include( array $include, $paginates, $paged, $per_page, $cap ) {
		$ids = array_values( array_filter( array_map( 'intval', $include ) ) );
		if ( empty( $ids ) ) {
			return array(
				'include' => array( 0 ),
				'pages'   => $paginates ? 1 : null,
			);
		}
		if ( ! $paginates ) {
			$slice = array_slice( $ids, 0, max( 1, (int) $cap ) );
			return array(
				'include' => $slice,
				'pages'   => null,
			);
		}
		$paged    = max( 1, (int) $paged );
		$per_page = max( 1, (int) $per_page );
		$slice    = array_slice( $ids, ( $paged - 1 ) * $per_page, $per_page );
		return array(
			'include' => empty( $slice ) ? array( 0 ) : $slice,
			'pages'   => max( 1, (int) ceil( count( $ids ) / $per_page ) ),
		);
	}

	/**
	 * Products per grid page — the WooCommerce shop grid size when available,
	 * else the blog per-page, else 12. The same ladder Shop Filters'
	 * Query_Builder::resolve_per_page() uses (deliberate small duplicate — the
	 * modules stay independent), so the page slice, the pagination links, and
	 * the facet panel's advisory count all agree on page size.
	 *
	 * @return int
	 */
	private function grid_per_page() {
		$per_page = 0;
		if ( function_exists( 'wc_get_default_products_per_page' ) ) {
			$per_page = (int) apply_filters( 'loop_shop_per_page', wc_get_default_products_per_page() );
		}
		if ( $per_page <= 0 ) {
			$per_page = (int) get_option( 'posts_per_page', 12 );
		}
		return $per_page > 0 ? $per_page : 12;
	}

	/**
	 * The canonical main query. WordPress keeps $wp_the_query pointing at the
	 * page's real main query even when a secondary loop (e.g. an Elementor
	 * archive template, or any `query_posts()` caller) reassigns the global
	 * $wp_query. Reading it lets `current_query` grid mode survive that swap;
	 * falls back to $wp_query when $wp_the_query is unavailable.
	 *
	 * @return \WP_Query|null
	 */
	private function main_query() {
		if ( isset( $GLOBALS['wp_the_query'] ) && $GLOBALS['wp_the_query'] instanceof \WP_Query ) {
			return $GLOBALS['wp_the_query'];
		}
		return ( isset( $GLOBALS['wp_query'] ) && $GLOBALS['wp_query'] instanceof \WP_Query ) ? $GLOBALS['wp_query'] : null;
	}

	/**
	 * Whether the given query is a WooCommerce product archive (shop, product
	 * post-type archive, or a product taxonomy). The conditional tags are
	 * tried first for the common case; the query object's own predicates are
	 * the fallback — they hold even when the global $wp_query has been swapped
	 * out from under the tags (the Elementor archive case).
	 *
	 * @param \WP_Query|null $query Query to inspect.
	 * @return bool
	 */
	private function is_product_archive( $query ) {
		if ( is_post_type_archive( 'product' )
			|| ( function_exists( 'is_shop' ) && is_shop() )
			|| is_tax( array( 'product_cat', 'product_tag' ) ) ) {
			return true;
		}
		return $query instanceof \WP_Query
			&& ( $query->is_post_type_archive( 'product' )
				|| $query->is_tax( array( 'product_cat', 'product_tag' ) ) );
	}

	/**
	 * Whether the `current_query` grid should read the archive main query
	 * directly. A genuine product archive (shop / category / tag) → yes; a
	 * product *search* rendered through an archive template → no, because its main
	 * query is not constrained by the search engine, so the grid must instead go
	 * through the standard wc_get_products() path where the search-results filter
	 * narrows it to the matches.
	 *
	 * @param bool $main_is_search     The main query is a search.
	 * @param bool $is_product_archive The main query is a product archive.
	 * @return bool
	 */
	public static function should_use_archive( $main_is_search, $is_product_archive ) {
		return ! $main_is_search && (bool) $is_product_archive;
	}

	/**
	 * The card thumbnail's `sizes` attribute for a column layout (pure). Slots
	 * mirror the stylesheet's breakpoints: ≤640px = mobile columns (a slider's
	 * fractional peek widens the slot), ≤1024px = tablet columns, above = a
	 * fixed pixel estimate from a generous 1400px content width — over-
	 * estimating the slot only rounds UP to the next srcset candidate, so
	 * sharpness is never sacrificed while viewport-width downloads are.
	 *
	 * @param int|float $per_view        Desktop columns.
	 * @param int|float $per_view_tablet Tablet columns.
	 * @param int|float $per_view_mobile Mobile columns (float peek in slider mode).
	 * @return string
	 */
	public static function card_sizes_attr( $per_view, $per_view_tablet, $per_view_mobile ) {
		$desktop = (int) ceil( 1400 / max( 1.0, (float) $per_view ) );
		$tablet  = (int) ceil( 100 / max( 1.0, (float) $per_view_tablet ) );
		$mobile  = (int) ceil( 100 / max( 1.0, (float) $per_view_mobile ) );
		return sprintf( '(max-width: 640px) %dvw, (max-width: 1024px) %dvw, %dpx', $mobile, $tablet, $desktop );
	}

	/**
	 * Collect visible WC_Product objects from a query's posts. Grid mode
	 * returns the full page (render() paginates the rest); slider mode bounds
	 * the row at $limit, since paginating a draggable row is meaningless.
	 *
	 * @param \WP_Query|null $query   Query whose posts to read.
	 * @param bool           $is_grid Whether the widget renders as a grid.
	 * @param int            $limit   Slider-mode product cap.
	 * @return \WC_Product[]
	 */
	private function collect_archive_products( $query, $is_grid, $limit ) {
		$out = array();
		if ( ! $query instanceof \WP_Query || empty( $query->posts ) ) {
			return $out;
		}
		foreach ( $query->posts as $p ) {
			$prod = wc_get_product( $p );
			if ( $prod instanceof \WC_Product && $prod->is_visible() ) {
				$out[] = $prod;
			}
			if ( ! $is_grid && count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Two-pass query for popularity / rating / price orderby — works around
	 * WC's INNER JOIN on the sort meta key, which silently excludes products
	 * with no `total_sales` / `average_rating` / `min_price` row.
	 *
	 * 1. Fetch all eligible product IDs ordered by date (no JOIN on the
	 *    sort meta).
	 * 2. Read the one sort column from `wc_product_meta_lookup` for those IDs
	 *    in a single indexed query (missing rows default to 0 so the product
	 *    still sorts). Replaces the former whole-catalog `update_meta_cache()`
	 *    postmeta prime — audit C1, the biggest per-pageview cost.
	 * 3. Sort in PHP, load top-N product objects, drop hidden ones.
	 *
	 * Defensive cap of 5000 IDs keeps the in-PHP sort + the IN() list bounded —
	 * far above any realistic shop slider input — without unbounded memory on a
	 * runaway catalog. Beyond 5000, only the newest 5000 by date are
	 * considered for the ranking.
	 *
	 * @since 1.11.42
	 * @since 1.21.28 Sort values read from `wc_product_meta_lookup` (min_price /
	 *                average_rating / total_sales) instead of postmeta.
	 *
	 * @param array $args Filtered wc_get_products args; `orderby` is
	 *                    `popularity`, `rating`, or `price` and `order` is
	 *                    ASC|DESC.
	 * @return \WC_Product[]
	 */
	private function fetch_products_by_meta_orderby( $args ) {
		$sort_orderby = (string) ( $args['orderby'] ?? '' );
		$sort_order   = ( 'ASC' === ( $args['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC';
		$limit        = max( 1, (int) ( $args['limit'] ?? 12 ) );
		$column_map = array(
			'popularity' => 'total_sales',
			'rating'     => 'average_rating',
			'price'      => 'min_price',
		);
		$column       = $column_map[ $sort_orderby ] ?? 'total_sales';

		$id_args            = $args;
		$id_args['orderby'] = 'date';
		$id_args['order']   = 'DESC';
		$id_args['limit']   = -1;
		$id_args['return']  = 'ids';
		unset( $id_args['paginate'] );
		$ids = wc_get_products( $id_args );
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return array();
		}
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		if ( count( $ids ) > 5000 ) {
			$ids = array_slice( $ids, 0, 5000 );
		}

		$values = $this->sort_values( $ids, $column );

		if ( 'ASC' === $sort_order ) {
			asort( $values, SORT_NUMERIC );
		} else {
			arsort( $values, SORT_NUMERIC );
		}

		// Overshoot to absorb visibility-filter drops without a second pass.
		$top_ids  = array_slice( array_keys( $values ), 0, $limit * 2 );
		$products = array();
		foreach ( $top_ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p instanceof \WC_Product && $p->is_visible() ) {
				$products[] = $p;
				if ( count( $products ) >= $limit ) {
					break;
				}
			}
		}
		return $products;
	}

	/**
	 * Read one `wc_product_meta_lookup` column for a set of products in a single
	 * indexed query, returned as an `[ id => float ]` map. Replaces the former
	 * whole-catalog `update_meta_cache()` postmeta prime (audit C1): the lookup
	 * table already carries `total_sales` / `average_rating` / `min_price`,
	 * densely populated for every product. IDs with no lookup row (or a NULL
	 * value) default to 0.0 so they still rank rather than dropping out.
	 *
	 * @since 1.21.28
	 *
	 * @param int[]  $ids    Candidate product ids (already unique + capped).
	 * @param string $column Whitelisted lookup column: total_sales | average_rating | min_price.
	 * @return array<int,float> id => sort value.
	 */
	private function sort_values( array $ids, $column ) {
		// $column is interpolated into SQL (column names can't be bound
		// placeholders), so it MUST come from this allowlist.
		$allowed = array( 'total_sales', 'average_rating', 'min_price' );
		if ( ! in_array( $column, $allowed, true ) ) {
			$column = 'total_sales';
		}
		// Seed every candidate at 0.0 so an id missing from the lookup still
		// ranks (preserving the no-drop guarantee), in date order.
		$values = array_fill_keys( $ids, 0.0 );
		if ( empty( $ids ) ) {
			return $values;
		}

		global $wpdb;
		$lookup       = $wpdb->prefix . 'wc_product_meta_lookup';
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, {$column} AS sort_value FROM {$lookup} WHERE product_id IN ({$placeholders})",
				$ids
			)
		);
		// phpcs:enable

		foreach ( (array) $rows as $row ) {
			$id = (int) $row->product_id;
			if ( isset( $values[ $id ] ) ) {
				$values[ $id ] = ( null === $row->sort_value ) ? 0.0 : (float) $row->sort_value;
			}
		}
		return $values;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$products = $this->fetch_products( $s );
		if ( empty( $products ) ) {
			if ( $this->is_elementor_edit_mode() ) {
				echo '<div class="cs-empty">' . esc_html__( 'No products match the current Query settings.', 'shopos-core' ) . '</div>';
			}
			return;
		}

		$display_mode    = ( ( $s['display_mode'] ?? 'slider' ) === 'grid' ) ? 'grid' : 'slider';
		$is_slider       = ( 'slider' === $display_mode );
		$per_view        = max( 2, $this->slider_int( $s['per_view'] ?? null, 4 ) );
		$per_view_tablet = max( 2, $this->slider_int( $s['per_view_tablet'] ?? null, 3 ) );
		$per_view_mobile = max( 1.0, $this->slider_float( $s['per_view_mobile'] ?? null, 1.4 ) );
		// Grid mode: `--cs-per-mobile` feeds `repeat()`, which requires an
		// integer track count — a fractional "peek" value (a slider-only
		// concept) is invalid CSS there and collapses the mobile grid to a
		// single column. Round to the nearest whole column; slider mode
		// keeps the float for the peek effect.
		if ( 'grid' === $display_mode ) {
			$per_view_mobile = max( 1.0, round( $per_view_mobile ) );
		}
		$gap             = max( 0, $this->slider_int( $s['gap'] ?? null, 20 ) );
		$card_height     = max( 100, $this->slider_int( $s['card_height'] ?? null, 320 ) );
		$shape           = in_array( $s['shape'] ?? 'soft', array( 'soft', 'rect' ), true ) ? $s['shape'] : 'soft';
		$show_cart       = ( $s['show_cart'] ?? 'yes' ) === 'yes';
		$show_sale_badge = ( $s['show_sale_badge'] ?? 'yes' ) === 'yes';

		// Slider-only knobs.
		$snap          = in_array( $s['snap'] ?? 'none', array( 'none', 'card', 'page' ), true ) ? $s['snap'] : 'none';
		$show_arrows   = $is_slider && ( $s['show_arrows'] ?? 'yes' ) === 'yes';
		$show_progress = $is_slider && ( $s['show_progress'] ?? 'yes' ) === 'yes';
		$mouse_drag    = $is_slider && ( $s['mouse_drag'] ?? 'yes' ) === 'yes';
		$dir           = $this->resolve_direction( $s['direction'] ?? 'auto' );

		// Advanced controls (autoplay / loop / indicator) — always-on since
		// 1.23.0 (the advanced_controls flag graduated), still gated on
		// $is_slider so grid mode emits no advanced data attrs and falls
		// through to the legacy show_progress-driven indicator state.
		$advanced_enabled = $is_slider;
		if ( $advanced_enabled ) {
			$raw_indicator = $s['indicator'] ?? null;
			if ( in_array( $raw_indicator, array( 'progress', 'dots', 'none' ), true ) ) {
				$indicator = $raw_indicator;
			} else {
				// Back-compat shim: pre-existing widgets don't have
				// `indicator` saved — honor whatever `show_progress` said.
				$indicator = $show_progress ? 'progress' : 'none';
			}
			$autoplay       = ( $s['autoplay'] ?? '' ) === 'yes';
			$autoplay_delay = max( 1000, min( 15000, $this->slider_int( $s['autoplay_delay'] ?? null, 5000 ) ) );
			$loop           = $autoplay && ( $s['loop'] ?? '' ) === 'yes';
		} else {
			$indicator      = $show_progress ? 'progress' : 'none';
			$autoplay       = false;
			$autoplay_delay = 0;
			$loop           = false;
		}

		// `per_view_mobile` is a float (1.0–3.0) so the next card can peek;
		// the others are integer cards-per-view.
		$style_vars = sprintf(
			'--cs-per: %d; --cs-per-tablet: %d; --cs-per-mobile: %s; --cs-gap: %dpx; --cs-card-h: %dpx;',
			$per_view,
			$per_view_tablet,
			rtrim( rtrim( number_format( $per_view_mobile, 2, '.', '' ), '0' ), '.' ),
			$gap,
			$card_height
		);

		// `woocommerce` is the ancestor class many third-party plugins
		// (WPC Quick View, YITH Wishlist, etc.) and theme overrides use to
		// scope selectors like `.woocommerce ul.products li.product .X`.
		// Elementor's default WC Products widget wraps its UL in a
		// `.woocommerce` div for the same reason — adding it here is what
		// puts the slider's cards in the same selector context as the
		// regular product grid, so user-written CSS that targets the
		// regular grid applies to the slider verbatim.
		$root_classes = 'cs cs-products woocommerce cs-shape-' . $shape;
		if ( ! $is_slider ) {
			$root_classes .= ' cs-grid-mode';
		}

		$total = count( $products );

		// Per-widget hook suppression. We restore each callback after the
		// loop so other product loops / widgets on the same page render
		// unchanged. Plugins that hook into the same actions stay wired
		// regardless of these toggles.
		if ( ! $show_sale_badge ) {
			remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		}
		if ( ! $show_cart ) {
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		}

		// Inject `cs-card` onto each product `<li>` so the shared `.cs-*`
		// flex-basis, scroll-snap, and drag-suppression rules from the
		// CategorySlider stylesheet apply to slider items unchanged. The
		// rest of the `<li>` body comes verbatim from
		// `wc_get_template_part( 'content', 'product' )` so every plugin
		// that styles the standard shop loop also lights up the slider.
		$cs_card_filter = static function ( $classes ) {
			$classes[] = 'cs-card';
			return $classes;
		};
		add_filter( 'post_class', $cs_card_filter );

		// Request the larger `large` image (up to 1024px) for the loop
		// thumbnail instead of `woocommerce_thumbnail` (~324px). Grid cards
		// render wider than 324px on hi-DPI screens — even the 600px
		// `woocommerce_single` upscaled on a full-width archive — so the
		// small source looked blurry. `large` gives headroom up to ~512px
		// card width at 2x DPR and is capped at the original. Scoped to this
		// render and removed after the loop so other product loops stay
		// unchanged.
		$cs_thumb_size_filter = static function () {
			/** Filter the image size used for grid/archive product cards. @since 1.21.40 */
			return apply_filters( 'shopos_core/product_slider/archive_thumbnail_size', 'large' );
		};
		add_filter( 'single_product_archive_thumbnail_size', $cs_thumb_size_filter );

		// WordPress's default sizes attr for `large` claims the image is
		// viewport-wide ("(max-width: 1024px) 100vw, 1024px"), so every card
		// downloaded the biggest srcset candidate — ~5-10x the bytes the
		// rendered slot needs, across a whole grid per pageview. Describe the
		// real card slot (the same column math as the CSS breakpoints) and let
		// srcset pick the right candidate; hi-DPI screens still resolve a
		// large-enough source through the device-pixel-ratio multiplier.
		$cs_sizes        = self::card_sizes_attr( $per_view, $per_view_tablet, $per_view_mobile );
		$cs_sizes_filter = static function () use ( $cs_sizes ) {
			return $cs_sizes;
		};
		add_filter( 'wp_calculate_image_sizes', $cs_sizes_filter );

		$track_classes = ( $is_slider ? 'cs-track' : 'cs-grid' ) . ' products columns-' . (int) $per_view;
		?>
		<div
			class="<?php echo esc_attr( $root_classes ); ?>"
			dir="<?php echo esc_attr( $dir ); ?>"
			<?php if ( $is_slider ) : ?>
				data-cs-snap="<?php echo esc_attr( $snap ); ?>"
				data-cs-mouse-drag="<?php echo $mouse_drag ? '1' : '0'; ?>"
				data-cs-clamp-children="1"
			<?php endif; ?>
			<?php if ( $advanced_enabled ) : ?>
				data-cs-indicator="<?php echo esc_attr( $indicator ); ?>"
				<?php if ( $autoplay ) : ?>
					data-cs-autoplay="1"
					data-cs-autoplay-delay="<?php echo (int) $autoplay_delay; ?>"
				<?php endif; ?>
				<?php if ( $loop ) : ?>
					data-cs-loop="1"
				<?php endif; ?>
			<?php endif; ?>
			style="<?php echo esc_attr( $style_vars ); ?>"
		>
			<?php if ( ! empty( $s['eyebrow'] ) || ! empty( $s['headline'] ) || ! empty( $s['headline_mute'] ) || $show_arrows ) : ?>
			<div class="cs-head">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
					<div class="cs-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $s['headline'] ) || ! empty( $s['headline_mute'] ) ) : ?>
					<div class="cs-headline">
						<?php echo esc_html( $s['headline'] ?? '' ); ?>
						<?php if ( ! empty( $s['headline_mute'] ) ) : ?>
							<span class="cs-headline-mute"> <?php echo esc_html( $s['headline_mute'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $show_arrows ) : ?>
					<div class="cs-arrows" role="group" aria-label="<?php esc_attr_e( 'Scroll products', 'shopos-core' ); ?>">
						<button type="button" class="cs-arrow" data-cs-dir="-1" aria-label="<?php esc_attr_e( 'Previous', 'shopos-core' ); ?>">
							<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M9 3L5 7L9 11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<button type="button" class="cs-arrow" data-cs-dir="1" aria-label="<?php esc_attr_e( 'Next', 'shopos-core' ); ?>">
							<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M5 3L9 7L5 11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<ul class="<?php echo esc_attr( $track_classes ); ?>"<?php echo $is_slider ? ' data-cs-track' : ''; ?>>
				<?php
				global $post, $product;
				$original_post    = $post;
				$original_product = $product;

				foreach ( $products as $product_obj ) {
					$post_object = get_post( $product_obj->get_id() );
					if ( ! $post_object ) {
						continue;
					}
					$post    = $post_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					$product = $product_obj; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );
					wc_get_template_part( 'content', 'product' );
				}

				$post    = $original_post;    // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				$product = $original_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				wp_reset_postdata();
				?>
			</ul>

			<?php
			// Archive pagination — only in grid mode bound to the current
			// query, where the widget stands in for the archive products
			// grid. Built straight from the canonical main query's
			// max_num_pages via paginate_links() so it works even on an
			// Elementor archive template that swaps $wp_query and never ran
			// wc_setup_loop() (which woocommerce_pagination() depends on).
			// main_query() reads $wp_the_query to survive that swap.
			if ( ! $is_slider && 'current_query' === ( $s['source'] ?? '' ) && function_exists( 'paginate_links' ) ) {
				if ( null !== $this->constrained_grid_pages ) {
					// A query_args listener composed this grid's id set (search
					// engine matches ∩ facet selection) — the page count derived
					// from that composed set in fetch_products() is the only
					// correct one. The main query object visible here may be a
					// different, unconstrained instance on an Elementor archive
					// template (the 1.21.5 diagnostic), and the grid_max_pages
					// filter's engine total ignores the facet intersection.
					$max_pages = $this->constrained_grid_pages;
				} else {
					$main      = $this->main_query();
					$max_pages = ( $main instanceof \WP_Query && ! empty( $main->max_num_pages ) ) ? (int) $main->max_num_pages : 1;

					/**
					 * Page count for a current-query grid whose id set no
					 * listener composed (since 1.24.10 a constrained grid's
					 * count is derived from the composed set above). Fallback
					 * seam for feeds the widget can't derive a count from.
					 *
					 * @param int   $max_pages Page count from the main query.
					 * @param array $s         Widget settings.
					 */
					$max_pages = max( 1, (int) apply_filters( 'shopos_core/product_slider/grid_max_pages', $max_pages, $s ) );
				}
				if ( $max_pages > 1 ) {
					$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
					$links = paginate_links(
						array(
							'total'     => $max_pages,
							'current'   => $paged,
							'type'      => 'list',
							'prev_text' => '&larr;',
							'next_text' => '&rarr;',
						)
					);
					if ( $links ) {
						echo '<nav class="woocommerce-pagination cs-pagination">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe core markup.
					}
				}
			}
			?>

			<?php if ( 'progress' === $indicator ) : ?>
				<div class="cs-foot">
					<div class="cs-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<div class="cs-progress-bar" data-cs-progress-bar></div>
					</div>
					<div class="cs-foot-label" data-cs-foot-label>
						<span data-cs-foot-current><?php echo esc_html( str_pad( (string) min( $total, $per_view ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="cs-foot-sep"> / <?php echo esc_html( str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ); ?></span>
					</div>
				</div>
			<?php elseif ( 'dots' === $indicator ) : ?>
				<?php $page_count = max( 1, (int) ceil( $total / max( 1, $per_view ) ) ); ?>
				<div class="cs-foot cs-foot-dots">
					<div class="cs-dots" data-cs-dots role="tablist" aria-label="<?php esc_attr_e( 'Slide navigation', 'shopos-core' ); ?>">
						<?php for ( $i = 0; $i < $page_count; $i++ ) : ?>
							<button type="button" class="cs-dot<?php echo 0 === $i ? ' cs-dot-active' : ''; ?>" data-cs-dot="<?php echo (int) $i; ?>" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d page index, 1-based */ __( 'Page %d', 'shopos-core' ), $i + 1 ) ); ?>"></button>
						<?php endfor; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php

		remove_filter( 'post_class', $cs_card_filter );
		remove_filter( 'single_product_archive_thumbnail_size', $cs_thumb_size_filter );
		remove_filter( 'wp_calculate_image_sizes', $cs_sizes_filter );

		if ( ! $show_sale_badge ) {
			add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		}
		if ( ! $show_cart ) {
			add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		}
	}
}
