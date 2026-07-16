<?php
/**
 * Dashboard self-updater.
 *
 * Checks the public ShopOS release manifest and surfaces new theme versions
 * in Dashboard → Updates / Appearance → Themes. Installation then runs
 * through WordPress's own theme upgrader, which replaces the theme folder
 * in place — the theme stays active, no re-activation needed.
 *
 * The manifest lives in the public releases repo (source stays private):
 * https://github.com/FreemanGT/shopos-releases
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

/** Raw URL of the release manifest (public repo, CDN-served). */
const SHOPOS_THEME_UPDATE_MANIFEST = 'https://raw.githubusercontent.com/FreemanGT/shopos-releases/main/manifest.json';

/** Transient key for the cached manifest lookup. */
const SHOPOS_THEME_UPDATE_CACHE = 'shopos_theme_update_manifest';

/**
 * Fetch the "shopos-theme" entry from the release manifest, cached for 5 min
 * (GitHub's raw CDN caches ~5 min, so a shorter TTL buys nothing).
 *
 * A failed lookup caches a short-lived sentinel so a down endpoint can't
 * slow every admin page load with a blocking HTTP request.
 *
 * @return object|null Manifest entry, or null when unavailable.
 */
function shopos_theme_update_manifest() {
	// "Check Again" on Dashboard → Updates should bypass the cache.
	$force = is_admin() && isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$cached = $force ? false : get_site_transient( SHOPOS_THEME_UPDATE_CACHE );
	if ( false !== $cached ) {
		return is_object( $cached ) ? $cached : null;
	}

	$response = wp_remote_get(
		SHOPOS_THEME_UPDATE_MANIFEST,
		array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	$entry = null;
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$manifest = json_decode( wp_remote_retrieve_body( $response ) );
		if ( isset( $manifest->{'shopos-theme'}->version, $manifest->{'shopos-theme'}->package ) ) {
			$entry = $manifest->{'shopos-theme'};
		}
	}

	// Cache hit and miss ('' sentinel) alike for 5 minutes.
	set_site_transient( SHOPOS_THEME_UPDATE_CACHE, $entry ? $entry : '', 5 * MINUTE_IN_SECONDS );

	return $entry;
}

/**
 * Inject an available theme update into the update_themes transient.
 *
 * @param object|mixed $transient Site transient value.
 * @return object|mixed
 */
function shopos_theme_check_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	// Key by the real installed folder so a renamed install still updates.
	$slug  = basename( dirname( __DIR__ ) );
	$entry = shopos_theme_update_manifest();

	if ( ! $entry || ! version_compare( $entry->version, SHOPOS_THEME_VERSION, '>' ) ) {
		// Let WP know the theme is current (enables auto-update UI).
		if ( isset( $transient->response[ $slug ] ) ) {
			unset( $transient->response[ $slug ] );
		}
		return $transient;
	}

	$transient->response[ $slug ] = array(
		'theme'        => $slug,
		'new_version'  => $entry->version,
		'package'      => $entry->package,
		'url'          => isset( $entry->changelog_url ) ? $entry->changelog_url : 'https://github.com/FreemanGT/shopos-releases/releases',
		'requires'     => isset( $entry->requires ) ? $entry->requires : '6.0',
		'requires_php' => isset( $entry->requires_php ) ? $entry->requires_php : '8.0',
	);

	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'shopos_theme_check_update' );
