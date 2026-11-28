<?php
declare(strict_types=1);

/**
 * Product trust badges: the icons and colours offered for them, and each product's badge list (up to
 * EGNITECH_ONE_TRUST_BADGE_LIMIT) with its cleaning, reading and rendering. The meta box, its
 * save handler and the [egnitech_product_trust_badges] shortcode are hooked in
 * inc/theme-setup.php; the meta box markup is in inc/woocommerce-trust-badges-admin.php.
 *
 * Icons are stored by name, never as markup: a theme icon ("lock") or a WordPress core
 * icon ("core/shield"). The product page prints only the chosen icons, inline.
 *
 * Icon credits:
 * - zap, shield and lock: Feather (https://feathericons.com), MIT License,
 *   Copyright (c) 2013-present Cole Bemis.
 * - All other theme icons: Lucide 1.79.0 (https://lucide.dev), ISC License,
 *   Copyright (c) 2026 Lucide Icons and Contributors. Permission to use, copy, modify,
 *   and/or distribute this software for any purpose with or without fee is hereby
 *   granted, provided that the above copyright notice and this permission notice appear
 *   in all copies. THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL
 *   WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 *   MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL,
 *   DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
 *   LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER
 *   TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS
 *   SOFTWARE.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Most badges a product can have. Enforced on save, not only in the admin screen. */
const EGNITECH_ONE_TRUST_BADGE_LIMIT = 10;

/** Icon for a new badge, and for a stored icon that is no longer offered. */
const EGNITECH_ONE_TRUST_BADGE_ICON = 'circle-check';

/** Product meta holding the badge list. */
const EGNITECH_ONE_TRUST_BADGES_META = '_egnitech_trust_badges';

/** Theme Options setting holding the site-wide badge style, and the style used until it is saved. */
const EGNITECH_ONE_TRUST_BADGE_STYLE_OPTION = 'egnitech_one_woocommerce_trust_badge_style';
const EGNITECH_ONE_TRUST_BADGE_STYLE        = 'soft';

/** Product meta holding a product's own badge style; empty means the site's. */
const EGNITECH_ONE_TRUST_BADGE_STYLE_META = '_egnitech_trust_badge_style';

/**
 * The three fixed rows products had before the badge list, and what each showed when its
 * fields were left empty. Used only for products whose badge list was never saved.
 *
 * @return array<int, array{text: string, icon: string}>
 */
function egnitech_one_trust_badge_defaults(): array {
	return array(
		1 => array(
			'text' => __( 'Eco-Friendly UV Tech', 'egnitech-one' ),
			'icon' => 'zap',
		),
		2 => array(
			'text' => __( '1 Year Warranty', 'egnitech-one' ),
			'icon' => 'shield',
		),
		3 => array(
			'text' => __( 'Secure Checkout', 'egnitech-one' ),
			'icon' => 'lock',
		),
	);
}

/**
 * The theme's own icons: inner SVG markup on a 24px grid, drawn as 2px outlines.
 *
 * zap, shield and lock are the exact drawings the badges first shipped with, so products
 * saved before icons could be chosen look the same.
 *
 * @return array<string, array{label: string, svg: string}>
 */
function egnitech_one_trust_badge_theme_icons(): array {
	return array(
		'zap'              => array( 'label' => __( 'Lightning', 'egnitech-one' ), 'svg' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>' ),
		'shield'           => array( 'label' => __( 'Shield', 'egnitech-one' ), 'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>' ),
		'lock'             => array( 'label' => __( 'Lock', 'egnitech-one' ), 'svg' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>' ),
		'shield-check'     => array( 'label' => __( 'Shield with tick', 'egnitech-one' ), 'svg' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /><path d="m9 12 2 2 4-4" />' ),
		'badge-check'      => array( 'label' => __( 'Verified', 'egnitech-one' ), 'svg' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><path d="m16 9-5.5 5.5L8 12" />' ),
		'circle-check'     => array( 'label' => __( 'Tick', 'egnitech-one' ), 'svg' => '<circle cx="12" cy="12" r="10" /><path d="m16 9-5.5 5.5L8 12" />' ),
		'award'            => array( 'label' => __( 'Award', 'egnitech-one' ), 'svg' => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526" /><circle cx="12" cy="8" r="6" />' ),
		'star'             => array( 'label' => __( 'Star', 'egnitech-one' ), 'svg' => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />' ),
		'thumbs-up'        => array( 'label' => __( 'Thumbs up', 'egnitech-one' ), 'svg' => '<path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z" /><path d="M7 10v12" />' ),
		'heart'            => array( 'label' => __( 'Heart', 'egnitech-one' ), 'svg' => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />' ),
		'truck'            => array( 'label' => __( 'Delivery', 'egnitech-one' ), 'svg' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />' ),
		'package'          => array( 'label' => __( 'Package', 'egnitech-one' ), 'svg' => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" /><path d="M12 22V12" /><polyline points="3.29 7 12 12 20.71 7" /><path d="m7.5 4.27 9 5.15" />' ),
		'rotate-ccw'       => array( 'label' => __( 'Returns', 'egnitech-one' ), 'svg' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" /><path d="M3 3v5h5" />' ),
		'credit-card'      => array( 'label' => __( 'Card payment', 'egnitech-one' ), 'svg' => '<rect width="20" height="14" x="2" y="5" rx="2" /><line x1="2" x2="22" y1="10" y2="10" /><path d="M6 14h2" />' ),
		'banknote'         => array( 'label' => __( 'Cash', 'egnitech-one' ), 'svg' => '<rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />' ),
		'gift'             => array( 'label' => __( 'Gift', 'egnitech-one' ), 'svg' => '<path d="M12 7v14" /><path d="M20 11v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8" /><path d="M7.5 7a1 1 0 0 1 0-5A4.8 8 0 0 1 12 7a4.8 8 0 0 1 4.5-5 1 1 0 0 1 0 5" /><rect x="3" y="7" width="18" height="4" rx="1" />' ),
		'headset'          => array( 'label' => __( 'Support', 'egnitech-one' ), 'svg' => '<path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z" /><path d="M21 16v2a4 4 0 0 1-4 4h-5" />' ),
		'clock'            => array( 'label' => __( 'Clock', 'egnitech-one' ), 'svg' => '<circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />' ),
		'wrench'           => array( 'label' => __( 'Service', 'egnitech-one' ), 'svg' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z" />' ),
		'leaf'             => array( 'label' => __( 'Leaf', 'egnitech-one' ), 'svg' => '<path d="M11 20a10 10 0 0010-10 25.9 25.9 0 00-1.04-7.281 1 1 0 00-1.755-.325C15.833 5.5 13 5.5 9.8 6.1A7 7 0 0011 20" /><path d="M2 21a5 5 0 012.911-4.544C7.613 15.212 8.351 15.24 11 13" />' ),
		'recycle'          => array( 'label' => __( 'Recycle', 'egnitech-one' ), 'svg' => '<path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5" /><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12" /><path d="m14 16-3 3 3 3" /><path d="M8.293 13.596 7.196 9.5 3.1 10.598" /><path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843" /><path d="m13.378 9.633 4.096 1.098 1.097-4.096" />' ),
		'sun'              => array( 'label' => __( 'Sun', 'egnitech-one' ), 'svg' => '<circle cx="12" cy="12" r="4" /><path d="M12 2v2" /><path d="M12 20v2" /><path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" /><path d="M2 12h2" /><path d="M20 12h2" /><path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />' ),
		'droplet'          => array( 'label' => __( 'Water drop', 'egnitech-one' ), 'svg' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z" />' ),
		'battery-full'     => array( 'label' => __( 'Battery', 'egnitech-one' ), 'svg' => '<path d="M10 10v4" /><path d="M14 10v4" /><path d="M22 14v-4" /><path d="M6 10v4" /><rect x="2" y="6" width="16" height="12" rx="2" />' ),
		'battery-charging' => array( 'label' => __( 'Charging', 'egnitech-one' ), 'svg' => '<path d="m11 7-3 5h4l-3 5" /><path d="M14.856 6H16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.935" /><path d="M22 14v-4" /><path d="M5.14 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2.936" />' ),
		'plug'             => array( 'label' => __( 'Plug', 'egnitech-one' ), 'svg' => '<path d="M12 22v-5" /><path d="M15 8V2" /><path d="M17 8a1 1 0 0 1 1 1v4a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1z" /><path d="M9 8V2" />' ),
		'map-pin'          => array( 'label' => __( 'Location', 'egnitech-one' ), 'svg' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /><circle cx="12" cy="10" r="3" />' ),
		'globe'            => array( 'label' => __( 'Globe', 'egnitech-one' ), 'svg' => '<circle cx="12" cy="12" r="10" /><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" /><path d="M2 12h20" />' ),
	);
}

/**
 * The badge colours, in the order a badge without a chosen colour takes them (by its
 * position in the list). Each is light enough for the pill's fixed near-black text and icon:
 * 7.8:1 contrast or more, WCAG AAA. The hex values live only here; the page and the meta
 * box both read them.
 *
 * @return array<string, array{label: string, hex: string}>
 */
function egnitech_one_trust_badge_colors(): array {
	return array(
		'sunflower' => array( 'label' => __( 'Sunflower', 'egnitech-one' ), 'hex' => '#FFD43B' ),
		'mint'      => array( 'label' => __( 'Mint', 'egnitech-one' ), 'hex' => '#38D9A9' ),
		'coral'     => array( 'label' => __( 'Coral', 'egnitech-one' ), 'hex' => '#FF8787' ),
		'sky'       => array( 'label' => __( 'Sky', 'egnitech-one' ), 'hex' => '#74C0FC' ),
		'lime'      => array( 'label' => __( 'Lime', 'egnitech-one' ), 'hex' => '#A9E34B' ),
		'rose'      => array( 'label' => __( 'Rose', 'egnitech-one' ), 'hex' => '#F783AC' ),
		'tangerine' => array( 'label' => __( 'Tangerine', 'egnitech-one' ), 'hex' => '#FFA94D' ),
		'aqua'      => array( 'label' => __( 'Aqua', 'egnitech-one' ), 'hex' => '#3BC9DB' ),
		'lavender'  => array( 'label' => __( 'Lavender', 'egnitech-one' ), 'hex' => '#B197FC' ),
		'leaf'      => array( 'label' => __( 'Leaf', 'egnitech-one' ), 'hex' => '#69DB7C' ),
	);
}

/**
 * The badge styles offered in Theme Options and per product. Each is a class on the badge
 * strip, styled in patterns/single-product-details/style.css.
 *
 * @return array<string, array{label: string, description: string}>
 */
function egnitech_one_trust_badge_styles(): array {
	return array(
		'soft'    => array( 'label' => __( 'Soft', 'egnitech-one' ), 'description' => __( 'Pale pill, coloured icon disc', 'egnitech-one' ) ),
		'glossy'  => array( 'label' => __( 'Glossy', 'egnitech-one' ), 'description' => __( 'Full colour with depth', 'egnitech-one' ) ),
		'duotone' => array( 'label' => __( 'Duotone', 'egnitech-one' ), 'description' => __( 'Two-colour gradient', 'egnitech-one' ) ),
	);
}

/**
 * The site's badge style: the saved one, or the default when it is unset or not offered.
 */
function egnitech_one_site_trust_badge_style(): string {
	$style = egnitech_one_get_option( EGNITECH_ONE_TRUST_BADGE_STYLE_OPTION, EGNITECH_ONE_TRUST_BADGE_STYLE );
	return is_string( $style ) && isset( egnitech_one_trust_badge_styles()[ $style ] ) ? $style : EGNITECH_ONE_TRUST_BADGE_STYLE;
}

/**
 * Cleans a product's own badge style (the meta's sanitize callback): a style that is not
 * offered becomes '', which means the site's style.
 */
function egnitech_one_sanitize_product_trust_badge_style( mixed $style ): string {
	return is_string( $style ) && isset( egnitech_one_trust_badge_styles()[ $style ] ) ? $style : '';
}

/**
 * The style a product's badges are drawn in: its own, or the site's when it has none.
 */
function egnitech_one_trust_badge_style( int $product_id ): string {
	$style = egnitech_one_sanitize_product_trust_badge_style( get_post_meta( $product_id, EGNITECH_ONE_TRUST_BADGE_STYLE_META, true ) );
	return '' === $style ? egnitech_one_site_trust_badge_style() : $style;
}

/**
 * Cached product pages carry the badge style, so clear them when it changes.
 */
function egnitech_one_purge_cache_on_trust_badge_style_change(): void {
	if ( function_exists( 'egnitech_one_purge_all_site_cache' ) ) {
		egnitech_one_purge_all_site_cache();
	}
}
add_action( 'update_option_egnitech_one_woocommerce_trust_badge_style', 'egnitech_one_purge_cache_on_trust_badge_style_change' );
add_action( 'add_option_egnitech_one_woocommerce_trust_badge_style', 'egnitech_one_purge_cache_on_trust_badge_style_change' );

/**
 * A badge's colour name: its own, or, when it has none, the palette colour at its position
 * in the list (switched-off badges counted), so neighbours differ without anyone choosing.
 *
 * @param string $color    The stored colour name, or ''.
 * @param int    $position The badge's 0-based position in the product's list.
 */
function egnitech_one_trust_badge_color( string $color, int $position ): string {
	$colors = egnitech_one_trust_badge_colors();
	if ( isset( $colors[ $color ] ) ) {
		return $color;
	}
	$names = array_keys( $colors );
	return $names[ $position % count( $names ) ];
}

/**
 * WordPress's own icons, core collection only (other collections belong to plugins).
 *
 * @return array<string, string> Icon name ("core/shield") => label.
 */
function egnitech_one_trust_badge_core_icons(): array {
	$icons = array();
	foreach ( WP_Icons_Registry::get_instance()->get_registered_icons() as $icon ) {
		if ( str_starts_with( $icon['name'], 'core/' ) && ! empty( $icon['content'] ) ) {
			$icons[ $icon['name'] ] = $icon['label'];
		}
	}
	return $icons;
}

/**
 * The complete <svg> for a badge icon name, or '' when the name is not an offered icon.
 *
 * @param string $name A theme icon ("lock") or a WordPress core icon ("core/shield").
 */
function egnitech_one_trust_badge_icon_svg( string $name ): string {
	if ( str_starts_with( $name, 'core/' ) ) {
		$icon = WP_Icons_Registry::get_instance()->get_registered_icon( $name );
		if ( empty( $icon['content'] ) ) {
			return '';
		}

		// Core icons are solid shapes with no size of their own.
		$svg = new WP_HTML_Tag_Processor( $icon['content'] );
		if ( ! $svg->next_tag( 'svg' ) ) {
			return '';
		}
		$svg->add_class( 'trust-icon' );
		$svg->add_class( 'trust-icon--solid' );
		$svg->set_attribute( 'width', '16' );
		$svg->set_attribute( 'height', '16' );
		$svg->set_attribute( 'aria-hidden', 'true' );
		$svg->set_attribute( 'focusable', 'false' );
		return $svg->get_updated_html();
	}

	$icons = egnitech_one_trust_badge_theme_icons();
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return '<svg class="trust-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ]['svg'] . '</svg>';
}

/**
 * Cleans a badge list before it is stored (the meta's sanitize callback) and when it is read.
 * Drops badges without text, replaces an icon that is not offered, empties a colour that is
 * not offered (the badge then takes its position's colour), keeps at most the limit.
 *
 * @param mixed $badges Raw list: array of array{text, icon, color, active}.
 * @return list<array{text: string, icon: string, color: string, active: bool}>
 */
function egnitech_one_sanitize_trust_badges( mixed $badges ): array {
	if ( ! is_array( $badges ) ) {
		return array();
	}

	$clean = array();
	foreach ( $badges as $badge ) {
		if ( ! is_array( $badge ) ) {
			continue;
		}

		$text = sanitize_text_field( $badge['text'] ?? '' );
		if ( '' === $text ) {
			continue;
		}

		$icon = is_string( $badge['icon'] ?? null ) ? $badge['icon'] : '';
		if ( '' === egnitech_one_trust_badge_icon_svg( $icon ) ) {
			$icon = EGNITECH_ONE_TRUST_BADGE_ICON;
		}

		$color = is_string( $badge['color'] ?? null ) ? $badge['color'] : '';
		if ( ! isset( egnitech_one_trust_badge_colors()[ $color ] ) ) {
			$color = '';
		}

		$clean[] = array(
			'text'   => $text,
			'icon'   => $icon,
			'color'  => $color,
			'active' => ! empty( $badge['active'] ),
		);

		if ( count( $clean ) === EGNITECH_ONE_TRUST_BADGE_LIMIT ) {
			break;
		}
	}
	return $clean;
}

/**
 * Registers the badge list so every write (the meta box, WP-CLI, imports) is cleaned the same way.
 */
function egnitech_one_register_trust_badges_meta(): void {
	register_post_meta(
		'product',
		EGNITECH_ONE_TRUST_BADGES_META,
		array(
			'type'              => 'array',
			'single'            => true,
			'sanitize_callback' => 'egnitech_one_sanitize_trust_badges',
		)
	);
	register_post_meta(
		'product',
		EGNITECH_ONE_TRUST_BADGE_STYLE_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'egnitech_one_sanitize_product_trust_badge_style',
		)
	);
}
add_action( 'init', 'egnitech_one_register_trust_badges_meta' );

/**
 * A product's badges, in display order, switched-off ones included.
 *
 * A product whose list was never saved gets its three old fixed rows, read from their old
 * meta keys with the old fallbacks, so it looks as it did. A saved empty list means no badges.
 *
 * @return list<array{text: string, icon: string, color: string, active: bool}>
 */
function egnitech_one_get_trust_badges( int $product_id ): array {
	if ( metadata_exists( 'post', $product_id, EGNITECH_ONE_TRUST_BADGES_META ) ) {
		return egnitech_one_sanitize_trust_badges( get_post_meta( $product_id, EGNITECH_ONE_TRUST_BADGES_META, true ) );
	}

	$badges = array();
	foreach ( egnitech_one_trust_badge_defaults() as $row => $default ) {
		$text = get_post_meta( $product_id, "_egnitech_trust_badge_{$row}", true );
		$icon = (string) get_post_meta( $product_id, "_egnitech_trust_badge_{$row}_icon", true );

		$badges[] = array(
			'text'   => empty( $text ) ? $default['text'] : (string) $text,
			'icon'   => '' === egnitech_one_trust_badge_icon_svg( $icon ) ? $default['icon'] : $icon,
			'color'  => '',
			'active' => 'no' !== get_post_meta( $product_id, "_egnitech_trust_badge_{$row}_active", true ),
		);
	}
	return $badges;
}

/**
 * One badge pill. A span, so the Theme Options style previews can sit inside their radio labels.
 */
function egnitech_one_trust_badge_pill( string $icon, string $text, string $hex ): string {
	return '<span class="egnitech-trust-badge" style="--egnitech-badge-color: ' . esc_attr( $hex ) . '">' . egnitech_one_trust_badge_icon_svg( $icon ) . '<span class="badge-text">' . esc_html( $text ) . '</span></span>';
}

/**
 * The product page's badge strip: the switched-on badges in order, or '' when there are none.
 */
function egnitech_one_render_trust_badges( int $product_id ): string {
	$colors = egnitech_one_trust_badge_colors();
	$badges = '';
	foreach ( egnitech_one_get_trust_badges( $product_id ) as $position => $badge ) {
		if ( $badge['active'] ) {
			$badges .= egnitech_one_trust_badge_pill( $badge['icon'], $badge['text'], $colors[ egnitech_one_trust_badge_color( $badge['color'], $position ) ]['hex'] );
		}
	}

	return '' === $badges ? '' : '<div class="egnitech-product-trust-badges egnitech-trust-badges--' . esc_attr( egnitech_one_trust_badge_style( $product_id ) ) . '">' . $badges . '</div>';
}
