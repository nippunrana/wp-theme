<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue parent and child stylesheets.
 */
function egnitech_one_child_enqueue_styles(): void {
	$parent_theme   = wp_get_theme()->parent();
	$parent_version = $parent_theme ? $parent_theme->get( 'Version' ) : '1.0.0';

	// Enqueue parent stylesheet.
	wp_enqueue_style(
		'egnitech-one-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		(string) $parent_version
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_child_enqueue_styles', 9 );

/**
 * Register pattern categories.
 */
function egnitech_one_child_register_pattern_categories(): void {
	register_block_pattern_category(
		'egnitech-one-child',
		array( 'label' => __( 'EgniTech One Child', 'egnitech-one-child' ) )
	);
}
add_action( 'init', 'egnitech_one_child_register_pattern_categories' );

/**
 * Optional: Opt-in to the parent's dark/light mode system.
 * By default, dark mode is disabled to prevent conflicts. To enable it,
 * uncomment the hook below.
 */
/*
function egnitech_one_child_setup(): void {
	add_theme_support( 'egnitech-one-dark-mode' );
}
add_action( 'after_setup_theme', 'egnitech_one_child_setup' );
*/
