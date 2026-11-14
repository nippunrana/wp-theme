<?php
declare(strict_types=1);

/**
 * Colour maths for the logo palette system: sRGB <-> OKLCH and WCAG contrast.
 *
 * OKLCH is used because equal steps in its lightness look equal to the eye whatever the hue,
 * so a brand colour can be darkened or lightened without its hue drifting.
 * Formulas: https://bottosson.github.io/posts/oklab/ and WCAG 2.2 relative luminance.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * sRGB channel (0-255) to linear light (0-1).
 */
function egnitech_one_srgb_to_linear( float $channel ): float {
	$c = $channel / 255;
	return $c <= 0.04045 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
}

/**
 * Linear light (0-1) to sRGB (0-1, unclamped).
 */
function egnitech_one_linear_to_srgb( float $c ): float {
	return $c <= 0.0031308 ? 12.92 * $c : 1.055 * ( $c ** ( 1 / 2.4 ) ) - 0.055;
}

/**
 * Convert sRGB (0-255 per channel) to OKLCH.
 *
 * @return array{0: float, 1: float, 2: float} Lightness 0-1, chroma 0-~0.37, hue 0-360 degrees.
 */
function egnitech_one_rgb_to_oklch( int $r, int $g, int $b ): array {
	$lr = egnitech_one_srgb_to_linear( (float) $r );
	$lg = egnitech_one_srgb_to_linear( (float) $g );
	$lb = egnitech_one_srgb_to_linear( (float) $b );

	$l = ( 0.4122214708 * $lr + 0.5363325363 * $lg + 0.0514459929 * $lb ) ** ( 1 / 3 );
	$m = ( 0.2119034982 * $lr + 0.6806995451 * $lg + 0.1073969566 * $lb ) ** ( 1 / 3 );
	$s = ( 0.0883024619 * $lr + 0.2817188376 * $lg + 0.6299787005 * $lb ) ** ( 1 / 3 );

	$ok_l = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
	$ok_a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
	$ok_b = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

	$hue = rad2deg( atan2( $ok_b, $ok_a ) );
	return array( $ok_l, sqrt( $ok_a * $ok_a + $ok_b * $ok_b ), $hue < 0 ? $hue + 360 : $hue );
}

/**
 * Convert OKLCH to sRGB (0-1 per channel). Values outside 0-1 mean the colour is out of gamut.
 *
 * @return array{0: float, 1: float, 2: float}
 */
function egnitech_one_oklch_to_srgb( float $lightness, float $chroma, float $hue ): array {
	$a = $chroma * cos( deg2rad( $hue ) );
	$b = $chroma * sin( deg2rad( $hue ) );

	$l = ( $lightness + 0.3963377774 * $a + 0.2158037573 * $b ) ** 3;
	$m = ( $lightness - 0.1055613458 * $a - 0.0638541728 * $b ) ** 3;
	$s = ( $lightness - 0.0894841775 * $a - 1.2914855480 * $b ) ** 3;

	return array(
		egnitech_one_linear_to_srgb( 4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s ),
		egnitech_one_linear_to_srgb( -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s ),
		egnitech_one_linear_to_srgb( -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s ),
	);
}

/**
 * Convert OKLCH to a #rrggbb hex string. An out-of-gamut colour keeps its lightness and hue
 * and loses chroma until it fits, which is how browsers map OKLCH colours too.
 */
function egnitech_one_oklch_to_hex( float $lightness, float $chroma, float $hue ): string {
	$lightness = max( 0.0, min( 1.0, $lightness ) );
	$in_gamut  = static function ( array $rgb ): bool {
		foreach ( $rgb as $channel ) {
			if ( $channel < -0.0005 || $channel > 1.0005 ) {
				return false;
			}
		}
		return true;
	};

	$rgb = egnitech_one_oklch_to_srgb( $lightness, $chroma, $hue );
	if ( ! $in_gamut( $rgb ) ) {
		$low  = 0.0;
		$high = $chroma;
		for ( $i = 0; $i < 20; $i++ ) {
			$mid = ( $low + $high ) / 2;
			if ( $in_gamut( egnitech_one_oklch_to_srgb( $lightness, $mid, $hue ) ) ) {
				$low = $mid;
			} else {
				$high = $mid;
			}
		}
		$rgb = egnitech_one_oklch_to_srgb( $lightness, $low, $hue );
	}

	return sprintf(
		'#%02x%02x%02x',
		(int) round( max( 0.0, min( 1.0, $rgb[0] ) ) * 255 ),
		(int) round( max( 0.0, min( 1.0, $rgb[1] ) ) * 255 ),
		(int) round( max( 0.0, min( 1.0, $rgb[2] ) ) * 255 )
	);
}

/**
 * Parse #rrggbb into 0-255 channels.
 *
 * @return array{0: int, 1: int, 2: int}
 */
function egnitech_one_hex_to_rgb( string $hex ): array {
	$hex = ltrim( $hex, '#' );
	return array( (int) hexdec( substr( $hex, 0, 2 ) ), (int) hexdec( substr( $hex, 2, 2 ) ), (int) hexdec( substr( $hex, 4, 2 ) ) );
}

/**
 * OKLCH of a #rrggbb hex string.
 *
 * @return array{0: float, 1: float, 2: float}
 */
function egnitech_one_hex_to_oklch( string $hex ): array {
	[ $r, $g, $b ] = egnitech_one_hex_to_rgb( $hex );
	return egnitech_one_rgb_to_oklch( $r, $g, $b );
}

/**
 * WCAG 2.x contrast ratio between two #rrggbb colours (1 to 21).
 */
function egnitech_one_contrast_ratio( string $hex_a, string $hex_b ): float {
	$luminance = static function ( string $hex ): float {
		[ $r, $g, $b ] = egnitech_one_hex_to_rgb( $hex );
		return 0.2126 * egnitech_one_srgb_to_linear( (float) $r )
			+ 0.7152 * egnitech_one_srgb_to_linear( (float) $g )
			+ 0.0722 * egnitech_one_srgb_to_linear( (float) $b );
	};
	$a = $luminance( $hex_a );
	$b = $luminance( $hex_b );
	return ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 );
}

/**
 * Shortest distance between two hues, in degrees (0-180).
 */
function egnitech_one_hue_distance( float $a, float $b ): float {
	$d = fmod( abs( $a - $b ), 360.0 );
	return $d > 180 ? 360 - $d : $d;
}
