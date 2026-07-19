<?php
/**
 * Bundle Deals — the discount math.
 *
 * Pure, WC-free, deterministic. `Cart_Pricing` turns the live WooCommerce cart
 * into a plain line list (`key => { product_id, qty, base }`) plus a
 * participation map (`bundle_id => [line keys it acts on]`, resolved through
 * `Targeting`) and hands both to `compute()`. Everything money-shaped happens
 * here so it can be exhaustively unit-tested without a cart.
 *
 * Every discount is expressed as a NEW effective per-unit price on a line
 * (`set_price()` in the engine) — never a phantom "free" line item, never a
 * cart-level fee — honouring the per-line pricing decision. Where a discount
 * lands on only some of a line's units (BOGO, mix-&-match `fixed_price`), the
 * saving is BLENDED into the unit price: `((qty-d)*base + d*disc) / qty`.
 *
 * Conflict resolution: a line may match several bundles; the single largest
 * per-line saving wins, discounts NEVER stack, and a bundle can never raise a
 * price or push it below zero. Bundle order (priority, then id) makes ties
 * deterministic.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Bundle discount engine.
 */
final class Pricing {

	/**
	 * Resolve the winning per-line discounts for a cart.
	 *
	 * @param array<int,array> $bundles       Active bundles, already ordered.
	 * @param array<string,array{product_id:int,qty:int,base:float}> $lines Cart lines by key.
	 * @param array<string,array<int,string>> $participation bundle id => line keys it acts on.
	 * @return array<string,array{unit:float,bundle_id:string,saved:float}> Discounted lines only.
	 */
	public static function compute( array $bundles, array $lines, array $participation ) {
		$result = array();

		foreach ( $bundles as $bundle ) {
			$id      = (string) ( $bundle['id'] ?? '' );
			$keys    = isset( $participation[ $id ] ) ? (array) $participation[ $id ] : array();
			$subset  = array();
			foreach ( $keys as $key ) {
				if ( isset( $lines[ $key ] ) ) {
					$subset[ $key ] = $lines[ $key ];
				}
			}
			if ( empty( $subset ) ) {
				continue;
			}

			$proposal = self::propose( $bundle, $subset );

			foreach ( $proposal as $key => $unit ) {
				$line = $lines[ $key ];
				$base = (float) $line['base'];
				$qty  = (int) $line['qty'];
				$unit = self::clamp_unit( $unit, $base );
				if ( $unit >= $base ) {
					continue; // No real reduction.
				}
				$saved = ( $base - $unit ) * $qty;

				if ( ! isset( $result[ $key ] ) || $saved > $result[ $key ]['saved'] ) {
					$result[ $key ] = array(
						'unit'      => $unit,
						'bundle_id' => $id,
						'saved'     => $saved,
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Dispatch a bundle to its type's proposal builder. Pure.
	 *
	 * @param array $bundle Bundle.
	 * @param array $lines  Participating lines by key.
	 * @return array<string,float> key => proposed new unit price.
	 */
	private static function propose( array $bundle, array $lines ) {
		switch ( $bundle['type'] ?? '' ) {
			case 'tiered':
				return self::propose_tiered( $bundle, $lines );
			case 'bogo':
				return self::propose_bogo( $bundle, $lines );
			case 'curated':
				return self::propose_curated( $bundle, $lines );
			case 'mixmatch':
				return self::propose_mixmatch( $bundle, $lines );
		}
		return array();
	}

	/**
	 * Volume/tiered: the combined qty of all matching lines picks the highest
	 * met tier; that tier's %/fixed reduction hits every matching line. Pure.
	 *
	 * @param array $bundle Bundle.
	 * @param array $lines  Lines.
	 * @return array<string,float>
	 */
	private static function propose_tiered( array $bundle, array $lines ) {
		$total = self::total_qty( $lines );
		$tier  = self::pick_tier( isset( $bundle['tiers'] ) ? (array) $bundle['tiers'] : array(), $total );
		if ( null === $tier ) {
			return array();
		}

		$out = array();
		foreach ( $lines as $key => $line ) {
			$out[ $key ] = self::discount_unit( (float) $line['base'], $tier['kind'], (float) $tier['amount'] );
		}

		return $out;
	}

	/**
	 * The highest tier whose threshold the quantity meets, or null. Pure.
	 *
	 * @param array $tiers Sorted-ascending tiers.
	 * @param int   $qty   Combined quantity.
	 * @return array{min:int,kind:string,amount:float}|null
	 */
	public static function pick_tier( array $tiers, $qty ) {
		$picked = null;
		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) || ! isset( $tier['min'] ) ) {
				continue;
			}
			if ( (int) $qty >= (int) $tier['min'] ) {
				$picked = $tier;
			}
		}

		return $picked;
	}

	/**
	 * BOGO: across matching units (cheapest first) every `buy` grants `get`
	 * units at `discount`%; the saving is blended into the affected lines'
	 * unit prices. Pure.
	 *
	 * @param array $bundle Bundle.
	 * @param array $lines  Lines.
	 * @return array<string,float>
	 */
	private static function propose_bogo( array $bundle, array $lines ) {
		$bogo  = isset( $bundle['bogo'] ) ? (array) $bundle['bogo'] : array();
		$buy   = max( 1, (int) ( $bogo['buy'] ?? 1 ) );
		$get   = max( 1, (int) ( $bogo['get'] ?? 1 ) );
		$pct   = self::pct( $bogo['discount'] ?? 100 );
		$group = $buy + $get;

		$total       = self::total_qty( $lines );
		$disc_units  = intdiv( (int) $total, $group ) * $get;
		if ( $disc_units <= 0 ) {
			return array();
		}

		$out       = array();
		$remaining = $disc_units;
		foreach ( self::by_price_asc( $lines ) as $key => $line ) {
			if ( $remaining <= 0 ) {
				break;
			}
			$qty  = (int) $line['qty'];
			$base = (float) $line['base'];
			$d    = min( $qty, $remaining );
			$remaining -= $d;
			$out[ $key ] = self::blend( $base, $qty, $d, $base * ( 1 - $pct / 100 ) );
		}

		return $out;
	}

	/**
	 * Curated (frequently-bought-together): only when EVERY required product
	 * is present does the set discount apply — to one unit of each required
	 * line, blended. `fixed` is a total off the one-of-each set, distributed
	 * proportionally to each item's value. Pure.
	 *
	 * @param array $bundle Bundle.
	 * @param array $lines  Participating lines (product_id in curated.products).
	 * @return array<string,float>
	 */
	private static function propose_curated( array $bundle, array $lines ) {
		$curated  = isset( $bundle['curated'] ) ? (array) $bundle['curated'] : array();
		$required = array_values( array_unique( array_map( 'intval', (array) ( $curated['products'] ?? array() ) ) ) );
		if ( empty( $required ) ) {
			return array();
		}

		// One representative line per product id; every required product must
		// be present, else the bundle does not fire.
		$by_product = array();
		foreach ( $lines as $key => $line ) {
			$pid = (int) $line['product_id'];
			if ( in_array( $pid, $required, true ) && ! isset( $by_product[ $pid ] ) ) {
				$by_product[ $pid ] = array( 'key' => $key ) + $line;
			}
		}
		if ( count( $by_product ) < count( $required ) ) {
			return array();
		}

		$kind   = 'fixed' === ( $curated['kind'] ?? 'percent' ) ? 'fixed' : 'percent';
		$amount = (float) ( $curated['amount'] ?? 0 );
		$out    = array();

		if ( 'percent' === $kind ) {
			foreach ( $by_product as $line ) {
				$base        = (float) $line['base'];
				$out[ $line['key'] ] = self::blend( $base, (int) $line['qty'], 1, $base * ( 1 - self::pct( $amount ) / 100 ) );
			}
			return $out;
		}

		// Fixed total off the set: split proportionally by item value.
		$set_value = 0.0;
		foreach ( $by_product as $line ) {
			$set_value += (float) $line['base'];
		}
		if ( $set_value <= 0 ) {
			return array();
		}
		foreach ( $by_product as $line ) {
			$base        = (float) $line['base'];
			$disc_unit   = $base - $amount * ( $base / $set_value );
			$out[ $line['key'] ] = self::blend( $base, (int) $line['qty'], 1, $disc_unit );
		}

		return $out;
	}

	/**
	 * Mix-&-match: once `need` units from the collection are in the cart,
	 * percent/fixed hit every matching unit; `fixed_price` prices the cheapest
	 * `need` units to sum to `amount` (blended, never a raise). Pure.
	 *
	 * @param array $bundle Bundle.
	 * @param array $lines  Lines.
	 * @return array<string,float>
	 */
	private static function propose_mixmatch( array $bundle, array $lines ) {
		$mm     = isset( $bundle['mixmatch'] ) ? (array) $bundle['mixmatch'] : array();
		$need   = max( 1, (int) ( $mm['need'] ?? 1 ) );
		$kind   = (string) ( $mm['kind'] ?? 'percent' );
		$amount = (float) ( $mm['amount'] ?? 0 );

		$total = self::total_qty( $lines );
		if ( $total < $need ) {
			return array();
		}

		$out = array();

		if ( 'fixed_price' === $kind ) {
			// The cheapest `need` units together cost `amount`. Select them,
			// then scale each proportionally so their base total collapses to
			// exactly `amount` (never a raise: if they already cost <= amount,
			// no discount applies).
			$remaining = $need;
			$selected  = array(); // key => selected unit count on that line.
			$base_sum  = 0.0;
			foreach ( self::by_price_asc( $lines ) as $key => $line ) {
				if ( $remaining <= 0 ) {
					break;
				}
				$d = min( (int) $line['qty'], $remaining );
				if ( $d > 0 ) {
					$selected[ $key ] = $d;
					$base_sum        += $d * (float) $line['base'];
					$remaining       -= $d;
				}
			}
			if ( $base_sum <= 0 || $amount >= $base_sum ) {
				return array();
			}
			$factor = $amount / $base_sum;
			foreach ( $selected as $key => $d ) {
				$base        = (float) $lines[ $key ]['base'];
				$out[ $key ] = self::blend( $base, (int) $lines[ $key ]['qty'], $d, $base * $factor );
			}
			return $out;
		}

		// percent / fixed apply to every matching unit.
		foreach ( $lines as $key => $line ) {
			$out[ $key ] = self::discount_unit( (float) $line['base'], 'fixed' === $kind ? 'fixed' : 'percent', $amount );
		}

		return $out;
	}

	/* -----------------------------------------------------------------
	 * Shared math helpers (pure)
	 * ----------------------------------------------------------------- */

	/**
	 * Apply a percent-or-fixed discount to a unit price, clamped so it never
	 * rises above base or falls below zero. Pure.
	 *
	 * @param float  $base   Base unit price.
	 * @param string $kind   'percent'|'fixed'.
	 * @param float  $amount Discount amount.
	 * @return float
	 */
	public static function discount_unit( $base, $kind, $amount ) {
		$base = (float) $base;
		if ( 'fixed' === $kind ) {
			$unit = $base - (float) $amount;
		} else {
			$unit = $base * ( 1 - self::pct( $amount ) / 100 );
		}

		return self::clamp_unit( $unit, $base );
	}

	/**
	 * Blend a partial-quantity discount into a whole-line unit price:
	 * `((qty-d)*base + d*disc) / qty`. Pure.
	 *
	 * @param float $base Base unit price.
	 * @param int   $qty  Line quantity.
	 * @param int   $d    Number of discounted units on the line.
	 * @param float $disc Discounted unit price.
	 * @return float
	 */
	public static function blend( $base, $qty, $d, $disc ) {
		$base = (float) $base;
		$qty  = (int) $qty;
		if ( $qty <= 0 ) {
			return $base;
		}
		$d     = max( 0, min( $qty, (int) $d ) );
		$disc  = self::clamp_unit( $disc, $base );
		$total = ( $qty - $d ) * $base + $d * $disc;

		return self::clamp_unit( $total / $qty, $base );
	}

	/**
	 * Clamp a unit price to [0, base]. Pure.
	 *
	 * @param float $unit Candidate unit price.
	 * @param float $base Base unit price (the ceiling — never raise).
	 * @return float
	 */
	private static function clamp_unit( $unit, $base ) {
		return max( 0.0, min( (float) $base, (float) $unit ) );
	}

	/**
	 * Clamp a percentage to [0, 100]. Pure.
	 *
	 * @param mixed $value Raw.
	 * @return float
	 */
	private static function pct( $value ) {
		return max( 0.0, min( 100.0, (float) $value ) );
	}

	/**
	 * Sum of line quantities. Pure.
	 *
	 * @param array $lines Lines.
	 * @return int
	 */
	private static function total_qty( array $lines ) {
		$total = 0;
		foreach ( $lines as $line ) {
			$total += (int) $line['qty'];
		}
		return $total;
	}

	/**
	 * Lines re-keyed and ordered cheapest base price first (stable on ties by
	 * original key) — the fair pick order for partial-quantity discounts. Pure.
	 *
	 * @param array $lines Lines by key.
	 * @return array<string,array>
	 */
	private static function by_price_asc( array $lines ) {
		$keys = array_keys( $lines );
		usort(
			$keys,
			static function ( $a, $b ) use ( $lines ) {
				$pa = (float) $lines[ $a ]['base'];
				$pb = (float) $lines[ $b ]['base'];
				if ( $pa === $pb ) {
					return strcmp( (string) $a, (string) $b );
				}
				return $pa <=> $pb;
			}
		);

		$out = array();
		foreach ( $keys as $key ) {
			$out[ $key ] = $lines[ $key ];
		}

		return $out;
	}
}
