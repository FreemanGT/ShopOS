<?php
/**
 * Card Image Effects module (id `hover_swap`).
 *
 * Enhances the product image on shop / archive cards. The `card_image_mode`
 * setting picks one behaviour:
 *
 *   - `none`           — leave the card image alone.
 *   - `hover_swap`     — hovering a card cross-fades the main image to the
 *                        product's second (gallery) image (pure CSS).
 *   - `gallery_slider` — replace the card image with a small swipeable slider
 *                        of all the product's images (swipe / drag, with
 *                        optional hover arrows).
 *
 * Both behaviours inject on `woocommerce_before_shop_loop_item_title`, which
 * the standard WC loop and the Freeman ProductSlider grid both render through
 * `content-product.php` — one injection point covers every card site-wide.
 *
 * The module id stays `hover_swap` (frozen — Hard Rule #2); the label widened
 * once the slider mode joined. This is NOT the VariationSwatches
 * `card_image_swap` feature (swatch-click → variation image).
 *
 * Activation is a single control: enable the module (Freeman → Modules) and
 * pick the Card image mode. The mode defaults to `none`, so the module ships
 * dark — no storefront output until a mode is chosen.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\HoverSwap;

use Freeman\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * @return string
	 */
	public function id() {
		return 'hover_swap';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Card Image Effects', 'freeman-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Enhances the product image on shop / archive cards: hover cross-fade to the second image, or a small swipeable slider of all images. Pick the behaviour with the Card image mode setting.', 'freeman-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' => true );
	}

	/**
	 * Settings schema. The slider sub-settings only take effect in
	 * `gallery_slider` mode.
	 *
	 * @return array
	 */
	public function settings_schema() {
		return array(
			'card_image_mode' => array(
				'label'       => __( 'Card image mode', 'freeman-core' ),
				'type'        => 'select',
				'choices'     => array(
					'none'           => __( 'None — leave the card image alone', 'freeman-core' ),
					'hover_swap'     => __( 'Hover swap — fade to the second image on hover', 'freeman-core' ),
					'gallery_slider' => __( 'Gallery slider — swipeable slider of all images', 'freeman-core' ),
				),
				'default'     => 'none',
				'description' => __( 'What happens to the product image on shop / archive cards.', 'freeman-core' ),
			),
			'slider_arrows'   => array(
				'label'          => __( 'Slider arrows', 'freeman-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Show prev / next arrows (fade in on hover)', 'freeman-core' ),
				'description'    => __( 'Gallery slider mode only. Turn off for a cleaner, swipe-only look.', 'freeman-core' ),
				'default'        => 1,
			),
		);
	}

	/**
	 * Boot — routed entirely by the `card_image_mode` setting. The module
	 * being enabled (Freeman → Modules) is the on/off; the mode defaults to
	 * `none` so an enabled module shows nothing until a mode is chosen.
	 */
	public function boot() {
		$mode = (string) $this->get_option( 'card_image_mode', 'none' );

		if ( 'hover_swap' === $mode ) {
			( new Frontend( $this ) )->register();
		} elseif ( 'gallery_slider' === $mode ) {
			( new Gallery_Slider( $this ) )->register();
		}
	}
}
