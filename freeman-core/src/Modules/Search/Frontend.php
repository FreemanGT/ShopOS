<?php
/**
 * Search — storefront wiring for the live dropdown.
 *
 * Enqueues the dropdown JS/CSS and localizes the config the script needs to
 * attach itself to the theme's search input. The store's search box isn't ours
 * (it comes from the theme builder / AWS), so the input is targeted by a
 * configurable selector rather than markup we render — progressive enhancement,
 * the native form still submits with JS off.
 *
 * Only constructed when the dropdown feature flag is on (Module::boot()).
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
final class Frontend {

	const HANDLE = 'freeman-core-search';

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the dropdown assets on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/search.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/search.js' ),
			array(),
			FREEMAN_CORE_VERSION,
			true
		);

		wp_localize_script( self::HANDLE, 'FreemanSearch', $this->localized_payload() );
	}

	/**
	 * Build the FreemanSearch JS payload. Extracted so PHPUnit can assert the
	 * shape (InfiniteScroll / QuickView precedent).
	 *
	 * @return array<string,mixed>
	 */
	public function localized_payload() {
		return array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'action'   => Ajax::ACTION,
			'nonce'    => wp_create_nonce( Ajax::NONCE ),
			'selector' => (string) $this->module->get_option( 'field_selector', 'input[type="search"], input[name="s"]' ),
			'minChars' => (int) $this->module->get_option( 'min_chars', 2 ),
			'debounce' => (int) $this->module->get_option( 'debounce_ms', 200 ),
			'labels'   => array(
				'noResults' => __( 'No products found', 'freeman-core' ),
				'seeAll'    => __( 'See all results', 'freeman-core' ),
				'searching' => __( 'Searching…', 'freeman-core' ),
			),
		);
	}
}
