<?php
/**
 * Settings hub — owns the single "ShopOS" admin menu and auto-renders every
 * module's declarative settings schema.
 *
 * Module settings schemas return an associative array of:
 *   'setting_key' => [
 *       'label'       => string,
 *       'type'        => 'text' | 'textarea' | 'checkbox' | 'select' | 'color' | 'number',
 *       'description' => string,
 *       'default'     => mixed,
 *       'choices'     => [ value => label ]   (for select),
 *       'section'     => string,              (optional grouping)
 *   ]
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Settings hub.
 */
final class Settings_Hub {

	const MENU_SLUG = 'shopos';
	const CAP       = 'manage_woocommerce';

	/**
	 * Registry reference.
	 *
	 * @var Module_Registry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param Module_Registry $registry Registry.
	 */
	public function __construct( Module_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Register admin hooks.
	 */
	public function boot() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_shopos_toggle_module', array( $this, 'handle_toggle' ) );
		add_action( 'admin_post_shopos_save_feature_flags', array( $this, 'handle_save_feature_flags' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin CSS on ShopOS screens.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'shopos' ) ) {
			return;
		}
		wp_enqueue_style(
			'shopos-core-admin',
			SHOPOS_CORE_ASSETS . '/css/admin.css',
			array(),
			SHOPOS_CORE_VERSION
		);

		// Wire wp-color-picker onto every `color`-typed field. The `color`
		// renderer emits the `.shopos-color-field` class already; without this
		// enqueue the picker script never loaded, so colours showed as bare hex
		// text (ProductPage button_color, InfiniteScroll shimmer_*).
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){ $(".shopos-color-field").wpColorPicker(); });'
		);

		// The media picker is heavy, so it is only pulled in when a module
		// actually declares a `media`-typed field. No module does today, so
		// this is a no-op on every current screen.
		if ( $this->schema_has_type( 'media' ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery' );
			wp_add_inline_script( 'jquery', $this->media_picker_inline_script() );
		}
	}

	/**
	 * Whether any registered module's settings schema declares a field of the
	 * given control type. Lets us enqueue heavy assets only on screens that
	 * need them.
	 *
	 * @param string $type Control type.
	 * @return bool
	 */
	private function schema_has_type( $type ) {
		foreach ( $this->registry->all() as $module ) {
			foreach ( $module->settings_schema() as $def ) {
				if ( isset( $def['type'] ) && $type === $def['type'] ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Inline JS wiring the `.shopos-image-field` media control to wp.media().
	 * One frame is reused across open/cancel cycles; the hidden input stores
	 * the attachment ID and the preview <img> mirrors the thumbnail URL. Mirrors
	 * the term-screen swatch uploader in VariationSwatches/legacy.
	 *
	 * @return string
	 */
	private function media_picker_inline_script() {
		return <<<'JS'
jQuery(function($) {
	var frame = null;
	$(document).on('click', '.shopos-image-field-pick', function(e) {
		e.preventDefault();
		var $wrap = $(this).closest('.shopos-image-field-wrap');
		var $id   = $wrap.find('.shopos-image-field-id');
		var $prev = $wrap.find('.shopos-image-field-preview');
		if (frame) { frame.off('select'); } else {
			frame = wp.media({ multiple: false });
		}
		frame.on('select', function() {
			var att = frame.state().get('selection').first().toJSON();
			$id.val(att.id);
			var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
			$prev.attr('src', url).css('display', '');
			$wrap.find('.shopos-image-field-clear').css('display', '');
		});
		frame.open();
	});
	$(document).on('click', '.shopos-image-field-clear', function(e) {
		e.preventDefault();
		var $wrap = $(this).closest('.shopos-image-field-wrap');
		$wrap.find('.shopos-image-field-id').val('0');
		$wrap.find('.shopos-image-field-preview').attr('src', '').css('display', 'none');
		$(this).css('display', 'none');
	});
});
JS;
	}

	/**
	 * Register menu + submenus (one per module).
	 */
	public function register_menu() {
		add_menu_page(
			__( 'ShopOS', 'shopos-core' ),
			__( 'ShopOS', 'shopos-core' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-admin-generic',
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'shopos-core' ),
			__( 'Dashboard', 'shopos-core' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' )
		);

		foreach ( $this->registry->all() as $module ) {
			$id = $module->id();
			add_submenu_page(
				self::MENU_SLUG,
				$module->label(),
				$module->label(),
				self::CAP,
				self::MENU_SLUG . '-' . $id,
				function () use ( $module ) {
					$this->render_module_page( $module );
				}
			);
		}

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Feature Flags', 'shopos-core' ),
			__( 'Feature Flags', 'shopos-core' ),
			self::CAP,
			self::MENU_SLUG . '-feature-flags',
			array( $this, 'render_feature_flags' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tools', 'shopos-core' ),
			__( 'Tools', 'shopos-core' ),
			self::CAP,
			self::MENU_SLUG . '-tools',
			array( $this, 'render_tools' )
		);
	}

	/**
	 * Register option-API settings for every module's schema.
	 */
	public function register_settings() {
		foreach ( $this->registry->all() as $module ) {
			$schema = $module->settings_schema();
			if ( empty( $schema ) ) {
				continue;
			}
			foreach ( $schema as $key => $def ) {
				$option = $module->option_name( $key );
				register_setting(
					'shopos_' . $module->id(),
					$option,
					array(
						'sanitize_callback' => $this->sanitizer_for( $def ),
						'default'           => isset( $def['default'] ) ? $def['default'] : '',
					)
				);
			}
		}

		register_setting(
			'shopos_core_modules_group',
			'shopos_core_modules',
			array(
				'sanitize_callback' => array( $this, 'sanitize_modules_toggle' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize an incoming module-toggle form.
	 *
	 * @param mixed $input Input.
	 * @return array
	 */
	public function sanitize_modules_toggle( $input ) {
		$input  = is_array( $input ) ? $input : array();
		$output = array();
		foreach ( $this->registry->all() as $module ) {
			$output[ $module->id() ] = ! empty( $input[ $module->id() ] );
		}
		return $output;
	}

	/**
	 * Pick a sanitizer callback for a schema entry.
	 *
	 * @param array $def Schema entry.
	 * @return callable
	 */
	private function sanitizer_for( $def ) {
		$type = isset( $def['type'] ) ? $def['type'] : 'text';
		switch ( $type ) {
			case 'textarea':
				return 'sanitize_textarea_field';
			case 'checkbox':
				return static function ( $v ) {
					return ! empty( $v ) ? 1 : 0;
				};
			case 'number':
				return static function ( $v ) {
					return is_numeric( $v ) ? $v + 0 : 0;
				};
			case 'email':
				return 'sanitize_email';
			case 'url':
				return 'esc_url_raw';
			case 'color':
				return static function ( $v ) {
					return preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $v ) ? $v : '';
				};
			case 'range':
				return static function ( $v ) use ( $def ) {
					$n = is_numeric( $v ) ? $v + 0 : 0;
					if ( isset( $def['min'] ) && $n < $def['min'] ) {
						$n = $def['min'] + 0;
					}
					if ( isset( $def['max'] ) && $n > $def['max'] ) {
						$n = $def['max'] + 0;
					}
					return $n;
				};
			case 'media':
				return 'absint';
			case 'typography-select':
				return static function ( $v ) use ( $def ) {
					$choices = isset( $def['choices'] ) ? $def['choices'] : array();
					$v       = sanitize_text_field( (string) $v );
					if ( array_key_exists( $v, $choices ) ) {
						return $v;
					}
					return isset( $def['default'] ) ? $def['default'] : '';
				};
			case 'multiselect':
				return static function ( $v ) use ( $def ) {
					$choices = isset( $def['choices'] ) ? $def['choices'] : array();
					$v       = is_array( $v ) ? $v : array();
					$out     = array();
					foreach ( $v as $item ) {
						$item = sanitize_text_field( (string) $item );
						if ( array_key_exists( $item, $choices ) ) {
							$out[] = $item;
						}
					}
					return $out;
				};
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Handle a POST that toggles a single module on/off (used by Dashboard).
	 */
	public function handle_toggle() {
		Security::verify_nonce( 'shopos_toggle_module' );
		Security::require_cap( self::CAP );

		$id      = isset( $_POST['module'] ) ? sanitize_key( wp_unslash( $_POST['module'] ) ) : '';
		$enabled = ! empty( $_POST['enabled'] );

		if ( $id && $this->registry->get( $id ) ) {
			$this->registry->set_enabled( $id, $enabled );
			$module = $this->registry->get( $id );
			if ( $enabled ) {
				$module->on_activate();
			} else {
				$module->on_deactivate();
			}
			flush_rewrite_rules();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/**
	 * Persist the Feature Flags page submission. The form posts a set of
	 * checked flag option names under `flags[<option_name>]`; every flag in
	 * the registry is written to 1/0 from that set (a flag's checkbox absent
	 * from the POST means "off"). Flags whose effective state is forced by a
	 * `shopos_core/feature_flag/...` filter are skipped — the UI disables
	 * those checkboxes, so honouring the POST would write a value the filter
	 * ignores anyway.
	 */
	public function handle_save_feature_flags() {
		Security::verify_nonce( 'shopos_save_feature_flags' );
		Security::require_cap( self::CAP );

		$checked = array();
		$raw     = ( isset( $_POST['flags'] ) && is_array( $_POST['flags'] ) ) ? array_keys( wp_unslash( $_POST['flags'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified by Security::verify_nonce() above; keys are whitelisted against the registry below and values are unused.
		foreach ( $raw as $key ) {
			$checked[ sanitize_key( $key ) ] = true;
		}

		foreach ( Feature_Flags::registry() as $flag ) {
			if ( Feature_Flags::is_forced_by_filter( $flag['module'], $flag['feature'] ) ) {
				continue;
			}
			$option = Feature_Flags::option_name( $flag['module'], $flag['feature'] );
			update_option( $option, isset( $checked[ $option ] ) ? 1 : 0 );
		}

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . self::MENU_SLUG . '-feature-flags' ) ) );
		exit;
	}

	/* -----------------------------------------------------------------
	 * Renderers (delegated to Admin\Dashboard and simple inline renderers)
	 * ----------------------------------------------------------------- */

	/**
	 * Dashboard page.
	 */
	public function render_dashboard() {
		$plugin    = Plugin::instance();
		$dashboard = new \ShopOS\Core\Admin\Dashboard( $plugin );
		$dashboard->render();
	}

	/**
	 * Render a single module's settings page.
	 *
	 * @param Module_Interface $module Module.
	 */
	public function render_module_page( $module ) {
		Security::require_cap( self::CAP );

		$schema = $module->settings_schema();
		echo '<div class="wrap shopos-wrap">';
		echo '<h1>' . esc_html( $module->label() ) . '</h1>';
		echo '<p class="description">' . esc_html( $module->description() ) . '</p>';

		if ( ! $module->is_enabled() ) {
			echo '<div class="notice notice-warning inline"><p>' .
				esc_html__( 'This module is disabled. Enable it from the Dashboard.', 'shopos-core' ) .
				'</p></div>';
		}

		if ( ! empty( $schema ) ) {
			echo '<form method="post" action="options.php">';
			settings_fields( 'shopos_' . $module->id() );

			$sections = array();
			foreach ( $schema as $key => $def ) {
				$section                  = isset( $def['section'] ) ? $def['section'] : __( 'General', 'shopos-core' );
				$sections[ $section ][ $key ] = $def;
			}

			foreach ( $sections as $section_title => $fields ) {
				echo '<h2>' . esc_html( $section_title ) . '</h2>';
				echo '<table class="form-table"><tbody>';
				foreach ( $fields as $key => $def ) {
					$this->render_field( $module, $key, $def );
				}
				echo '</tbody></table>';
			}

			submit_button();
			echo '</form>';
		}

		do_action( 'shopos_core/module_page/' . $module->id(), $module );
		echo '</div>';
	}

	/**
	 * Render a single schema field.
	 *
	 * @param Module_Interface $module Module.
	 * @param string           $key    Setting key.
	 * @param array            $def    Schema entry.
	 */
	private function render_field( $module, $key, $def ) {
		$option = $module->option_name( $key );
		$value  = get_option( $option, isset( $def['default'] ) ? $def['default'] : '' );
		$label  = isset( $def['label'] ) ? $def['label'] : $key;
		$desc   = isset( $def['description'] ) ? $def['description'] : '';
		$type   = isset( $def['type'] ) ? $def['type'] : 'text';

		echo '<tr><th scope="row"><label for="' . esc_attr( $option ) . '">' . esc_html( $label ) . '</label></th><td>';

		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="4" cols="60" class="large-text">%2$s</textarea>',
					esc_attr( $option ),
					esc_textarea( (string) $value )
				);
				break;
			case 'checkbox':
				$checked = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s/> %3$s</label>',
					esc_attr( $option ),
					checked( true === $checked, true, false ),
					esc_html( isset( $def['checkbox_label'] ) ? $def['checkbox_label'] : '' )
				);
				break;
			case 'select':
				$choices = isset( $def['choices'] ) ? $def['choices'] : array();
				echo '<select id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '">';
				foreach ( $choices as $val => $label2 ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $val ),
						selected( $value, $val, false ),
						esc_html( $label2 )
					);
				}
				echo '</select>';
				break;
			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text shopos-color-field" placeholder="#000000"/>',
					esc_attr( $option ),
					esc_attr( (string) $value )
				);
				break;
			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text"/>',
					esc_attr( $option ),
					esc_attr( (string) $value )
				);
				break;
			case 'range':
				$min  = isset( $def['min'] ) ? $def['min'] : 0;
				$max  = isset( $def['max'] ) ? $def['max'] : 100;
				$step = isset( $def['step'] ) ? $def['step'] : 1;
				$unit = isset( $def['unit'] ) ? $def['unit'] : '';
				printf(
					'<input type="range" id="%1$s" name="%1$s" value="%2$s" min="%3$s" max="%4$s" step="%5$s" class="shopos-range-field"/> <output class="shopos-range-value" for="%1$s">%2$s%6$s</output>',
					esc_attr( $option ),
					esc_attr( (string) $value ),
					esc_attr( (string) $min ),
					esc_attr( (string) $max ),
					esc_attr( (string) $step ),
					'' !== (string) $unit ? ' ' . esc_html( (string) $unit ) : ''
				);
				break;
			case 'media':
				$att_id     = absint( $value );
				$img_url    = $att_id ? wp_get_attachment_image_url( $att_id, 'thumbnail' ) : '';
				$select_txt = isset( $def['button_label'] ) ? $def['button_label'] : __( 'Select image', 'shopos-core' );
				echo '<span class="shopos-image-field-wrap">';
				printf(
					'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="shopos-image-field-id"/>',
					esc_attr( $option ),
					esc_attr( (string) $att_id )
				);
				printf(
					'<img src="%1$s" alt="" class="shopos-image-field-preview" style="max-width:80px;height:auto;vertical-align:middle;%2$s"/> ',
					esc_url( (string) $img_url ),
					$img_url ? '' : 'display:none'
				);
				printf(
					'<button type="button" class="button shopos-image-field-pick">%1$s</button> ',
					esc_html( $select_txt )
				);
				printf(
					'<button type="button" class="button-link shopos-image-field-clear" style="%1$s">%2$s</button>',
					$att_id ? '' : 'display:none',
					esc_html__( 'Remove', 'shopos-core' )
				);
				echo '</span>';
				break;
			case 'typography-select':
				$choices = isset( $def['choices'] ) ? $def['choices'] : array();
				echo '<select id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '" class="shopos-typography-select">';
				foreach ( $choices as $val => $label2 ) {
					printf(
						'<option value="%1$s" style="font-family:%2$s" %3$s>%4$s</option>',
						esc_attr( $val ),
						esc_attr( (string) $val ),
						selected( $value, $val, false ),
						esc_html( $label2 )
					);
				}
				echo '</select>';
				break;
			case 'multiselect':
				$choices  = isset( $def['choices'] ) ? $def['choices'] : array();
				$selected = is_array( $value ) ? array_map( 'strval', $value ) : array();
				echo '<select id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '[]" multiple class="shopos-multiselect">';
				foreach ( $choices as $val => $label2 ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $val ),
						in_array( (string) $val, $selected, true ) ? ' selected="selected"' : '',
						esc_html( $label2 )
					);
				}
				echo '</select>';
				break;
			default:
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text"/>',
					esc_attr( $option ),
					esc_attr( (string) $value )
				);
		}

		if ( $desc ) {
			echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
		}

		echo '</td></tr>';
	}

	/**
	 * Tools page.
	 */
	public function render_tools() {
		Security::require_cap( self::CAP );
		include SHOPOS_CORE_PATH . 'src/Admin/views/tools.php';
	}

	/**
	 * Feature Flags page.
	 */
	public function render_feature_flags() {
		Security::require_cap( self::CAP );
		include SHOPOS_CORE_PATH . 'src/Admin/views/feature-flags.php';
	}
}
