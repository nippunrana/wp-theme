<?php
declare(strict_types=1);

/**
 * Theme setup, configurations, and core hooks.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register block pattern category.
 */
function egnitech_one_pattern_categories(): void {
	register_block_pattern_category(
		'egnitech-one',
		array(
			'label'       => __( 'EgniTech One', 'egnitech-one' ),
			'description' => __( 'Patterns for the EgniTech One theme.', 'egnitech-one' ),
		)
	);
}
add_action( 'init', 'egnitech_one_pattern_categories' );

/**
 * Enqueue editor styles to distinguish dual logos.
 */
function egnitech_one_add_editor_styles(): void {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'editor-style.css' );
}
add_action( 'after_setup_theme', 'egnitech_one_add_editor_styles' );

/**
 * Remove unnecessary default scripts/styles for performance.
 */
function egnitech_one_dequeue_bloat(): void {
	// Remove WordPress emoji scripts.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	// Remove wlwmanifest and rsd_link.
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );

	// Remove WordPress version from head.
	remove_action( 'wp_head', 'wp_generator' );
}
add_action( 'init', 'egnitech_one_dequeue_bloat' );

/**
 * Add body class based on Sticky Header theme option.
 *
 * @param array $classes CSS classes.
 * @return array Modified CSS classes.
 */
function egnitech_one_body_classes( array $classes ): array {
	$sticky_header = get_option( 'egnitech_one_sticky_header', 'yes' );
	if ( 'yes' === $sticky_header ) {
		$classes[] = 'has-fixed-header';
	}

	// Blog layout class.
	$blog_layout = get_option( 'egnitech_one_blog_layout', 'list' );
	$classes[] = 'blog-layout-' . sanitize_html_class( (string) $blog_layout );

	// Sidebar class.
	$sidebar = get_option( 'egnitech_one_sidebar_position', 'none' );
	if ( 'none' !== $sidebar ) {
		$classes[] = 'has-sidebar';
		$classes[] = 'sidebar-' . sanitize_html_class( (string) $sidebar );
	}

	return $classes;
}
add_filter( 'body_class', 'egnitech_one_body_classes' );

/**
 * Render breadcrumbs on single posts and pages if enabled.
 */
function egnitech_one_render_breadcrumbs(): void {
	if ( 'yes' !== get_option( 'egnitech_one_breadcrumbs', '' ) ) {
		return;
	}

	// Only show on singular content.
	if ( ! is_singular() ) {
		return;
	}

	$sep   = ' <span class="egnitech-breadcrumb-sep">›</span> ';
	$trail = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'egnitech-one' ) . '</a>';

	if ( is_single() ) {
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$cat = $categories[0];
			$trail .= $sep . '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
		}
	}

	$trail .= $sep . '<span class="egnitech-breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

	echo '<nav class="egnitech-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'egnitech-one' ) . '">' . $trail . '</nav>' . "\n";
}
add_action( 'wp_body_open', 'egnitech_one_render_breadcrumbs' );

/**
 * Register shortcode for the Dark Mode Toggle.
 * This prevents raw HTML from showing up in the Site Editor when using a Custom HTML block.
 *
 * @return string Toggle HTML.
 */
function egnitech_one_dark_mode_toggle_shortcode(): string {
	if ( ! egnitech_one_is_dark_mode_enabled() ) {
		return '';
	}
	return '<button class="egnitech-dark-mode-toggle" aria-label="Switch to dark mode" type="button">
		<svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
		<svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
	</button>';
}
add_shortcode( 'egnitech_dark_mode_toggle', 'egnitech_one_dark_mode_toggle_shortcode' );

/**
 * Ensure shortcodes are properly rendered inside block templates.
 * WordPress block themes sometimes block shortcode execution in patterns/templates.
 *
 * @param string $block_content Outer HTML block content.
 * @param array $block Block parameters.
 * @return string Modified block content.
 */
function egnitech_one_force_render_shortcodes( string $block_content, array $block ): string {
	if ( isset( $block['blockName'] ) && 'core/shortcode' === $block['blockName'] ) {
		return do_shortcode( $block_content );
	}
	return $block_content;
}
add_filter( 'render_block', 'egnitech_one_force_render_shortcodes', 10, 2 );

/**
 * Filter theme.json data to strip dark mode color scheme if dark mode is disabled.
 *
 * @param WP_Theme_JSON_Data $theme_json_data Original theme.json data object.
 * @return WP_Theme_JSON_Data Modified theme.json data object.
 */
function egnitech_one_filter_theme_json( WP_Theme_JSON_Data $theme_json_data ): WP_Theme_JSON_Data {
	if ( ! egnitech_one_is_dark_mode_enabled() ) {
		$data = $theme_json_data->get_data();
		if ( isset( $data['styles']['css'] ) ) {
			$data['styles']['css'] = str_replace( 'color-scheme: light dark;', 'color-scheme: light;', $data['styles']['css'] );
		}
		return new WP_Theme_JSON_Data( $data );
	}
	return $theme_json_data;
}
add_filter( 'wp_theme_json_data_theme', 'egnitech_one_filter_theme_json' );

