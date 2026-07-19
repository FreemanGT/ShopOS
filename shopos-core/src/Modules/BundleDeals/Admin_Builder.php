<?php
/**
 * Bundle Deals — the visual bundle builder.
 *
 * Injects a card-based repeater onto the ShopOS → Bundle Deals settings page
 * (via `shopos_core/module_page/bundle_deals`) and saves it through
 * admin-post.php + the pure {@see Bundle_Config::sanitize()} (the ShopFilters
 * `Admin_Config_Page` PRG pattern). Each card is one bundle: pick a type and
 * only that type's fields show (toggled by bundle-admin.js), set the target by
 * category / tag / product ids, and save.
 *
 * The card markup is produced once by {@see card_html()} so the server-rendered
 * existing bundles and the JS "add bundle" `<template>` are byte-identical —
 * the template is the same method rendered with an `__i__` index placeholder.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

use ShopOS\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Bundle builder admin surface.
 */
final class Admin_Builder {

	const NONCE  = 'shopos_core_bundle_deals_save';
	const ACTION = 'shopos_core_bundle_deals_save';
	const HANDLE = 'shopos-core-bundle-admin';

	/** Fixed tier slots per tiered bundle (blank slots are dropped on save). */
	const TIER_SLOTS = 4;

	/**
	 * Register the renderer, the save handler and the admin assets.
	 */
	public function register() {
		add_action( 'shopos_core/module_page/bundle_deals', array( $this, 'render' ), 30 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the builder assets on the Bundle Deals page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, 'bundle_deals' ) ) {
			return;
		}
		$fs  = SHOPOS_CORE_PATH . 'src/Modules/BundleDeals/assets/';
		$url = SHOPOS_CORE_URL . 'src/Modules/BundleDeals/assets/';
		wp_enqueue_style( self::HANDLE, \ShopOS\Core\Core\Module_Base::pick_min_url( $fs, $url, 'css/bundle-admin.css' ), array(), SHOPOS_CORE_VERSION );
		wp_enqueue_script( self::HANDLE, \ShopOS\Core\Core\Module_Base::pick_min_url( $fs, $url, 'js/bundle-admin.js' ), array(), SHOPOS_CORE_VERSION, true );
	}

	/**
	 * Persist a builder submission.
	 */
	public function handle_save() {
		Security::verify_nonce( self::NONCE );
		Security::require_cap( 'manage_woocommerce' );

		$raw = ( isset( $_POST['bundles'] ) && is_array( $_POST['bundles'] ) )
			? wp_unslash( $_POST['bundles'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalised by Bundle_Config::sanitize() (whitelists types + coerces every value).
			: array();

		update_option( Bundle_Config::OPTION, Bundle_Config::sanitize( $raw ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'shopos-bundle_deals',
					'bundles-updated' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the builder on the module settings page.
	 */
	public function render() {
		$bundles = Bundle_Config::all();
		$cats    = $this->terms_choices( 'product_cat', true );
		$tags    = $this->terms_choices( 'product_tag', false );

		$action_url  = admin_url( 'admin-post.php' );
		$action_name = self::ACTION;
		$nonce       = self::NONCE;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
		$saved = isset( $_GET['bundles-updated'] );

		// Template card (blank, placeholder index) for the JS "add bundle" clone.
		$blank_card = $this->card_html( '__i__', array(), $cats, $tags );

		include SHOPOS_CORE_PATH . 'src/Modules/BundleDeals/templates/admin-builder.php';
	}

	/**
	 * One bundle card. Used for both saved rows and the JS clone template, so
	 * the two can never drift.
	 *
	 * @param string|int $index  Row index (or '__i__' placeholder).
	 * @param array      $bundle Bundle (empty for a blank card).
	 * @param array      $cats   Category choices (id, name, depth).
	 * @param array      $tags   Tag choices (id, name).
	 * @return string
	 */
	public function card_html( $index, array $bundle, array $cats, array $tags ) {
		$n     = 'bundles[' . $index . ']';
		$type  = (string) ( $bundle['type'] ?? 'tiered' );
		$scope = (array) ( $bundle['scope'] ?? array() );

		$types = array(
			'tiered'   => __( 'Volume / tiered discount', 'shopos-core' ),
			'bogo'     => __( 'Buy X get Y (BOGO)', 'shopos-core' ),
			'curated'  => __( 'Frequently bought together', 'shopos-core' ),
			'mixmatch' => __( 'Mix & match', 'shopos-core' ),
		);

		$html  = '<div class="shopos-bundle-card" data-type="' . esc_attr( $type ) . '">';
		$html .= '<input type="hidden" name="' . esc_attr( $n ) . '[id]" value="' . esc_attr( (string) ( $bundle['id'] ?? '' ) ) . '" />';

		// Head: title + type + enabled + remove.
		$html .= '<div class="shopos-bundle-card__head">';
		$html .= '<label class="shopos-bundle-card__enable"><input type="checkbox" name="' . esc_attr( $n ) . '[enabled]" value="1" ' . checked( ! empty( $bundle['enabled'] ), true, false ) . ' /> ' . esc_html__( 'On', 'shopos-core' ) . '</label>';
		$html .= '<input type="text" class="shopos-bundle-card__title" name="' . esc_attr( $n ) . '[title]" value="' . esc_attr( (string) ( $bundle['title'] ?? '' ) ) . '" placeholder="' . esc_attr__( 'Bundle name (internal)', 'shopos-core' ) . '" />';
		$html .= '<select class="shopos-bundle-card__type" name="' . esc_attr( $n ) . '[type]" data-bundle-type>' . $this->options( $types, $type ) . '</select>';
		$html .= '<button type="button" class="button-link shopos-bundle-card__remove" data-bundle-remove aria-label="' . esc_attr__( 'Remove bundle', 'shopos-core' ) . '">&times;</button>';
		$html .= '</div>';

		$html .= '<p class="shopos-bundle-card__preview" data-bundle-preview></p>';

		// Type-specific panels (all present; JS shows only the active one).
		$html .= '<div class="shopos-bundle-panel" data-panel="tiered">' . $this->tier_fields( $n, (array) ( $bundle['tiers'] ?? array() ) ) . '</div>';
		$html .= '<div class="shopos-bundle-panel" data-panel="bogo">' . $this->bogo_fields( $n, (array) ( $bundle['bogo'] ?? array() ) ) . '</div>';
		$html .= '<div class="shopos-bundle-panel" data-panel="curated">' . $this->curated_fields( $n, (array) ( $bundle['curated'] ?? array() ) ) . '</div>';
		$html .= '<div class="shopos-bundle-panel" data-panel="mixmatch">' . $this->mixmatch_fields( $n, (array) ( $bundle['mixmatch'] ?? array() ) ) . '</div>';

		// Targeting (shared) — hidden for curated, which targets its own set.
		$html .= '<div class="shopos-bundle-scope" data-scope>' . $this->scope_fields( $n, $scope, $cats, $tags ) . '</div>';

		// Priority.
		$html .= '<p class="shopos-bundle-card__priority"><label>' . esc_html__( 'Priority (lower wins ties)', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[priority]" value="' . esc_attr( (string) ( $bundle['priority'] ?? 0 ) ) . '" step="1" style="width:5em;" /></label></p>';

		$html .= '</div>';

		return $html;
	}

	/* -----------------------------------------------------------------
	 * Field-group builders
	 * ----------------------------------------------------------------- */

	/**
	 * Fixed tier slots.
	 *
	 * @param string $n     Field-name stem.
	 * @param array  $tiers Saved tiers.
	 * @return string
	 */
	private function tier_fields( $n, array $tiers ) {
		$kinds = array(
			'percent' => __( '% off', 'shopos-core' ),
			'fixed'   => __( 'amount off', 'shopos-core' ),
		);
		$html  = '<p class="description">' . esc_html__( 'Combined quantity of matching items unlocks the highest met tier.', 'shopos-core' ) . '</p>';
		$html .= '<table class="shopos-bundle-tiers"><thead><tr><th>' . esc_html__( 'Min qty', 'shopos-core' ) . '</th><th>' . esc_html__( 'Discount', 'shopos-core' ) . '</th><th></th></tr></thead><tbody>';
		for ( $j = 0; $j < self::TIER_SLOTS; $j++ ) {
			$tier   = $tiers[ $j ] ?? array();
			$tn     = $n . '[tiers][' . $j . ']';
			$html  .= '<tr>'
				. '<td><input type="number" name="' . esc_attr( $tn ) . '[min]" value="' . esc_attr( isset( $tier['min'] ) ? (string) $tier['min'] : '' ) . '" min="1" step="1" placeholder="—" style="width:6em;" /></td>'
				. '<td><input type="number" name="' . esc_attr( $tn ) . '[amount]" value="' . esc_attr( isset( $tier['amount'] ) ? (string) $tier['amount'] : '' ) . '" min="0" step="0.01" style="width:7em;" /> '
				. '<select name="' . esc_attr( $tn ) . '[kind]">' . $this->options( $kinds, (string) ( $tier['kind'] ?? 'percent' ) ) . '</select></td>'
				. '<td></td>'
				. '</tr>';
		}
		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * BOGO fields.
	 *
	 * @param string $n    Field-name stem.
	 * @param array  $bogo Saved bogo.
	 * @return string
	 */
	private function bogo_fields( $n, array $bogo ) {
		return '<p class="shopos-bundle-inline">'
			. '<label>' . esc_html__( 'Buy', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[bogo][buy]" value="' . esc_attr( (string) ( $bogo['buy'] ?? 2 ) ) . '" min="1" step="1" style="width:5em;" /></label> '
			. '<label>' . esc_html__( 'Get', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[bogo][get]" value="' . esc_attr( (string) ( $bogo['get'] ?? 1 ) ) . '" min="1" step="1" style="width:5em;" /></label> '
			. '<label>' . esc_html__( 'at', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[bogo][discount]" value="' . esc_attr( (string) ( $bogo['discount'] ?? 100 ) ) . '" min="0" max="100" step="1" style="width:5em;" /> % ' . esc_html__( 'off', 'shopos-core' ) . '</label>'
			. '</p><p class="description">' . esc_html__( '100% = the "get" items are free. Applies to the cheapest matching units first.', 'shopos-core' ) . '</p>';
	}

	/**
	 * Curated (frequently-bought-together) fields.
	 *
	 * @param string $n       Field-name stem.
	 * @param array  $curated Saved curated.
	 * @return string
	 */
	private function curated_fields( $n, array $curated ) {
		$kinds = array(
			'percent' => __( '% off the set', 'shopos-core' ),
			'fixed'   => __( 'amount off the set', 'shopos-core' ),
		);
		return '<p><label>' . esc_html__( 'Product IDs in the set (comma separated)', 'shopos-core' ) . '<br />'
			. '<input type="text" class="regular-text" name="' . esc_attr( $n ) . '[curated][products]" value="' . esc_attr( implode( ',', array_map( 'intval', (array) ( $curated['products'] ?? array() ) ) ) ) . '" placeholder="e.g. 12, 34, 56" /></label></p>'
			. '<p class="shopos-bundle-inline"><label>' . esc_html__( 'Discount', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[curated][amount]" value="' . esc_attr( (string) ( $curated['amount'] ?? 10 ) ) . '" min="0" step="0.01" style="width:7em;" /></label> '
			. '<select name="' . esc_attr( $n ) . '[curated][kind]">' . $this->options( $kinds, (string) ( $curated['kind'] ?? 'percent' ) ) . '</select></p>'
			. '<p class="description">' . esc_html__( 'The discount applies only when every product in the set is in the cart.', 'shopos-core' ) . '</p>';
	}

	/**
	 * Mix-&-match fields.
	 *
	 * @param string $n  Field-name stem.
	 * @param array  $mm Saved mixmatch.
	 * @return string
	 */
	private function mixmatch_fields( $n, array $mm ) {
		$kinds = array(
			'percent'     => __( '% off each', 'shopos-core' ),
			'fixed'       => __( 'amount off each', 'shopos-core' ),
			'fixed_price' => __( 'fixed price for the set', 'shopos-core' ),
		);
		return '<p class="shopos-bundle-inline">'
			. '<label>' . esc_html__( 'Buy any', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[mixmatch][need]" value="' . esc_attr( (string) ( $mm['need'] ?? 3 ) ) . '" min="1" step="1" style="width:5em;" /></label> '
			. '<label>' . esc_html__( 'for', 'shopos-core' ) . ' <input type="number" name="' . esc_attr( $n ) . '[mixmatch][amount]" value="' . esc_attr( (string) ( $mm['amount'] ?? 0 ) ) . '" min="0" step="0.01" style="width:7em;" /></label> '
			. '<select name="' . esc_attr( $n ) . '[mixmatch][kind]">' . $this->options( $kinds, (string) ( $mm['kind'] ?? 'percent' ) ) . '</select>'
			. '</p><p class="description">' . esc_html__( 'Shoppers pick any items from the targeted collection below.', 'shopos-core' ) . '</p>';
	}

	/**
	 * Shared targeting fields.
	 *
	 * @param string $n     Field-name stem.
	 * @param array  $scope Saved scope.
	 * @param array  $cats  Category choices.
	 * @param array  $tags  Tag choices.
	 * @return string
	 */
	private function scope_fields( $n, array $scope, array $cats, array $tags ) {
		$html  = '<h4>' . esc_html__( 'Applies to', 'shopos-core' ) . '</h4>';
		$html .= '<div class="shopos-bundle-scope__grid">';
		$html .= '<label>' . esc_html__( 'Categories', 'shopos-core' ) . $this->term_select( $n . '[scope][categories][]', $cats, (array) ( $scope['categories'] ?? array() ) ) . '</label>';
		$html .= '<label>' . esc_html__( 'Tags', 'shopos-core' ) . $this->term_select( $n . '[scope][tags][]', $tags, (array) ( $scope['tags'] ?? array() ) ) . '</label>';
		$html .= '<label>' . esc_html__( 'Exclude categories', 'shopos-core' ) . $this->term_select( $n . '[scope][exclude_categories][]', $cats, (array) ( $scope['exclude_categories'] ?? array() ) ) . '</label>';
		$html .= '<label>' . esc_html__( 'Product IDs', 'shopos-core' ) . '<input type="text" name="' . esc_attr( $n ) . '[scope][products]" value="' . esc_attr( implode( ',', array_map( 'intval', (array) ( $scope['products'] ?? array() ) ) ) ) . '" placeholder="' . esc_attr__( 'comma separated', 'shopos-core' ) . '" /></label>';
		$html .= '</div>';

		return $html;
	}

	/* -----------------------------------------------------------------
	 * Small HTML helpers
	 * ----------------------------------------------------------------- */

	/**
	 * `<option>` list for a select.
	 *
	 * @param array<string,string> $choices value => label.
	 * @param string               $current Selected value.
	 * @return string
	 */
	private function options( array $choices, $current ) {
		$html = '';
		foreach ( $choices as $value => $label ) {
			$html .= '<option value="' . esc_attr( (string) $value ) . '" ' . selected( (string) $value, (string) $current, false ) . '>' . esc_html( (string) $label ) . '</option>';
		}
		return $html;
	}

	/**
	 * A multi-select of term choices.
	 *
	 * @param string $name     Field name.
	 * @param array  $choices  Term choices (id, name, [depth]).
	 * @param int[]  $selected Selected ids.
	 * @return string
	 */
	private function term_select( $name, array $choices, array $selected ) {
		$selected = array_map( 'intval', $selected );
		$html     = '<select name="' . esc_attr( $name ) . '" multiple size="4">';
		foreach ( $choices as $choice ) {
			$depth = isset( $choice['depth'] ) ? (int) $choice['depth'] : 0;
			$html .= '<option value="' . esc_attr( (string) $choice['id'] ) . '" ' . selected( in_array( (int) $choice['id'], $selected, true ), true, false ) . '>'
				. esc_html( str_repeat( "\u{2014} ", $depth ) . $choice['name'] )
				. '</option>';
		}
		$html .= '</select>';

		return $html;
	}

	/**
	 * Term choices for a taxonomy (categories depth-walked, tags flat).
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param bool   $tree     Whether to depth-order (categories).
	 * @return array<int,array{id:int,name:string,depth:int}>
	 */
	private function terms_choices( $taxonomy, $tree ) {
		if ( ! function_exists( 'get_terms' ) ) {
			return array();
		}
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}
		if ( ! $tree ) {
			$flat = array();
			foreach ( $terms as $t ) {
				$flat[] = array( 'id' => (int) $t->term_id, 'name' => (string) $t->name, 'depth' => 0 );
			}
			return $flat;
		}

		$by_parent = array();
		foreach ( $terms as $t ) {
			$by_parent[ (int) $t->parent ][] = $t;
		}
		$ordered = array();
		$walk    = static function ( $parent, $depth ) use ( &$walk, &$ordered, $by_parent ) {
			if ( empty( $by_parent[ $parent ] ) ) {
				return;
			}
			foreach ( $by_parent[ $parent ] as $t ) {
				$ordered[] = array( 'id' => (int) $t->term_id, 'name' => (string) $t->name, 'depth' => (int) $depth );
				$walk( (int) $t->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );

		return $ordered;
	}
}
