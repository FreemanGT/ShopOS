<?php
/**
 * Search module.
 *
 * In-house product search, built to eventually replace the Advanced Woo Search
 * plugin. Wave 1 is the foundation only: a background full-text index
 * (`freeman_search_index`) kept fresh by an event-driven queue + reconcile
 * sweep, plus a "Reindex all" admin tool. No storefront search surface yet —
 * the live dropdown (Wave 2) and results page (Wave 3) come later. Gated by the
 * `search`/`indexer` feature flag; off = zero hooks, no index maintenance.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

use Freeman\Core\Core\Module_Base;

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
		return __( 'Search', 'freeman-core' );
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'In-house full-text product search. Wave 1 builds and maintains the search index; the storefront search surface arrives in later waves.', 'freeman-core' );
	}

	/**
	 * Settings schema. The live-dropdown knobs (Freeman → Search), used only when
	 * the dropdown feature flag is on.
	 *
	 * @return array
	 */
	public function settings_schema() {
		return array(
			'min_chars'      => array(
				'label'       => __( 'Minimum characters', 'freeman-core' ),
				'type'        => 'number',
				'default'     => 2,
				'description' => __( 'How many characters before the dropdown starts searching.', 'freeman-core' ),
			),
			'debounce_ms'    => array(
				'label'       => __( 'Debounce (ms)', 'freeman-core' ),
				'type'        => 'number',
				'default'     => 200,
				'description' => __( 'Idle delay after the last keystroke before a request fires.', 'freeman-core' ),
			),
		);
	}

	/**
	 * Boot. The module graduated to always-on in 1.21.0 (previously each surface
	 * sat behind its own `search`/* flag): the background indexer (lifecycle hooks
	 * + reconcile sweep + admin "Reindex all" tool), the storefront live dropdown
	 * + `[freeman_search]` shortcode + public endpoint, and the engine-driven
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
		( new Ajax() )->register();
		( new Results_Query() )->register();

		if ( is_admin() ) {
			( new Admin_Page( $indexer ) )->boot();
		}
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
				'display'  => __( 'Every 5 minutes (Freeman Search)', 'freeman-core' ),
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
