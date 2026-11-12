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
