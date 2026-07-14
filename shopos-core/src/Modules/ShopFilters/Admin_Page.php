<?php
/**
 * Shop Filters admin tool — a "Reindex now" batch runner rendered on the
 * ShopOS → Shop Filters page. Mirrors the VariableStockFix bulk-audit pattern:
 * a get-total call followed by offset-paged batch calls driven by inline JS, so
 * a full rebuild of a medium catalogue never blocks a single request.
 *
 * Wired whenever the module is enabled (always-on since 1.12.26; Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ShopFilters;

use ShopOS\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Admin reindex tool.
 */
final class Admin_Page {

	const NONCE          = 'shopos_core_shop_filters_reindex';
	const AJAX_GET_TOTAL = 'shopos_core_shop_filters_get_total';
	const AJAX_RUN_BATCH = 'shopos_core_shop_filters_reindex_batch';
	const BATCH_SIZE     = 50;

	/**
	 * Indexer.
	 *
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * Constructor.
	 *
	 * @param Indexer $indexer Indexer.
	 */
	public function __construct( Indexer $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * Register the page renderer + AJAX handlers.
	 */
	public function boot() {
		add_action( 'shopos_core/module_page/shop_filters', array( $this, 'render' ) );
		add_action( 'wp_ajax_' . self::AJAX_GET_TOTAL, array( $this, 'ajax_get_total' ) );
		add_action( 'wp_ajax_' . self::AJAX_RUN_BATCH, array( $this, 'ajax_run_batch' ) );
	}

	/**
	 * AJAX: report the product total + current index stats.
	 */
	public function ajax_get_total() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );
		Security::require_cap_ajax( 'manage_woocommerce' );

		wp_send_json_success(
			array(
				'total'            => $this->indexer->count_products(),
				'indexed_products' => $this->indexer->repository()->count_indexed_products(),
				'rows'             => $this->indexer->repository()->count_rows(),
			)
		);
	}

	/**
	 * AJAX: reindex one batch.
	 */
	public function ajax_run_batch() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );
		Security::require_cap_ajax( 'manage_woocommerce' );

		$offset    = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$processed = $this->indexer->reindex_batch( $offset, self::BATCH_SIZE );

		wp_send_json_success( array( 'processed' => $processed ) );
	}

	/**
	 * Render the tool on the module settings page.
	 */
	public function render() {
		$repo      = $this->indexer->repository();
		$last_run  = (string) get_option( Indexer::LAST_RUN_OPTION, '' );
		$scheduled = function_exists( 'as_next_scheduled_action' )
			? (bool) as_next_scheduled_action( Indexer::RECONCILE_HOOK )
			: (bool) wp_next_scheduled( Indexer::RECONCILE_HOOK );
		$status    = sprintf(
			/* translators: 1: indexed product count, 2: row count, 3: last refresh time, 4: scheduled state */
			__( 'Indexed: %1$d products, %2$d rows. Last refresh: %3$s. Auto-reindex: %4$s.', 'shopos-core' ),
			(int) $repo->count_indexed_products(),
			(int) $repo->count_rows(),
			'' !== $last_run ? $last_run : __( 'never', 'shopos-core' ),
			$scheduled ? __( 'scheduled', 'shopos-core' ) : __( 'not scheduled', 'shopos-core' )
		);

		$nonce = wp_create_nonce( self::NONCE );
		?>
		<h2><?php esc_html_e( 'Search index', 'shopos-core' ); ?></h2>
		<p><?php echo esc_html( $status ); ?></p>
		<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
			<p class="description"><?php esc_html_e( 'WP-Cron is disabled on this site (DISABLE_WP_CRON), so the automatic refresh depends on a real server cron triggering Action Scheduler. Make sure that is configured, or rebuild on demand with the button below.', 'shopos-core' ); ?></p>
		<?php endif; ?>
		<p><?php esc_html_e( 'The index refreshes automatically as products change. Use this to rebuild from scratch (e.g. after a bulk import that bypassed the normal save hooks).', 'shopos-core' ); ?></p>
		<p>
			<button id="shopos-sf-start" class="button button-primary"><?php esc_html_e( 'Reindex all products', 'shopos-core' ); ?></button>
			<button id="shopos-sf-stop"  class="button" disabled><?php esc_html_e( 'Stop', 'shopos-core' ); ?></button>
		</p>
		<div id="shopos-sf-progress" style="margin-top:20px;display:none;">
			<p id="shopos-sf-status"></p>
			<div style="background:#ddd;width:100%;height:20px;border-radius:3px;overflow:hidden;">
				<div id="shopos-sf-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;"></div>
			</div>
			<p id="shopos-sf-counts" style="margin-top:10px;font-family:monospace;"></p>
		</div>
		<script>
		(function () {
			var nonce   = <?php echo wp_json_encode( $nonce ); ?>;
			var actions = {
				getTotal: <?php echo wp_json_encode( self::AJAX_GET_TOTAL ); ?>,
				runBatch: <?php echo wp_json_encode( self::AJAX_RUN_BATCH ); ?>
			};
			var i18n = {
				counting: <?php echo wp_json_encode( __( 'Counting products…', 'shopos-core' ) ); ?>,
				indexing: <?php echo wp_json_encode( __( 'Indexing from', 'shopos-core' ) ); ?>,
				done:     <?php echo wp_json_encode( __( 'Reindex complete.', 'shopos-core' ) ); ?>,
				stopped:  <?php echo wp_json_encode( __( 'Stopped.', 'shopos-core' ) ); ?>,
				indexed:  <?php echo wp_json_encode( __( 'indexed', 'shopos-core' ) ); ?>
			};
			var startBtn = document.getElementById('shopos-sf-start');
			var stopBtn  = document.getElementById('shopos-sf-stop');
			var progress = document.getElementById('shopos-sf-progress');
			var statusEl = document.getElementById('shopos-sf-status');
			var bar      = document.getElementById('shopos-sf-bar');
			var counts   = document.getElementById('shopos-sf-counts');
			var total = 0, processed = 0, offset = 0, stopped = false;

			function ajax(action, data) {
				var body = new URLSearchParams(Object.assign({ action: action, _ajax_nonce: nonce }, data || {}));
				return fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); });
			}

			startBtn.addEventListener('click', async function () {
				stopped = false; processed = 0; offset = 0;
				progress.style.display = 'block';
				startBtn.disabled = true; stopBtn.disabled = false;
				statusEl.textContent = i18n.counting;
				bar.style.width = '0%';

				var totalResp = await ajax(actions.getTotal);
				if (!totalResp || !totalResp.success) {
					statusEl.textContent = 'Error'; startBtn.disabled = false; stopBtn.disabled = true; return;
				}
				total = totalResp.data.total;
				counts.textContent = '0 / ' + total + ' ' + i18n.indexed;

				while (!stopped) {
					statusEl.textContent = i18n.indexing + ' ' + offset + '…';
					var resp = await ajax(actions.runBatch, { offset: offset });
					if (!resp || !resp.success) { statusEl.textContent = 'Error'; break; }
					processed += resp.data.processed;
					offset    += resp.data.processed;
					var pct = total ? Math.min(100, Math.round((processed / total) * 100)) : 100;
					bar.style.width = pct + '%';
					counts.textContent = processed + ' / ' + total + ' ' + i18n.indexed;
					if (resp.data.processed === 0) break;
				}
				statusEl.textContent = stopped ? i18n.stopped : i18n.done;
				startBtn.disabled = false; stopBtn.disabled = true;
			});
			stopBtn.addEventListener('click', function () { stopped = true; });
		})();
		</script>
		<?php
	}
}
