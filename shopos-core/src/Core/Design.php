<?php
/**
 * ShopOS Design panel — a curated store-accent control that writes a handful of
 * `--shopos-ui-*` design tokens once and lets them flow to every module.
 *
 * The Phase-1 token bridge (shopos-theme) reads theme.json and emits the
 * `--shopos-ui-*` layer that ShopFilters / Search / QuickView / ProductPage /
 * the sliders all consume through `var( --shopos-ui-*, <fallback> )`. This panel
 * is the write-side counterpart: the owner picks an accent preset and, if they
 * want, overrides a small curated set of colour / radius tokens, and those
 * choices emit as an inline `:root { … }` block AFTER the theme's `tokens.css`
 * so they win suite-wide — on a shopos-theme site and a non-theme site alike.
 *
 * Deliberately curated, NOT a theme customizer: a short allow-list of
 * high-value, safe-to-change tokens (accent, ink, paper, hairline, one radius),
 * so this is a "set the store's accent" control, not a CSS editor. §4.3
 * (Elementor-only) is untouched — this sets semantic tokens, not page layout,
 * and never pushes into the block editor. The `--e-global-*` Elementor Style-Kit
 * values stay the fallback in the `var()` chains, never the target here.
 *
 * Gated by `shopos_core_design_panel_enabled` (default false, wired in
 * Plugin::boot()): off ⇒ no admin page and no inline CSS ⇒ byte-identical to
 * pre-panel output. Even when on, only tokens the owner actually changed are
 * emitted, so an untouched panel is still a no-op.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Design panel.
 */
final class Design {

	/** Admin submenu slug (child of the ShopOS menu). */
	const PAGE_SLUG = 'shopos-design';

	/** Settings group + option-name prefix. */
	const OPTION_GROUP  = 'shopos_core_design_group';
	const OPTION_PREFIX = 'shopos_core_design_';

	/** Inline-style handle for the front-end token emit. */
	const STYLE_HANDLE = 'shopos-design-tokens';

	/**
	 * Accent presets: slug => map of `--shopos-ui-*` token => value. Each preset
	 * repaints the accent slot; `default` contributes nothing (inherit the
	 * theme's own accent) so choosing it — the default — emits no CSS.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function presets() {
		return array(
			'default'    => array(),
			'terracotta' => array( '--shopos-ui-palette-gold' => '#b5532a' ),
			'forest'     => array( '--shopos-ui-palette-gold' => '#3f6b4f' ),
			'indigo'     => array( '--shopos-ui-palette-gold' => '#4a54b5' ),
			'plum'       => array( '--shopos-ui-palette-gold' => '#7a3f6b' ),
		);
	}

	/**
	 * Human labels for the accent presets (translated at call sites via the
	 * settings `choices` map).
	 *
	 * @return array<string,string>
	 */
	public static function preset_labels() {
		return array(
			'default'    => __( 'Theme default (gold)', 'shopos-core' ),
			'terracotta' => __( 'Terracotta', 'shopos-core' ),
			'forest'     => __( 'Forest', 'shopos-core' ),
			'indigo'     => __( 'Indigo', 'shopos-core' ),
			'plum'       => __( 'Plum', 'shopos-core' ),
		);
	}

	/**
	 * Curated colour overrides: option short-key => [ label, `--shopos-ui-*` token ].
	 * Each is a `color` field defaulting to '' (empty ⇒ inherit, nothing emitted).
	 * Semantic tokens (red/green/amber/info) are intentionally excluded — they
	 * carry good/warning/critical meaning, not brand.
	 *
	 * @return array<string,array{label:string,var:string}>
	 */
	public static function colour_fields() {
		return array(
			'col_gold'      => array( 'label' => __( 'Accent / primary', 'shopos-core' ), 'var' => '--shopos-ui-palette-gold' ),
			'col_ink'       => array( 'label' => __( 'Text', 'shopos-core' ), 'var' => '--shopos-ui-palette-ink' ),
			'col_ink_soft'  => array( 'label' => __( 'Text (soft)', 'shopos-core' ), 'var' => '--shopos-ui-palette-ink-soft' ),
			'col_paper'     => array( 'label' => __( 'Surface', 'shopos-core' ), 'var' => '--shopos-ui-palette-paper' ),
			'col_paper_alt' => array( 'label' => __( 'Surface (alt)', 'shopos-core' ), 'var' => '--shopos-ui-palette-paper-alt' ),
			'col_hairline'  => array( 'label' => __( 'Borders', 'shopos-core' ), 'var' => '--shopos-ui-palette-hairline' ),
		);
	}

	/**
	 * Corner-radius override bounds (a single `range` field, empty ⇒ inherit).
	 */
	const RADIUS_VAR = '--shopos-ui-radius-md';
	const RADIUS_MIN = 0;
	const RADIUS_MAX = 24;

	/**
	 * Default Style Kits typography slots feeding the theme's two font tokens
	 * (`--shopos-ui-font-body` / `--shopos-ui-font-display`). Must match the
	 * `--e-global-typography-<slot>-font-family` slot ids hardcoded in
	 * shopos-theme/assets/css/shopos-tokens.css.
	 */
	const KIT_SLOT_DEFAULTS = array(
		'body'    => 'sk_type_12',
		'display' => 'sk_type_2',
	);

	/**
	 * Resolved Style Kits typography slot ids (decisions §11 Ruling 8).
	 *
	 * De-hardcodes the sk_type_12/sk_type_2 mapping behind a Core option
	 * (`shopos_core_theme_kit_slots`, no UI) with a filterable value — the
	 * default ships current behaviour, so a store that never touches either
	 * lever changes nothing. Consumed by the theme's design-tokens bridge
	 * through a guarded `class_exists` read (Core absent ⇒ defaults ⇒ the
	 * bridge emits no re-map). Values are clamped to option-name-safe slugs
	 * because they are interpolated into inline CSS. Static on purpose —
	 * callable whether or not the design-panel flag booted this class
	 * (the Blueprint precedent).
	 *
	 * @return array{body:string,display:string}
	 */
	public static function kit_slots() {
		$value = get_option( 'shopos_core_theme_kit_slots', self::KIT_SLOT_DEFAULTS );
		$value = wp_parse_args( is_array( $value ) ? $value : array(), self::KIT_SLOT_DEFAULTS );

		/**
		 * Filters the Style Kits typography slot ids the theme maps to
		 * `--shopos-ui-font-body` / `--shopos-ui-font-display`.
		 *
		 * @param array{body:string,display:string} $value Slot ids.
		 */
		$value = apply_filters( 'shopos_core/theme/kit_slots', $value );

		$out = array();
		foreach ( self::KIT_SLOT_DEFAULTS as $key => $default ) {
			$slot        = isset( $value[ $key ] ) && is_string( $value[ $key ] )
				? preg_replace( '/[^a-z0-9_]/', '', strtolower( $value[ $key ] ) )
				: '';
			$out[ $key ] = '' !== $slot ? $slot : $default;
		}
		return $out;
	}

	/* -----------------------------------------------------------------
	 * Boot — register admin page + settings + the front-end emit.
	 * ----------------------------------------------------------------- */

	/**
	 * Register hooks. Called from Plugin::boot() only when the flag is on, so an
	 * off flag registers nothing at all.
	 */
	public function boot() {
		if ( is_admin() ) {
			// Priority 11: the Settings Hub registers the parent `shopos` menu at
			// the default 10, and Design boots before the hub in Plugin::boot().
			// Adding this submenu before its parent exists makes WordPress file
			// the page hook under `admin_page_*` instead of `shopos_page_*`, so
			// the menu entry renders but the page itself 403s.
			add_action( 'admin_menu', array( $this, 'register_menu' ), 11 );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}
		// Priority 30: after the theme token bridge's prio-21 emit, so overrides win.
		add_action( 'wp_enqueue_scripts', array( $this, 'emit_tokens' ), 30 );
	}

	/* -----------------------------------------------------------------
	 * Pure seams (unit-tested — no WordPress needed).
	 * ----------------------------------------------------------------- */

	/**
	 * Resolve saved options to the `--shopos-ui-*` => value map to emit. Starts
	 * from the chosen accent preset, then overlays any non-empty individual
	 * colour override (individual wins) and the radius override. Invalid values
	 * are dropped. Pure.
	 *
	 * @param array<string,mixed> $options short-key => saved value.
	 * @return array<string,string> css var => value.
	 */
	public static function resolve_values( array $options ) {
		$vars = array();

		// Accent preset base.
		$accent  = isset( $options['accent'] ) ? (string) $options['accent'] : 'default';
		$presets = self::presets();
		if ( isset( $presets[ $accent ] ) ) {
			foreach ( $presets[ $accent ] as $var => $val ) {
				$vars[ $var ] = $val;
			}
		}

		// Individual colour overrides (win over the preset).
		foreach ( self::colour_fields() as $key => $def ) {
			$val = isset( $options[ $key ] ) ? self::sanitize_hex( $options[ $key ] ) : '';
			if ( '' !== $val ) {
				$vars[ $def['var'] ] = $val;
			}
		}

		// Radius override.
		if ( isset( $options['radius'] ) && '' !== (string) $options['radius'] && is_numeric( $options['radius'] ) ) {
			$n = (int) $options['radius'];
			if ( $n >= self::RADIUS_MIN && $n <= self::RADIUS_MAX ) {
				$vars[ self::RADIUS_VAR ] = $n . 'px';
			}
		}

		return $vars;
	}

	/**
	 * Build the inline CSS block from a resolved var => value map. Re-validates
	 * every value (defence in depth over the option sanitisers) and drops any
	 * unsafe one; returns '' when nothing survives. Pure.
	 *
	 * @param array<string,string> $vars css var => value.
	 * @return string
	 */
	public static function build_css( array $vars ) {
		$decls = array();
		foreach ( $vars as $var => $val ) {
			if ( ! preg_match( '/^--shopos-ui-[a-z0-9-]+$/', (string) $var ) ) {
				continue;
			}
			$val = self::sanitize_value( $val );
			if ( '' !== $val ) {
				$decls[] = $var . ':' . $val . ';';
			}
		}
		return array() === $decls ? '' : ':root{' . implode( '', $decls ) . '}';
	}

	/**
	 * Validate a hex colour (3–8 hex digits), matching the Settings_Hub `color`
	 * sanitiser. Returns '' when not a hex colour.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_hex( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? $value : '';
	}

	/**
	 * Sanitize a value bound for an inline custom-property declaration — the same
	 * shape guard the theme token bridge uses: reject anything that can terminate
	 * the declaration or open markup, then allow only the colour / length
	 * character set. Returns '' if unsafe / empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_value( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/[;{}<>@\\\\]/', $value ) ) {
			return '';
		}
		if ( ! preg_match( '~^[a-zA-Z0-9#%.,()/ _-]+$~', $value ) ) {
			return '';
		}
		return $value;
	}

	/* -----------------------------------------------------------------
	 * Front-end emit.
	 * ----------------------------------------------------------------- */

	/**
	 * Emit the resolved token overrides as an inline `:root { … }` block after
	 * the theme's `tokens.css`. Nothing to emit ⇒ nothing enqueued.
	 */
	public function emit_tokens() {
		/**
		 * Filters whether the Design panel emits its inline token overrides.
		 * Return false to disable (rollback lever), mirroring the theme bridge's
		 * `shopos_theme_design_tokens_enabled`.
		 *
		 * @since 1.35.0
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'shopos_core/design/tokens_css_enabled', true ) ) {
			return;
		}

		$css = self::build_css( self::resolve_values( self::saved_options() ) );
		if ( '' === $css ) {
			return;
		}

		// Inline-only style (src === false). Depend on the theme's token handle
		// when present so this prints after it; harmless when absent.
		$deps = wp_style_is( 'shopos-tokens', 'registered' ) ? array( 'shopos-tokens' ) : array();
		wp_register_style( self::STYLE_HANDLE, false, $deps );
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, $css );
	}

	/**
	 * Read every panel option into a short-key => value map.
	 *
	 * @return array<string,mixed>
	 */
	private static function saved_options() {
		$options = array( 'accent' => get_option( self::OPTION_PREFIX . 'accent', 'default' ) );
		foreach ( self::colour_fields() as $key => $def ) {
			$options[ $key ] = get_option( self::OPTION_PREFIX . $key, '' );
		}
		$options['radius'] = get_option( self::OPTION_PREFIX . 'radius', '' );
		return $options;
	}

	/* -----------------------------------------------------------------
	 * Admin — menu, settings registration, page render.
	 * ----------------------------------------------------------------- */

	/**
	 * Add the Design submenu under the ShopOS menu.
	 */
	public function register_menu() {
		add_submenu_page(
			Settings_Hub::MENU_SLUG,
			__( 'Design', 'shopos-core' ),
			__( 'Design', 'shopos-core' ),
			Settings_Hub::CAP,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the panel's options with sanitisers + defaults.
	 */
	public function register_settings() {
		$preset_keys = array_keys( self::presets() );
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_PREFIX . 'accent',
			array(
				'sanitize_callback' => static function ( $v ) use ( $preset_keys ) {
					$v = sanitize_text_field( (string) $v );
					return in_array( $v, $preset_keys, true ) ? $v : 'default';
				},
				'default'           => 'default',
			)
		);

		foreach ( array_keys( self::colour_fields() ) as $key ) {
			register_setting(
				self::OPTION_GROUP,
				self::OPTION_PREFIX . $key,
				array(
					'sanitize_callback' => array( __CLASS__, 'sanitize_hex' ),
					'default'           => '',
				)
			);
		}

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_PREFIX . 'radius',
			array(
				'sanitize_callback' => static function ( $v ) {
					if ( '' === (string) $v || ! is_numeric( $v ) ) {
						return '';
					}
					$n = (int) $v;
					$n = max( self::RADIUS_MIN, min( self::RADIUS_MAX, $n ) );
					return (string) $n;
				},
				'default'           => '',
			)
		);
	}

	/**
	 * Render the Design settings page. Reuses the WordPress Settings API + the
	 * `shopos-color-field` class the ShopOS admin already wires to wp-color-picker.
	 */
	public function render_page() {
		Security::require_cap( Settings_Hub::CAP );

		echo '<div class="wrap shopos-wrap">';
		echo '<h1>' . esc_html__( 'Design', 'shopos-core' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Set the store accent and a few core surfaces. Changes flow to every ShopOS module through the shared design tokens; leave a field blank to inherit the theme.', 'shopos-core' ) . '</p>';

		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );
		echo '<table class="form-table"><tbody>';

		// Accent preset.
		$accent  = get_option( self::OPTION_PREFIX . 'accent', 'default' );
		$labels  = self::preset_labels();
		$opt_acc = self::OPTION_PREFIX . 'accent';
		echo '<tr><th scope="row"><label for="' . esc_attr( $opt_acc ) . '">' . esc_html__( 'Accent preset', 'shopos-core' ) . '</label></th><td>';
		echo '<select id="' . esc_attr( $opt_acc ) . '" name="' . esc_attr( $opt_acc ) . '">';
		foreach ( self::presets() as $slug => $unused ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $slug ),
				selected( $accent, $slug, false ),
				esc_html( isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Repaints the accent colour. Override it below to fine-tune.', 'shopos-core' ) . '</p>';
		echo '</td></tr>';

		// Curated colours.
		foreach ( self::colour_fields() as $key => $def ) {
			$option = self::OPTION_PREFIX . $key;
			$value  = get_option( $option, '' );
			echo '<tr><th scope="row"><label for="' . esc_attr( $option ) . '">' . esc_html( $def['label'] ) . '</label></th><td>';
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text shopos-color-field" placeholder="%3$s"/>',
				esc_attr( $option ),
				esc_attr( (string) $value ),
				esc_attr__( 'inherit', 'shopos-core' )
			);
			echo '</td></tr>';
		}

		// Radius.
		$opt_r = self::OPTION_PREFIX . 'radius';
		$rv    = get_option( $opt_r, '' );
		echo '<tr><th scope="row"><label for="' . esc_attr( $opt_r ) . '">' . esc_html__( 'Corner radius', 'shopos-core' ) . '</label></th><td>';
		printf(
			'<input type="number" id="%1$s" name="%1$s" value="%2$s" min="%3$d" max="%4$d" step="1" class="small-text"/> px',
			esc_attr( $opt_r ),
			esc_attr( (string) $rv ),
			(int) self::RADIUS_MIN,
			(int) self::RADIUS_MAX
		);
		echo '<p class="description">' . esc_html__( 'Blank inherits the theme radius.', 'shopos-core' ) . '</p>';
		echo '</td></tr>';

		echo '</tbody></table>';
		submit_button();
		echo '</form></div>';
	}
}
