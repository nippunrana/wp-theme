<?php
declare(strict_types=1);

/**
 * Shared utility functions and helpers.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a theme option with an optional default value.
 *
 * @param string $option  Option name.
 * @param mixed  $default Default value.
 * @return mixed          Option value.
 */
if ( ! function_exists( 'egnitech_one_get_option' ) ) {
	function egnitech_one_get_option( string $option, mixed $default = '' ): mixed {
		return get_option( $option, $default );
	}
}

/**
 * Version string for a theme stylesheet or script: the file's modification time.
 * Its address then changes whenever the file does, so no browser keeps a stale copy.
 *
 * @param string $file Absolute path of the file the address points at.
 * @return string      Modification time, or the theme version if the file is missing.
 */
if ( ! function_exists( 'egnitech_one_asset_version' ) ) {
	function egnitech_one_asset_version( string $file ): string {
		return is_file( $file ) ? (string) filemtime( $file ) : (string) wp_get_theme()->get( 'Version' );
	}
}

/**
 * Get the footer copyright text from theme options.
 *
 * @return string Copyright text.
 */
if ( ! function_exists( 'egnitech_one_get_footer_copyright' ) ) {
	function egnitech_one_get_footer_copyright(): string {
		return (string) egnitech_one_get_option( 'egnitech_one_footer_copyright', '' );
	}
}

/**
 * Get the footer credits text from theme options.
 *
 * @return string Credits HTML/text.
 */
if ( ! function_exists( 'egnitech_one_get_footer_credits' ) ) {
	function egnitech_one_get_footer_credits(): string {
		return (string) egnitech_one_get_option(
			'egnitech_one_footer_credits',
			'Built by <a href="https://egnitech.com" rel="nofollow">EgniTech</a>'
		);
	}
}

/**
 * Check if native dark/light mode is enabled.
 * Off (light only) until the "Dark Mode" switch in Theme Options is turned on and saved.
 *
 * @return bool True if dark mode is enabled, false otherwise.
 */
if ( ! function_exists( 'egnitech_one_is_dark_mode_enabled' ) ) {
	function egnitech_one_is_dark_mode_enabled(): bool {
		return 'yes' === egnitech_one_get_option( 'egnitech_one_dark_mode_enabled', 'no' );
	}
}

/**
 * Get the Google reCAPTCHA v2 credentials and enabled status.
 *
 * @return array The reCAPTCHA settings.
 */
if ( ! function_exists( 'egnitech_one_get_recaptcha_settings' ) ) {
	function egnitech_one_get_recaptcha_settings(): array {
		return array(
			'enabled'    => 'yes' === egnitech_one_get_option( 'egnitech_one_recaptcha_enabled', 'no' ),
			'site_key'   => (string) egnitech_one_get_option( 'egnitech_one_recaptcha_site_key', '' ),
			'secret_key' => (string) egnitech_one_get_option( 'egnitech_one_recaptcha_secret_key', '' ),
		);
	}
}

/**
 * Fetch every setting a front-end page reads with one database query.
 * A setting that was never saved has no database row, and WordPress would otherwise
 * look for each of those with a query of its own on every page not served from cache.
 *
 * @return void
 */
if ( ! function_exists( 'egnitech_one_prime_options' ) ) {
	function egnitech_one_prime_options(): void {
		wp_prime_option_caches(
			array(
				'egnitech_one_sticky_header',
				'egnitech_one_header_padding_desktop_top',
				'egnitech_one_header_padding_desktop_bottom',
				'egnitech_one_header_padding_mobile_top',
				'egnitech_one_header_padding_mobile_bottom',
				'egnitech_one_logo_width_desktop',
				'egnitech_one_logo_width_mobile',
				'egnitech_one_dark_logo_url',
				'egnitech_one_scroll_to_top',
				'egnitech_one_reading_progress',
				'egnitech_one_reading_progress_height',
				'egnitech_one_breadcrumbs',
				'egnitech_one_dark_mode_enabled',
				'egnitech_one_dark_mode_default',
				'egnitech_one_footer_copyright',
				'egnitech_one_footer_credits',
				'egnitech_one_blog_layout',
				'egnitech_one_sidebar_position',
				'egnitech_one_meta_author',
				'egnitech_one_meta_date',
				'egnitech_one_meta_categories',
				'egnitech_one_meta_tags',
				'egnitech_one_custom_scripts',
				'egnitech_one_woocommerce_gallery_layout',
				'egnitech_one_woocommerce_gallery_zoom',
				'egnitech_one_woocommerce_gallery_lightbox',
				'egnitech_one_woocommerce_gallery_lightbox_icon',
				'egnitech_one_woocommerce_gallery_thumbnail_style',
			)
		);
	}
}
// Page views only: on REST, AJAX and admin requests this would add a query, not save any.
add_action( 'template_redirect', 'egnitech_one_prime_options', 0 );
