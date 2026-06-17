<?php
declare(strict_types=1);

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

// 2. Theme Setup (Support, block patterns, removing bloat)
require_once get_theme_file_path( 'inc/theme-setup.php' );

// 3. Asset Enqueuing (Styles, Scripts, Dark Mode Toggle)
require_once get_theme_file_path( 'inc/enqueue-assets.php' );

// 4. Header/Footer Scripts
require_once get_theme_file_path( 'inc/custom-scripts.php' );

// 5. Admin Theme Options
if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/font-manager.php' );
	require_once get_theme_file_path( 'inc/admin-options.php' );
}

// 6. SMTP Configuration (PHPMailer hook)
require_once get_theme_file_path( 'inc/smtp-config.php' );

// 7. WooCommerce Bulk Discount System
require_once get_theme_file_path( 'inc/woocommerce-bulk-discount.php' );

// 8. WooCommerce Cart Drawer Auto-Open System
require_once get_theme_file_path( 'inc/woocommerce-cart-drawer.php' );

// 9. FastCGI Cache Purging System
require_once get_theme_file_path( 'inc/fastcgi-cache-purger.php' );

