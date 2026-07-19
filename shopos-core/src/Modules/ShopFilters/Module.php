<?php
/**
 * Shop Filters module.
 *
 * Faceted AJAX product filters for shop / category pages, built on a background
 * index. Covers the background index + "Reindex now" tool, the storefront read
 * path (the [shopos_shop_filters] shortcode + public query endpoint), the
 * filtered-URL SEO policy, and the admin facet-configuration matrix. Graduated
 * to always-on in 1.12.25 (previously gated by per-surface feature flags).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ShopFilters;

use ShopOS\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'shop_filters';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Shop Filters', 'shopos-core' );
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Faceted, context-aware product filters for shop and category pages, backed by a lightweight background index.', 'shopos-core' );
	}

	/**
	 * Settings schema. Exposes the storefront label overrides, price bands and
	 * default sort on the ShopOS → Shop Filters page.
	 *
	 * @return array
	 */
	public function settings_schema() {
		// Storefront label overrides: one text field per panel string, built by
		// the shared helper (byte-identical to the loop this replaced). Blank =
		// the English default (Labels::get() falls back), so leaving these empty
		// keeps the current output. Lets the site set Hebrew wording without code.
		$schema = $this->label_fields(
			Labels::defaults(),
			__( 'Filter panel wording — leave a field blank to use its English default.', 'shopos-core' )
		);

		$schema['price_bands'] = array(
			'label'       => __( 'Price bands', 'shopos-core' ),
			'type'        => 'text',
			'default'     => '',
			'description' => __( 'Comma-separated upper price points for the price filter, e.g. 50, 100, 200, 500 (gives 0–50, 50–100, 100–200, 200–500, 500+). Leave blank to auto-derive bands from your catalogue prices.', 'shopos-core' ),
		);

		$choices = array( '' => __( '— WooCommerce default —', 'shopos-core' ) );
		foreach ( Url_State::orderby_whitelist() as $orderby ) {
			$choices[ $orderby ] = self::orderby_label( $orderby );
		}
		$schema['default_sort'] = array(
			'label'       => __( 'Default sort', 'shopos-core' ),
			'type'        => 'select',
			'choices'     => $choices,
			'default'     => '',
			'description' => __( 'Default product ordering for shop and category pages (until the shopper picks a sort). Blank keeps the WooCommerce default.', 'shopos-core' ),
		);

		// Sort-option label overrides — one text field per sort key. Blank keeps
		// the English default (mirrors the WooCommerce catalog-ordering names).
		// These are the only visible panel strings not already owner-editable.
		$first = true;
		foreach ( Url_State::orderby_whitelist() as $orderby ) {
			/* translators: %s: the English default wording for this sort option. */
			$desc = sprintf( __( 'Default: %s', 'shopos-core' ), self::default_orderby_label( $orderby ) );
			if ( $first ) {
				$desc = __( 'Sort-menu wording — leave a field blank to use its English default.', 'shopos-core' ) . ' ' . $desc;
				$first = false;
			}
			$schema[ 'label_sort_' . $orderby ] = array(
				'label'       => self::default_orderby_label( $orderby ),
				'type'        => 'text',
				'default'     => '',
				'description' => $desc,
			);
		}

		$schema['filter_style'] = array(
			'label'       => __( 'Filter panel style', 'shopos-core' ),
			'type'        => 'select',
			'choices'     => array(
				'classic' => __( 'Classic — checkbox lists (current)', 'shopos-core' ),
				'refined' => __( 'Refined — size pills, collapsible facets, compact', 'shopos-core' ),
			),
			'default'     => 'classic',
			'description' => __( 'Visual style of the storefront filter panel. Classic keeps the current checkbox layout. Refined renders attribute values as pill buttons, collapses long facet lists with a “show more”, uses circular remove buttons, and contains the panel height with its own scroll.', 'shopos-core' ),
		);

		return $schema;
	}

	/**
	 * Human label for a sort option (mirrors WooCommerce's catalog-ordering names).
	 *
	 * @param string $orderby Orderby key from Url_State::orderby_whitelist().
	 * @return string
	 */
	public static function orderby_label( $orderby ) {
		// Owner override (ShopOS → Shop Filters → sort-label fields). Blank = default.
		$override = get_option( 'shopos_core_shop_filters_label_sort_' . $orderby, '' );
		if ( is_string( $override ) && '' !== trim( $override ) ) {
			return $override;
		}
		return self::default_orderby_label( $orderby );
	}

	/**
	 * The built-in English label for a sort option (mirrors WooCommerce's
	 * catalog-ordering names), before any owner override.
	 *
	 * @param string $orderby Orderby key.
	 * @return string
	 */
	public static function default_orderby_label( $orderby ) {
		$labels = array(
			'menu_order' => __( 'Default sorting', 'shopos-core' ),
			'popularity' => __( 'Popularity', 'shopos-core' ),
			'rating'     => __( 'Average rating', 'shopos-core' ),
			'date'       => __( 'Latest', 'shopos-core' ),
			'price'      => __( 'Price: low to high', 'shopos-core' ),
			'price-desc' => __( 'Price: high to low', 'shopos-core' ),
		);
		return isset( $labels[ $orderby ] ) ? $labels[ $orderby ] : (string) $orderby;
	}

	/**
	 * Boot. Registers the background indexer (lifecycle hooks + reconcile sweep),
	 * the storefront read path (shortcode + public query endpoint), the
	 * filtered-URL SEO policy, and — in wp-admin — the index status / reindex
	 * tool and the facet-configuration matrix.
	 */
	public function boot() {
		add_filter( 'cron_schedules', array( $this, 'register_cron_schedule' ) );

		$indexer = new Indexer();
		$indexer->register_hooks();
		// Defer scheduling to `init`: Action Scheduler's store isn't ready at
		// plugins_loaded, so as_schedule_recurring_action() there silently
		// no-ops. Running at `init` (after AS initialises) makes both the
		// schedule call and the status check use the same, ready scheduler.
		add_action( 'init', array( $indexer, 'ensure_scheduled' ) );

		( new Query() )->register();
		( new Shortcode() )->register();
		( new Ajax() )->register();

		// Filtered-URL SEO policy acts on filter params in the URL whether or not
		// the storefront panel is rendered.
		( new Seo() )->register();

		// Optional Elementor widget (a draggable [shopos_shop_filters] panel).
		// Gated on the action itself, which only fires when Elementor is active —
		// so the module keeps booting Elementor-free (its shortcode / index / SEO
		// policy are independent). No module-level `elementor` dependency: that
		// would stop the whole module booting on an Elementor-less store.
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );

		if ( is_admin() ) {
			( new Admin_Page( $indexer ) )->boot();
			( new Admin_Config_Page() )->boot();
		}
	}

	/**
	 * Register the Shop Filters Elementor widget. Fired on
	 * `elementor/widgets/register` (Elementor-active only).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widget( $widgets_manager ) {
		$widgets_manager->register( new Widget( array(), array( 'shopos_module' => $this ) ) );
	}

	/**
	 * Register the 5-minute recurrence the wp-cron fallback path uses. (When
	 * Action Scheduler is available the indexer uses that instead and this is
	 * unused but harmless.)
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public function register_cron_schedule( $schedules ) {
		if ( ! is_array( $schedules ) ) {
			$schedules = array();
		}
		if ( ! isset( $schedules[ Indexer::CRON_SCHEDULE ] ) ) {
			$schedules[ Indexer::CRON_SCHEDULE ] = array(
				'interval' => Indexer::SWEEP_INTERVAL,
				'display'  => __( 'Every 5 minutes (ShopOS Shop Filters)', 'shopos-core' ),
			);
		}
		return $schedules;
	}

	/**
	 * On deactivation — clear the indexer's scheduled events and queue.
	 */
	public function on_deactivate() {
		( new Indexer() )->unschedule();
	}

	/**
	 * On uninstall — delete the module's options, clear scheduling, and drop the
	 * index table (the data is a pure derived cache with no user value).
	 */
	public function on_uninstall() {
		parent::on_uninstall();
		( new Indexer() )->unschedule();
		Database::drop();
	}
}
