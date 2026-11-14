<?php
declare(strict_types=1);

/**
 * Theme Options > General > Appearance: palette suggestions from the logo, ready-made palettes,
 * the last three palettes used, and applying one to Global Styles.
 *
 * Applying is its own AJAX action, never part of the Theme Options form: that form rewrites every
 * registered setting on each save, so a palette applied from it would be re-applied (and overwrite
 * Site Editor colour edits) whenever any tab is saved. For the same reason the two options below are
 * never registered with register_setting().
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/logo-colors.php';
require_once __DIR__ . '/palette-builder.php';

const EGNITECH_ONE_PALETTE_HISTORY = 'egnitech_one_palette_history'; // Last three palettes replaced, newest first.
const EGNITECH_ONE_PALETTE_APPLIED = 'egnitech_one_palette_applied'; // Last applied: label, resulting colours, and what this screen itself wrote per slot.

/**
 * The palette the site uses now (theme.json merged with Site Editor changes), slug => colour.
 *
 * @return array<string, string>
 */
function egnitech_one_current_palette(): array {
	$colors = array();
	foreach ( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) as $entry ) {
		$colors[ $entry['slug'] ] = $entry['color'];
	}
	return $colors;
}

/**
 * Palette suggestions for a logo, with a message when there are none.
 *
 * @return array{status: string, message: string, palettes: list<array{label: string, colors: array<string, string>}>}
 */
function egnitech_one_logo_palette_data( int $attachment_id ): array {
	$result   = egnitech_one_logo_seed_colors( $attachment_id );
	$messages = array(
		'ok'             => '',
		'no_logo'        => __( 'Add a Light Mode Logo under Branding & Logo and palettes made from its colours will appear here.', 'egnitech-one' ),
		'no_library'     => __( 'Your server cannot read images: PHP has neither the GD nor the Imagick extension. If your host enabled GD (the php-gd package), we would suggest palettes made from your logo here. Until then, pick one of the ready-made palettes below.', 'egnitech-one' ),
		/* translators: %s: file type, e.g. image/svg+xml. */
		'unreadable'     => sprintf( __( 'This server cannot read your logo\'s file type (%s). Upload a PNG, JPEG or WebP version of the logo to get palettes made from its colours. Until then, pick one of the ready-made palettes below.', 'egnitech-one' ), $result['mime'] ),
		'no_brand_color' => __( 'Your logo uses only black, white or grey, so it has no brand colour to build a palette from. Pick one of the ready-made palettes below.', 'egnitech-one' ),
	);

	return array(
		'status'   => $result['status'],
		'message'  => $messages[ $result['status'] ],
		'palettes' => egnitech_one_logo_palettes( $result['seeds'] ),
	);
}

/**
 * The site's Global Styles record for the active theme, created the way WordPress creates it when the
 * site has none yet. Uses public functions only: WordPress marks WP_Theme_JSON_Resolver as internal.
 *
 * @return int Post ID, or 0 when it could not be created.
 */
function egnitech_one_palette_global_styles_id(): int {
	$id = egnitech_one_get_global_styles_post_id(); // inc/admin-options.php
	if ( $id ) {
		return $id;
	}

	$stylesheet = get_stylesheet();
	$created    = wp_insert_post(
		array(
			'post_content' => wp_slash( wp_json_encode( array( 'version' => 3, 'isGlobalStylesUserThemeJSON' => true ) ) ),
			'post_status'  => 'publish',
			'post_title'   => 'Custom Styles',
			'post_type'    => 'wp_global_styles',
			'post_name'    => sprintf( 'wp-global-styles-%s', urlencode( $stylesheet ) ),
			'tax_input'    => array( 'wp_theme' => array( $stylesheet ) ),
		),
		true
	);
	return is_wp_error( $created ) ? 0 : (int) $created;
}

/**
 * Write a palette into the site's Global Styles record.
 *
 * A slot someone changed in the Site Editor (it differs from both theme.json and the palette this
 * screen last wrote) is kept unless $replace_custom is true.
 *
 * @param array<string, string> $colors         slug => colour.
 * @param bool                  $replace_custom Overwrite Site Editor changes too.
 * @param string                $label          Name kept with the palette in the history.
 * @return array{skipped: list<string>}|WP_Error
 */
function egnitech_one_apply_palette( array $colors, bool $replace_custom, string $label ): array|WP_Error {
	// Without unfiltered_html, WordPress strips light-dark() values out of Global Styles on save.
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		return new WP_Error( 'palette_filtered', __( 'Your account is not allowed to save unfiltered code, so WordPress would strip these colours on save. Ask an administrator who is (on multisite, a network administrator).', 'egnitech-one' ) );
	}

	// Creates the record when the site has none yet, which the layout-width sync does not.
	$id = egnitech_one_palette_global_styles_id();
	if ( ! $id ) {
		return new WP_Error( 'no_global_styles', __( 'Could not find or create the site\'s Global Styles record.', 'egnitech-one' ) );
	}

	$original = (string) get_post_field( 'post_content', $id, 'raw' );
	$content  = json_decode( $original, true );
	if ( ! is_array( $content ) ) {
		$content = array( 'version' => 3, 'isGlobalStylesUserThemeJSON' => true );
	}

	$user = array();
	foreach ( (array) ( $content['settings']['color']['palette']['theme'] ?? array() ) as $entry ) {
		$user[ $entry['slug'] ] = $entry['color'];
	}
	// Only what this screen wrote counts as its own. A Site Editor value kept with the switch off stays protected next time too.
	// Records saved before 'written' existed fall back to 'colors'.
	$applied = (array) get_option( EGNITECH_ONE_PALETTE_APPLIED, array() );
	$written = (array) ( $applied['written'] ?? $applied['colors'] ?? array() );
	$before  = egnitech_one_current_palette();
	$theme   = egnitech_one_theme_json_palette();
	$entries = array();
	$skipped = array();

	foreach ( $theme as $entry ) {
		$slug  = $entry['slug'];
		$value = $user[ $slug ] ?? $entry['color'];
		if ( isset( $colors[ $slug ] ) ) {
			$customised = isset( $user[ $slug ] ) && $user[ $slug ] !== $entry['color'] && $user[ $slug ] !== ( $written[ $slug ] ?? null );
			if ( $customised && ! $replace_custom ) {
				$skipped[] = $entry['name'];
			} else {
				$value            = $colors[ $slug ];
				$written[ $slug ] = $value;
			}
		}
		$entries[] = array( 'slug' => $slug, 'color' => $value, 'name' => $entry['name'] );
	}

	// Back to exactly theme.json: remove the override, so later theme.json palette edits reach the site again.
	if ( array_column( $entries, 'color' ) === array_column( $theme, 'color' ) ) {
		unset( $content['settings']['color']['palette']['theme'] );
		if ( empty( $content['settings']['color']['palette'] ) ) {
			unset( $content['settings']['color']['palette'] );
		}
		if ( empty( $content['settings']['color'] ) ) {
			unset( $content['settings']['color'] );
		}
	} else {
		$content['settings']['color']['palette']['theme'] = $entries;
	}

	// wp_update_post() strips slashes, so the JSON is slashed first or its escapes (\", \n) are lost.
	$updated = wp_update_post(
		array(
			'ID'           => $id,
			'post_content' => wp_slash( wp_json_encode( $content ) ),
		),
		true
	);
	if ( is_wp_error( $updated ) ) {
		return $updated;
	}
	wp_clean_theme_json_cache();

	$after = egnitech_one_current_palette();

	// A plugin filtering the save could still change values. Put the old record back rather than
	// leave half a palette.
	foreach ( $entries as $entry ) {
		if ( ( $after[ $entry['slug'] ] ?? null ) !== $entry['color'] ) {
			wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $original ) ) );
			wp_clean_theme_json_cache();
			return new WP_Error( 'palette_filtered', __( 'Something on this site changed the colours while saving them, so the palette was not applied. The previous colours are back.', 'egnitech-one' ) );
		}
	}

	if ( $after !== $before ) {
		egnitech_one_remember_palette( $before, $after );
	}
	if ( $skipped ) {
		/* translators: %s: palette name. */
		$label = sprintf( __( '%s + your Site Editor colours', 'egnitech-one' ), $label );
	}
	update_option( EGNITECH_ONE_PALETTE_APPLIED, array( 'label' => $label, 'colors' => $after, 'written' => $written ), false );

	// Cached pages still carry the old colours in their inline styles.
	if ( function_exists( 'egnitech_one_purge_all_site_cache' ) ) {
		egnitech_one_purge_all_site_cache();
	}

	return array( 'skipped' => $skipped );
}

/**
 * Put the palette being replaced at the top of the history (three kept, no duplicates).
 *
 * @param array<string, string> $before The palette being replaced.
 * @param array<string, string> $after  The palette now in use; never listed in the history.
 */
function egnitech_one_remember_palette( array $before, array $after ): void {
	$applied = get_option( EGNITECH_ONE_PALETTE_APPLIED, array() );
	$label   = __( 'Site Editor colours', 'egnitech-one' );
	if ( ( $applied['colors'] ?? null ) === $before ) {
		$label = (string) $applied['label'];
	} elseif ( egnitech_one_preset_palettes()[0]['colors'] === $before ) {
		$label = __( 'Theme default', 'egnitech-one' );
	}

	$history = array( array( 'label' => $label, 'colors' => $before, 'time' => time() ) );
	foreach ( (array) get_option( EGNITECH_ONE_PALETTE_HISTORY, array() ) as $item ) {
		if ( $item['colors'] !== $before && $item['colors'] !== $after ) {
			$history[] = $item;
		}
	}
	update_option( EGNITECH_ONE_PALETTE_HISTORY, array_slice( $history, 0, 3 ), false );
}

/**
 * Everything the palette UI needs.
 *
 * @return array<string, mixed>
 */
function egnitech_one_palette_screen_data(): array {
	return array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'egnitech_one_palettes' ),
		'slots'    => array_keys( egnitech_one_current_palette() ),
		'current'  => egnitech_one_current_palette(),
		'logo'     => egnitech_one_logo_palette_data( (int) get_theme_mod( 'custom_logo', 0 ) ),
		'presets'  => egnitech_one_preset_palettes(),
		'history'  => array_values( (array) get_option( EGNITECH_ONE_PALETTE_HISTORY, array() ) ),
		'i18n'     => array(
			'fromLogo'  => __( 'From your logo', 'egnitech-one' ),
			'presets'   => __( 'Ready-made palettes', 'egnitech-one' ),
			'history'   => __( 'Recently used', 'egnitech-one' ),
			'apply'     => __( 'Apply', 'egnitech-one' ),
			'restore'   => __( 'Switch back', 'egnitech-one' ),
			'inUse'     => __( 'In use', 'egnitech-one' ),
			'applying'  => __( 'Applying…', 'egnitech-one' ),
			'reading'   => __( 'Reading your logo…', 'egnitech-one' ),
			'applied'   => __( 'Palette applied. The site uses it now; no need to save.', 'egnitech-one' ),
			/* translators: %s: comma-separated colour names. */
			'skipped'   => __( 'Kept your Site Editor colours for: %s. Turn on the switch above to replace them too.', 'egnitech-one' ),
			'failed'    => __( 'Could not apply the palette.', 'egnitech-one' ),
		),
	);
}

/**
 * AJAX: suggestions for a logo picked on the screen but not saved yet.
 */
function egnitech_one_ajax_logo_palettes(): void {
	check_ajax_referer( 'egnitech_one_palettes', 'security' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
	}

	$id = absint( $_POST['attachment_id'] ?? 0 );
	wp_send_json_success( egnitech_one_logo_palette_data( wp_attachment_is_image( $id ) ? $id : 0 ) );
}
add_action( 'wp_ajax_egnitech_one_logo_palettes', 'egnitech_one_ajax_logo_palettes' );

/**
 * AJAX: apply a palette.
 */
function egnitech_one_ajax_apply_palette(): void {
	check_ajax_referer( 'egnitech_one_palettes', 'security' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
	}

	$raw    = json_decode( wp_unslash( (string) ( $_POST['colors'] ?? '' ) ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each value is checked below.
	$slots  = array_keys( egnitech_one_current_palette() );
	$colors = array();
	foreach ( is_array( $raw ) ? $raw : array() as $slug => $value ) {
		// The value becomes a CSS custom property, so only colour syntax gets through.
		if ( in_array( $slug, $slots, true ) && is_string( $value ) && strlen( $value ) <= 100 && preg_match( '/^[#a-zA-Z0-9(),.%\s-]+$/', $value ) ) {
			$colors[ $slug ] = $value;
		}
	}
	if ( ! $colors ) {
		wp_send_json_error( array( 'message' => __( 'No valid colours were sent.', 'egnitech-one' ) ) );
	}

	$label  = mb_substr( sanitize_text_field( wp_unslash( (string) ( $_POST['label'] ?? '' ) ) ), 0, 60 );
	$result = egnitech_one_apply_palette( $colors, ! empty( $_POST['replace'] ), $label );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'skipped' => $result['skipped'],
			'current' => egnitech_one_current_palette(),
			'history' => array_values( (array) get_option( EGNITECH_ONE_PALETTE_HISTORY, array() ) ),
		)
	);
}
add_action( 'wp_ajax_egnitech_one_apply_palette', 'egnitech_one_ajax_apply_palette' );

/**
 * Load the palette UI's script and style on the Theme Options page.
 */
function egnitech_one_palette_admin_assets( string $hook ): void {
	if ( 'appearance_page_egnitech-one-options' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'egnitech-one-palettes',
		get_theme_file_uri( 'assets/css/admin-palettes.css' ),
		array( 'egnitech-one-admin' ),
		egnitech_one_asset_version( get_theme_file_path( 'assets/css/admin-palettes.css' ) )
	);
	wp_enqueue_script(
		'egnitech-one-palettes',
		get_theme_file_uri( 'assets/js/admin-palettes.js' ),
		array(),
		egnitech_one_asset_version( get_theme_file_path( 'assets/js/admin-palettes.js' ) ),
		true
	);
	wp_add_inline_script( 'egnitech-one-palettes', 'window.egnitechPalettes = ' . wp_json_encode( egnitech_one_palette_screen_data() ) . ';', 'before' );
}
add_action( 'admin_enqueue_scripts', 'egnitech_one_palette_admin_assets' );
