<?php
/**
 * ShopOS → Tools page view.
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

$plugin   = \ShopOS\Core\Core\Plugin::instance();
$importer = $plugin->importer();
$scan     = $importer->scan();
$imported = $importer->imported();
$entries  = \ShopOS\Core\Core\Logger::entries();

$any_installed = false;
foreach ( $scan as $row ) {
	if ( ! empty( $row['installed'] ) ) {
		$any_installed = true;
		break;
	}
}

$uid                  = get_current_user_id();
$just_imported_flag   = get_transient( 'shopos_core_import_done_' . $uid );
$just_deleted_flag    = get_transient( 'shopos_core_delete_done_' . $uid );
if ( $just_imported_flag ) {
	delete_transient( 'shopos_core_import_done_' . $uid );
}
if ( $just_deleted_flag ) {
	delete_transient( 'shopos_core_delete_done_' . $uid );
}
?>
<div class="wrap shopos-wrap">
	<h1><?php esc_html_e( 'ShopOS Tools', 'shopos-core' ); ?></h1>

	<?php if ( $just_imported_flag ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Import complete.', 'shopos-core' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $just_deleted_flag ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Legacy plugins deactivated and deleted.', 'shopos-core' ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Legacy plugin import', 'shopos-core' ); ?></h2>
	<p><?php esc_html_e( 'ShopOS Core can import data from the legacy plugins it replaces. Importing is non-destructive — legacy plugin files remain installed until you run the delete step below.', 'shopos-core' ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Module', 'shopos-core' ); ?></th>
				<th><?php esc_html_e( 'Legacy plugin', 'shopos-core' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopos-core' ); ?></th>
				<th><?php esc_html_e( 'Imported', 'shopos-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $scan as $module_id => $row ) : ?>
				<?php
				$label   = isset( $row['module_label'] ) ? $row['module_label'] : $module_id;
				$file    = isset( $row['file'] ) ? $row['file'] : '';
				$has_imp = ! empty( $row['has_importer'] );
				$inst    = ! empty( $row['installed'] );
				$active  = ! empty( $row['active'] );
				$in_rec  = isset( $imported[ $module_id ] );
				?>
				<tr>
					<td><strong><?php echo esc_html( $label ); ?></strong></td>
					<td><code><?php echo esc_html( $file ? $file : '—' ); ?></code></td>
					<td>
						<?php if ( ! $has_imp ) : ?>
							<em><?php esc_html_e( 'No importer', 'shopos-core' ); ?></em>
						<?php elseif ( $active ) : ?>
							<span class="shopos-dot shopos-dot-amber"></span>
							<?php esc_html_e( 'Legacy plugin active', 'shopos-core' ); ?>
						<?php elseif ( $inst ) : ?>
							<span class="shopos-dot shopos-dot-amber"></span>
							<?php esc_html_e( 'Installed (inactive)', 'shopos-core' ); ?>
						<?php else : ?>
							<span class="shopos-dot shopos-dot-green"></span>
							<?php esc_html_e( 'Not installed', 'shopos-core' ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $in_rec ) : ?>
							<span class="shopos-dot shopos-dot-green"></span>
							<?php echo esc_html( isset( $imported[ $module_id ]['at'] ) ? $imported[ $module_id ]['at'] : '' ); ?>
							<?php if ( ! empty( $imported[ $module_id ]['message'] ) ) : ?>
								<br><small><?php echo esc_html( $imported[ $module_id ]['message'] ); ?></small>
							<?php endif; ?>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
		<?php wp_nonce_field( \ShopOS\Core\Core\Legacy_Importer::NONCE_IMPORT ); ?>
		<input type="hidden" name="action" value="shopos_legacy_import"/>
		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Run legacy import', 'shopos-core' ); ?>
		</button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px;" onsubmit="return confirm('<?php echo esc_js( __( 'This will deactivate and permanently delete every detected legacy plugin. Continue?', 'shopos-core' ) ); ?>');">
		<?php wp_nonce_field( \ShopOS\Core\Core\Legacy_Importer::NONCE_DELETE ); ?>
		<input type="hidden" name="action" value="shopos_legacy_delete"/>
		<button type="submit" class="button button-secondary" <?php disabled( ! $any_installed ); ?>>
			<?php esc_html_e( 'Deactivate & delete legacy plugins', 'shopos-core' ); ?>
		</button>
		<?php if ( ! $any_installed ) : ?>
			<p class="description"><?php esc_html_e( 'No legacy plugins detected. Nothing to delete.', 'shopos-core' ); ?></p>
		<?php endif; ?>
	</form>

	<?php include SHOPOS_CORE_PATH . 'src/Admin/views/settings-tools-section.php'; ?>

	<hr style="margin:32px 0;"/>

	<h2><?php esc_html_e( 'Recent log', 'shopos-core' ); ?></h2>

	<?php if ( empty( $entries ) ) : ?>
		<p><em><?php esc_html_e( 'No log entries yet.', 'shopos-core' ); ?></em></p>
	<?php else : ?>
		<pre class="shopos-log"><?php
		foreach ( array_reverse( $entries ) as $entry ) {
			printf(
				"[%s] %s: %s\n",
				esc_html( $entry['time'] ),
				esc_html( strtoupper( $entry['level'] ) ),
				esc_html( $entry['message'] )
			);
		}
		?></pre>
	<?php endif; ?>
</div>
