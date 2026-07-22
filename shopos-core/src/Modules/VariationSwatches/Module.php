<?php
/**
 * Variation Swatches module.
 *
 * Replaces the default WooCommerce add-to-cart form on variable products with
 * a modern, RTL/Hebrew-first buy box featuring color swatches, size buttons
 * and quantity stepper. Also adds a compact inline variation picker on shop /
 * archive pages.
 *
 * Ported from shopos-variation-swatches v1.6.6. The legacy class bodies are
 * kept verbatim under `legacy/includes/` to preserve the plugin's mature
 * matching + AJAX logic; this Module is a thin bootstrap that wires them into
 * the ShopOS lifecycle.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\VariationSwatches;

use ShopOS\Core\Core\Feature_Flags;
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
		return 'variation_swatches';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Variation Swatches', 'shopos-core' );
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Color swatches, size pills, quick-add buy box and shop-grid variation picker for variable products.', 'shopos-core' );
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
	 * Settings schema.
	 *
	 * The 15 options are owned by this page (ShopOS → Variation Swatches);
	 * they are stored under `shopos_core_variation_swatches_*` and read via
	 * the {@see Settings_Reader} shim (new key first, legacy `shopos_vs_*`
	 * key as fallback). The legacy WooCommerce → Settings → Products → "Shop
	 * swatches" section is retired to a "moved" notice — see
	 * `legacy/includes/class-settings.php`.
	 *
	 * Wave 2.2 / 4a (1.11.21) added the page behind the `settings_hub` flag;
	 * Wave 2.2 / 4g (1.11.45) graduated it (flag retired) and made it the
	 * sole editing surface.
	 *
	 * @return array
	 */
	public function settings_schema() {
		return array(
			'shop_enabled'             => array(
				'label'          => __( 'Enable shop-grid variation picker', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Show the compact variation picker on shop / archive pages', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_max_visible'         => array(
				'label'       => __( 'Max visible swatches per attribute', 'shopos-core' ),
				'type'        => 'number',
				'description' => __( 'Hard-clamped between 1 and 50.', 'shopos-core' ),
				'default'     => 5,
			),
			'shop_show_price'          => array(
				'label'          => __( 'Show price in archive picker', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Display variation price under each swatch on archives', 'shopos-core' ),
				'default'        => 'no',
			),
			'shop_apply_shop'          => array(
				'label'          => __( 'Apply on the main shop archive', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker on /shop', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_apply_category'      => array(
				'label'          => __( 'Apply on category archives', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker on product category pages', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_apply_tag'           => array(
				'label'          => __( 'Apply on tag archives', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker on product tag pages', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_apply_search'        => array(
				'label'          => __( 'Apply on search results', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker on product search results', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_apply_related'       => array(
				'label'          => __( 'Apply on related products', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker in related-products carousels', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_excluded_categories' => array(
				'label'       => __( 'Excluded category IDs', 'shopos-core' ),
				'type'        => 'text',
				'description' => __( 'Comma-separated WooCommerce category term IDs where the picker should not render.', 'shopos-core' ),
				'default'     => '',
			),
			'pdp_hide_oos'             => array(
				'label'          => __( 'PDP: hide out-of-stock variations', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Skip rendering swatches for variations that are out of stock on single-product pages', 'shopos-core' ),
				'default'        => 'no',
			),
			'pdp_show_sticky_bar'      => array(
				'label'          => __( 'Buy box: mobile sticky bar', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Show the sticky add-to-cart bar that follows the shopper on mobile', 'shopos-core' ),
				'default'        => 'yes',
			),
			'pdp_show_buy_now'         => array(
				'label'          => __( 'Buy box: "Buy now" button', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Show the express "Buy now" button beside Add to cart', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_hide_oos'            => array(
				'label'          => __( 'Shop: hide out-of-stock swatches', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Skip rendering swatches for variations that are out of stock in the archive picker', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_no_preselect'        => array(
				'label'          => __( 'Shop: skip pre-selecting any variation', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Render the picker with no variation pre-selected (forces an explicit choice)', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_hide_attr_labels'    => array(
				'label'          => __( 'Shop: hide attribute labels', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Hide the attribute label row (e.g. "Size:") above the swatches', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_hide_selected'       => array(
				'label'          => __( 'Shop: hide "selected option" text', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Hide the "selected option" text row under the swatches', 'shopos-core' ),
				'default'        => 'yes',
			),
			'shop_names_price_only'    => array(
				'label'          => __( 'Shop: show name & price only', 'shopos-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Hide the variation picker and add-to-cart on shop / archive cards — show only the product name and price (customers click through to buy)', 'shopos-core' ),
				'default'        => 'no',
			),
		);
	}

	/**
	 * Settings now live under ShopOS → Variation Swatches (Wave 2.2 / 4g).
	 * The Dashboard renders a "Settings" button from `settings_schema()`
	 * pointing at `admin.php?page=shopos-variation_swatches` automatically,
	 * so this legacy shim — kept only because Dashboard checks for it — just
	 * points there too rather than at the retired WooCommerce tab.
	 */
	public function legacy_settings_url() {
		return admin_url( 'admin.php?page=shopos-variation_swatches' );
	}

	/**
	 * Deactivation — scrub the per-product transients this module generates
	 * (prepare_product_data() populates `_transient_shopos_vs_pd_*`), so a
	 * disabled module doesn't leave stale picker JSON lying around for the
	 * next re-enable.
	 */
	public function on_deactivate() {
		global $wpdb;
		if ( isset( $wpdb ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_shopos_vs_pd_' ) . '%',
					$wpdb->esc_like( '_transient_timeout_shopos_vs_pd_' ) . '%'
				)
			);
		}
	}

	/**
	 * Uninstall — the option sweep in the parent only reaches this module's
	 * `shopos_core_variation_swatches_%` options; the color sampler writes a
	 * `_shopos_core_vs_sampled_color` post-meta on every variation, which sits
	 * outside that prefix. Delete those meta rows too so a full uninstall leaves
	 * no orphans behind.
	 */
	public function on_uninstall() {
		parent::on_uninstall();
		if ( function_exists( 'delete_post_meta_by_key' ) ) {
			delete_post_meta_by_key( Color_Sampler::META_KEY );
		}
	}

	/**
	 * Boot — define constants the bundled classes expect, require them, and
	 * boot the legacy singleton.
	 *
	 * If the original shopos-variation-swatches plugin is still active, any
	 * of its global classes will already be loaded. Requiring our copy on top
	 * would fatal the whole site, so we bail and record the conflict for the
	 * admin notice instead.
	 */
	public function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$conflicts = array_filter(
			array(
				'ShopOS_VS_Plugin',
				'ShopOS_VS_Frontend',
				'ShopOS_VS_Admin',
				'ShopOS_VS_Ajax',
				'ShopOS_VS_Settings',
				'ShopOS_VS_Archive',
			),
			static function ( $c ) {
				return class_exists( $c, false );
			}
		);
		if ( ! empty( $conflicts ) ) {
			set_transient(
				'shopos_core_swatches_conflict',
				array_values( $conflicts ),
				HOUR_IN_SECONDS
			);
			return;
		}

		$this->define_legacy_constants();
		$this->require_legacy_classes();

		if ( ! defined( 'SHOPOS_VS_BOOTED' ) ) {
			define( 'SHOPOS_VS_BOOTED', true );
			\ShopOS_VS_Plugin::instance()->boot();
		}

		// Wave 2.2 / 4d (1.11.27) — register the auto-color sampler scheduler
		// listeners + cron callback. Listeners flag-gate internally so
		// flag-OFF sites pay only the option read on hook entry; the
		// flag-flip listeners (handle_flag_add / handle_flag_update) fire
		// regardless because they ARE the flip detector.
		Sampler_Scheduler::register();

		// Wave 4.5 (1.11.40) — expose feature-flag values to the swatches JS
		// bundle. Priority 10001 runs after legacy register_assets (9999) so
		// the `shopos-core` script handle is already registered.
		add_action( 'wp_enqueue_scripts', array( $this, 'inject_feature_flags' ), 10001 );
	}

	/**
	 * Inject `window.ShopOSCoreVSFlags` ahead of the swatches script.
	 *
	 * The JS reads `ShopOSCoreVSFlags.bundleCompat` to gate its WPC Bundles /
	 * WPC FBT compatibility path (full-form serialization → wc-ajax=add_to_cart).
	 * Flag OFF emits `{ bundleCompat: false }` and the JS stays on its legacy
	 * whitelist payload — byte-identical to pre-1.11.40.
	 *
	 * @since 1.11.40
	 */
	public function inject_feature_flags() {
		$handle = 'shopos-core';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$flags = array(
			'bundleCompat' => Feature_Flags::is_enabled( 'variation_swatches', 'bundle_compat' ),
		);
		wp_add_inline_script(
			$handle,
			'window.ShopOSCoreVSFlags = ' . wp_json_encode( $flags ) . ';',
			'before'
		);
	}

	/**
	 * Define legacy constants so the bundled classes resolve their paths
	 * correctly from inside the module.
	 */
	private function define_legacy_constants() {
		if ( ! defined( 'SHOPOS_VS_VERSION' ) ) {
			define( 'SHOPOS_VS_VERSION', SHOPOS_CORE_VERSION );
		}
		if ( ! defined( 'SHOPOS_VS_FILE' ) ) {
			define( 'SHOPOS_VS_FILE', __FILE__ );
		}
		if ( ! defined( 'SHOPOS_VS_DIR' ) ) {
			// Templates live in legacy/templates/, used via SHOPOS_VS_DIR . 'templates/'.
			define( 'SHOPOS_VS_DIR', trailingslashit( __DIR__ ) . 'legacy/' );
		}
		if ( ! defined( 'SHOPOS_VS_URL' ) ) {
			// Assets live at module root, used via SHOPOS_VS_URL . 'assets/…'.
			define( 'SHOPOS_VS_URL', trailingslashit( SHOPOS_CORE_URL . 'src/Modules/VariationSwatches' ) );
		}
	}

	/**
	 * Require the legacy class files.
	 */
	private function require_legacy_classes() {
		$dir = __DIR__ . '/legacy/includes/';
		require_once $dir . 'class-plugin.php';
		require_once $dir . 'class-admin.php';
		require_once $dir . 'class-frontend.php';
		require_once $dir . 'class-ajax.php';
		require_once $dir . 'class-settings.php';
		require_once $dir . 'class-archive.php';
	}
}
