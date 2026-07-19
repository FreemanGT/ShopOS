<?php
/**
 * Bundle Deals — the bundle-definition store.
 *
 * The whole list of bundles is one serialized option
 * (`shopos_core_bundle_deals_bundles`), exactly like the ShopFilters facet
 * matrix (`Facet_Config`): a single option holds an array of rows, normalised
 * through a pure `sanitize()` that whitelists shapes and coerces every value,
 * so a malformed admin submission (or an imported Blueprint) can never write a
 * price rule the pricing engine can't reason about.
 *
 * Each bundle is one of four types — tiered / bogo / curated / mixmatch — with
 * a shared `scope` (products / categories / tags / exclude_categories) and one
 * type-specific block. `active()` is the storefront read (enabled rows only);
 * `all()` is the admin read (every row, so a disabled bundle stays editable) —
 * the deliberate `resolve()`/`all_defs()` split Facet_Config uses.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Bundle-definition storage + pure normaliser.
 */
final class Bundle_Config {

	/** The single option holding the whole bundle list. */
	const OPTION = 'shopos_core_bundle_deals_bundles';

	/** The four bundle types. A row with any other type is dropped. */
	const TYPES = array( 'tiered', 'bogo', 'curated', 'mixmatch' );

	/**
	 * Saved bundles (admin read) — every row, disabled included, so the
	 * builder can re-enable them. Empty when nothing is saved.
	 *
	 * @return array<int,array>
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return is_array( $saved ) ? array_values( $saved ) : array();
	}

	/**
	 * Enabled bundles (storefront read) — the rows the cart engine + display
	 * actually evaluate, ordered by `priority` then `id` for deterministic
	 * conflict resolution.
	 *
	 * @return array<int,array>
	 */
	public static function active() {
		$rows = array_filter(
			self::all(),
			static function ( $b ) {
				return ! empty( $b['enabled'] );
			}
		);
		usort( $rows, array( __CLASS__, 'compare' ) );

		return array_values( $rows );
	}

	/**
	 * Stable ordering: lower priority first, ties broken by id so the
	 * "best-discount-wins" conflict resolution is deterministic across runs.
	 * Pure.
	 *
	 * @param array $a One bundle.
	 * @param array $b Other bundle.
	 * @return int
	 */
	public static function compare( $a, $b ) {
		$pa = isset( $a['priority'] ) ? (int) $a['priority'] : 0;
		$pb = isset( $b['priority'] ) ? (int) $b['priority'] : 0;
		if ( $pa !== $pb ) {
			return $pa <=> $pb;
		}
		return strcmp( (string) ( $a['id'] ?? '' ), (string) ( $b['id'] ?? '' ) );
	}

	/**
	 * Normalise a raw admin/Blueprint submission into the canonical bundle
	 * list. Pure — no I/O. Rows with an unrecognised type are dropped; every
	 * scalar is coerced, every id-list deduped, amounts clamped, tiers sorted.
	 *
	 * @param array $raw Raw rows (numeric-indexed or map — order preserved).
	 * @return array<int,array> Sanitised bundles.
	 */
	public static function sanitize( array $raw ) {
		$out = array();
		$i   = 0;
		foreach ( array_values( $raw ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$type = isset( $row['type'] ) ? (string) $row['type'] : '';
			if ( ! in_array( $type, self::TYPES, true ) ) {
				continue;
			}
			++$i;

			$id = isset( $row['id'] ) ? preg_replace( '/[^a-z0-9_\-]/i', '', (string) $row['id'] ) : '';
			if ( '' === $id ) {
				$id = 'b_' . $i;
			}

			$bundle = array(
				'id'       => $id,
				'type'     => $type,
				'title'    => isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '',
				'enabled'  => ! empty( $row['enabled'] ),
				'priority' => isset( $row['priority'] ) ? (int) $row['priority'] : 0,
				'scope'    => self::sanitize_scope( isset( $row['scope'] ) && is_array( $row['scope'] ) ? $row['scope'] : array() ),
			);

			switch ( $type ) {
				case 'tiered':
					$bundle['tiers'] = self::sanitize_tiers( isset( $row['tiers'] ) && is_array( $row['tiers'] ) ? $row['tiers'] : array() );
					break;
				case 'bogo':
					$bundle['bogo'] = self::sanitize_bogo( isset( $row['bogo'] ) && is_array( $row['bogo'] ) ? $row['bogo'] : array() );
					break;
				case 'curated':
					$bundle['curated'] = self::sanitize_curated( isset( $row['curated'] ) && is_array( $row['curated'] ) ? $row['curated'] : array() );
					break;
				case 'mixmatch':
					$bundle['mixmatch'] = self::sanitize_mixmatch( isset( $row['mixmatch'] ) && is_array( $row['mixmatch'] ) ? $row['mixmatch'] : array() );
					break;
			}

			$out[] = $bundle;
		}

		return $out;
	}

	/**
	 * Coerce the shared targeting block. Pure.
	 *
	 * @param array $scope Raw scope.
	 * @return array{products:int[],categories:int[],tags:int[],exclude_categories:int[]}
	 */
	private static function sanitize_scope( array $scope ) {
		return array(
			'products'           => self::id_list( $scope['products'] ?? array() ),
			'categories'         => self::id_list( $scope['categories'] ?? array() ),
			'tags'               => self::id_list( $scope['tags'] ?? array() ),
			'exclude_categories' => self::id_list( $scope['exclude_categories'] ?? array() ),
		);
	}

	/**
	 * Coerce a tier list: each row { min, kind, amount }, sorted ascending by
	 * threshold so the engine can walk it. Pure.
	 *
	 * @param array $tiers Raw tiers.
	 * @return array<int,array{min:int,kind:string,amount:float}>
	 */
	private static function sanitize_tiers( array $tiers ) {
		$out = array();
		foreach ( array_values( $tiers ) as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$min = isset( $tier['min'] ) ? (int) $tier['min'] : 0;
			if ( $min < 1 ) {
				continue;
			}
			$out[] = array(
				'min'    => $min,
				'kind'   => self::amount_kind( $tier['kind'] ?? 'percent' ),
				'amount' => self::amount( $tier['amount'] ?? 0, self::amount_kind( $tier['kind'] ?? 'percent' ) ),
			);
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return $a['min'] <=> $b['min'];
			}
		);

		return $out;
	}

	/**
	 * Coerce a BOGO block. Pure.
	 *
	 * @param array $bogo Raw bogo.
	 * @return array{buy:int,get:int,discount:float}
	 */
	private static function sanitize_bogo( array $bogo ) {
		return array(
			'buy'      => isset( $bogo['buy'] ) ? max( 1, (int) $bogo['buy'] ) : 1,
			'get'      => isset( $bogo['get'] ) ? max( 1, (int) $bogo['get'] ) : 1,
			'discount' => self::clamp_percent( $bogo['discount'] ?? 100 ),
		);
	}

	/**
	 * Coerce a curated (frequently-bought-together) block. Pure.
	 *
	 * @param array $curated Raw curated.
	 * @return array{products:int[],kind:string,amount:float}
	 */
	private static function sanitize_curated( array $curated ) {
		$kind = self::amount_kind( $curated['kind'] ?? 'percent' );
		return array(
			'products' => self::id_list( $curated['products'] ?? array() ),
			'kind'     => $kind,
			'amount'   => self::amount( $curated['amount'] ?? 0, $kind ),
		);
	}

	/**
	 * Coerce a mix-&-match block. `fixed_price` = the cheapest `need` units
	 * together cost `amount`; percent/fixed apply per matching unit. Pure.
	 *
	 * @param array $mm Raw mixmatch.
	 * @return array{need:int,kind:string,amount:float}
	 */
	private static function sanitize_mixmatch( array $mm ) {
		$kind = self::mixmatch_kind( $mm['kind'] ?? 'percent' );
		return array(
			'need'   => isset( $mm['need'] ) ? max( 1, (int) $mm['need'] ) : 1,
			'kind'   => $kind,
			'amount' => 'percent' === $kind ? self::clamp_percent( $mm['amount'] ?? 0 ) : max( 0.0, (float) ( $mm['amount'] ?? 0 ) ),
		);
	}

	/**
	 * A discount kind for tiers/curated: percent or a fixed amount off. Pure.
	 *
	 * @param mixed $kind Raw.
	 * @return string 'percent'|'fixed'
	 */
	private static function amount_kind( $kind ) {
		return 'fixed' === $kind ? 'fixed' : 'percent';
	}

	/**
	 * A mix-&-match kind: percent, fixed-per-unit, or a fixed bundle price. Pure.
	 *
	 * @param mixed $kind Raw.
	 * @return string 'percent'|'fixed'|'fixed_price'
	 */
	private static function mixmatch_kind( $kind ) {
		if ( 'fixed' === $kind ) {
			return 'fixed';
		}
		if ( 'fixed_price' === $kind ) {
			return 'fixed_price';
		}
		return 'percent';
	}

	/**
	 * Clamp a discount amount to its kind's valid range. Pure.
	 *
	 * @param mixed  $amount Raw amount.
	 * @param string $kind   'percent'|'fixed'.
	 * @return float
	 */
	private static function amount( $amount, $kind ) {
		return 'percent' === $kind ? self::clamp_percent( $amount ) : max( 0.0, (float) $amount );
	}

	/**
	 * Clamp a percentage to [0, 100]. Pure.
	 *
	 * @param mixed $value Raw.
	 * @return float
	 */
	private static function clamp_percent( $value ) {
		return max( 0.0, min( 100.0, (float) $value ) );
	}

	/**
	 * Dedupe + intify an id list (the Facet_Config idiom). Pure.
	 *
	 * @param mixed $ids Raw list, or a comma-separated string.
	 * @return int[]
	 */
	private static function id_list( $ids ) {
		if ( is_string( $ids ) ) {
			$ids = explode( ',', $ids );
		}
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	}
}
