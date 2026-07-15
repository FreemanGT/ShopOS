<?php
/**
 * Search module.
 *
 * In-house product search, built to eventually replace the Advanced Woo Search
 * plugin. Wave 1 is the foundation only: a background full-text index
 * (`shopos_search_index`) kept fresh by an event-driven queue + reconcile
 * sweep, plus a "Reindex all" admin tool. No storefront search surface yet —
 * the live dropdown (Wave 2) and results page (Wave 3) shipped in later waves.
 * Always-on since 1.21.0 (Wave 8.4); the search feature flags were removed then.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\Search;

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
		return 'search';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Search', 'shopos-core' );
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'In-house full-text product search. Wave 1 builds and maintains the search index; the storefront search surface arrives in later waves.', 'shopos-core' );
	}

	/**
	 * Settings schema. The live-dropdown knobs (ShopOS → Search), always wired
	 * since the dropdown graduated to always-on in 1.21.0.
	 *
	 * @return array
	 */
	public function settings_schema() {
		$schema = array();

		// Storefront label overrides: one text field per search string. Blank = the
		// English default (Labels::get() falls back), so leaving these empty keeps
		// the current output. Lets the site set Hebrew wording without code
		// (QuickView / ShopFilters Labels precedent).
		$first = true;
		foreach ( Labels::defaults() as $key => $def ) {
			/* translators: %s: the English default wording for this field. */
			$desc = sprintf( __( 'Default: %s', 'shopos-core' ), $def['default'] );
			if ( $first ) {
				$desc = __( 'Search wording — leave a field blank to use its English default.', 'shopos-core' ) . ' ' . $desc;
			}
			$schema[ 'label_' . $key ] = array(
				'label'       => $def['label'],
				'type'        => 'text',
				'default'     => '',
				'description' => $desc,
			);
			$first = false;
		}

		$schema += array(
			'min_chars'      => array(
				'label'       => __( 'Minimum characters', 'shopos-core' ),
				'type'        => 'number',
				'default'     => 2,
				'description' => __( 'How many characters before the dropdown starts searching.', 'shopos-core' ),
			),
			'debounce_ms'    => array(
				'label'       => __( 'Debounce (ms)', 'shopos-core' ),
				'type'        => 'number',
				'default'     => 200,
				'description' => __( 'Idle delay after the last keystroke before a request fires.', 'shopos-core' ),
			),
			'max_results'    => array(
				'label'       => __( 'Max dropdown results', 'shopos-core' ),
				'type'        => 'number',
				'default'     => 8,
				'description' => __( 'How many products the live dropdown shows (clamped 1–20).', 'shopos-core' ),
			),
			'show_image'     => array(
				'label'          => __( 'Show product image', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Display the thumbnail on each dropdown result', 'shopos-core' ),
				'default'        => 'yes',
			),
			'show_price'     => array(
				'label'          => __( 'Show price', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Display the price on each dropdown result', 'shopos-core' ),
				'default'        => 'yes',
			),
			'show_sku'       => array(
				'label'          => __( 'Show SKU', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Display the SKU on each dropdown result', 'shopos-core' ),
				'default'        => 'no',
			),
		);

		return $schema;
	}

	/**
	 * Boot. The module graduated to always-on in 1.21.0 (previously each surface
	 * sat behind its own `search`/* flag): the background indexer (lifecycle hooks
	 * + reconcile sweep + admin "Reindex all" tool), the storefront live dropdown
	 * + `[shopos_search]` shortcode + public endpoint, and the engine-driven
	 * results page all wire whenever the module is enabled. The module-enable
	 * toggle is the single kill-switch; a not-yet-built index degrades safely
	 * (dropdown empty, results fall back to native WP search via has_data()).
	 */
	public function boot() {
		add_filter( 'cron_schedules', array( $this, 'register_cron_schedule' ) );

		$indexer = new Indexer();
		$indexer->register_hooks();
		// Defer scheduling to `init`: Action Scheduler's store isn't ready at
		// plugins_loaded, so as_schedule_recurring_action() there silently no-ops.
		add_action( 'init', array( $indexer, 'ensure_scheduled' ) );

		( new Frontend( $this ) )->register();
		( new Ajax( $this ) )->register();
		( new Results_Query() )->register();

		// Optional Elementor widget (a draggable [shopos_search] box). Gated on
		// the action itself, which only fires when Elementor is active — so the
		// module keeps booting Elementor-free (its shortcode/dropdown/results are
		// independent). No module-level `elementor` dependency: that would stop
		// the whole module booting on an Elementor-less store.
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );

		if ( is_admin() ) {
			( new Admin_Page( $indexer ) )->boot();
		}
	}

	/**
	 * Register the ShopOS Search Elementor widget.
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
				'display'  => __( 'Every 5 minutes (ShopOS Search)', 'shopos-core' ),
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
	 * index table (a pure derived cache with no user value).
	 */
	public function on_uninstall() {
		parent::on_uninstall();
		( new Indexer() )->unschedule();
		Database::drop();
	}
}
