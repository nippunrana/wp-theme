<?php
declare(strict_types=1);

/**
 * EgniTech One Child Theme — functions and definitions.
 *
 * @package EgniTech_One_Child
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override the parent theme's dark mode helper to prevent infinite recursion
 * when resolving global settings in the child theme.
 *
 * @return bool True if dark mode is enabled, false otherwise.
 */
if ( ! function_exists( 'egnitech_one_is_dark_mode_enabled' ) ) {
	function egnitech_one_is_dark_mode_enabled(): bool {
		static $is_resolving = false;
		if ( $is_resolving ) {
			return false;
		}

		// If parent theme is active, dark mode is always enabled.
		if ( ! is_child_theme() ) {
			return true;
		}

		// Check PHP theme support declaration.
		if ( current_theme_supports( 'egnitech-one-dark-mode' ) ) {
			return true;
		}

		// Check JSON declaration in theme.json's settings.custom.darkMode.
		$is_resolving = true;
		$custom_settings = wp_get_global_settings( array( 'custom' ) );
		$is_resolving = false;

		if ( is_array( $custom_settings ) && ! empty( $custom_settings['darkMode'] ) ) {
			return true;
		}

		return false;
	}
}

add_action( 'after_setup_theme', function (): void {
	register_block_pattern_category(
		'egnitech-one-child',
		[ 'label' => __( 'EgniTech One Child', 'egnitech-one-child' ) ]
	);
} );
