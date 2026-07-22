<?php
/**
 * theme.json -> --shopos-ui-* design-token bridge.
 *
 * Reads the merged Global Settings tree (theme.json presets plus any user
 * Global-Styles overrides) and re-emits the palette / spacing / radius / motion
 * values as `--shopos-ui-*` custom properties inline, right after
 * `shopos-tokens.css`. That file stays the semantic + fallback layer: its
 * literals render unchanged whenever this block is absent or a value has no
 * theme.json source, so the bridge is purely additive (Hard Rule #1 additive
 * exception — new CSS variables with backward-compatible fallbacks).
 *
 * Its purpose is a single source of truth: change a colour / space / radius /
 * motion value in theme.json (or via a Global-Styles override) and it flows to
 * every ShopOS Core module through the existing token layer, instead of being
 * hand-synced into `shopos-tokens.css` a second time.
 *
 * Scope note — deliberately NOT bridged:
 *   - Typography: the theme's hand-tuned clamp() scale and the sk_type_*
 *     Elementor-global bridge own that; theme.json's fluid clamps differ.
 *   - The semantic `--shopos-ui-color-*` layer and the `.is-accent-*` presets:
 *     they reference the raw palette (which this bridges) and flow through.
 *   - Primitives (card / input / button / badge) and tokens with no theme.json
 *     source (palette-black, palette-sand, radius-round, motion-instant, eases).
 *
 * This is the theme.json -> CSS direction (front-end render). It does not push
 * tokens into the block editor's pickers — that opposite direction was dropped
 * for being Elementor-only (decisions §4.3, ex-Roadmap #7).
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * theme.json source key => `--shopos-ui-*` token suffix, per group.
 *
 * Only entries with a faithful, equal-valued theme.json source are listed;
 * everything else stays on the `shopos-tokens.css` fallback. Palette maps the
 * theme.json colour slug to the raw palette suffix (a few names differ:
 * ink-muted->mute, paper-soft->paper-alt, danger->red, success->green,
 * warning->amber); the other groups are identity maps kept explicit so the
 * bridged surface is obvious at a glance.
 *
 * @return array<string,array<string,string>>
 */
function shopos_theme_token_maps() {
	return array(
		// theme.json color slug => --shopos-ui-palette-<suffix>.
		'palette' => array(
			'ink'        => 'ink',
			'ink-soft'   => 'ink-soft',
			'ink-muted'  => 'mute',
			'hairline'   => 'hairline',
			'paper'      => 'paper',
			'paper-soft' => 'paper-alt',
			'paper-dim'  => 'paper-dim',
			'gold'       => 'gold',
			'danger'     => 'red',
			'success'    => 'green',
			'warning'    => 'amber',
			'info'       => 'info',
		),
		// theme.json spacingSizes slug => --shopos-ui-space-<suffix>.
		'space'   => array(
			'xxs' => 'xxs',
			'xs'  => 'xs',
			'sm'  => 'sm',
			'md'  => 'md',
			'lg'  => 'lg',
			'xl'  => 'xl',
			'xxl' => 'xxl',
			'3xl' => '3xl',
		),
		// theme.json custom.radius key => --shopos-ui-radius-<suffix>.
		'radius'  => array(
			'xs'   => 'xs',
			'sm'   => 'sm',
			'md'   => 'md',
			'lg'   => 'lg',
			'xl'   => 'xl',
			'pill' => 'pill',
		),
		// theme.json custom.motion key => --shopos-ui-motion-<suffix>.
		'motion'  => array(
			'fast' => 'fast',
			'base' => 'base',
			'slow' => 'slow',
		),
	);
}

/**
 * Flatten a preset node (color.palette / spacing.spacingSizes) to slug => value.
 *
 * The merged settings store presets grouped by origin (default/theme/custom);
 * iterating in that order lets a user Global-Styles override (custom) beat the
 * theme default. A flat list (older / edge shapes) is treated as one origin, so
 * the resolver is correct regardless of how a given WordPress version shapes it.
 *
 * @param mixed  $node      Preset node from wp_get_global_settings().
 * @param string $value_key Entry key holding the value ('color' or 'size').
 * @return array<string,string> slug => value.
 */
function shopos_theme_flatten_preset( $node, $value_key ) {
	if ( ! is_array( $node ) || array() === $node ) {
		return array();
	}

	$origins = array();
	if ( isset( $node['default'] ) || isset( $node['theme'] ) || isset( $node['custom'] ) ) {
		foreach ( array( 'default', 'theme', 'custom' ) as $origin ) {
			if ( isset( $node[ $origin ] ) && is_array( $node[ $origin ] ) ) {
				$origins[] = $node[ $origin ];
			}
		}
	} else {
		$origins[] = $node;
	}

	$map = array();
	foreach ( $origins as $entries ) {
		if ( ! is_array( $entries ) ) {
			continue;
		}
		foreach ( $entries as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'], $entry[ $value_key ] ) ) {
				$map[ $entry['slug'] ] = $entry[ $value_key ];
			}
		}
	}
	return $map;
}

/**
 * Sanitize a value bound for an inline custom-property declaration.
 *
 * theme.json values can carry user Global-Styles overrides, so a value could
 * contain characters that break out of the declaration ( ; } < ) or open
 * markup. Allow only the shapes tokens actually use — hex / rgb(a) / hsl(a) /
 * var() / number+unit / bare keywords — and reject anything else.
 *
 * @param mixed $value Raw value.
 * @return string Sanitized value, or '' if unsafe / empty.
 */
function shopos_theme_sanitize_token_value( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value ) {
		return '';
	}
	// Reject anything that can terminate the declaration or open markup.
	if ( preg_match( '/[;{}<>@\\\\]/', $value ) ) {
		return '';
	}
	// Whitelist the character set CSS colour / length / duration values use.
	// Delimiter is ~ because the class itself contains #.
	if ( ! preg_match( '~^[a-zA-Z0-9#%.,()/ _-]+$~', $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Build the inline `--shopos-ui-*` block from Global Settings.
 *
 * @return string CSS, or '' when the bridge is disabled or nothing resolved.
 */
function shopos_theme_design_tokens_css() {
	/**
	 * Filters whether the theme.json -> --shopos-ui-* bridge emits its block.
	 *
	 * Return false to disable (the kill switch / rollback lever): the block
	 * empties and `shopos-tokens.css`'s literals render — i.e. today's output.
	 *
	 * @param bool $enabled Default true.
	 */
	if ( ! apply_filters( 'shopos_theme_design_tokens_enabled', true ) ) {
		return '';
	}
	if ( ! function_exists( 'wp_get_global_settings' ) ) {
		return '';
	}

	$maps  = shopos_theme_token_maps();
	$decls = array();

	// --- Palette -> --shopos-ui-palette-* ---.
	$palette = shopos_theme_flatten_preset( wp_get_global_settings( array( 'color', 'palette' ) ), 'color' );
	foreach ( $maps['palette'] as $slug => $suffix ) {
		if ( isset( $palette[ $slug ] ) ) {
			$val = shopos_theme_sanitize_token_value( $palette[ $slug ] );
			if ( '' !== $val ) {
				$decls[] = "--shopos-ui-palette-{$suffix}:{$val};";
			}
		}
	}

	// --- Spacing -> --shopos-ui-space-* ---.
	$space = shopos_theme_flatten_preset( wp_get_global_settings( array( 'spacing', 'spacingSizes' ) ), 'size' );
	foreach ( $maps['space'] as $slug => $suffix ) {
		if ( isset( $space[ $slug ] ) ) {
			$val = shopos_theme_sanitize_token_value( $space[ $slug ] );
			if ( '' !== $val ) {
				$decls[] = "--shopos-ui-space-{$suffix}:{$val};";
			}
		}
	}

	// --- custom.radius -> --shopos-ui-radius-* ---.
	$radius = wp_get_global_settings( array( 'custom', 'radius' ) );
	if ( is_array( $radius ) ) {
		foreach ( $maps['radius'] as $key => $suffix ) {
			if ( isset( $radius[ $key ] ) ) {
				$val = shopos_theme_sanitize_token_value( $radius[ $key ] );
				if ( '' !== $val ) {
					$decls[] = "--shopos-ui-radius-{$suffix}:{$val};";
				}
			}
		}
	}

	// --- custom.motion -> --shopos-ui-motion-* ---.
	$motion_suffixes = array();
	$motion          = wp_get_global_settings( array( 'custom', 'motion' ) );
	if ( is_array( $motion ) ) {
		foreach ( $maps['motion'] as $key => $suffix ) {
			if ( isset( $motion[ $key ] ) ) {
				$val = shopos_theme_sanitize_token_value( $motion[ $key ] );
				if ( '' !== $val ) {
					$decls[]           = "--shopos-ui-motion-{$suffix}:{$val};";
					$motion_suffixes[] = $suffix;
				}
			}
		}
	}

	// --- Style Kits typography slots (decisions §11 Ruling 8) ---.
	// Core's Design::kit_slots() de-hardcodes which --e-global-typography-*
	// slots feed the two font tokens; the defaults match shopos-tokens.css
	// verbatim, so a re-map is emitted ONLY when a store re-points a slot
	// (option or filter). Core absent ⇒ defaults ⇒ no emit. Slot values are
	// sanitised to [a-z0-9_] by kit_slots() before this interpolation.
	// method_exists guard: kit_slots() arrived in core 1.42.0 — a theme zip
	// updated ahead of core must not fatal the storefront (§11.5).
	if ( class_exists( '\ShopOS\Core\Core\Design' )
		&& method_exists( '\ShopOS\Core\Core\Design', 'kit_slots' ) ) {
		$fr_slots  = \ShopOS\Core\Core\Design::kit_slots();
		$fr_stacks = array(
			'body'    => array( '--shopos-ui-font-body', "'Heebo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" ),
			'display' => array( '--shopos-ui-font-display', 'var(--shopos-ui-font-body)' ),
		);
		foreach ( \ShopOS\Core\Core\Design::KIT_SLOT_DEFAULTS as $fr_key => $fr_default ) {
			if ( isset( $fr_slots[ $fr_key ] ) && $fr_slots[ $fr_key ] !== $fr_default ) {
				list( $fr_var, $fr_fallback ) = $fr_stacks[ $fr_key ];
				$decls[]                      = "{$fr_var}:var(--e-global-typography-{$fr_slots[$fr_key]}-font-family, {$fr_fallback});";
			}
		}
	}

	if ( array() === $decls ) {
		return '';
	}

	$css = ':root,.shopos-theme{' . implode( '', $decls ) . '}';

	// Re-assert the reduced-motion collapse for any motion token we just
	// re-declared. This block prints after shopos-tokens.css, so without this
	// our unconditional 180/280/480ms would override that file's
	// `@media (prefers-reduced-motion: reduce)` 0ms rule (equal specificity,
	// later source order) and defeat the accessibility preference.
	if ( array() !== $motion_suffixes ) {
		$reduced = array();
		foreach ( $motion_suffixes as $suffix ) {
			$reduced[] = "--shopos-ui-motion-{$suffix}:0ms;";
		}
		$css .= '@media (prefers-reduced-motion:reduce){:root,.shopos-theme{' . implode( '', $reduced ) . '}}';
	}

	return $css;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		// Priority 21 so the theme's prio-20 enqueue has registered the handle.
		$css = shopos_theme_design_tokens_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'shopos-tokens', $css );
		}
	},
	21
);
