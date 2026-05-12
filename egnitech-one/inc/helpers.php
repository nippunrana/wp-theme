<?php
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
function egnitech_one_get_option( $option, $default = '' ) {
    return get_option( $option, $default );
}
