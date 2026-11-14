<?php
declare(strict_types=1);

/**
 * Pick the brand colours out of a logo image.
 *
 * Modelled on Google's Material Color Utilities (quantize, then score): colours are grouped,
 * transparent pixels, the background and greys are set aside, and the rest are ranked by how
 * much of the logo they cover and how vivid they are. Only GD or Imagick is needed; with
 * neither, the caller gets a status saying so instead of an error.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bump when the extraction logic changes, so cached results are thrown away.
 */
const EGNITECH_ONE_LOGO_COLORS_VERSION = 1;

/**
 * Brand colours of an image attachment, cached until the file or the algorithm changes.
 *
 * @param int $attachment_id Image attachment ID.
 * @return array{status: string, seeds: list<string>, mime: string}
 *         status: ok | no_logo | no_library | unreadable | no_brand_color.
 */
function egnitech_one_logo_seed_colors( int $attachment_id ): array {
	$file = $attachment_id ? get_attached_file( $attachment_id ) : false;
	if ( ! $file || ! is_file( $file ) ) {
		return array( 'status' => 'no_logo', 'seeds' => array(), 'mime' => '' );
	}

	$mime      = (string) get_post_mime_type( $attachment_id );
	$cache_key = 'egnitech_one_logo_colors_' . md5( $file . '|' . filemtime( $file ) . '|' . EGNITECH_ONE_LOGO_COLORS_VERSION );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$pixels = egnitech_one_read_logo_pixels( $file, $mime );
	if ( is_string( $pixels ) ) {
		$result = array( 'status' => $pixels, 'seeds' => array(), 'mime' => $mime );
	} else {
		$seeds  = egnitech_one_pick_seed_colors( $pixels['grid'], $pixels['width'], $pixels['height'] );
		$result = array( 'status' => $seeds ? 'ok' : 'no_brand_color', 'seeds' => $seeds, 'mime' => $mime );
	}

	// A missing library or format support can be fixed without the file changing, so don't keep those answers long.
	set_transient( $cache_key, $result, in_array( $result['status'], array( 'no_library', 'unreadable' ), true ) ? HOUR_IN_SECONDS : MONTH_IN_SECONDS );
	return $result;
}

/**
 * Read an image, shrunk to at most 128px on its longest side, as RGBA pixels.
 *
 * @return array{grid: list<array{0: int, 1: int, 2: int, 3: float}>, width: int, height: int}|string
 *         Pixels (row by row, opacity 0-1), or the status 'no_library' / 'unreadable'.
 */
function egnitech_one_read_logo_pixels( string $file, string $mime ): array|string {
	$has_gd      = function_exists( 'imagecreatefromstring' );
	$has_imagick = class_exists( 'Imagick' );
	if ( ! $has_gd && ! $has_imagick ) {
		return 'no_library';
	}

	// SVG is never handed to Imagick: rendering it can make the server read other files or URLs.
	if ( str_contains( $mime, 'svg' ) ) {
		return 'unreadable';
	}

	wp_raise_memory_limit( 'image' );
	$max = 128;

	// Decode with GD only when this GD build reads the file's real type, so nothing is
	// suppressed: imagecreatefromstring() warns on data it cannot read.
	$gd_types = array(
		'image/png'  => IMG_PNG,
		'image/jpeg' => IMG_JPG,
		'image/gif'  => IMG_GIF,
		'image/webp' => IMG_WEBP,
		'image/bmp'  => IMG_BMP,
		'image/avif' => IMG_AVIF,
	);
	$real_mime = (string) wp_get_image_mime( $file );

	if ( $has_gd && isset( $gd_types[ $real_mime ] ) && ( imagetypes() & $gd_types[ $real_mime ] ) ) {
		$src = imagecreatefromstring( (string) file_get_contents( $file ) );
		if ( $src ) {
			$w     = imagesx( $src );
			$h     = imagesy( $src );
			$scale = min( 1, $max / max( $w, $h ) );
			$nw    = max( 1, (int) round( $w * $scale ) );
			$nh    = max( 1, (int) round( $h * $scale ) );

			// Copying into a true-colour canvas also turns palette PNGs/GIFs into real RGB values:
			// imagecolorat() on a palette image returns an index, which reads as near-black.
			$img = imagecreatetruecolor( $nw, $nh );
			imagealphablending( $img, false );
			imagesavealpha( $img, true );
			imagefill( $img, 0, 0, imagecolorallocatealpha( $img, 0, 0, 0, 127 ) );
			imagecopyresampled( $img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h );

			$grid = array();
			for ( $y = 0; $y < $nh; $y++ ) {
				for ( $x = 0; $x < $nw; $x++ ) {
					$c      = imagecolorat( $img, $x, $y );
					$grid[] = array( ( $c >> 16 ) & 255, ( $c >> 8 ) & 255, $c & 255, 1 - ( ( $c >> 24 ) & 127 ) / 127 );
				}
			}
			return array( 'grid' => $grid, 'width' => $nw, 'height' => $nh );
		}
	}

	// GD is missing or can't read this format (e.g. AVIF on some builds): try Imagick.
	if ( $has_imagick ) {
		try {
			$im = new Imagick( $file );
			$im->setIteratorIndex( 0 );
			$im->thumbnailImage( $max, $max, true );
			$nw  = $im->getImageWidth();
			$nh  = $im->getImageHeight();
			$raw = $im->exportImagePixels( 0, 0, $nw, $nh, 'RGBA', Imagick::PIXEL_CHAR );
			$im->clear();

			$grid = array();
			foreach ( array_chunk( $raw, 4 ) as $p ) {
				$grid[] = array( (int) $p[0], (int) $p[1], (int) $p[2], $p[3] / 255 );
			}
			return array( 'grid' => $grid, 'width' => $nw, 'height' => $nh );
		} catch ( Exception $e ) {
			return 'unreadable';
		}
	}

	return 'unreadable';
}

/**
 * A solid background colour: the border is fully opaque and at least 60% one colour.
 *
 * @param list<array{0: int, 1: int, 2: int, 3: float}> $grid Pixels, row by row.
 * @return array{0: int, 1: int, 2: int, 3: float}|null
 */
function egnitech_one_detect_background( array $grid, int $width, int $height ): ?array {
	$groups = array();
	$count  = 0;
	foreach ( $grid as $i => $p ) {
		$x = $i % $width;
		$y = intdiv( $i, $width );
		if ( 0 !== $x && 0 !== $y && $width - 1 !== $x && $height - 1 !== $y ) {
			continue;
		}
		if ( $p[3] < 0.5 ) {
			return null; // A transparent edge means there is no background to remove.
		}
		$groups[ ( $p[0] >> 4 ) . '-' . ( $p[1] >> 4 ) . '-' . ( $p[2] >> 4 ) ][] = $p;
		++$count;
	}
	if ( ! $groups ) {
		return null;
	}
	usort( $groups, static fn( array $a, array $b ): int => count( $b ) <=> count( $a ) );
	return count( $groups[0] ) >= 0.6 * $count ? $groups[0][0] : null;
}

/**
 * Rank the brand colours in a pixel grid.
 *
 * @param list<array{0: int, 1: int, 2: int, 3: float}> $grid   Pixels, row by row.
 * @param int                                           $width  Grid width.
 * @param int                                           $height Grid height.
 * @return list<string> Up to three #rrggbb colours, best first. Empty when the logo has no colour.
 */
function egnitech_one_pick_seed_colors( array $grid, int $width, int $height ): array {
	// 1. A solid white, black or grey background is left out, so it does not shrink every colour's share.
	// A colourful one is kept: white lettering on a red square means red is the brand colour.
	$background = egnitech_one_detect_background( $grid, $width, $height );
	if ( $background && egnitech_one_rgb_to_oklch( $background[0], $background[1], $background[2] )[1] >= 0.04 ) {
		$background = null;
	}


	// 2. Quantize: group opaque, non-background pixels into 32 levels per channel.
	$buckets = array();
	$total   = 0;
	foreach ( $grid as $p ) {
		if ( $p[3] < 0.5 ) {
			continue;
		}
		if ( $background && ( ( $p[0] - $background[0] ) ** 2 + ( $p[1] - $background[1] ) ** 2 + ( $p[2] - $background[2] ) ** 2 ) < 24 ** 2 ) {
			continue;
		}
		$key = ( ( $p[0] >> 3 ) << 10 ) | ( ( $p[1] >> 3 ) << 5 ) | ( $p[2] >> 3 );
		if ( ! isset( $buckets[ $key ] ) ) {
			$buckets[ $key ] = array( 0, 0, 0, 0 );
		}
		$buckets[ $key ][0] += $p[0];
		$buckets[ $key ][1] += $p[1];
		$buckets[ $key ][2] += $p[2];
		$buckets[ $key ][3]++;
		++$total;
	}
	if ( 0 === $total ) {
		return array();
	}

	// 3. Keep colourful buckets only. Greys, near-black and near-white become neutrals, not brand colours.
	$colors = array();
	foreach ( $buckets as $bucket ) {
		$r = (int) round( $bucket[0] / $bucket[3] );
		$g = (int) round( $bucket[1] / $bucket[3] );
		$b = (int) round( $bucket[2] / $bucket[3] );

		[ $l, $c, $h ] = egnitech_one_rgb_to_oklch( $r, $g, $b );
		if ( $c >= 0.04 && $l >= 0.12 && $l <= 0.97 ) {
			$colors[] = array(
				'hex'    => sprintf( '#%02x%02x%02x', $r, $g, $b ),
				'chroma' => $c,
				'hue'    => $h,
				'share'  => $bucket[3] / $total,
			);
		}
	}

	// 4. Score as Material does: a colour's share counts everything within 15 degrees of its hue,
	// so anti-aliased edges add to the colour they belong to, and vivid colours get a bonus.
	foreach ( $colors as &$color ) {
		$share = 0.0;
		foreach ( $colors as $other ) {
			if ( egnitech_one_hue_distance( $color['hue'], $other['hue'] ) <= 15 ) {
				$share += $other['share'];
			}
		}
		$cam_chroma        = $color['chroma'] * 400; // Rough CAM16 chroma, the scale Material's weights assume.
		$color['hue_share'] = $share;
		$color['score']     = $share * 70 + ( $cam_chroma - 48 ) * ( $cam_chroma >= 48 ? 0.3 : 0.1 );
	}
	unset( $color );

	$colors = array_filter( $colors, static fn( array $c ): bool => $c['hue_share'] >= 0.03 );
	usort( $colors, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );

	// 5. Pick up to three that are clearly different hues.
	$seeds = array();
	foreach ( $colors as $candidate ) {
		foreach ( $seeds as $seed ) {
			if ( egnitech_one_hue_distance( $candidate['hue'], $seed['hue'] ) < 25 ) {
				continue 2;
			}
		}
		$seeds[] = $candidate;
		if ( 3 === count( $seeds ) ) {
			break;
		}
	}

	return array_map( static fn( array $s ): string => $s['hex'], $seeds );
}
