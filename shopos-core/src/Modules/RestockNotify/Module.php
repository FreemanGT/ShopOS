<?php
/**
 * Restock Notify module.
 *
 * Hebrew-first back-in-stock notification system. Customers register for
 * out-of-stock products and get emailed as soon as stock returns. Owns a
 * custom DB table (`{prefix}shopos_restock_subscribers`).
 *
 * Ported from restock-notify v1.2.0. Legacy class bodies are bundled under
 * `legacy/includes/` to preserve behaviour; this Module wires them into the
 * ShopOS lifecycle and reuses the same table name so data is preserved when
 * the legacy plugin is deactivated.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\RestockNotify;

use ShopOS\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * The 18 `shopos_restock_*` option keys seeded by `seed_locale_defaults()`.
	 *
	 * Defined as a constant so non-option entries in `locales/<locale>.php`
	 * (e.g. `shell_*` strings consumed by the modern Email class) don't get
	 * accidentally written to the WP options table.
	 *
	 * @since 1.11.4
	 */
	private const OPTION_KEYS = array(
		'auto_inject',
		'form_heading',
		'form_description',
		'form_button_text',
		'form_success_message',
		'form_duplicate_message',
		'enable_confirmation',
		'enable_gdpr',
		'gdpr_text',
		'confirm_subject',
		'confirm_heading',
		'confirm_body',
		'notify_subject',
		'notify_heading',
		'notify_body',
		'notify_button_text',
		'from_name',
		'from_email',
	);

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'restock_notify';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Restock Notify', 'shopos-core' );
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Customers subscribe to out-of-stock products and are emailed the moment stock returns. Hebrew-first UI, exportable subscriber list.', 'shopos-core' );
	}

	/**
	 * Dependencies.
	 *
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' );
	}

	/**
	 * Settings are rendered by the legacy admin class under its own WP menu
	 * (Restock Notify). Nothing to render via Settings_Hub.
	 *
	 * @return array
	 */
	public function settings_schema() {
		return array();
	}

	/**
	 * The legacy admin class registers its own top-level "Restock Notify" menu
	 * under `admin.php?page=restock-notify`. Wire the dashboard button there.
	 */
	public function legacy_settings_url() {
		return admin_url( 'admin.php?page=restock-notify' );
	}

	/**
	 * Activation — ensure table + seeded defaults.
	 */
	public function on_activate() {
		$this->define_legacy_constants();
		require_once __DIR__ . '/legacy/includes/class-shopos-restock-database.php';
		\ShopOS_Restock_Database::create_tables();
		update_option( 'shopos_restock_db_version', SHOPOS_CORE_VERSION );
		$this->seed_locale_defaults();
	}

	/**
	 * Seed any missing `shopos_restock_*` options from the active locale's defaults.
	 *
	 * Existing values are NOT overwritten (per `/docs/decisions-2026-04-28.md`
	 * §4.2): a Hebrew install that activated under the pre-1.11.2 hardcoded
	 * Hebrew defaults keeps its values untouched even after this method runs
	 * under a different locale.
	 *
	 * Extracted from `on_activate()` so tests can drive the seeding without
	 * spinning up a real `$wpdb` for `ShopOS_Restock_Database::create_tables()`.
	 *
	 * @since 1.11.2
	 */
	public function seed_locale_defaults() {
		$defaults = self::defaults();
		foreach ( self::OPTION_KEYS as $key ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue;
			}
			if ( false === get_option( 'shopos_restock_' . $key, false ) ) {
				update_option( 'shopos_restock_' . $key, $defaults[ $key ] );
			}
		}
	}

	/**
	 * Deactivation — clear cron.
	 */
	public function on_deactivate() {
		wp_clear_scheduled_hook( 'shopos_restock_cleanup_old_entries' );
	}

	/**
	 * Uninstall — remove options AND drop the subscriber table.
	 *
	 * The subscriber table holds customer PII (emails). WordPress only runs this
	 * handler on an explicit "Delete" of the plugin, which is a deliberate,
	 * data-destroying action, so the table is dropped along with everything else
	 * (owner-approved 2026-07-22; Hard Rule #3 legacy-surface edit — the table
	 * name is derived from the trusted `$wpdb->prefix`, no legacy class loaded).
	 * The `shopos_restock_notification_log` accumulator lives off both the module
	 * prefix and OPTION_KEYS, so it is deleted explicitly.
	 */
	public function on_uninstall() {
		parent::on_uninstall();
		foreach ( self::OPTION_KEYS as $key ) {
			delete_option( 'shopos_restock_' . $key );
		}
		delete_option( 'shopos_restock_db_version' );
		delete_option( 'shopos_restock_notification_log' );

		global $wpdb;
		$table = $wpdb->prefix . 'shopos_restock_subscribers';
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB -- uninstall PII cleanup; table name from trusted prefix.
	}

	/**
	 * Register the WP_Privacy exporter + eraser unconditionally — even when this
	 * module is disabled (or WooCommerce is absent), so a privacy admin can
	 * still export/erase subscriber PII that persists in the `shopos_restock_subscribers`
	 * table. Privacy hooks are a platform contract (OS-5(a)), not a feature to
	 * gate behind module-enabled state. Called from Plugin::boot() for every
	 * discovered module regardless of is_enabled().
	 */
	public function register_persistent_hooks() {
		( new Privacy() )->register();
	}

	/**
	 * Boot — load legacy classes and instantiate their components.
	 *
	 * If any of the legacy global classes already exist — because the
	 * original standalone Restock Notify plugin is still active alongside
	 * ShopOS Core — skip booting this module and surface an admin notice.
	 * Loading a second class of the same name would fatal the whole site.
	 */
	public function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$conflicts = array_filter(
			array( 'ShopOS_Restock_Frontend', 'ShopOS_Restock_Ajax', 'ShopOS_Restock_Email', 'ShopOS_Restock_Database', 'ShopOS_Restock_Stock_Monitor', 'ShopOS_Restock_Admin' ),
			static function ( $c ) {
				return class_exists( $c, false );
			}
		);
		if ( ! empty( $conflicts ) ) {
			set_transient(
				'shopos_core_restock_conflict',
				array_values( $conflicts ),
				HOUR_IN_SECONDS
			);
			return;
		}

		$this->define_legacy_constants();

		require_once __DIR__ . '/legacy/helpers.php';

		$dir = __DIR__ . '/legacy/includes/';
		require_once $dir . 'class-shopos-restock-database.php';

		$installed = get_option( 'shopos_restock_db_version', '0' );
		if ( version_compare( $installed, SHOPOS_CORE_VERSION, '<' ) ) {
			\ShopOS_Restock_Database::create_tables();
			update_option( 'shopos_restock_db_version', SHOPOS_CORE_VERSION );
		}

		// Modern Email + Stock_Monitor (Wave 2.3b) — alias the legacy global
		// names onto the modern PSR-4 classes so legacy callers
		// (`ShopOS_Restock_Ajax::handle_subscribe()` calling `\ShopOS_Restock_Email::send_confirmation`,
		// `ShopOS_Restock_Admin::handle_actions()` calling `\ShopOS_Restock_Stock_Monitor::manual_notify`)
		// resolve to the modern classes — bilingual-email shell fix and the
		// `shopos_core/restock_notify/email_args` + `before_send` hooks apply
		// universally, not only on the stock-change path.
		//
		// IMPORTANT ordering:
		//  - Aliases happen AFTER the `class_exists(..., false)` conflict check
		//    above, so they don't trip the guard against ourselves.
		//  - The legacy class-shopos-restock-email.php and class-shopos-restock-stock-monitor.php files
		//    are NOT `require_once`'d — loading them would `class ShopOS_Restock_Email {}`
		//    against the alias and fatal.
		class_alias( '\\ShopOS\\Core\\Modules\\RestockNotify\\Email',         'ShopOS_Restock_Email' );
		class_alias( '\\ShopOS\\Core\\Modules\\RestockNotify\\Stock_Monitor', 'ShopOS_Restock_Stock_Monitor' );

		// Wave 2.3c — same alias pattern for Frontend. Legacy callers that
		// reference `\ShopOS_Restock_Frontend` (none currently — it's only instantiated
		// from this method) resolve to modern Frontend. The legacy
		// class-shopos-restock-frontend.php file is NOT `require_once`'d below — loading
		// it would `class ShopOS_Restock_Frontend {}` against the alias and fatal.
		class_alias( '\\ShopOS\\Core\\Modules\\RestockNotify\\Frontend',      'ShopOS_Restock_Frontend' );

		require_once $dir . 'class-shopos-restock-ajax.php';

		new \ShopOS\Core\Modules\RestockNotify\Frontend();
		new \ShopOS_Restock_Ajax();
		new \ShopOS\Core\Modules\RestockNotify\Stock_Monitor();

		// Elementor: register the back-in-stock form widget. The action only
		// fires with Elementor active, so no module-level `elementor` dependency
		// is added — the module keeps booting (shortcode + auto-inject) on an
		// Elementor-less store. Registered here, after the Woo + legacy-conflict
		// guards above, so the widget is available exactly where the shortcode is.
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );

		// WP_Privacy exporter + eraser are now registered unconditionally via
		// register_persistent_hooks() (called from Plugin::boot() regardless of
		// module-enabled state), so persisted subscriber PII stays covered even
		// when this module is disabled.

		if ( is_admin() ) {
			require_once $dir . 'class-shopos-restock-admin.php';
			new \ShopOS_Restock_Admin();

			// Wave 4.1b — CSV export of subscribers; always-on since 1.23.0
			// (the csv_export flag graduated). Capability + nonce checks
			// inside the exporter remain the gate.
			( new \ShopOS\Core\Modules\RestockNotify\Admin_Tools() )->register();
			( new \ShopOS\Core\Modules\RestockNotify\CSV_Exporter() )->register();
		}
	}

	/**
	 * Register the RestockNotify Elementor widget. Fired on
	 * `elementor/widgets/register` (Elementor-active only).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widget( $widgets_manager ) {
		$widgets_manager->register( new Widget( array(), array( 'shopos_module' => $this ) ) );
	}

	/**
	 * Define the legacy constants the bundled classes expect.
	 */
	private function define_legacy_constants() {
		if ( ! defined( 'SHOPOS_RESTOCK_VERSION' ) ) {
			define( 'SHOPOS_RESTOCK_VERSION', SHOPOS_CORE_VERSION );
		}
		if ( ! defined( 'SHOPOS_RESTOCK_PLUGIN_DIR' ) ) {
			define( 'SHOPOS_RESTOCK_PLUGIN_DIR', trailingslashit( __DIR__ ) . 'legacy/' );
		}
		if ( ! defined( 'SHOPOS_RESTOCK_PLUGIN_URL' ) ) {
			define( 'SHOPOS_RESTOCK_PLUGIN_URL', trailingslashit( SHOPOS_CORE_URL . 'src/Modules/RestockNotify' ) );
		}
		if ( ! defined( 'SHOPOS_RESTOCK_PLUGIN_BASENAME' ) ) {
			define( 'SHOPOS_RESTOCK_PLUGIN_BASENAME', plugin_basename( SHOPOS_CORE_FILE ) );
		}
	}

	/**
	 * Static accessor for the per-locale defaults (used by the legacy
	 * function shim `shopos_restock_option_defaults()`).
	 *
	 * Returns the option-key → default-value map for the requested locale,
	 * loaded from `locales/<locale>.php`. Falls back to `en_US.php` for any
	 * unknown locale. Strings inside the locale files are literals — they
	 * are the per-locale source, not translatable copy.
	 *
	 * Existing callers passing no argument get the active site's locale via
	 * `get_locale()` (or `en_US` if WP isn't loaded).
	 *
	 * @since 1.11.2 Locale-aware. Pre-1.11.2 the method returned hardcoded
	 *               Hebrew defaults regardless of locale.
	 *
	 * @param string|null $locale Locale code, or null to detect via `get_locale()`.
	 * @return array
	 */
	public static function defaults( $locale = null ) {
		if ( null === $locale ) {
			$locale = function_exists( 'get_locale' ) ? get_locale() : 'en_US';
		}

		$dir       = __DIR__ . '/locales/';
		$candidate = $dir . $locale . '.php';
		if ( ! is_readable( $candidate ) ) {
			$candidate = $dir . 'en_US.php';
		}

		return (array) require $candidate;
	}

	/**
	 * Instance-level alias.
	 *
	 * @return array
	 */
	private function option_defaults() {
		return self::defaults();
	}
}
