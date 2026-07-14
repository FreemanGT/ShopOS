<?php
/**
 * Variation Swatches — locale-aware buy-box string resolver.
 *
 * The legacy buy-box templates + the shop/PDP JS i18n payloads shipped with
 * their strings hard-coded as Hebrew literals wrapped in __(). Because the
 * literal *is* the msgid, an English-locale site still rendered Hebrew — there
 * was no He/En switch. This resolver returns the Hebrew wording on a Hebrew
 * site (locale prefix `he`) and the English wording everywhere else, so the
 * buy box follows the site language. It deliberately does NOT use __(): the
 * switch must be deterministic per site locale, not dependent on a .mo catalog.
 *
 * Unlike QuickView's Labels, there are no admin-override options here — the
 * owner asked for automatic He/En, not per-string control (kept scope tight,
 * zero new option keys).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\VariationSwatches;

defined( 'ABSPATH' ) || exit;

/**
 * Locale-aware label resolver.
 */
final class Labels {

	/**
	 * Whether the current site locale is Hebrew.
	 *
	 * Prefers determine_locale() (honours per-request locale switches); falls
	 * back to get_locale() where determine_locale() is unavailable.
	 *
	 * @return bool
	 */
	public static function is_hebrew() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return 0 === strpos( (string) $locale, 'he' );
	}

	/**
	 * Resolve a label by key for the current locale. Unknown key → ''.
	 *
	 * @param string $key Short key (e.g. 'add_to_cart').
	 * @return string
	 */
	public static function get( $key ) {
		$map = self::is_hebrew() ? self::he() : self::en();
		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}

	/**
	 * English wording.
	 *
	 * @return array<string,string>
	 */
	private static function en() {
		return array(
			'add_to_cart'    => 'Add to cart',
			'buy_now'        => 'Buy now',
			'out_of_stock'   => 'Out of stock',
			'from_price'     => 'Starting from:',
			'choose_option'  => 'Choose an option',
			'select_options' => 'Select options',
			'quantity'       => 'Quantity',
			'not_available'  => 'This combination is not available',
			'unavailable'    => 'Unavailable',
			'added_to_cart'  => 'Added to cart',
			'error_generic'  => 'Something went wrong, please try again',
			'close'          => 'Close',
			'notices'        => 'Shop notices',
		);
	}

	/**
	 * Hebrew wording (the suite's original buy-box literals).
	 *
	 * @return array<string,string>
	 */
	private static function he() {
		return array(
			'add_to_cart'    => 'הוספה לעגלה',
			'buy_now'        => 'קנה עכשיו',
			'out_of_stock'   => 'אזל מהמלאי',
			'from_price'     => 'החל מ:',
			'choose_option'  => 'בחר/י אפשרות',
			'select_options' => 'בחר/י אפשרות',
			'quantity'       => 'כמות',
			'not_available'  => 'הצירוף הזה אינו זמין',
			'unavailable'    => 'לא זמין',
			'added_to_cart'  => 'נוסף לעגלה',
			'error_generic'  => 'שגיאה, נסו שוב',
			'close'          => 'סגירה',
			'notices'        => 'הודעות חנות',
		);
	}
}
