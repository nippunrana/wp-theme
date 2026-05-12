<?php
/**
 * EgniTech One — Font Manager
 *
 * Handles curated Google Fonts installation, font assignment to
 * body/headings, and sync with the Site Editor via Global Styles.
 *
 * @package EgniTech_One
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all fonts that are available for use (installed locally).
 * Merges theme-bundled fonts with any installed via the Font Library.
 */
function egnitech_one_get_installed_fonts() {
	$fonts = array();

	// 1. Theme-bundled fonts from theme.json.
	$theme_json_path = get_theme_file_path( 'theme.json' );
	if ( file_exists( $theme_json_path ) ) {
		$theme_json = json_decode( file_get_contents( $theme_json_path ), true );
		if ( isset( $theme_json['settings']['typography']['fontFamilies'] ) ) {
			foreach ( $theme_json['settings']['typography']['fontFamilies'] as $family ) {
				if ( isset( $family['name'] ) && isset( $family['slug'] ) ) {
					$fonts[ $family['slug'] ] = array(
						'name'     => $family['name'],
						'slug'     => $family['slug'],
						'family'   => isset( $family['fontFamily'] ) ? $family['fontFamily'] : $family['name'],
						'source'   => 'theme',
					);
				}
			}
		}
	}

	// 2. Fonts installed via the WP Font Library (wp_font_family CPT).
	$font_families = get_posts( array(
		'post_type'      => 'wp_font_family',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'no_found_rows'  => true,
	) );

	foreach ( $font_families as $family_post ) {
		$content = json_decode( $family_post->post_content, true );
		if ( ! is_array( $content ) ) {
			continue;
		}
		$slug = isset( $content['slug'] ) ? $content['slug'] : sanitize_title( $family_post->post_title );
		$name = isset( $content['name'] ) ? $content['name'] : $family_post->post_title;
		$css_family = isset( $content['fontFamily'] ) ? $content['fontFamily'] : $name;

		$fonts[ $slug ] = array(
			'name'     => $name,
			'slug'     => $slug,
			'family'   => $css_family,
			'source'   => 'library',
		);
	}

	return $fonts;
}

/**
 * Get the current body and heading font assignment from Global Styles.
 */
function egnitech_one_get_font_assignments() {
	$styles = wp_get_global_styles();

	$body_font    = '';
	$heading_font = '';

	// Body font from root typography.
	if ( isset( $styles['typography']['fontFamily'] ) ) {
		$body_font = egnitech_one_parse_font_preset( $styles['typography']['fontFamily'] );
	}

	// Heading font from elements.heading typography.
	if ( isset( $styles['elements']['heading']['typography']['fontFamily'] ) ) {
		$heading_font = egnitech_one_parse_font_preset( $styles['elements']['heading']['typography']['fontFamily'] );
	}

	// If headings not explicitly set, they inherit body.
	if ( empty( $heading_font ) ) {
		$heading_font = $body_font;
	}

	return array(
		'body'    => $body_font,
		'heading' => $heading_font,
	);
}

/**
 * Parse a font preset reference like "var:preset|font-family|inter"
 * or "var(--wp--preset--font-family--inter)" into the slug "inter".
 */
function egnitech_one_parse_font_preset( $value ) {
	// Format: var:preset|font-family|slug
	if ( strpos( $value, 'var:preset|font-family|' ) === 0 ) {
		return substr( $value, strlen( 'var:preset|font-family|' ) );
	}
	// Format: var(--wp--preset--font-family--slug)
	if ( preg_match( '/--wp--preset--font-family--([a-z0-9-]+)/', $value, $m ) ) {
		return $m[1];
	}
	return '';
}
