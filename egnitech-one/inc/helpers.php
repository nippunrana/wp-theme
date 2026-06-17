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
 * If the parent theme is active, it is enabled by default.
 * If a child theme is active, it is disabled by default unless explicitly opted in.
 *
 * @return bool True if dark mode is enabled, false otherwise.
 */
if ( ! function_exists( 'egnitech_one_is_dark_mode_enabled' ) ) {
	function egnitech_one_is_dark_mode_enabled(): bool {
		// If parent theme is active, dark mode is always enabled.
		if ( ! is_child_theme() ) {
			return true;
		}

		// Check PHP theme support declaration.
		if ( current_theme_supports( 'egnitech-one-dark-mode' ) ) {
			return true;
		}

		// Check JSON declaration in theme.json's settings.custom.darkMode.
		$custom_settings = wp_get_global_settings( array( 'custom' ) );
		if ( is_array( $custom_settings ) && ! empty( $custom_settings['darkMode'] ) ) {
			return true;
		}

		return false;
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
