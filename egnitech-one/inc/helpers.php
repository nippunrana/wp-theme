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
function egnitech_one_get_option( string $option, mixed $default = '' ): mixed {
	return get_option( $option, $default );
}

/**
 * Get the footer copyright text from theme options.
 *
 * @return string Copyright text.
 */
function egnitech_one_get_footer_copyright(): string {
	return (string) egnitech_one_get_option( 'egnitech_one_footer_copyright', '' );
}

/**
 * Get the footer credits text from theme options.
 *
 * @return string Credits HTML/text.
 */
function egnitech_one_get_footer_credits(): string {
	return (string) egnitech_one_get_option(
		'egnitech_one_footer_credits',
		'<a href="https://egnitech.com" rel="nofollow">EgniTech</a> &middot; Built with <a href="https://wordpress.org" rel="nofollow">WordPress</a>'
	);
}
