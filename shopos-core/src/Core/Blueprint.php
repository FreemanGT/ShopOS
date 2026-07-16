<?php
/**
 * Store Blueprint — settings-as-code (decisions §10).
 *
 * A Blueprint is a named, versioned JSON file capturing the suite's five
 * behavioural option surfaces — the modules map, every feature flag, the four
 * modules' label overrides, the ShopFilters facet config and the Design panel
 * tokens — so a second store starts configured instead of re-clicked.
 *
 * The file rides the Wave 0.3 envelope unchanged (version 1 + the same
 * required fields), plus one extra `blueprint` block ({format, name,
 * generator}) that validate_envelope() tolerates — so every Blueprint file is
 * also importable through the ShopOS → Tools page and shares the rolling-5
 * auto-backup / Restore machinery.
 *
 * Unlike the Tools import (raw, halt-on-first-false, same-store rollback),
 * apply() is built for cross-store idempotence: keys are enumerated from code
 * registries (never a DB scan), every value is validated up front
 * (invalid_value rejects the whole file with zero writes), unchanged values
 * are skipped rather than tripping update_option()'s value-unchanged false,
 * the modules map merges by id so a stale preset can never disable modules
 * shipped after the snapshot, and facet rows for taxonomies the store has not
 * indexed yet are kept with a warning (they activate once the index exists —
 * dropping them would lose config on exactly the fresh store #2 this exists
 * for).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

use ShopOS\Core\Modules\ProductPage\Labels as Product_Page_Labels;
use ShopOS\Core\Modules\QuickView\Labels as Quick_View_Labels;
use ShopOS\Core\Modules\Search\Labels as Search_Labels;
use ShopOS\Core\Modules\ShopFilters\Facet_Config;
use ShopOS\Core\Modules\ShopFilters\Labels as Shop_Filters_Labels;

/**
 * Blueprint.
 */
final class Blueprint {

	/** Blueprint block format version (the envelope itself stays at version 1). */
	const FORMAT = 1;

	/** The modules on/off map option. */
	const OPTION_MODULES = 'shopos_core_modules';

	/**
	 * The four option-backed label resolvers. VariationSwatches' Labels is
	 * deliberately absent — it locale-switches in code, not via options.
	 */
	const LABEL_CLASSES = array(
		Quick_View_Labels::class,
		Shop_Filters_Labels::class,
		Search_Labels::class,
		Product_Page_Labels::class,
	);

	/* -----------------------------------------------------------------
	 * Pure seams (unit-tested)
	 * ----------------------------------------------------------------- */

	/**
	 * The curated snapshot surface: option key => surface id. Enumerated from
	 * code registries at call time — never a DB scan — so runtime state
	 * (logs, index rows, backups, onboarding) can never leak into a preset.
	 *
	 * @return array<string,string> option key => 'modules'|'flag'|'label'|'facet'|'design'.
	 */
	public static function key_set() {
		$keys = array( self::OPTION_MODULES => 'modules' );

		foreach ( Feature_Flags::registry() as $def ) {
			$keys[ Feature_Flags::option_name( $def['module'], $def['feature'] ) ] = 'flag';
		}

		foreach ( self::LABEL_CLASSES as $class ) {
			foreach ( array_keys( $class::defaults() ) as $short ) {
				$keys[ $class::OPTION_PREFIX . $short ] = 'label';
			}
		}

		$keys[ Facet_Config::OPTION ] = 'facet';

		$keys[ Design::OPTION_PREFIX . 'accent' ] = 'design';
		foreach ( array_keys( Design::colour_fields() ) as $short ) {
			$keys[ Design::OPTION_PREFIX . $short ] = 'design';
		}
		$keys[ Design::OPTION_PREFIX . 'radius' ] = 'design';

		return $keys;
	}

	/**
	 * Build a Blueprint envelope from current options. Absent options export
	 * as their semantic defaults ('' labels, '0' flags, 'default' accent,
	 * empty maps) so a preset is portable rather than site-shaped.
	 *
	 * @param string $name Blueprint name.
	 * @return array
	 */
	public static function export_payload( $name ) {
		$options = array();
		foreach ( self::key_set() as $key => $surface ) {
			$options[ $key ] = get_option( $key, self::export_default( $key, $surface ) );
		}
		ksort( $options );

		return array(
			'version'     => Settings_Tools::ENVELOPE_VERSION,
			'exported_at' => gmdate( 'c' ),
			'site_url'    => function_exists( 'home_url' ) ? home_url() : '',
			'blueprint'   => array(
				'format'    => self::FORMAT,
				'name'      => (string) $name,
				'generator' => 'shopos-core ' . Plugin::VERSION,
			),
			'options'     => $options,
		);
	}

	/**
	 * Validate a decoded Blueprint file. Strict by design — settings-as-code
	 * means a typo fails loudly with zero writes, never a silent fallback.
	 * Cross-store data drift (module ids / taxonomies the target store lacks)
	 * is NOT an error; apply() handles it with warnings.
	 *
	 * @param mixed $envelope Decoded JSON.
	 * @return array{ok:bool,reason:string,warnings:array<int,string>}
	 */
	public static function validate( $envelope ) {
		$warnings = array();

		if ( ! is_array( $envelope ) ) {
			return array( 'ok' => false, 'reason' => 'not_an_object', 'warnings' => $warnings );
		}
		foreach ( array( 'version', 'exported_at', 'options', 'blueprint' ) as $required ) {
			if ( ! array_key_exists( $required, $envelope ) ) {
				return array( 'ok' => false, 'reason' => 'missing_field:' . $required, 'warnings' => $warnings );
			}
		}
		if ( (int) $envelope['version'] !== Settings_Tools::ENVELOPE_VERSION ) {
			return array( 'ok' => false, 'reason' => 'unsupported_version:' . $envelope['version'], 'warnings' => $warnings );
		}

		$block = $envelope['blueprint'];
		if ( ! is_array( $block ) || ! isset( $block['format'] ) || ! isset( $block['name'] ) ) {
			return array( 'ok' => false, 'reason' => 'invalid_blueprint_block', 'warnings' => $warnings );
		}
		if ( (int) $block['format'] !== self::FORMAT ) {
			return array( 'ok' => false, 'reason' => 'unsupported_format:' . $block['format'], 'warnings' => $warnings );
		}
		if ( ! is_string( $block['name'] ) || '' === trim( $block['name'] ) ) {
			return array( 'ok' => false, 'reason' => 'invalid_name', 'warnings' => $warnings );
		}
		if ( isset( $block['generator'] ) && self::generator_is_newer( $block['generator'] ) ) {
			$warnings[] = sprintf( 'Blueprint was generated by a newer core (%s, running %s) — unknown keys would be rejected below.', (string) $block['generator'], Plugin::VERSION );
		}

		if ( ! is_array( $envelope['options'] ) ) {
			return array( 'ok' => false, 'reason' => 'options_not_an_object', 'warnings' => $warnings );
		}

		$key_set = self::key_set();
		foreach ( $envelope['options'] as $k => $v ) {
			if ( ! is_string( $k ) || ! isset( $key_set[ $k ] ) ) {
				return array( 'ok' => false, 'reason' => 'unexpected_key:' . $k, 'warnings' => $warnings );
			}
			if ( ! self::value_is_valid( $key_set[ $k ], $k, $v ) ) {
				return array( 'ok' => false, 'reason' => 'invalid_value:' . $k, 'warnings' => $warnings );
			}
		}

		return array( 'ok' => true, 'reason' => '', 'warnings' => $warnings );
	}

	/**
	 * Structural value validity per surface. Pure.
	 *
	 * @param string $surface Surface id from key_set().
	 * @param string $key     Option key.
	 * @param mixed  $value   Raw value from the file.
	 * @return bool
	 */
	public static function value_is_valid( $surface, $key, $value ) {
		switch ( $surface ) {
			case 'modules':
				if ( ! is_array( $value ) ) {
					return false;
				}
				foreach ( $value as $id => $enabled ) {
					// Same unambiguous-boolean rule as the flag surface — a
					// typo like "enabled" must fail loudly, never silently
					// coerce to a module being switched off.
					if ( ! is_string( $id ) || '' === $id || null === filter_var( $enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ) {
						return false;
					}
				}
				return true;

			case 'flag':
				// Accept anything with an unambiguous boolean reading (a real
				// store's option may hold 1, '1' or 'true'); garbage rejects.
				return null !== filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			case 'label':
				return is_scalar( $value ) || null === $value;

			case 'facet':
				if ( ! is_array( $value ) ) {
					return false;
				}
				foreach ( $value as $row ) {
					if ( ! is_array( $row ) || empty( $row['taxonomy'] ) || ! is_string( $row['taxonomy'] ) ) {
						return false;
					}
				}
				return true;

			case 'design':
				if ( Design::OPTION_PREFIX . 'accent' === $key ) {
					return is_string( $value ) && isset( Design::presets()[ $value ] );
				}
				if ( ! is_scalar( $value ) && null !== $value ) {
					return false; // Guard the string casts below (an array would warn).
				}
				if ( Design::OPTION_PREFIX . 'radius' === $key ) {
					if ( '' === (string) $value ) {
						return true;
					}
					return is_numeric( $value ) && (int) $value >= Design::RADIUS_MIN && (int) $value <= Design::RADIUS_MAX;
				}
				// Colour override: empty = inherit, else a hex colour.
				return '' === (string) $value || '' !== Design::sanitize_hex( $value );
		}
		return false;
	}

	/**
	 * Normalise a validated blueprint value into the exact shape the admin
	 * pages write, resolving cross-store drift. Pure — store facts arrive as
	 * arguments. Returns the value to write plus any warnings.
	 *
	 * @param string     $key            Option key.
	 * @param string     $surface        Surface id.
	 * @param mixed      $value          Validated raw value.
	 * @param mixed      $current        Current option value (for the modules merge).
	 * @param string[]   $known_modules  Module ids the running core ships.
	 * @param string[]|null $available_tax Indexed taxonomies, or null when unknowable.
	 * @return array{0:mixed,1:array<int,string>} [normalised value, warnings].
	 */
	public static function normalize_value( $key, $surface, $value, $current, array $known_modules, $available_tax ) {
		$warnings = array();

		switch ( $surface ) {
			case 'modules':
				// Merge-by-id: blueprint ids win, the store's other ids keep
				// their state — a stale preset can never disable modules
				// shipped after the snapshot (owner ruling §10.5).
				$merged = is_array( $current ) ? $current : array();
				foreach ( $value as $id => $enabled ) {
					if ( ! in_array( $id, $known_modules, true ) ) {
						$warnings[] = sprintf( 'Module "%s" is unknown to this core — dropped.', $id );
						continue;
					}
					$merged[ $id ] = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
				}
				return array( $merged, $warnings );

			case 'flag':
				return array( filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0', $warnings );

			case 'label':
				return array( sanitize_text_field( (string) $value ), $warnings );

			case 'facet':
				// Reuse the module's own pure normaliser: convert saved-shape
				// rows to its admin-matrix input, valid list = the blueprint's
				// own taxonomies (deduped, last row wins) so nothing is
				// silently dropped; type is always recomputed, never trusted.
				$matrix = array();
				foreach ( $value as $row ) {
					$matrix[ (string) $row['taxonomy'] ] = array(
						'enabled'            => ! empty( $row['enabled'] ) ? '1' : '',
						'order'              => isset( $row['order'] ) ? (int) $row['order'] : 0,
						'hide_on_categories' => isset( $row['hide_on_categories'] ) && is_array( $row['hide_on_categories'] ) ? $row['hide_on_categories'] : array(),
					);
				}
				if ( is_array( $available_tax ) ) {
					foreach ( array_keys( $matrix ) as $taxonomy ) {
						if ( ! in_array( $taxonomy, $available_tax, true ) ) {
							$warnings[] = sprintf( 'Facet taxonomy "%s" is not in this store\'s filter index yet — kept; it activates after "wp shopos reindex shop-filters".', $taxonomy );
						}
					}
				}
				return array( Facet_Config::sanitize( $matrix, array_keys( $matrix ) ), $warnings );

			case 'design':
				if ( Design::OPTION_PREFIX . 'radius' === $key ) {
					return array( '' === (string) $value ? '' : (string) (int) $value, $warnings );
				}
				if ( Design::OPTION_PREFIX . 'accent' === $key ) {
					return array( (string) $value, $warnings );
				}
				return array( '' === (string) $value ? '' : Design::sanitize_hex( $value ), $warnings );
		}

		return array( $value, $warnings );
	}

	/**
	 * Whether a blueprint's generator string names a newer core than the one
	 * running. Unparseable generators are treated as not-newer. Pure.
	 *
	 * @param mixed $generator e.g. 'shopos-core 1.39.0'.
	 * @return bool
	 */
	public static function generator_is_newer( $generator ) {
		if ( ! is_string( $generator ) || ! preg_match( '/(\d+\.\d+\.\d+)/', $generator, $m ) ) {
			return false;
		}
		return version_compare( $m[1], Plugin::VERSION, '>' );
	}

	/* -----------------------------------------------------------------
	 * Diff + apply
	 * ----------------------------------------------------------------- */

	/**
	 * Dry-run rows for a validated envelope: one per option key, with the
	 * normalised target value compared against the store's current value.
	 *
	 * @param array         $envelope      Validated Blueprint envelope.
	 * @param string[]      $known_modules Module ids the running core ships.
	 * @param string[]|null $available_tax Indexed taxonomies, or null.
	 * @return array{rows:array<int,array<string,string>>,warnings:array<int,string>}
	 */
	public static function diff_rows( array $envelope, array $known_modules, $available_tax ) {
		$key_set  = self::key_set();
		$options  = $envelope['options'];
		$rows     = array();
		$warnings = array();
		ksort( $options );

		foreach ( $options as $key => $value ) {
			$surface = $key_set[ $key ];
			$current = get_option( $key, self::export_default( $key, $surface ) );

			list( $target, $value_warnings ) = self::normalize_value( $key, $surface, $value, $current, $known_modules, $available_tax );
			$warnings = array_merge( $warnings, $value_warnings );

			$rows[] = array(
				'option'    => $key,
				'action'    => self::values_equal( $current, $target ) ? 'skip' : 'write',
				'current'   => self::stringify( $current ),
				'blueprint' => self::stringify( $target ),
			);
		}

		return array( 'rows' => $rows, 'warnings' => $warnings );
	}

	/**
	 * Apply a Blueprint: validate, auto-backup via the shared rolling-5 store,
	 * then write each normalised value, skipping unchanged ones (idempotent
	 * re-apply is the normal settings-as-code case, and update_option()
	 * returns false on unchanged values — never conflate that with failure).
	 *
	 * @param mixed         $envelope      Decoded Blueprint file.
	 * @param string[]|null $known_modules Module ids; null = ask the live registry.
	 * @param string[]|null $available_tax Indexed taxonomies; null = ask the live index.
	 * @return array{ok:bool,reason:string,name:string,written:int,skipped:int,total:int,backup_at:?string,warnings:array<int,string>}
	 */
	public static function apply( $envelope, $known_modules = null, $available_tax = null ) {
		$check = self::validate( $envelope );
		if ( ! $check['ok'] ) {
			Logger::log( 'Blueprint apply rejected: ' . $check['reason'], 'error' );
			return array(
				'ok'        => false,
				'reason'    => $check['reason'],
				'name'      => '',
				'written'   => 0,
				'skipped'   => 0,
				'total'     => 0,
				'backup_at' => null,
				'warnings'  => $check['warnings'],
			);
		}

		if ( null === $known_modules ) {
			$known_modules = array_keys( Plugin::instance()->registry()->all() );
		}

		$name     = (string) $envelope['blueprint']['name'];
		$key_set  = self::key_set();
		$options  = $envelope['options'];
		$total    = count( $options );
		$warnings = $check['warnings'];
		ksort( $options );

		Logger::log( sprintf( 'Blueprint apply starting: "%s", %d options from %s', $name, $total, isset( $envelope['site_url'] ) ? $envelope['site_url'] : 'unknown' ), 'info' );

		$backup    = ( new Settings_Tools() )->backup_current( 'blueprint' );
		$backup_at = $backup['exported_at'];

		/**
		 * Fires after validation + auto-backup, before the first write.
		 *
		 * @since 1.39.0
		 *
		 * @param array $envelope The validated Blueprint envelope.
		 */
		do_action( 'shopos_core/blueprint/before_apply', $envelope );

		$written = 0;
		$skipped = 0;
		foreach ( $options as $key => $value ) {
			$surface = $key_set[ $key ];
			$current = get_option( $key, self::export_default( $key, $surface ) );

			list( $target, $value_warnings ) = self::normalize_value( $key, $surface, $value, $current, $known_modules, $available_tax );
			$warnings = array_merge( $warnings, $value_warnings );

			if ( self::values_equal( $current, $target ) ) {
				$skipped++;
				continue;
			}
			Logger::log( 'Blueprint apply: writing ' . $key, 'info' );
			update_option( $key, $target );
			$written++;
		}

		foreach ( Feature_Flags::registry() as $def ) {
			if ( isset( $options[ Feature_Flags::option_name( $def['module'], $def['feature'] ) ] )
				&& Feature_Flags::is_forced_by_filter( $def['module'], $def['feature'] ) ) {
				$warnings[] = sprintf( 'A shopos_core/feature_flag/%s/%s filter is active and overrides the saved %s.%s option.', $def['module'], $def['feature'], $def['module'], $def['feature'] );
			}
		}

		Logger::log( sprintf( 'Blueprint apply complete: "%s", %d written, %d unchanged', $name, $written, $skipped ), 'info' );

		/**
		 * Fires after every write.
		 *
		 * @since 1.39.0
		 *
		 * @param array $envelope The applied Blueprint envelope.
		 * @param array $result   { written: int, skipped: int, total: int }.
		 */
		do_action( 'shopos_core/blueprint/after_apply', $envelope, array( 'written' => $written, 'skipped' => $skipped, 'total' => $total ) );

		return array(
			'ok'        => true,
			'reason'    => '',
			'name'      => $name,
			'written'   => $written,
			'skipped'   => $skipped,
			'total'     => $total,
			'backup_at' => $backup_at,
			'warnings'  => $warnings,
		);
	}

	/* -----------------------------------------------------------------
	 * Helpers
	 * ----------------------------------------------------------------- */

	/**
	 * The semantic default an absent option exports as, per surface.
	 *
	 * @param string $key     Option key.
	 * @param string $surface Surface id.
	 * @return mixed
	 */
	public static function export_default( $key, $surface ) {
		switch ( $surface ) {
			case 'modules':
			case 'facet':
				return array();
			case 'flag':
				return '0';
			case 'design':
				return Design::OPTION_PREFIX . 'accent' === $key ? 'default' : '';
		}
		return '';
	}

	/**
	 * Loose-but-safe equality for the skip check: strict for scalars of the
	 * same type, string-compared across scalar types (a stored int 1 equals
	 * the normalised '1'), recursive for arrays.
	 *
	 * @param mixed $a One value.
	 * @param mixed $b Other value.
	 * @return bool
	 */
	public static function values_equal( $a, $b ) {
		if ( is_array( $a ) && is_array( $b ) ) {
			if ( count( $a ) !== count( $b ) || array_keys( $a ) !== array_keys( $b ) ) {
				return false;
			}
			foreach ( $a as $k => $v ) {
				if ( ! self::values_equal( $v, $b[ $k ] ) ) {
					return false;
				}
			}
			return true;
		}
		if ( is_scalar( $a ) && is_scalar( $b ) ) {
			return (string) $a === (string) $b;
		}
		return $a === $b;
	}

	/**
	 * Compact single-line rendering of an option value for the diff table.
	 *
	 * @param mixed $value Option value.
	 * @return string
	 */
	public static function stringify( $value ) {
		$str = is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value );
		if ( '' === $str ) {
			return '(empty)';
		}
		return strlen( $str ) > 60 ? substr( $str, 0, 57 ) . '...' : $str;
	}
}
