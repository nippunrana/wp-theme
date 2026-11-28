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

// 1. Colour maths (contrast checks for button text; also used by the admin palette tools)
require_once get_theme_file_path( 'inc/palette/color-math.php' );

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
	require_once get_theme_file_path( 'inc/palette/palette-admin.php' );
}

// 6. SMTP Configuration (PHPMailer hook)
require_once get_theme_file_path( 'inc/smtp-config.php' );

// 7. WooCommerce Bulk Discount System
require_once get_theme_file_path( 'inc/woocommerce-bulk-discount.php' );

// 8. WooCommerce Cart Drawer Auto-Open System
require_once get_theme_file_path( 'inc/woocommerce-cart-drawer.php' );

// 9. FastCGI Cache Purging System
require_once get_theme_file_path( 'inc/fastcgi-cache-purger.php' );

// 10. Cloudflare Turnstile (login, registration, lost-password and comment forms)
require_once get_theme_file_path( 'inc/turnstile.php' );

// 11. Stored secrets (encryption for the SMTP password and the Turnstile secret key)
require_once get_theme_file_path( 'inc/secrets.php' );

// 12. Product trust badges: icons and the badge list (the meta box and shortcode are hooked in theme-setup.php)
require_once get_theme_file_path( 'inc/woocommerce-trust-badges.php' );
if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/woocommerce-trust-badges-admin.php' );
}

// 13. Product page reviews: summary row and the review form drawer (called from the sections shortcode in theme-setup.php)
require_once get_theme_file_path( 'inc/woocommerce-reviews.php' );

