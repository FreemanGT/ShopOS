<?php
/**
 * Side Cart — drawer body template.
 *
 * Rendered from the live cart by Module::render_body() and returned by both the
 * AJAX endpoint and the WC cart fragment. Theme-overridable at
 * `shopos/side_cart/body.php`.
 *
 * @var \ShopOS\Core\Modules\SideCart\Module $module    Owning module.
 * @var \WC_Cart                             $cart      The cart.
 * @var array                                $meter     Meter state (Meter::compute or ['active'=>false]).
 * @var int[]                                $recommend Recommendation product ids.
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

/** @var callable $t Label resolver shortcut. */
$t = static function ( $key ) {
	return \ShopOS\Core\Modules\SideCart\Labels::get( $key );
};

$items   = $cart->get_cart();
$removed = $cart->get_removed_cart_contents();
?>

<?php if ( ! empty( $meter['active'] ) ) : ?>
	<div class="shopos-side-cart__meter<?php echo $meter['reached'] ? ' is-reached' : ''; ?>">
		<p class="shopos-side-cart__meter-text">
			<?php
			if ( $meter['reached'] ) {
				echo esc_html( $t( 'free_ship_reached' ) );
			} else {
				printf(
					esc_html( $t( 'free_ship_remaining' ) ),
					wp_kses_post( wc_price( $meter['remaining'] ) )
				);
			}
			?>
		</p>
		<div class="shopos-side-cart__meter-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $meter['percent'] ); ?>">
			<span class="shopos-side-cart__meter-fill" style="inline-size:<?php echo esc_attr( (string) $meter['percent'] ); ?>%"></span>
		</div>
	</div>
<?php endif; ?>

<?php foreach ( $removed as $key => $item ) : ?>
	<div class="shopos-side-cart__removed" data-shopos-sc-removed>
		<span><?php echo esc_html( $t( 'removed' ) ); ?></span>
		<button type="button" class="shopos-side-cart__undo" data-shopos-sc-restore="<?php echo esc_attr( $key ); ?>">
			<?php echo esc_html( $t( 'undo' ) ); ?>
		</button>
	</div>
<?php endforeach; ?>

<?php if ( empty( $items ) ) : ?>
	<p class="shopos-side-cart__empty"><?php echo esc_html( $t( 'empty' ) ); ?></p>
<?php else : ?>
	<ul class="shopos-side-cart__items">
		<?php
		foreach ( $items as $key => $item ) :
			$product = isset( $item['data'] ) ? $item['data'] : null;
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$permalink = $product->is_visible() ? $product->get_permalink( $item ) : '';
			?>
			<li class="shopos-side-cart__item">
				<a class="shopos-side-cart__item-thumb" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-escaped markup. ?>
				</a>
				<div class="shopos-side-cart__item-detail">
					<a class="shopos-side-cart__item-name" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo esc_html( $product->get_name() ); ?>
					</a>
					<span class="shopos-side-cart__item-meta">
						<?php echo esc_html( $item['quantity'] . ' × ' ); ?>
						<?php echo wp_kses_post( wc_price( (float) $product->get_price() ) ); ?>
					</span>
				</div>
				<div class="shopos-side-cart__item-line">
					<?php echo wp_kses_post( $cart->get_product_subtotal( $product, $item['quantity'] ) ); ?>
					<button type="button" class="shopos-side-cart__remove shopos-ui-iconbtn shopos-ui-iconbtn--sm" data-shopos-sc-remove="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $t( 'remove' ) ); ?>">
						<svg width="12" height="12" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2 2L12 12M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
					</button>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<form class="shopos-side-cart__coupon" data-shopos-sc-coupon-form>
		<input type="text" class="shopos-side-cart__coupon-input" data-shopos-sc-coupon placeholder="<?php echo esc_attr( $t( 'coupon_placeholder' ) ); ?>" autocomplete="off" />
		<button type="submit" class="shopos-side-cart__coupon-apply"><?php echo esc_html( $t( 'apply' ) ); ?></button>
	</form>

	<?php $applied = $cart->get_applied_coupons(); ?>
	<?php if ( ! empty( $applied ) ) : ?>
		<ul class="shopos-side-cart__coupons">
			<?php foreach ( $applied as $code ) : ?>
				<li class="shopos-side-cart__coupon-tag">
					<span><?php echo esc_html( wc_cart_totals_coupon_label( $code, false ) ); ?></span>
					<button type="button" class="shopos-side-cart__coupon-remove" data-shopos-sc-remove-coupon="<?php echo esc_attr( $code ); ?>" aria-label="<?php echo esc_attr( $t( 'remove' ) ); ?>">×</button>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="shopos-side-cart__totals">
		<span class="shopos-side-cart__subtotal-label"><?php echo esc_html( $t( 'subtotal' ) ); ?></span>
		<span class="shopos-side-cart__subtotal-value"><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></span>
	</div>

	<div class="shopos-side-cart__actions">
		<a class="shopos-side-cart__checkout button" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php echo esc_html( $t( 'checkout' ) ); ?></a>
		<a class="shopos-side-cart__view-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php echo esc_html( $t( 'view_cart' ) ); ?></a>
	</div>

	<?php if ( ! empty( $recommend ) ) : ?>
		<div class="shopos-side-cart__recommends">
			<h3 class="shopos-side-cart__recommends-title"><?php echo esc_html( $t( 'recommends' ) ); ?></h3>
			<ul class="shopos-side-cart__recommends-list">
				<?php
				foreach ( $recommend as $rid ) :
					$rec = wc_get_product( $rid );
					if ( ! $rec instanceof \WC_Product || ! $rec->is_purchasable() || ! $rec->is_in_stock() ) {
						continue;
					}
					?>
					<li class="shopos-side-cart__rec">
						<a class="shopos-side-cart__rec-thumb" href="<?php echo esc_url( $rec->get_permalink() ); ?>">
							<?php echo $rec->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-escaped markup. ?>
						</a>
						<div class="shopos-side-cart__rec-detail">
							<a class="shopos-side-cart__rec-name" href="<?php echo esc_url( $rec->get_permalink() ); ?>"><?php echo esc_html( $rec->get_name() ); ?></a>
							<span class="shopos-side-cart__rec-price"><?php echo wp_kses_post( $rec->get_price_html() ); ?></span>
						</div>
						<?php if ( $rec->is_type( 'simple' ) ) : ?>
							<a class="shopos-side-cart__rec-add button add_to_cart_button ajax_add_to_cart" href="?add-to-cart=<?php echo esc_attr( (string) $rec->get_id() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( (string) $rec->get_id() ); ?>" rel="nofollow"><?php echo esc_html( $t( 'add' ) ); ?></a>
						<?php else : ?>
							<a class="shopos-side-cart__rec-add button" href="<?php echo esc_url( $rec->get_permalink() ); ?>"><?php echo esc_html( $t( 'add' ) ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
<?php endif; ?>
