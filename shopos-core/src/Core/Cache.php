<?php
/**
 * Object-cache abstraction — one get/set/delete facade over the two storage
 * backends WordPress may offer, so module code stops hard-coding transients.
 *
 * When a persistent external object cache is installed (Redis / Memcached, i.e.
 * `wp_using_ext_object_cache()` is true) this routes through the `wp_cache_*`
 * API, which keeps hot cache reads in RAM instead of round-tripping the options
 * table. With no such backend it degrades to the same `*_transient()` calls the
 * module used before this class existed — BYTE-IDENTICAL: the transient name is
 * the caller's key verbatim (see the group note below), the TTL is passed
 * through unchanged, and a miss returns `false` exactly like `get_transient()`.
 * So adopting this facade is a pure refactor on a store with no object cache,
 * and a free speed-up on one that has it.
 *
 * Group semantics: WordPress transients have NO native group concept — callers
 * already namespace their own keys (e.g. ShopFilters' `shopos_core_sf_q_*`), so
 * the transient path uses the key as-is and ignores the group. The group is a
 * first-class parameter for the object-cache path only, where `wp_cache_*` takes
 * it as a real argument (grouped storage + `wp_cache_flush_group()`). Passing a
 * group therefore never changes the transient option row a caller writes today.
 *
 * Kill switch: the `shopos_core/cache/use_object_cache` filter (default =
 * `wp_using_ext_object_cache()`) forces the transient path when returned falsey,
 * so a store can roll back to pre-abstraction behaviour without a code change.
 *
 * This is a cache, not a store: values may vanish at any time (object caches are
 * volatile and unflushed between requests only when persistent). Never route
 * durable state — auto-backups, rate-limit counters, migration locks — through
 * it; those stay on transients/options deliberately.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Static cache facade.
 */
final class Cache {

	/**
	 * Default object-cache group for callers that don't pass one. Only ever used
	 * as the `wp_cache_*` group argument — the transient path ignores it.
	 */
	const DEFAULT_GROUP = 'shopos_core';

	/**
	 * Read a cached value.
	 *
	 * @param string $key   Fully-namespaced cache key (also the transient name).
	 * @param string $group Object-cache group (ignored on the transient path).
	 * @return mixed The stored value, or `false` on a miss — matching
	 *               `get_transient()` so `is_array()` / `false !==` guards work.
	 */
	public static function get( $key, $group = self::DEFAULT_GROUP ) {
		$key = (string) $key;

		if ( self::use_object_cache() ) {
			$found = false;
			$value = wp_cache_get( $key, (string) $group, false, $found );
			return $found ? $value : false;
		}

		return get_transient( $key );
	}

	/**
	 * Write a cached value.
	 *
	 * @param string $key   Fully-namespaced cache key (also the transient name).
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Time-to-live in seconds; 0 = no explicit expiry.
	 * @param string $group Object-cache group (ignored on the transient path).
	 * @return bool True on success.
	 */
	public static function set( $key, $value, $ttl = 0, $group = self::DEFAULT_GROUP ) {
		$key = (string) $key;
		$ttl = (int) $ttl;

		if ( self::use_object_cache() ) {
			return wp_cache_set( $key, $value, (string) $group, $ttl );
		}

		return set_transient( $key, $value, $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key   Fully-namespaced cache key (also the transient name).
	 * @param string $group Object-cache group (ignored on the transient path).
	 * @return bool True on success.
	 */
	public static function delete( $key, $group = self::DEFAULT_GROUP ) {
		$key = (string) $key;

		if ( self::use_object_cache() ) {
			return wp_cache_delete( $key, (string) $group );
		}

		return delete_transient( $key );
	}

	/**
	 * Whether the object-cache backend should be used. Defaults to WordPress's
	 * own "is a persistent external cache installed?" answer, and exposes it
	 * through a filter so a store can force the transient path (rollback lever).
	 *
	 * @return bool
	 */
	private static function use_object_cache() {
		$default = function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();

		/**
		 * Filter whether ShopOS caches route through the `wp_cache_*` object-cache
		 * API (true) or fall back to transients (false).
		 *
		 * @since 1.34.0
		 * @param bool $use_object_cache Default: `wp_using_ext_object_cache()`.
		 */
		return (bool) apply_filters( 'shopos_core/cache/use_object_cache', $default );
	}
}
