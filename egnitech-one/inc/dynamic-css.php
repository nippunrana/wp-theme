<?php
/**
 * Dynamic CSS and scripts injected into the <head> or <footer>.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Helper: Get the rendered footer copyright text.
 */
function egnitech_one_get_footer_copyright() {
	$text = get_option( 'egnitech_one_footer_copyright', '' );

	if ( empty( $text ) ) {
		$text = '&copy; {year} {site_name} &middot; All rights reserved';
	}

	$text = str_replace( '{year}', gmdate( 'Y' ), $text );
	$text = str_replace( '{site_name}', get_bloginfo( 'name', 'display' ), $text );

	return wp_kses_post( $text );
}

/**
 * Helper: Get the rendered footer credits text.
 */
function egnitech_one_get_footer_credits() {
	$default = '<a href="https://egnitech.com" rel="nofollow">EgniTech</a> &middot; Built with <a href="https://wordpress.org" rel="nofollow">WordPress</a>';
	$text = get_option( 'egnitech_one_footer_credits', $default );

	return wp_kses_post( $text );
}

/**
 * Consolidated dynamic CSS output for Theme Options.
 * Combines header padding, logo width, blog grid, entry meta, and breadcrumbs into a single <style> tag.
 */
function egnitech_one_dynamic_css() {
	$css = '';

	// 1. Header Padding.
	$desktop_padding_top    = absint( get_option( 'egnitech_one_header_padding_desktop_top', '8' ) );
	$desktop_padding_bottom = absint( get_option( 'egnitech_one_header_padding_desktop_bottom', '8' ) );
	$mobile_padding_top     = absint( get_option( 'egnitech_one_header_padding_mobile_top', '4' ) );
	$mobile_padding_bottom  = absint( get_option( 'egnitech_one_header_padding_mobile_bottom', '4' ) );

	$css .= '.egnitech-site-header { padding-top: ' . $mobile_padding_top . 'px; padding-bottom: ' . $mobile_padding_bottom . 'px; }';
	$css .= '@media (min-width: 768px) { .egnitech-site-header { padding-top: ' . $desktop_padding_top . 'px; padding-bottom: ' . $desktop_padding_bottom . 'px; } }';

	// 2. Logo width, blog grid, entry meta (Phase 2 options).
	$logo_desktop = absint( get_option( 'egnitech_one_logo_width_desktop', 0 ) );
	$logo_mobile  = absint( get_option( 'egnitech_one_logo_width_mobile', 0 ) );
	$meta_author  = get_option( 'egnitech_one_meta_author', 'yes' );
	$meta_date    = get_option( 'egnitech_one_meta_date', 'yes' );
	$meta_cats    = get_option( 'egnitech_one_meta_categories', 'yes' );
	$meta_tags    = get_option( 'egnitech_one_meta_tags', '' );

	if ( $logo_mobile > 0 ) {
		$css .= '.egnitech-header-logo-container img { max-width: ' . $logo_mobile . 'px; height: auto; }';
	}
	if ( $logo_desktop > 0 ) {
		$css .= '@media (min-width: 768px) { .egnitech-header-logo-container img { max-width: ' . $logo_desktop . 'px; } }';
	}

	$css .= '.blog-layout-grid-2 .wp-block-post-template { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--wp--preset--spacing--50, 24px); }';
	$css .= '.blog-layout-grid-3 .wp-block-post-template { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--wp--preset--spacing--50, 24px); }';
	$css .= '@media (max-width: 767px) { .blog-layout-grid-2 .wp-block-post-template, .blog-layout-grid-3 .wp-block-post-template { grid-template-columns: 1fr; } }';

	if ( 'yes' !== $meta_author ) {
		$css .= '.wp-block-post-author { display: none !important; }';
	}
	if ( 'yes' !== $meta_date ) {
		$css .= '.wp-block-post-date { display: none !important; }';
	}
	if ( 'yes' !== $meta_cats ) {
		$css .= '.taxonomy-category.wp-block-post-terms { display: none !important; }';
	}
	if ( 'yes' !== $meta_tags ) {
		$css .= '.taxonomy-post_tag.wp-block-post-terms { display: none !important; }';
	}

	// 3. Breadcrumbs (Phase 3).
	if ( 'yes' === get_option( 'egnitech_one_breadcrumbs', '' ) && is_singular() ) {
		$css .= '.egnitech-breadcrumbs { max-width: var(--wp--style--global--wide-size, 1280px); margin: 0 auto; padding: var(--wp--preset--spacing--40, 16px) var(--wp--preset--spacing--50, 24px); font-size: var(--wp--preset--font-size--small, 0.875rem); color: var(--wp--preset--color--secondary, #555); }';
		$css .= '.egnitech-breadcrumbs a { color: var(--wp--preset--color--secondary, #555); text-decoration: none; }';
		$css .= '.egnitech-breadcrumbs a:hover { color: var(--wp--preset--color--accent, #00D4AA); }';
		$css .= '.egnitech-breadcrumb-sep { margin: 0 6px; opacity: 0.5; }';
		$css .= '.egnitech-breadcrumb-current { color: var(--wp--preset--color--contrast, #111); }';
	}

	// 4. Reading Progress Bar (Phase 3).
	if ( 'yes' === get_option( 'egnitech_one_reading_progress', 'yes' ) ) {
		$progress_height = absint( get_option( 'egnitech_one_reading_progress_height', 2 ) );
		$css .= '.egnitech-progress-bar { height: ' . $progress_height . 'px; }';
	}

	if ( ! empty( $css ) ) {
		echo '<style id="egnitech-one-dynamic-css">' . $css . '</style>' . "\n";
	}
}
add_action( 'wp_head', 'egnitech_one_dynamic_css', 11 );

/**
 * Output the dark mode default preference as a data attribute on <html>.
 * This runs early in wp_head so the JS picks it up before render.
 */
function egnitech_one_dark_mode_default_attr() {
	$default = get_option( 'egnitech_one_dark_mode_default', 'system' );
	echo '<script>document.documentElement.setAttribute("data-default-scheme","' . esc_js( $default ) . '");</script>' . "\n";
}
add_action( 'wp_head', 'egnitech_one_dark_mode_default_attr', 1 );
