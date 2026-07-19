<?php
/**
 * Bundle Deals — the builder repeater.
 *
 * Rendered by Admin_Builder::render(). Expects:
 *
 * @var array  $bundles     Saved bundles.
 * @var array  $cats        Category choices (id, name, depth).
 * @var array  $tags        Tag choices (id, name).
 * @var string $action_url  admin-post.php URL.
 * @var string $action_name admin_post action.
 * @var string $nonce       Nonce action.
 * @var bool   $saved       Whether the page just saved.
 * @var string $blank_card  Blank card HTML for the JS "add bundle" clone.
 * @var \ShopOS\Core\Modules\BundleDeals\Admin_Builder $this
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;
?>
<h2><?php esc_html_e( 'Bundles', 'shopos-core' ); ?></h2>

<?php if ( ! empty( $saved ) ) : ?>
	<div class="notice notice-success inline"><p><?php esc_html_e( 'Bundles saved.', 'shopos-core' ); ?></p></div>
<?php endif; ?>

<p class="description">
	<?php esc_html_e( 'Create discount bundles. Each bundle has a type, a target (categories, tags or product IDs), and its own discount. Turn a bundle off to pause it without deleting it.', 'shopos-core' ); ?>
</p>

<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="shopos-bundle-builder">
	<input type="hidden" name="action" value="<?php echo esc_attr( $action_name ); ?>" />
	<?php wp_nonce_field( $nonce ); ?>

	<div id="shopos-bundle-list" class="shopos-bundle-list">
		<?php
		foreach ( array_values( $bundles ) as $i => $bundle ) {
			echo $this->card_html( (string) $i, (array) $bundle, $cats, $tags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card_html returns escaped markup.
		}
		?>
	</div>

	<p>
		<button type="button" class="button button-secondary" id="shopos-bundle-add">
			<?php esc_html_e( '+ Add bundle', 'shopos-core' ); ?>
		</button>
	</p>

	<?php submit_button( __( 'Save bundles', 'shopos-core' ) ); ?>
</form>

<?php // The blank card the "Add bundle" button clones (index placeholder swapped by JS). ?>
<template id="shopos-bundle-tpl"><?php echo $blank_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card_html returns escaped markup. ?></template>
