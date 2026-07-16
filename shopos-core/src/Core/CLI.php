<?php
/**
 * Scoped `wp shopos` CLI — operational commands for the ShopOS suite.
 *
 * Deliberately narrow (decisions §4.4 dropped the broad REST/automation
 * surface, and this stays inside that line): full index rebuilds for the two
 * indexer-backed modules plus feature-flag list/set. Nothing here adds a web
 * surface — the command registers from Plugin::boot() behind the WP_CLI
 * constant, so every ordinary request never even constructs the class.
 *
 * The reindex loop is byte-identical in effect to the admin reindex tools:
 * it drives the same `Indexer::reindex_batch()` in the same 50-product steps,
 * and deliberately makes the final zero-row call so the reconcile watermark
 * parks at "now" exactly like the admin tool's last AJAX round-trip.
 *
 * `flags set` writes the same `shopos_core_<module>_<feature>_enabled` option
 * shape the Feature Flags admin page writes ('1'/'0'), validated against
 * Feature_Flags::registry() so a typo can never mint a stray option.
 *
 * `blueprint export|diff|import` (decisions §10) is operator-invoked
 * settings-as-code over Core\Blueprint — still no web surface, still inside
 * the §4.4 line.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

use ShopOS\Core\Modules\Search\Indexer as Search_Indexer;
use ShopOS\Core\Modules\ShopFilters\Indexer as Shop_Filters_Indexer;

/**
 * `wp shopos` command implementation.
 */
final class CLI {

	/** Frozen top-level command name. */
	const COMMAND = 'shopos';

	/** Mirrors the admin reindex tools' AJAX batch size. */
	const BATCH_SIZE = 50;

	/**
	 * Register the command with WP-CLI. A no-op on every non-CLI request.
	 */
	public static function register() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( self::COMMAND, self::class );
	}

	/**
	 * Rebuild a module's product index from scratch.
	 *
	 * ## OPTIONS
	 *
	 * <target>
	 * : Which index to rebuild.
	 * ---
	 * options:
	 *   - search
	 *   - shop-filters
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp shopos reindex search
	 *     wp shopos reindex shop-filters
	 *
	 * @param array $args Positional args.
	 */
	public function reindex( $args ) {
		$target    = isset( $args[0] ) ? (string) $args[0] : '';
		$module_id = self::reindex_module_id( $target );
		if ( null === $module_id ) {
			\WP_CLI::error( 'Unknown index. Use "search" or "shop-filters".' );
			return;
		}

		$module = Plugin::instance()->registry()->get( $module_id );
		if ( ! $module || ! $module->is_enabled() ) {
			\WP_CLI::error( sprintf( 'The "%s" module is disabled — enable it on the ShopOS dashboard first.', $module_id ) );
			return;
		}
		if ( ! $module->dependencies_met() ) {
			\WP_CLI::error( sprintf( 'The "%s" module\'s dependencies are not met (is WooCommerce active?).', $module_id ) );
			return;
		}

		$indexer = 'search' === $module_id ? new Search_Indexer() : new Shop_Filters_Indexer();
		$total   = $indexer->count_products();
		\WP_CLI::log( sprintf( 'Reindexing %d products (%s)...', $total, $target ) );

		$offset = 0;
		do {
			// The final zero-row batch is intentional: reindex_batch() parks
			// the reconcile watermark + last-run at "now" on that call, the
			// same way the admin tool's terminating AJAX round-trip does.
			$processed = $indexer->reindex_batch( $offset, self::BATCH_SIZE );
			$offset   += $processed;
			if ( $processed > 0 ) {
				\WP_CLI::log( sprintf( '  %d / %d', min( $offset, $total ), $total ) );
			}
		} while ( $processed > 0 );

		\WP_CLI::success( sprintf( 'Reindexed %d products (%s).', $offset, $target ) );
	}

	/**
	 * List ShopOS feature flags, or set one.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : "list" or "set".
	 *
	 * [<flag>]
	 * : The flag to set, as module.feature (see "wp shopos flags list").
	 *
	 * [<state>]
	 * : "on" or "off".
	 *
	 * ## EXAMPLES
	 *
	 *     wp shopos flags list
	 *     wp shopos flags set design.panel on
	 *
	 * @param array $args Positional args.
	 */
	public function flags( $args ) {
		$action = isset( $args[0] ) ? (string) $args[0] : '';

		if ( 'list' === $action ) {
			\WP_CLI\Utils\format_items( 'table', self::flag_rows(), array( 'flag', 'enabled', 'forced_by_filter', 'since' ) );
			return;
		}

		if ( 'set' === $action ) {
			$flag = self::parse_flag( isset( $args[1] ) ? (string) $args[1] : '' );
			if ( null === $flag ) {
				\WP_CLI::error( 'Unknown flag. Run "wp shopos flags list" for valid module.feature names.' );
				return;
			}
			$state = self::parse_state( isset( $args[2] ) ? (string) $args[2] : '' );
			if ( null === $state ) {
				\WP_CLI::error( 'State must be "on" or "off".' );
				return;
			}

			list( $module, $feature ) = $flag;
			update_option( Feature_Flags::option_name( $module, $feature ), $state ? '1' : '0' );

			if ( Feature_Flags::is_forced_by_filter( $module, $feature ) ) {
				\WP_CLI::warning( sprintf( 'A shopos_core/feature_flag/%s/%s filter is active and overrides the saved option.', $module, $feature ) );
			}
			\WP_CLI::success(
				sprintf(
					'Saved %s.%s = %s. Effective state: %s.',
					$module,
					$feature,
					$state ? 'on' : 'off',
					Feature_Flags::is_enabled( $module, $feature ) ? 'on' : 'off'
				)
			);
			return;
		}

		\WP_CLI::error( 'Unknown subcommand. Use "list" or "set".' );
	}

	/**
	 * Export, diff or import a Store Blueprint (settings-as-code).
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : "export", "diff" or "import".
	 * ---
	 * options:
	 *   - export
	 *   - diff
	 *   - import
	 * ---
	 *
	 * <file>
	 * : Path to the blueprint JSON file.
	 *
	 * [<name>]
	 * : Blueprint name on export. Defaults to the file name without extension.
	 *
	 * ## EXAMPLES
	 *
	 *     wp shopos blueprint export flagship.json
	 *     wp shopos blueprint diff flagship.json
	 *     wp shopos blueprint import flagship.json
	 *
	 * @param array $args Positional args.
	 */
	public function blueprint( $args ) {
		$action = isset( $args[0] ) ? (string) $args[0] : '';
		$file   = isset( $args[1] ) ? (string) $args[1] : '';

		if ( ! in_array( $action, array( 'export', 'diff', 'import' ), true ) ) {
			\WP_CLI::error( 'Unknown subcommand. Use "export", "diff" or "import".' );
			return;
		}
		if ( '' === $file ) {
			\WP_CLI::error( 'Missing file path. Usage: wp shopos blueprint ' . $action . ' <file>.' );
			return;
		}

		if ( 'export' === $action ) {
			$name     = self::blueprint_name( $file, isset( $args[2] ) ? (string) $args[2] : '' );
			$envelope = Blueprint::export_payload( $name );
			$json     = wp_json_encode( $envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			if ( false === file_put_contents( $file, $json . "\n" ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				\WP_CLI::error( sprintf( 'Could not write %s.', $file ) );
				return;
			}
			\WP_CLI::success( sprintf( 'Exported blueprint "%s" (%d options) to %s.', $name, count( $envelope['options'] ), $file ) );
			return;
		}

		// diff / import both start from a decoded file.
		$envelope = self::read_blueprint_file( $file );
		if ( is_string( $envelope ) ) {
			\WP_CLI::error( $envelope );
			return;
		}

		$known_modules = array_keys( Plugin::instance()->registry()->all() );
		$available_tax = self::indexed_taxonomies();

		if ( 'diff' === $action ) {
			// diff validates here; import delegates to apply()'s own validate.
			$check = Blueprint::validate( $envelope );
			if ( ! $check['ok'] ) {
				\WP_CLI::error( sprintf( 'Invalid blueprint (%s). No options were written.', $check['reason'] ) );
				return;
			}
			$diff = Blueprint::diff_rows( $envelope, $known_modules, $available_tax );
			foreach ( array_merge( $check['warnings'], $diff['warnings'] ) as $warning ) {
				\WP_CLI::warning( $warning );
			}
			\WP_CLI\Utils\format_items( 'table', $diff['rows'], array( 'option', 'action', 'current', 'blueprint' ) );
			$writes = count( array_filter( $diff['rows'], static function ( $row ) {
				return 'write' === $row['action'];
			} ) );
			\WP_CLI::log( sprintf( '%d option(s) would change, %d already match. Nothing was written.', $writes, count( $diff['rows'] ) - $writes ) );
			return;
		}

		$result = Blueprint::apply( $envelope, $known_modules, $available_tax );
		foreach ( $result['warnings'] as $warning ) {
			\WP_CLI::warning( $warning );
		}
		if ( ! $result['ok'] ) {
			\WP_CLI::error( sprintf( 'Invalid blueprint (%s). No options were written.', $result['reason'] ) );
			return;
		}
		\WP_CLI::success(
			sprintf(
				'Applied blueprint "%s": %d written, %d unchanged. Pre-apply backup taken %s (ShopOS → Tools restores it).',
				$result['name'],
				$result['written'],
				$result['skipped'],
				$result['backup_at']
			)
		);
	}

	/* -----------------------------------------------------------------
	 * Pure seams (unit-tested)
	 * ----------------------------------------------------------------- */

	/**
	 * Resolve the blueprint name for an export: the explicit positional when
	 * given, else the file's base name without its extension.
	 *
	 * @param string $file Export file path.
	 * @param string $arg  Optional explicit name positional.
	 * @return string
	 */
	public static function blueprint_name( $file, $arg ) {
		if ( '' !== trim( $arg ) ) {
			return trim( $arg );
		}
		$base = basename( $file );
		$dot  = strrpos( $base, '.' );
		return ( false === $dot || 0 === $dot ) ? $base : substr( $base, 0, $dot );
	}

	/**
	 * Map a CLI reindex target to its module id, or null when unknown.
	 *
	 * @param string $target CLI positional, e.g. 'shop-filters'.
	 * @return string|null
	 */
	public static function reindex_module_id( $target ) {
		$map = array(
			'search'       => 'search',
			'shop-filters' => 'shop_filters',
		);
		return isset( $map[ $target ] ) ? $map[ $target ] : null;
	}

	/**
	 * Table rows for `flags list`: one per Feature_Flags::registry() entry,
	 * resolved through the same is_enabled() the runtime uses (so a
	 * filter-forced flag shows its effective state, with the forced column
	 * explaining why the option alone won't change it).
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function flag_rows() {
		$rows = array();
		foreach ( Feature_Flags::registry() as $def ) {
			$rows[] = array(
				'flag'             => $def['module'] . '.' . $def['feature'],
				'enabled'          => Feature_Flags::is_enabled( $def['module'], $def['feature'] ) ? 'on' : 'off',
				'forced_by_filter' => Feature_Flags::is_forced_by_filter( $def['module'], $def['feature'] ) ? 'yes' : 'no',
				'since'            => (string) $def['since'],
			);
		}
		return $rows;
	}

	/**
	 * Parse a "module.feature" CLI arg against the canonical registry.
	 * Unknown / malformed input returns null — a typo never mints an option.
	 *
	 * @param string $arg CLI positional, e.g. 'design.panel'.
	 * @return array{0:string,1:string}|null
	 */
	public static function parse_flag( $arg ) {
		$parts = explode( '.', $arg, 2 );
		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
			return null;
		}
		foreach ( Feature_Flags::registry() as $def ) {
			if ( $def['module'] === $parts[0] && $def['feature'] === $parts[1] ) {
				return array( $parts[0], $parts[1] );
			}
		}
		return null;
	}

	/**
	 * Parse the on/off positional. Strict by design: '1'/'true'/'yes' are
	 * rejected so the accepted vocabulary matches the help text exactly.
	 *
	 * @param string $arg CLI positional.
	 * @return bool|null
	 */
	public static function parse_state( $arg ) {
		if ( 'on' === $arg ) {
			return true;
		}
		if ( 'off' === $arg ) {
			return false;
		}
		return null;
	}

	/* -----------------------------------------------------------------
	 * I/O glue (exercised in live-QA, like the reindex loop)
	 * ----------------------------------------------------------------- */

	/**
	 * Read + decode a blueprint file. Returns the decoded array, or an error
	 * message string for WP_CLI::error().
	 *
	 * @param string $file Path.
	 * @return array|string
	 */
	private static function read_blueprint_file( $file ) {
		if ( ! is_readable( $file ) ) {
			return sprintf( 'Cannot read %s.', $file );
		}
		$decoded = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $decoded ) ) {
			return sprintf( '%s is not valid JSON.', $file );
		}
		return $decoded;
	}

	/**
	 * Taxonomies present in the ShopFilters index, or null when the index is
	 * unavailable (module never indexed / table absent) — null suppresses the
	 * not-indexed-yet facet warnings rather than firing them for everything.
	 *
	 * @return string[]|null
	 */
	private static function indexed_taxonomies() {
		// Only touch the index table when the module is enabled — its
		// migration guarantees the table then, so a fresh store never sees a
		// raw missing-table query (WP_DEBUG would print the db error).
		$module = Plugin::instance()->registry()->get( 'shop_filters' );
		if ( ! $module || ! $module->is_enabled() ) {
			return null;
		}
		$taxonomies = ( new \ShopOS\Core\Modules\ShopFilters\Index_Repository() )->available_taxonomies();
		return array() === $taxonomies ? null : $taxonomies;
	}
}
