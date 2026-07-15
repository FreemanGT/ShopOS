<?php
/**
 * ShopOS Theme bootstrap.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

define( 'SHOPOS_THEME_VERSION',   '1.11.28' );
define( 'SHOPOS_THEME_PATH',      get_stylesheet_directory() );
define( 'SHOPOS_THEME_URL',       get_stylesheet_directory_uri() );
define( 'SHOPOS_THEME_ASSETS',    SHOPOS_THEME_URL . '/assets' );
define( 'SHOPOS_CORE_MIN_VERSION', '1.0.0' );

require_once SHOPOS_THEME_PATH . '/inc/class-shopos-theme.php';
require_once SHOPOS_THEME_PATH . '/inc/plugin-dependencies.php';
require_once SHOPOS_THEME_PATH . '/inc/hooks.php';
require_once SHOPOS_THEME_PATH . '/inc/woocommerce.php';
require_once SHOPOS_THEME_PATH . '/inc/customizer.php';
require_once SHOPOS_THEME_PATH . '/inc/design-tokens.php';

ShopOS_Theme::instance();
ShopOS_Theme_Plugin_Dependencies::instance();
