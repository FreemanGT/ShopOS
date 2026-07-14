<?php
/**
 * ShopOS admin dashboard — module cards + health bar.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Admin;

use ShopOS\Core\Core\Plugin;
use ShopOS\Core\Core\Security;
use ShopOS\Core\Core\Settings_Hub;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard.
 */
final class Dashboard {

	/**
	 * Per-user meta key — truthy once the user dismisses the onboarding nudge
	 * via its "×" button. Distinct from the global `shopos_core_onboarded`
	 * option (set only when onboarding is completed or explicitly skipped).
	 */
	const ONBOARDING_NOTICE_DISMISSED_META = 'shopos_core_onboarding_notice_dismissed';

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 */
	public function boot() {
		add_action( 'admin_notices', array( $this, 'maybe_onboarding_notice' ) );
		add_action( 'admin_notices', array( $this, 'maybe_legacy_conflict_notice' ) );
		add_action( 'admin_notices', array( $this, 'maybe_boot_failure_notice' ) );
	}

	/**
	 * Surface module boot failures that Plugin::boot() caught and stashed.
	 */
	public function maybe_boot_failure_notice() {
		if ( ! current_user_can( Settings_Hub::CAP ) ) {
			return;
		}
		$failures = get_transient( 'shopos_core_boot_failures' );
		if ( empty( $failures ) || ! is_array( $failures ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'ShopOS Core — one or more modules failed to boot', 'shopos-core' ) . '</strong></p><ul style="margin:0 0 0 20px;list-style:disc;">';
		foreach ( $failures as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? (string) $row['label'] : '';
			$msg   = isset( $row['message'] ) ? (string) $row['message'] : '';
			printf(
				'<li><strong>%s</strong>: <code>%s</code></li>',
				esc_html( $label ),
				esc_html( $msg )
			);
		}
		echo '</ul></div>';
	}

	/**
	 * Warn admin when a legacy plugin is still active alongside ShopOS Core.
	 * The corresponding module bailed out of boot() to avoid a fatal — this
	 * tells the admin why nothing's working and how to fix it.
	 */
	public function maybe_legacy_conflict_notice() {
		if ( ! current_user_can( Settings_Hub::CAP ) ) {
			return;
		}

		$conflicts = array(
			'restock_notify'     => array(
				'transient' => 'shopos_core_restock_conflict',
				'label'     => __( 'Restock Notify', 'shopos-core' ),
				'plugin'    => 'restock-notify',
			),
			'variation_swatches' => array(
				'transient' => 'shopos_core_swatches_conflict',
				'label'     => __( 'Variation Swatches', 'shopos-core' ),
				'plugin'    => 'shopos-variation-swatches',
			),
		);

		foreach ( $conflicts as $info ) {
			$classes = get_transient( $info['transient'] );
			if ( empty( $classes ) || ! is_array( $classes ) ) {
				continue;
			}
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p><p><code>%s</code></p><p>%s</p></div>',
				esc_html( sprintf( __( 'ShopOS Core — %s disabled', 'shopos-core' ), $info['label'] ) ),
				esc_html__( 'The module did not boot because its legacy plugin is still active. These class names already exist:', 'shopos-core' ),
				esc_html( implode( ', ', array_map( 'strval', $classes ) ) ),
				esc_html__( 'Deactivate the legacy plugin in Plugins → Installed Plugins (or run ShopOS → Tools → Deactivate & delete legacy plugins) and reload.', 'shopos-core' )
			);
		}
	}

	/**
	 * Entry point called by Settings_Hub.
	 */
	public function render() {
		Security::require_cap( Settings_Hub::CAP );

		$registry   = $this->plugin->registry();
		$modules    = $registry->all();
		$onboarded  = (bool) get_option( 'shopos_core_onboarded', false );
		$health_bar = $this->environment_health();

		echo '<div class="wrap shopos-wrap shopos-dashboard">';
		echo '<h1>' . esc_html__( 'ShopOS', 'shopos-core' ) . ' <span class="shopos-version">v' . esc_html( SHOPOS_CORE_VERSION ) . '</span></h1>';

		$this->render_health_bar( $health_bar );

		if ( ! $onboarded ) {
			$this->render_onboarding();
		}

		echo '<div class="shopos-modules-grid">';
		foreach ( $modules as $module ) {
			$this->render_module_card( $module );
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render a module card.
	 *
	 * @param \ShopOS\Core\Core\Module_Interface $module Module.
	 */
	private function render_module_card( $module ) {
		$health        = $module->health();
		$level_class   = 'shopos-dot-' . $health['level'];
		$toggle_action = admin_url( 'admin-post.php?action=shopos_toggle_module' );
		$enabled       = $module->is_enabled();
		$settings_url  = admin_url( 'admin.php?page=shopos-' . $module->id() );
		$schema        = $module->settings_schema();

		echo '<div class="shopos-module-card">';
		echo '<div class="shopos-module-head">';
		echo '<h2 class="shopos-module-title">' . esc_html( $module->label() ) . '</h2>';
		echo '<span class="shopos-dot ' . esc_attr( $level_class ) . '" title="' . esc_attr( $health['message'] ) . '"></span>';
		echo '</div>';

		echo '<p class="shopos-module-desc">' . esc_html( $module->description() ) . '</p>';

		echo '<div class="shopos-module-actions">';

		printf(
			'<form method="post" action="%1$s" class="shopos-toggle-form">%2$s<input type="hidden" name="module" value="%3$s"/><input type="hidden" name="enabled" value="%4$d"/><button type="submit" class="button %5$s">%6$s</button></form>',
			esc_url( $toggle_action ),
			wp_nonce_field( 'shopos_toggle_module', '_wpnonce', true, false ),
			esc_attr( $module->id() ),
			$enabled ? 0 : 1,
			$enabled ? 'button-secondary' : 'button-primary',
			$enabled ? esc_html__( 'Disable', 'shopos-core' ) : esc_html__( 'Enable', 'shopos-core' )
		);

		if ( ! empty( $schema ) ) {
			printf(
				'<a href="%s" class="button">%s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'shopos-core' )
			);
		} elseif ( method_exists( $module, 'legacy_settings_url' ) ) {
			$legacy_url = (string) $module->legacy_settings_url();
			if ( '' !== $legacy_url ) {
				printf(
					'<a href="%s" class="button">%s</a>',
					esc_url( $legacy_url ),
					esc_html__( 'Settings (legacy menu)', 'shopos-core' )
				);
			}
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Environment health: Woo, Elementor, PHP, DB migration state.
	 *
	 * @return array
	 */
	private function environment_health() {
		$php_ok   = version_compare( PHP_VERSION, '7.4', '>=' );
		$wp_ok    = function_exists( 'get_bloginfo' ) ? version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) : false;
		$woo_ok   = class_exists( 'WooCommerce' );
		$woo_ver  = $woo_ok && defined( 'WC_VERSION' ) ? WC_VERSION : '';
		$el_ok    = defined( 'ELEMENTOR_VERSION' );
		$el_ver   = $el_ok ? ELEMENTOR_VERSION : '';
		$db_ver   = get_option( 'shopos_core_db_version', '0' );
		$db_ok    = version_compare( $db_ver, SHOPOS_CORE_VERSION, '>=' );

		return array(
			array( 'label' => 'PHP',         'ok' => $php_ok, 'value' => PHP_VERSION ),
			array( 'label' => 'WordPress',   'ok' => $wp_ok,  'value' => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '?' ),
			array( 'label' => 'WooCommerce', 'ok' => $woo_ok, 'value' => $woo_ver ?: __( 'missing', 'shopos-core' ) ),
			array( 'label' => 'Elementor',   'ok' => $el_ok,  'value' => $el_ver ?: __( 'not installed', 'shopos-core' ), 'optional' => true ),
			array( 'label' => 'DB version',  'ok' => $db_ok,  'value' => $db_ver ),
		);
	}

	/**
	 * Render environment health bar.
	 *
	 * @param array $bar Bar entries.
	 */
	private function render_health_bar( $bar ) {
		echo '<div class="shopos-healthbar">';
		foreach ( $bar as $item ) {
			$class = ! empty( $item['ok'] ) ? 'ok' : ( ! empty( $item['optional'] ) ? 'soft' : 'bad' );
			printf(
				'<span class="shopos-healthbar-item %1$s"><strong>%2$s</strong> %3$s</span>',
				esc_attr( $class ),
				esc_html( $item['label'] ),
				esc_html( $item['value'] )
			);
		}
		echo '</div>';
	}

	/**
	 * Onboarding wizard (shown once).
	 */
	private function render_onboarding() {
		echo '<div class="shopos-onboarding">';
		echo '<h2>' . esc_html__( 'Welcome to ShopOS', 'shopos-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'ShopOS gives you seven WooCommerce super-powers in one plugin. Pick what you want now — you can change this any time from the Dashboard.', 'shopos-core' ) . '</p>';
		printf(
			'<form method="post" action="%s">',
			esc_url( admin_url( 'options.php' ) )
		);
		settings_fields( 'shopos_core_modules_group' );

		echo '<ul class="shopos-onboarding-list">';
		foreach ( $this->plugin->registry()->all() as $module ) {
			$id    = $module->id();
			$name  = 'shopos_core_modules[' . $id . ']';
			$checked = checked( $module->is_enabled(), true, false );
			printf(
				'<li><label><input type="checkbox" name="%1$s" value="1" %2$s/> <strong>%3$s</strong> — %4$s</label></li>',
				esc_attr( $name ),
				$checked,
				esc_html( $module->label() ),
				esc_html( $module->description() )
			);
		}
		echo '</ul>';

		submit_button( __( 'Save & continue', 'shopos-core' ) );
		echo '</form>';

		printf(
			'<p><a href="%s">%s</a></p>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=shopos_mark_onboarded' ), 'shopos_mark_onboarded' ) ),
			esc_html__( 'Skip onboarding', 'shopos-core' )
		);

		echo '</div>';
	}

	/**
	 * Show a nudge notice outside the dashboard until the user saves onboarding.
	 */
	public function maybe_onboarding_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, 'shopos' ) ) {
			return;
		}
		if ( (bool) get_option( 'shopos_core_onboarded', false ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), self::ONBOARDING_NOTICE_DISMISSED_META, true ) ) {
			return;
		}
		if ( ! current_user_can( Settings_Hub::CAP ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info is-dismissible" data-shopos-notice="onboarding"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'ShopOS Core is installed — pick which modules to activate.', 'shopos-core' ),
			esc_url( admin_url( 'admin.php?page=shopos' ) ),
			esc_html__( 'Open the dashboard', 'shopos-core' )
		);

		// WordPress's `is-dismissible` only hides the notice client-side for the
		// current pageview — it persists nothing, so the nudge reappears on reload.
		// Mirror the "×" click to an AJAX call that records a per-user dismissal.
		printf(
			'<script>(function(){document.addEventListener("click",function(e){var b=e.target&&e.target.closest&&e.target.closest(".notice-dismiss");if(!b||!b.closest("[data-shopos-notice=\'onboarding\']")||!window.fetch)return;var d=new FormData();d.append("action","shopos_dismiss_onboarding_notice");d.append("_ajax_nonce",%s);fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:d});});})();</script>', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			wp_json_encode( wp_create_nonce( 'shopos_dismiss_onboarding_notice' ) )
		);
	}
}

add_action(
	'admin_post_shopos_mark_onboarded',
	static function () {
		\ShopOS\Core\Core\Security::verify_nonce( 'shopos_mark_onboarded' );
		\ShopOS\Core\Core\Security::require_cap( \ShopOS\Core\Core\Settings_Hub::CAP );
		update_option( 'shopos_core_onboarded', 1 );
		wp_safe_redirect( admin_url( 'admin.php?page=shopos' ) );
		exit;
	}
);

// Capture "Save & continue" from the onboarding form → mark onboarded after save.
add_action(
	'update_option_shopos_core_modules',
	static function () {
		if ( ! get_option( 'shopos_core_onboarded', false ) ) {
			update_option( 'shopos_core_onboarded', 1 );
		}
	}
);

// Persist the onboarding-nudge "×" click (the dismiss button itself is rendered
// and animated by WP core; this only records that the user dismissed it).
add_action(
	'wp_ajax_shopos_dismiss_onboarding_notice',
	static function () {
		\ShopOS\Core\Core\Security::verify_ajax_nonce( 'shopos_dismiss_onboarding_notice' );
		\ShopOS\Core\Core\Security::require_cap_ajax( \ShopOS\Core\Core\Settings_Hub::CAP );
		update_user_meta( get_current_user_id(), Dashboard::ONBOARDING_NOTICE_DISMISSED_META, 1 );
		wp_send_json_success();
	}
);
