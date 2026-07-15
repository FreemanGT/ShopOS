<?php
/**
 * Shared base for the option-backed, per-string storefront label resolvers.
 *
 * QuickView, ShopFilters, Search and ProductPage each ship a `Labels` class
 * with an identical resolver: a canonical `defaults()` map (short key => [admin
 * field label, English default]) and a `get()` that returns the saved
 * `<OPTION_PREFIX><key>` option when non-empty, else the English default. This
 * base holds that resolver once so a module's `Labels` becomes:
 *
 *     final class Labels extends Labels_Base {
 *         const OPTION_PREFIX = 'shopos_core_<module>_label_';
 *         public static function defaults() { return array( ... ); }
 *     }
 *
 * Behaviour is byte-identical to the hand-rolled `get()` — same option name,
 * same non-empty-trim override rule, same default fallback — so adoption is a
 * pure refactor. Landed caller-free; modules adopt it in follow-up PRs (one per
 * module, to respect the >3-module change gate).
 *
 * VariationSwatches' `Labels` is deliberately NOT a subclass: it is a
 * locale-switch resolver (he()/en(), no admin options) — a different contract.
 *
 * Subclass contract:
 *   - MUST define `const OPTION_PREFIX` (the `shopos_core_<module>_label_` stem).
 *   - MUST implement `defaults()` returning `array<string,array{label,default}>`.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Option-backed label resolver base.
 */
abstract class Labels_Base {

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	abstract public static function defaults();

	/**
	 * Resolve a label by short key. Returns the saved override when non-empty,
	 * otherwise the English default. Unknown key → ''.
	 *
	 * Uses late static binding so the subclass's `OPTION_PREFIX` const and
	 * `defaults()` map drive resolution.
	 *
	 * @param string $key Short key (e.g. 'close').
	 * @return string
	 */
	public static function get( $key ) {
		$key      = (string) $key;
		$defaults = static::defaults();
		$default  = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ]['default'] : '';

		$value = (string) get_option( static::OPTION_PREFIX . $key, '' );
		return '' !== trim( $value ) ? $value : $default;
	}
}
