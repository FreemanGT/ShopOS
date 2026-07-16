<?php
/**
 * Dashboard self-updater.
 *
 * Checks the public ShopOS release manifest and surfaces new plugin versions
 * in Dashboard → Updates / Plugins. Installation runs through WordPress's
 * own plugin upgrader, so the plugin folder is replaced in place and stays
 * active — no manual zip upload, no re-activation.
 *
 * The manifest lives in the public releases repo (source stays private):
 * https://github.com/FreemanGT/shopos-releases
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

final class Updater {

	/** Raw URL of the release manifest (public repo, CDN-served). */
	private const MANIFEST_URL = 'https://raw.githubusercontent.com/FreemanGT/shopos-releases/main/manifest.json';

	/** Transient key for the cached manifest lookup. */
	private const CACHE_KEY = 'shopos_core_update_manifest';

	/** Manifest key for this product. */
	private const PRODUCT = 'shopos-core';

	public function boot() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
	}

	/**
	 * Fetch the "shopos-core" entry from the release manifest, cached for 6 h.
	 * A failed lookup caches a short-lived sentinel so a down endpoint can't
	 * add a blocking HTTP request to every update check.
	 *
	 * @return object|null Manifest entry, or null when unavailable.
	 */
	private function manifest() {
		// "Check Again" on Dashboard → Updates should bypass the cache.
		$force = is_admin() && isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$cached = $force ? false : get_site_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_object( $cached ) ? $cached : null;
		}

		$response = wp_remote_get(
			self::MANIFEST_URL,
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		$entry = null;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$manifest = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $manifest->{self::PRODUCT}->version, $manifest->{self::PRODUCT}->package ) ) {
				$entry = $manifest->{self::PRODUCT};
			}
		}

		set_site_transient( self::CACHE_KEY, $entry ? $entry : '', $entry ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );

		return $entry;
	}

	/**
	 * Inject an available update into the update_plugins transient.
	 *
	 * @param object|mixed $transient Site transient value.
	 * @return object|mixed
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$entry = $this->manifest();
		$item  = $this->update_item( $entry );

		if ( $entry && version_compare( $entry->version, SHOPOS_CORE_VERSION, '>' ) ) {
			$transient->response[ SHOPOS_CORE_BASENAME ] = $item;
			unset( $transient->no_update[ SHOPOS_CORE_BASENAME ] );
		} elseif ( $item ) {
			// Listing in no_update enables the auto-update toggle UI.
			$transient->no_update[ SHOPOS_CORE_BASENAME ] = $item;
			unset( $transient->response[ SHOPOS_CORE_BASENAME ] );
		}

		return $transient;
	}

	/**
	 * "View details" modal content for the plugins screen.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action plugins_api action.
	 * @param object             $args   Request args.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PRODUCT !== $args->slug ) {
			return $result;
		}

		$entry = $this->manifest();
		if ( ! $entry ) {
			return $result;
		}

		return (object) array(
			'name'          => 'ShopOS Core',
			'slug'          => self::PRODUCT,
			'version'       => $entry->version,
			'author'        => '<a href="https://shoposdigital.com">ShopOS Digital</a>',
			'homepage'      => 'https://shoposdigital.com/shopos-core',
			'download_link' => $entry->package,
			'requires'      => isset( $entry->requires ) ? $entry->requires : '6.0',
			'requires_php'  => isset( $entry->requires_php ) ? $entry->requires_php : '8.0',
			'tested'        => isset( $entry->tested ) ? $entry->tested : '',
			'last_updated'  => isset( $entry->released ) ? $entry->released : '',
			'sections'      => array(
				'changelog' => sprintf(
					'<p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
					esc_url( isset( $entry->changelog_url ) ? $entry->changelog_url : 'https://github.com/FreemanGT/shopos-releases/releases' ),
					esc_html__( 'Full changelog on the ShopOS releases page', 'shopos-core' )
				),
			),
		);
	}

	/**
	 * Build the update-row object WordPress expects in the transient.
	 *
	 * @param object|null $entry Manifest entry.
	 * @return object|null
	 */
	private function update_item( $entry ) {
		if ( ! $entry ) {
			return null;
		}

		return (object) array(
			'id'           => 'shopos-releases/' . self::PRODUCT,
			'slug'         => self::PRODUCT,
			'plugin'       => SHOPOS_CORE_BASENAME,
			'new_version'  => $entry->version,
			'package'      => $entry->package,
			'url'          => 'https://shoposdigital.com/shopos-core',
			'requires'     => isset( $entry->requires ) ? $entry->requires : '6.0',
			'requires_php' => isset( $entry->requires_php ) ? $entry->requires_php : '8.0',
			'tested'       => isset( $entry->tested ) ? $entry->tested : '',
		);
	}
}
