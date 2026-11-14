<?php
declare(strict_types=1);

/**
 * Turn brand colours into full theme palettes: the six theme.json slots, each a light-dark() pair.
 *
 * Rules (the design decisions live here, not in a library):
 * - Only Accent (and, in a two-colour palette, Secondary) carries a brand colour. The neutrals get
 *   a faint tint of the brand hue so the site feels on-brand without being loud.
 * - A brand colour keeps its hue and is darkened (light mode) or lightened (dark mode) only as far
 *   as needed to reach WCAG AA 4.5:1 against Surface (and so against Base), because Accent is also
 *   used for text and icons on those. Text drawn on Accent is chosen separately (--egnitech-on-accent).
 * - The neutral lightness levels match the theme's own greys, so layouts look the same.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightness and tint chroma for each neutral slot: [ light [L, C], dark [L, C] ].
 * Chroma is per unit of tint, so tint 0 gives pure greys.
 */
const EGNITECH_ONE_NEUTRAL_RECIPE = array(
	'base'      => array( array( 0.99, 0.003 ), array( 0.17, 0.006 ) ),
	'contrast'  => array( array( 0.20, 0.012 ), array( 0.925, 0.006 ) ),
	'secondary' => array( array( 0.47, 0.016 ), array( 0.74, 0.012 ) ),
	'surface'   => array( array( 0.97, 0.008 ), array( 0.215, 0.009 ) ),
	'border'    => array( array( 0.905, 0.012 ), array( 0.285, 0.012 ) ),
);

/**
 * Build one palette.
 *
 * @param string      $accent    Brand colour for Accent (#rrggbb).
 * @param float       $tint      How strongly the neutrals take the tint hue: 0 none, 1 subtle, 2.5 bold.
 * @param string|null $tint_from Colour whose hue tints the neutrals. Defaults to the accent.
 * @param string|null $secondary Second brand colour for Secondary (muted text), or null for a tinted grey.
 * @return array<string, string> slug => 'light-dark(#light, #dark)'.
 */
function egnitech_one_build_palette( string $accent, float $tint = 1.0, ?string $tint_from = null, ?string $secondary = null ): array {
	$tint_hue = egnitech_one_hex_to_oklch( $tint_from ?? $accent )[2];

	$light = array();
	$dark  = array();
	foreach ( EGNITECH_ONE_NEUTRAL_RECIPE as $slug => [ $l, $d ] ) {
		// Pure-grey Base stays exactly white.
		$light[ $slug ] = egnitech_one_oklch_to_hex( 'base' === $slug && 0.0 === $tint ? 1.0 : $l[0], $l[1] * $tint, $tint_hue );
		$dark[ $slug ]  = egnitech_one_oklch_to_hex( $d[0], $d[1] * $tint, $tint_hue );
	}

	if ( null !== $secondary ) {
		[ , $c, $h ] = egnitech_one_hex_to_oklch( $secondary );
		$c           = min( $c, 0.08 ); // Calm enough for body-size muted text.
		$light['secondary'] = egnitech_one_fit_contrast( 0.45, $c, $h, $light['surface'], -1 );
		$dark['secondary']  = egnitech_one_fit_contrast( 0.76, $c, $h, $dark['surface'], 1 );
	} else {
		[ $l, $c, $h ]      = egnitech_one_hex_to_oklch( $light['secondary'] );
		$light['secondary'] = egnitech_one_fit_contrast( $l, $c, $h, $light['surface'], -1 );
		[ $l, $c, $h ]      = egnitech_one_hex_to_oklch( $dark['secondary'] );
		$dark['secondary']  = egnitech_one_fit_contrast( $l, $c, $h, $dark['surface'], 1 );
	}

	[ $l, $c, $h ]   = egnitech_one_hex_to_oklch( $accent );
	// Fitted against Surface, the harder of the two in both modes (header icons hover Accent on Surface).
	$light['accent'] = egnitech_one_fit_contrast( $l, $c, $h, $light['surface'], -1 );
	$dark['accent']  = egnitech_one_fit_contrast( $l, $c, $h, $dark['surface'], 1 );

	$palette = array();
	foreach ( array( 'base', 'contrast', 'accent', 'secondary', 'surface', 'border' ) as $slug ) {
		$palette[ $slug ] = sprintf( 'light-dark(%s, %s)', $light[ $slug ], $dark[ $slug ] );
	}
	return $palette;
}

/**
 * Move a colour's lightness (keeping hue and chroma) until it reaches 4.5:1 against a background.
 * A colour that already passes is returned unchanged, so brand colours stay exact where possible.
 *
 * @param int $direction -1 to darken (light backgrounds), 1 to lighten (dark backgrounds).
 */
function egnitech_one_fit_contrast( float $lightness, float $chroma, float $hue, string $against, int $direction ): string {
	$hex = egnitech_one_oklch_to_hex( $lightness, $chroma, $hue );
	while ( egnitech_one_contrast_ratio( $hex, $against ) < 4.5 && $lightness > 0.0 && $lightness < 1.0 ) {
		$lightness += 0.01 * $direction;
		$hex        = egnitech_one_oklch_to_hex( $lightness, $chroma, $hue );
	}
	return $hex;
}

/**
 * Plain-English name of a colour's hue, for palette labels.
 */
function egnitech_one_hue_name( string $hex ): string {
	$h     = egnitech_one_hex_to_oklch( $hex )[2];
	$names = array(
		27  => __( 'Red', 'egnitech-one' ),
		55  => __( 'Orange', 'egnitech-one' ),
		95  => __( 'Yellow', 'egnitech-one' ),
		125 => __( 'Lime', 'egnitech-one' ),
		145 => __( 'Green', 'egnitech-one' ),
		180 => __( 'Teal', 'egnitech-one' ),
		215 => __( 'Cyan', 'egnitech-one' ),
		255 => __( 'Blue', 'egnitech-one' ),
		280 => __( 'Indigo', 'egnitech-one' ),
		305 => __( 'Purple', 'egnitech-one' ),
		340 => __( 'Pink', 'egnitech-one' ),
	);
	$best = '';
	$gap  = 360.0;
	foreach ( $names as $center => $name ) {
		$d = egnitech_one_hue_distance( $h, (float) $center );
		if ( $d < $gap ) {
			$gap  = $d;
			$best = $name;
		}
	}
	return $best;
}

/**
 * Up to three palettes built from a logo's brand colours.
 *
 * One colour: subtle tint, pure greys, bold tint.
 * Two colours: each colour as Accent, then the first as Accent with the second as Secondary
 * (colours that share a logo are already known to work together).
 * Three colours: each colour as Accent.
 *
 * @param list<string> $seeds Brand colours, best first.
 * @return list<array{label: string, colors: array<string, string>}>
 */
function egnitech_one_logo_palettes( array $seeds ): array {
	if ( ! $seeds ) {
		return array();
	}

	$palettes = array();
	foreach ( $seeds as $seed ) {
		/* translators: %s: colour name, e.g. Red. */
		$palettes[] = array( 'label' => sprintf( __( 'Logo %s', 'egnitech-one' ), egnitech_one_hue_name( $seed ) ), 'colors' => egnitech_one_build_palette( $seed ) );
	}

	$first = egnitech_one_hue_name( $seeds[0] );
	if ( 2 === count( $seeds ) ) {
		$palettes[] = array(
			/* translators: 1: first colour name, 2: second colour name. */
			'label'  => sprintf( __( '%1$s with %2$s', 'egnitech-one' ), $first, egnitech_one_hue_name( $seeds[1] ) ),
			'colors' => egnitech_one_build_palette( $seeds[0], 1.0, $seeds[1], $seeds[1] ),
		);
	} elseif ( 1 === count( $seeds ) ) {
		/* translators: %s: colour name. */
		$palettes[] = array( 'label' => sprintf( __( '%s on clean greys', 'egnitech-one' ), $first ), 'colors' => egnitech_one_build_palette( $seeds[0], 0.0 ) );
		/* translators: %s: colour name. */
		$palettes[] = array( 'label' => sprintf( __( 'Bold %s', 'egnitech-one' ), $first ), 'colors' => egnitech_one_build_palette( $seeds[0], 2.5 ) );
	}

	return array_slice( $palettes, 0, 3 );
}

/**
 * The theme's own palette entries (theme.json, before any Site Editor change).
 * Origin 'base' is core + theme without the user's Global Styles, through the public API.
 *
 * @return list<array{slug: string, color: string, name: string}>
 */
function egnitech_one_theme_json_palette(): array {
	return array_values( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ), array( 'origin' => 'base' ) ) );
}

/**
 * The three ready-made palettes: the theme's own (read from theme.json), plus two common ones.
 *
 * @return list<array{label: string, colors: array<string, string>}>
 */
function egnitech_one_preset_palettes(): array {
	$defaults = array();
	foreach ( egnitech_one_theme_json_palette() as $entry ) {
		$defaults[ $entry['slug'] ] = $entry['color'];
	}

	return array(
		array( 'label' => __( 'Theme default', 'egnitech-one' ), 'colors' => $defaults ),
		array( 'label' => __( 'Ocean', 'egnitech-one' ), 'colors' => egnitech_one_build_palette( '#1d4ed8' ) ),
		array( 'label' => __( 'Forest', 'egnitech-one' ), 'colors' => egnitech_one_build_palette( '#15803d' ) ),
	);
}
