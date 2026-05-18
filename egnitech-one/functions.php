<?php
/**
 * EgniTech One functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package EgniTech_One
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 0. Shared Helpers (Utility functions)
require_once get_theme_file_path( 'inc/helpers.php' );

// 1. Font Manager (Typography system)
require_once get_theme_file_path( 'inc/font-manager.php' );

// 2. Theme Setup (Support, block patterns, removing bloat)
require_once get_theme_file_path( 'inc/theme-setup.php' );

// 3. Asset Enqueuing (Styles, Scripts, Dark Mode Toggle)
require_once get_theme_file_path( 'inc/enqueue-assets.php' );

// 4. Header/Footer Scripts
require_once get_theme_file_path( 'inc/custom-scripts.php' );

// 5. Admin Theme Options
if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/admin-options.php' );
}

// 6. SMTP Configuration (PHPMailer hook)
require_once get_theme_file_path( 'inc/smtp-config.php' );
