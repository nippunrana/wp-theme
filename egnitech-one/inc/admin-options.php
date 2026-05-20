<?php
declare(strict_types=1);

/**
 * Helper to generate shared accordion structure
 *
 * @param array $args The arguments array.
 * @return string The generated card HTML.
 */
function egnitech_one_render_admin_card_item( array $args = array() ): string {
    $item_class = 'egnitech-script-item ' . ( isset( $args['class'] ) ? $args['class'] : '' );
    if ( ! empty( $args['is_inactive'] ) ) $item_class .= ' is-inactive';
    if ( ! empty( $args['is_expanded'] ) ) $item_class .= ' is-expanded';

    $display_content = ! empty( $args['is_expanded'] ) ? 'block' : 'none';
    $data_attr = isset( $args['data_id'] ) ? 'data-id="' . esc_attr( $args['data_id'] ) . '"' : '';
    $delete_title = isset( $args['delete_title'] ) ? $args['delete_title'] : __( 'Delete Item', 'egnitech-one' );

    ob_start();
    ?>
    <div class="<?php echo esc_attr( trim( $item_class ) ); ?>" <?php echo $data_attr; ?>>
        <div class="egnitech-script-header">
            <div class="egnitech-script-title">
                <span class="dashicons dashicons-arrow-right-alt2 egnitech-script-toggle-icon"></span>
                <span class="egnitech-script-name"><?php echo esc_html( $args['title'] ); ?></span>
                <?php if ( ! empty( $args['highlights'] ) ) : ?>
                    <div class="egnitech-script-highlights">
                        <?php echo $args['highlights']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="egnitech-script-actions">
                <?php if ( ! empty( $args['actions'] ) ) echo $args['actions']; ?>
                <button type="button" class="egnitech-btn-icon egnitech-script-delete" title="<?php echo esc_attr( $delete_title ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <div class="egnitech-script-content" style="display: <?php echo esc_attr( $display_content ); ?>;">
            <?php if ( ! empty( $args['content'] ) ) echo $args['content']; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Function to generate script item HTML
function egnitech_one_generate_script_item_html( string|int $id, string $name, string $location = 'header', string $load_type = 'normal', string $code = '', bool $is_active = true, bool $is_expanded = false ): string {
    $current_location_label = 'header' === $location ? __( 'Header', 'egnitech-one' ) : __( 'Footer', 'egnitech-one' );
    
    $current_load_type_label = __( 'Normal', 'egnitech-one' );
    if ( 'after_dom' === $load_type ) {
        $current_load_type_label = __( 'After DOM', 'egnitech-one' );
    } elseif ( 'delayed_3s' === $load_type ) {
        $current_load_type_label = __( 'Delayed', 'egnitech-one' );
    }

    $checked_attr = $is_active ? 'checked="checked"' : '';
    
    $loc_header_checked     = 'header' === $location ? 'checked="checked"' : '';
    $loc_footer_checked     = 'footer' === $location ? 'checked="checked"' : '';
    $load_normal_checked    = 'normal' === $load_type ? 'checked="checked"' : '';
    $load_after_dom_checked = 'after_dom' === $load_type ? 'checked="checked"' : '';
    $load_delayed_checked   = 'delayed_3s' === $load_type ? 'checked="checked"' : '';
    $disclaimer_display     = 'delayed_3s' === $load_type ? 'block' : 'none';

    // Render using shared helper
    $highlights = '<span class="egnitech-highlight-badge highlight-location">' . esc_html( $current_location_label ) . '</span>' .
                  '<span class="egnitech-highlight-badge highlight-load">' . esc_html( $current_load_type_label ) . '</span>';
    
    $actions = '<label class="egnitech-switch egnitech-script-status">' .
               '<input type="checkbox" ' . esc_attr( $checked_attr ) . ' />' .
               '<span class="egnitech-slider"></span>' .
               '</label>';

    ob_start();
    ?>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Script Name', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <input type="text" class="egnitech-input script-name-input" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'e.g., Google Analytics', 'egnitech-one' ); ?>" />
        </div>
    </div>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Placement (Where to insert)', 'egnitech-one' ); ?></label>
            <p class="description"><?php esc_html_e( 'Choose where this code should be injected into your site\'s HTML.', 'egnitech-one' ); ?></p>
        </div>
        <div class="egnitech-option-control">
            <div class="egnitech-radio-group">
                <label class="egnitech-radio-label">
                    <input type="radio" name="location_<?php echo esc_attr( $id ); ?>" value="header" <?php echo esc_attr( $loc_header_checked ); ?> />
                    <span class="egnitech-radio-btn">
                        <?php esc_html_e( 'Header (<head>)', 'egnitech-one' ); ?>
                        <span class="egnitech-tooltip-wrap">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span class="egnitech-tooltip-content"><?php esc_html_e( 'Best for Analytics (GA4), Search Console, and meta-tag verifications.', 'egnitech-one' ); ?></span>
                        </span>
                    </span>
                </label>
                <label class="egnitech-radio-label">
                    <input type="radio" name="location_<?php echo esc_attr( $id ); ?>" value="footer" <?php echo esc_attr( $loc_footer_checked ); ?> />
                    <span class="egnitech-radio-btn">
                        <?php esc_html_e( 'Footer (before </body>)', 'egnitech-one' ); ?>
                        <span class="egnitech-tooltip-wrap">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span class="egnitech-tooltip-content"><?php esc_html_e( 'Best for live chats, non-critical tracking, and heavy scripts that shouldn\'t delay page load.', 'egnitech-one' ); ?></span>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Load Type', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <div class="egnitech-radio-group">
                <label class="egnitech-radio-label">
                    <input type="radio" name="load_<?php echo esc_attr( $id ); ?>" value="normal" <?php echo esc_attr( $load_normal_checked ); ?> />
                    <span class="egnitech-radio-btn egnitech-radio-btn-pill">
                        <?php esc_html_e( 'Normal', 'egnitech-one' ); ?>
                        <span class="egnitech-tooltip-wrap">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span class="egnitech-tooltip-content"><?php esc_html_e( 'Code loads along/first with our website code. Can slow down your website and lower your Google Web Vitals score.', 'egnitech-one' ); ?></span>
                        </span>
                    </span>
                </label>
                <label class="egnitech-radio-label">
                    <input type="radio" name="load_<?php echo esc_attr( $id ); ?>" value="after_dom" <?php echo esc_attr( $load_after_dom_checked ); ?> />
                    <span class="egnitech-radio-btn egnitech-radio-btn-pill">
                        <?php esc_html_e( 'After DOM', 'egnitech-one' ); ?>
                        <span class="egnitech-tooltip-wrap">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span class="egnitech-tooltip-content"><?php esc_html_e( 'Loads after your website content. Better for keeping your site fast and maintaining a good Web Vitals score.', 'egnitech-one' ); ?></span>
                        </span>
                    </span>
                </label>
                <label class="egnitech-radio-label">
                    <input type="radio" name="load_<?php echo esc_attr( $id ); ?>" value="delayed_3s" <?php echo esc_attr( $load_delayed_checked ); ?> />
                    <span class="egnitech-radio-btn egnitech-radio-btn-pill">
                        <?php esc_html_e( 'Delayed (3s)', 'egnitech-one' ); ?>
                        <span class="egnitech-tooltip-wrap">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span class="egnitech-tooltip-content"><?php esc_html_e( 'Loads after a 3-second delay. Best choice for top page speed and a perfect Google Web Vitals score.', 'egnitech-one' ); ?></span>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>
    <div class="egnitech-option-row egnitech-option-row-vertical">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Code', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <textarea class="egnitech-code-field" rows="6" placeholder="<!-- <?php esc_attr_e( 'Your custom script here', 'egnitech-one' ); ?> -->"><?php echo esc_textarea( $code ); ?></textarea>
            <p class="description egnitech-delayed-disclaimer" style="display: <?php echo esc_attr( $disclaimer_display ); ?>; color: #d63638; margin-top: 8px;">
                <?php esc_html_e( 'Note: Loading revenue-generating scripts (like Google AdSense) or strict tracking pixels with a delay may violate their terms of service or result in lost data. Please check their documentation before using this option.', 'egnitech-one' ); ?>
            </p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();

    return egnitech_one_render_admin_card_item( array(
        'title'        => $name,
        'is_inactive'  => ! $is_active,
        'is_expanded'  => $is_expanded,
        'highlights'   => $highlights,
        'actions'      => $actions,
        'content'      => $content,
        'delete_title' => __( 'Delete Script', 'egnitech-one' )
    ) );
    return ob_get_clean();
}
/**
 * Theme Options Page and Settings
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! is_admin() ) {
// Only load these functions in the admin area
return;
}

/**
 * Register the Theme Options page under Appearance.
 */
function egnitech_one_add_theme_page(): void {
add_theme_page(
__( 'Theme Options', 'egnitech-one' ),
__( 'Theme Options', 'egnitech-one' ),
'edit_theme_options',
'egnitech-one-options',
'egnitech_one_options_page_html'
);
}
add_action( 'admin_menu', 'egnitech_one_add_theme_page' );

/**
 * Register all theme option settings.
 */
function egnitech_one_register_settings(): void {

/* ----- Header ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_sticky_header', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_header_padding_desktop_top', [
'type'              => 'number',
'default'           => 8,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_header_padding_desktop_bottom', [
'type'              => 'number',
'default'           => 8,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_header_padding_mobile_top', [
'type'              => 'number',
'default'           => 4,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_header_padding_mobile_bottom', [
'type'              => 'number',
'default'           => 4,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_dark_logo_url', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'esc_url_raw',
] );

/* ----- General ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_scroll_to_top', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_content_width', [
'type'              => 'number',
'default'           => 900,
'sanitize_callback' => 'egnitech_one_sanitize_content_width',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_wide_width', [
'type'              => 'number',
'default'           => 1280,
'sanitize_callback' => 'egnitech_one_sanitize_wide_width',
] );

/* ----- Footer ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_footer_copyright', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'wp_kses_post',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_footer_credits', [
'type'              => 'string',
'default'           => '<a href="https://egnitech.com" rel="nofollow">EgniTech</a> &middot; Built with <a href="https://wordpress.org" rel="nofollow">WordPress</a>',
'sanitize_callback' => 'wp_kses_post',
] );


/* ----- Blog ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_blog_layout', [
'type'              => 'string',
'default'           => 'list',
'sanitize_callback' => 'egnitech_one_sanitize_blog_layout',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_sidebar_position', [
'type'              => 'string',
'default'           => 'none',
'sanitize_callback' => 'egnitech_one_sanitize_sidebar_position',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_meta_author', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_meta_date', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_meta_categories', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_meta_tags', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

/* ----- Header: Logo Width ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_logo_width_desktop', [
'type'              => 'number',
'default'           => 0,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_logo_width_mobile', [
'type'              => 'number',
'default'           => 0,
'sanitize_callback' => 'absint',
] );

/* ----- General: Phase 3 ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_dark_mode_default', [
'type'              => 'string',
'default'           => 'system',
'sanitize_callback' => 'egnitech_one_sanitize_dark_mode_default',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_reading_progress', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_reading_progress_height', [
'type'              => 'number',
'default'           => 2,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_breadcrumbs', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

/* ----- Advanced ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_custom_scripts', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'egnitech_one_sanitize_scripts',
] );

/* ----- Integrations: reCAPTCHA ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_recaptcha_enabled', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_recaptcha_site_key', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_recaptcha_secret_key', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

/* ----- Contact Us ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_contact_title', [
'type'              => 'string',
'default'           => 'Let\'s build something extraordinary.',
'sanitize_callback' => 'wp_kses_post',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_contact_desc', [
'type'              => 'string',
'default'           => 'Have a project in mind or just want to say hello? Drop us a message and we\'ll get back to you within 24 hours.',
'sanitize_callback' => 'wp_kses_post',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_contact_email', [
'type'              => 'string',
'default'           => 'hello@egnitech.com',
'sanitize_callback' => 'sanitize_email',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_contact_recipient_email', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_email',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_contact_phone', [
'type'              => 'string',
'default'           => '+1 (555) 000-0000',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_contact_form_fields', [
'type'              => 'string',
'default'           => '[]',
'sanitize_callback' => 'egnitech_one_sanitize_scripts', // Same unfiltered_html check
] );

/* ----- SMTP Settings ----- */
register_setting( 'egnitech_one_options', 'egnitech_one_smtp_enabled', [
'type'              => 'string',
'default'           => 'no',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_host', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_port', [
'type'              => 'number',
'default'           => 587,
'sanitize_callback' => 'absint',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_encryption', [
'type'              => 'string',
'default'           => 'tls',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_auth', [
'type'              => 'string',
'default'           => 'yes',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_username', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_password', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_text_field',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_from_email', [
'type'              => 'string',
'default'           => '',
'sanitize_callback' => 'sanitize_email',
] );

register_setting( 'egnitech_one_options', 'egnitech_one_smtp_from_name', [
'type'              => 'string',
'default'           => get_bloginfo( 'name' ),
'sanitize_callback' => 'sanitize_text_field',
] );
}
add_action( 'admin_init', 'egnitech_one_register_settings' );

/* ----- Sanitize callbacks ----- */

/**
 * Sanitize scripts — only allow for users with unfiltered_html capability.
 */
function egnitech_one_sanitize_scripts( mixed $input ): string {
if ( ! current_user_can( 'unfiltered_html' ) ) {
return '';
}
return is_string( $input ) ? $input : '';
}

/**
 * Check if the "Contact Us" template is currently assigned to any published page.
 *
 * @return bool True if in use, false otherwise.
 */
function egnitech_one_is_contact_template_in_use(): bool {
    $args = array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'egnitech-one-contact-us',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    );

    $query = new WP_Query( $args );
    return $query->have_posts();
}

/**
 * Sanitize dark mode default option.
 */
function egnitech_one_sanitize_dark_mode_default( mixed $input ): string {
$valid = array( 'system', 'light', 'dark' );
return in_array( $input, $valid, true ) ? (string) $input : 'system';
}

/**
 * Sanitize blog layout option.
 */
function egnitech_one_sanitize_blog_layout( mixed $input ): string {
$valid = array( 'list', 'grid-2', 'grid-3' );
return in_array( $input, $valid, true ) ? (string) $input : 'list';
}

/**
 * Sanitize sidebar position option.
 */
function egnitech_one_sanitize_sidebar_position( mixed $input ): string {
$valid = array( 'none', 'left', 'right' );
return in_array( $input, $valid, true ) ? (string) $input : 'none';
}

/**
 * Sanitize content width and sync to Global Styles.
 */
function egnitech_one_sanitize_content_width( mixed $input ): int {
$value = absint( $input );
if ( $value < 320 ) {
$value = 320;
}
if ( $value > 2560 ) {
$value = 2560;
}
egnitech_one_sync_layout_to_global_styles( 'contentSize', $value . 'px' );
return $value;
}

/**
 * Sanitize wide width and sync to Global Styles.
 */
function egnitech_one_sanitize_wide_width( mixed $input ): int {
$value = absint( $input );
if ( $value < 320 ) {
$value = 320;
}
if ( $value > 2560 ) {
$value = 2560;
}
egnitech_one_sync_layout_to_global_styles( 'wideSize', $value . 'px' );
return $value;
}

/**
 * Sync a layout property to the wp_global_styles post so the
 * Site Editor stays in sync with Theme Options.
 */
function egnitech_one_sync_layout_to_global_styles( string $property, string $value ): void {
$global_styles_id = egnitech_one_get_global_styles_post_id();
if ( ! $global_styles_id ) {
return;
}

$post = get_post( $global_styles_id );
if ( ! $post ) {
return;
}

$content = json_decode( $post->post_content, true );
if ( ! is_array( $content ) ) {
$content = array( 'version' => 3, 'isGlobalStylesUserThemeJSON' => true );
}

if ( ! isset( $content['settings'] ) ) {
$content['settings'] = array();
}
if ( ! isset( $content['settings']['layout'] ) ) {
$content['settings']['layout'] = array();
}

$content['settings']['layout'][ $property ] = $value;

wp_update_post( array(
'ID'           => $global_styles_id,
'post_content' => wp_json_encode( $content ),
) );
}

/**
 * Get the wp_global_styles post ID for the active theme.
 */
function egnitech_one_get_global_styles_post_id(): int {
$query = new WP_Query( array(
'post_type'      => 'wp_global_styles',
'post_status'    => array( 'publish', 'draft' ),
'posts_per_page' => 1,
'no_found_rows'  => true,
'tax_query'      => array(
array(
'taxonomy' => 'wp_theme',
'field'    => 'name',
'terms'    => get_stylesheet(),
),
),
) );

if ( $query->have_posts() ) {
return (int) $query->posts[0]->ID;
}

return 0;
}

/**
 * Read current layout sizes from Global Styles (merged with theme.json).
 */
function egnitech_one_get_layout_settings(): array {
$layout = wp_get_global_settings( array( 'layout' ) );
return array(
'contentSize' => isset( $layout['contentSize'] ) ? intval( $layout['contentSize'] ) : 900,
'wideSize'    => isset( $layout['wideSize'] ) ? intval( $layout['wideSize'] ) : 1280,
);
}


/* ========================================================================
 * RENDER OPTIONS PAGE HTML
 * ======================================================================== */

/**
 * Render the Theme Options page with tabbed navigation.
 */
function egnitech_one_options_page_html(): void {
if ( ! current_user_can( 'edit_theme_options' ) ) {
return;
}

// Existing options.
$sticky_header        = get_option( 'egnitech_one_sticky_header', 'yes' );
$desktop_padding_top  = get_option( 'egnitech_one_header_padding_desktop_top', '8' );
$desktop_padding_bot  = get_option( 'egnitech_one_header_padding_desktop_bottom', '8' );
$mobile_padding_top   = get_option( 'egnitech_one_header_padding_mobile_top', '4' );
$mobile_padding_bot   = get_option( 'egnitech_one_header_padding_mobile_bottom', '4' );
$dark_logo_url        = get_option( 'egnitech_one_dark_logo_url', '' );
$dark_logo_url        = $dark_logo_url ? set_url_scheme( $dark_logo_url, 'https' ) : '';

// Phase 1 options.
$scroll_to_top        = get_option( 'egnitech_one_scroll_to_top', 'yes' );
$footer_copyright     = get_option( 'egnitech_one_footer_copyright', '' );
$footer_credits       = get_option( 'egnitech_one_footer_credits', '<a href="https://egnitech.com" rel="nofollow">EgniTech</a> &middot; Built with <a href="https://wordpress.org" rel="nofollow">WordPress</a>' );
$custom_scripts_raw   = get_option( 'egnitech_one_custom_scripts', '[]' );
$custom_scripts       = json_decode( $custom_scripts_raw, true );
if ( ! is_array( $custom_scripts ) ) {
    $custom_scripts = array();
}

// Phase 2 options.
$blog_layout          = get_option( 'egnitech_one_blog_layout', 'list' );
$sidebar_position     = get_option( 'egnitech_one_sidebar_position', 'none' );
$meta_author          = get_option( 'egnitech_one_meta_author', 'yes' );
$meta_date            = get_option( 'egnitech_one_meta_date', 'yes' );
$meta_categories      = get_option( 'egnitech_one_meta_categories', 'yes' );
$meta_tags            = get_option( 'egnitech_one_meta_tags', '' );
$logo_width_desktop   = get_option( 'egnitech_one_logo_width_desktop', 0 );
$logo_width_mobile    = get_option( 'egnitech_one_logo_width_mobile', 0 );

// Phase 3 options.
$dark_mode_default    = get_option( 'egnitech_one_dark_mode_default', 'system' );
$reading_progress     = get_option( 'egnitech_one_reading_progress', 'yes' );
$reading_progress_height = get_option( 'egnitech_one_reading_progress_height', 2 );
$breadcrumbs          = get_option( 'egnitech_one_breadcrumbs', '' );

// Integrations options.
$recaptcha_enabled    = get_option( 'egnitech_one_recaptcha_enabled', '' );
$recaptcha_site_key   = get_option( 'egnitech_one_recaptcha_site_key', '' );
$recaptcha_secret_key = get_option( 'egnitech_one_recaptcha_secret_key', '' );

// Contact options
$contact_title        = get_option( 'egnitech_one_contact_title', 'Let\'s build something extraordinary.' );
$contact_desc         = get_option( 'egnitech_one_contact_desc', 'Have a project in mind or just want to say hello? Drop us a message and we\'ll get back to you within 24 hours.' );
$contact_email        = get_option( 'egnitech_one_contact_email', 'hello@egnitech.com' );
$recipient_email      = get_option( 'egnitech_one_contact_recipient_email', '' );
$contact_phone        = get_option( 'egnitech_one_contact_phone', '+1 (555) 000-0000' );
$contact_fields_raw   = get_option( 'egnitech_one_contact_form_fields', '[]' );
$contact_fields       = json_decode( (string) $contact_fields_raw, true );
if ( ! is_array( $contact_fields ) ) {
    $contact_fields = array();
}

// SMTP options.
$smtp_enabled     = get_option( 'egnitech_one_smtp_enabled', 'no' );
$smtp_host        = get_option( 'egnitech_one_smtp_host', '' );
$smtp_port        = get_option( 'egnitech_one_smtp_port', 587 );
$smtp_encryption  = get_option( 'egnitech_one_smtp_encryption', 'tls' );
$smtp_auth        = get_option( 'egnitech_one_smtp_auth', 'yes' );
$smtp_username    = get_option( 'egnitech_one_smtp_username', '' );
$smtp_password    = get_option( 'egnitech_one_smtp_password', '' );
$smtp_from_email  = get_option( 'egnitech_one_smtp_from_email', '' );
$smtp_from_name   = get_option( 'egnitech_one_smtp_from_name', get_bloginfo( 'name' ) );

// Layout from Global Styles (synced).
$layout               = egnitech_one_get_layout_settings();
?>
<div class="wrap egnitech-options-wrap">
<div class="egnitech-options-header">
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<p class="egnitech-options-subtitle"><?php esc_html_e( 'Customize your EgniTech One experience.', 'egnitech-one' ); ?></p>
</div>

<div class="egnitech-options-container">
<!-- Sidebar Navigation -->
<div class="egnitech-options-sidebar">
<ul class="egnitech-options-tabs">
<li><a href="#tab-general"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-header"><span class="dashicons dashicons-editor-kitchensink"></span> <?php esc_html_e( 'Header', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-blog"><span class="dashicons dashicons-welcome-write-blog"></span> <?php esc_html_e( 'Blog', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-footer"><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Footer', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-contact"><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Contact Us', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-integrations"><span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Integrations', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-smtp"><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'SMTP Settings', 'egnitech-one' ); ?></a></li>
<li><a href="#tab-advanced"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Advanced', 'egnitech-one' ); ?></a></li>
</ul>
</div>

<div class="egnitech-options-content">
<form action="options.php" method="post" class="egnitech-options-form">
<?php settings_fields( 'egnitech_one_options' ); ?>

<!-- ==================== GENERAL TAB ==================== -->
<div id="tab-general" class="egnitech-tab-panel">

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Typography', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Manage global typography settings.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<?php
$fonts_list      = egnitech_one_get_installed_fonts();
$assignments     = egnitech_one_get_font_assignments();
$body_name       = isset( $fonts_list[ $assignments['body'] ] ) ? $fonts_list[ $assignments['body'] ]['name'] : 'System Default';
$heading_name    = isset( $fonts_list[ $assignments['heading'] ] ) ? $fonts_list[ $assignments['heading'] ]['name'] : 'System Default';
$site_editor_url = admin_url( 'site-editor.php?p=%2Fstyles&section=%2Ftypography' );
?>
<div class="egnitech-typography-overview">
<div class="egnitech-typography-item">
<div class="egnitech-typography-header">
<span class="egnitech-typography-label"><?php esc_html_e( 'Body Font', 'egnitech-one' ); ?></span>
<span class="egnitech-typography-value"><?php echo esc_html( $body_name ); ?></span>
</div>
<div class="egnitech-typography-preview" style="font-family: <?php echo esc_attr( $body_name ); ?>, sans-serif;">
The quick brown fox jumps over the lazy dog.
</div>
</div>
<div class="egnitech-typography-item">
<div class="egnitech-typography-header">
<span class="egnitech-typography-label"><?php esc_html_e( 'Heading Font', 'egnitech-one' ); ?></span>
<span class="egnitech-typography-value"><?php echo esc_html( $heading_name ); ?></span>
</div>
<div class="egnitech-typography-preview" style="font-family: <?php echo esc_attr( $heading_name ); ?>, sans-serif; font-weight: 700;">
The Quick Brown Fox
</div>
</div>
</div>
<div class="egnitech-typography-actions">
<a href="<?php echo esc_url( $site_editor_url ); ?>" class="egnitech-btn egnitech-btn-secondary">
<span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Modify Typography in Site Editor', 'egnitech-one' ); ?>
</a>
<p class="description"><?php esc_html_e( 'Manage your global fonts visually in the WordPress Site Editor.', 'egnitech-one' ); ?></p>
</div>

</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Layout', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Set global layout widths.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Content Width', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Maximum width for content and wide blocks. Synced with the Site Editor.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-flex-row">
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Content:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_content_width" value="<?php echo esc_attr( $layout['contentSize'] ); ?>" min="320" max="2560" step="10" />
<span class="suffix-text">px</span>
</div>
</label>
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Wide:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_wide_width" value="<?php echo esc_attr( $layout['wideSize'] ); ?>" min="320" max="2560" step="10" />
<span class="suffix-text">px</span>
</div>
</label>
</div>
</div>
</div>
</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Appearance', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Customize your global color palette and default theme mode.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<?php
$color_palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
$color_editor_url = admin_url( 'site-editor.php?p=%2Fstyles&section=%2Fcolors' );
?>
<div class="egnitech-option-row egnitech-option-row-vertical">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Theme Color Palette', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'The overarching color system driving your site globally.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-palette-overview">
<?php
if ( ! empty( $color_palette ) && is_array( $color_palette ) ) {
    foreach ( $color_palette as $color ) {
        // Handle CSS var or light-dark functions by resolving them to actual CSS on the element
        $bg_css = '';
        if ( preg_match( '/light-dark\(\s*(#[0-9a-fA-F]+)\s*,\s*(#[0-9a-fA-F]+)\s*\)/', $color['color'], $m ) ) {
            // It's a light/dark color, render as diagonal split gradient
            $bg_css = "background: linear-gradient(135deg, {$m[1]} 50%, {$m[2]} 50%);";
        } else {
            // Standard color or CSS var, pass directly to background
            $bg_css = "background: {$color['color']};";
        }
        ?>
        <div class="egnitech-palette-swatch" title="<?php echo esc_attr( $color['name'] ); ?>">
            <div class="egnitech-palette-color" style="<?php echo esc_attr( $bg_css ); ?>"></div>
            <span class="egnitech-palette-label"><?php echo esc_html( $color['name'] ); ?></span>
        </div>
        <?php
    }
}
?>
</div>
<div style="margin-top: 16px;">
<a href="<?php echo esc_url( $color_editor_url ); ?>" class="egnitech-btn egnitech-btn-secondary">
<span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Modify Colors in Site Editor', 'egnitech-one' ); ?>
</a>
</div>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Dark Mode Default', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Initial color scheme for new visitors. Users can still toggle manually.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<select name="egnitech_one_dark_mode_default" class="egnitech-input">
<option value="system" <?php selected( 'system', $dark_mode_default ); ?>><?php esc_html_e( 'Follow System Preference', 'egnitech-one' ); ?></option>
<option value="light" <?php selected( 'light', $dark_mode_default ); ?>><?php esc_html_e( 'Always Light', 'egnitech-one' ); ?></option>
<option value="dark" <?php selected( 'dark', $dark_mode_default ); ?>><?php esc_html_e( 'Always Dark', 'egnitech-one' ); ?></option>
</select>
</div>
</div>
</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Features', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Enable or disable interactive theme features.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Scroll to Top Button', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Show a scroll-to-top button when the user scrolls down', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<label class="egnitech-switch">
<input type="checkbox" name="egnitech_one_scroll_to_top" value="yes" <?php checked( 'yes', $scroll_to_top ); ?> />
<span class="egnitech-slider"></span>
</label>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Reading Progress Bar', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Show a reading progress bar at the top of the page', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<label class="egnitech-switch">
<input type="checkbox" name="egnitech_one_reading_progress" id="egnitech_one_reading_progress" value="yes" <?php checked( 'yes', $reading_progress ); ?> />
<span class="egnitech-slider"></span>
</label>
</div>
</div>

<div class="egnitech-option-row" id="reading-progress-height-row" style="<?php echo 'yes' === $reading_progress ? '' : 'display: none;'; ?>">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Progress Bar Height', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Adjust the thickness of the progress bar in pixels.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-range-control">
<input type="range" name="egnitech_one_reading_progress_height" value="<?php echo esc_attr( $reading_progress_height ); ?>" min="1" max="10" step="1" class="egnitech-range-input" id="egnitech_one_reading_progress_height" />
<span class="egnitech-range-value"><span><?php echo esc_html( $reading_progress_height ); ?></span>px</span>
</div>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Breadcrumbs', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Show breadcrumb navigation on single posts and pages', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<label class="egnitech-switch">
<input type="checkbox" name="egnitech_one_breadcrumbs" value="yes" <?php checked( 'yes', $breadcrumbs ); ?> />
<span class="egnitech-slider"></span>
</label>
</div>
</div>
</div>
</div>
</div> <!-- end tab-general -->

<!-- ==================== HEADER TAB ==================== -->
<div id="tab-header" class="egnitech-tab-panel">

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Header Behavior', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Settings for header interactions.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Sticky Header', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Enable sticky fixed-header on scroll', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<label class="egnitech-switch">
<input type="checkbox" name="egnitech_one_sticky_header" value="yes" <?php checked( 'yes', $sticky_header ); ?> />
<span class="egnitech-slider"></span>
</label>
</div>
</div>
</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Spacing', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Control the dimensions of your site header.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Header Padding (Desktop)', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Top and bottom spacing for screens larger than 768px. Default: 8.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-flex-row">
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Top:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_header_padding_desktop_top" value="<?php echo esc_attr( $desktop_padding_top ); ?>" min="0" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Bottom:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_header_padding_desktop_bottom" value="<?php echo esc_attr( $desktop_padding_bot ); ?>" min="0" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
</div>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Header Padding (Mobile)', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Top and bottom spacing for screens smaller than 768px. Default: 4.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-flex-row">
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Top:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_header_padding_mobile_top" value="<?php echo esc_attr( $mobile_padding_top ); ?>" min="0" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Bottom:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_header_padding_mobile_bottom" value="<?php echo esc_attr( $mobile_padding_bot ); ?>" min="0" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
</div>
</div>
</div>
</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Branding & Logo', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Configure separate dark and light mode logos.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row egnitech-option-row-vertical">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Dark Mode Logo', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Choose the logo image to show in Dark Mode. The light mode logo is set via Appearance → Editor → Site Logo.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-logo-uploader">
<div class="egnitech-logo-preview" style="<?php echo $dark_logo_url ? '' : 'display: none;'; ?>">
<img id="egnitech-dark-logo-preview" src="<?php echo esc_url( $dark_logo_url ); ?>" />
</div>
<input type="hidden" name="egnitech_one_dark_logo_url" id="egnitech_one_dark_logo_url" value="<?php echo esc_attr( $dark_logo_url ); ?>" />
<div class="egnitech-logo-actions">
<button type="button" class="egnitech-btn" id="egnitech_select_logo">
<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Select Logo', 'egnitech-one' ); ?>
</button>
<button type="button" class="egnitech-btn egnitech-btn-danger" id="egnitech_remove_logo" style="<?php echo $dark_logo_url ? '' : 'display:none;'; ?>">
<?php esc_html_e( 'Remove', 'egnitech-one' ); ?>
</button>
</div>
</div>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Logo Width', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Maximum logo width in pixels. Set to 0 for automatic sizing.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-flex-row">
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Desktop:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_logo_width_desktop" value="<?php echo esc_attr( $logo_width_desktop ); ?>" min="0" max="500" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
<label class="egnitech-input-label">
<span><?php esc_html_e( 'Mobile:', 'egnitech-one' ); ?></span>
<div class="egnitech-input-suffix">
<input type="number" class="egnitech-input" name="egnitech_one_logo_width_mobile" value="<?php echo esc_attr( $logo_width_mobile ); ?>" min="0" max="500" step="1" />
<span class="suffix-text">px</span>
</div>
</label>
</div>
</div>
</div>
</div>
</div>
</div> <!-- end tab-header -->

<!-- ==================== BLOG TAB ==================== -->
<div id="tab-blog" class="egnitech-tab-panel">

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Blog Layout', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Customize your blog archives.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row egnitech-option-row-vertical">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Layout Style', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Choose how blog posts are displayed on archive and index pages.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-radio-group">
<label class="egnitech-radio-label">
<input type="radio" name="egnitech_one_blog_layout" value="list" <?php checked( 'list', $blog_layout ); ?> />
<span class="egnitech-radio-btn"><?php esc_html_e( 'List', 'egnitech-one' ); ?></span>
</label>
<label class="egnitech-radio-label">
<input type="radio" name="egnitech_one_blog_layout" value="grid-2" <?php checked( 'grid-2', $blog_layout ); ?> />
<span class="egnitech-radio-btn"><?php esc_html_e( '2 Columns Grid', 'egnitech-one' ); ?></span>
</label>
<label class="egnitech-radio-label">
<input type="radio" name="egnitech_one_blog_layout" value="grid-3" <?php checked( 'grid-3', $blog_layout ); ?> />
<span class="egnitech-radio-btn"><?php esc_html_e( '3 Columns Grid', 'egnitech-one' ); ?></span>
</label>
</div>
</div>
</div>

<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Sidebar Position', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Global sidebar position for blog archive pages. Requires a widget area to be configured.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<select name="egnitech_one_sidebar_position" class="egnitech-input">
<option value="none" <?php selected( 'none', $sidebar_position ); ?>><?php esc_html_e( 'No Sidebar', 'egnitech-one' ); ?></option>
<option value="right" <?php selected( 'right', $sidebar_position ); ?>><?php esc_html_e( 'Right Sidebar', 'egnitech-one' ); ?></option>
<option value="left" <?php selected( 'left', $sidebar_position ); ?>><?php esc_html_e( 'Left Sidebar', 'egnitech-one' ); ?></option>
</select>
</div>
</div>
</div>
</div>

<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Post Meta', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Visibility of post metadata.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Visible Meta Elements', 'egnitech-one' ); ?></label>
<p class="description"><?php esc_html_e( 'Select which post meta elements to display on blog archive and single post pages.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-option-control">
<div class="egnitech-checkbox-list">
<label class="egnitech-checkbox">
<input type="checkbox" name="egnitech_one_meta_author" value="yes" <?php checked( 'yes', $meta_author ); ?> />
<span class="egnitech-checkbox-box"></span>
<?php esc_html_e( 'Author', 'egnitech-one' ); ?>
</label>
<label class="egnitech-checkbox">
<input type="checkbox" name="egnitech_one_meta_date" value="yes" <?php checked( 'yes', $meta_date ); ?> />
<span class="egnitech-checkbox-box"></span>
<?php esc_html_e( 'Date', 'egnitech-one' ); ?>
</label>
<label class="egnitech-checkbox">
<input type="checkbox" name="egnitech_one_meta_categories" value="yes" <?php checked( 'yes', $meta_categories ); ?> />
<span class="egnitech-checkbox-box"></span>
<?php esc_html_e( 'Categories', 'egnitech-one' ); ?>
</label>
<label class="egnitech-checkbox">
<input type="checkbox" name="egnitech_one_meta_tags" value="yes" <?php checked( 'yes', $meta_tags ); ?> />
<span class="egnitech-checkbox-box"></span>
<?php esc_html_e( 'Tags', 'egnitech-one' ); ?>
</label>
</div>
</div>
</div>
</div>
</div>
</div> <!-- end tab-blog -->

<!-- ==================== FOOTER TAB ==================== -->
<div id="tab-footer" class="egnitech-tab-panel">
<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Footer Content', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Manage the copyright section of your footer.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-option-row egnitech-option-row-vertical">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Footer Copyright Text', 'egnitech-one' ); ?></label>
<p class="description">
<?php
printf(
/* translators: %1$s is {year}, %2$s is {site_name} */
esc_html__( 'HTML allowed. Use %1$s for the current year and %2$s for the site name. Leave empty for default: "© {year} {site_name} · All rights reserved"', 'egnitech-one' ),
'<code>{year}</code>',
'<code>{site_name}</code>'
);
?>
</p>
</div>
<div class="egnitech-option-control">
<textarea name="egnitech_one_footer_copyright" rows="2" class="egnitech-textarea" placeholder="© {year} {site_name} · All rights reserved"><?php echo esc_textarea( $footer_copyright ); ?></textarea>
</div>
</div>

<div class="egnitech-option-row egnitech-option-row-vertical">
<div class="egnitech-option-info">
<label><?php esc_html_e( 'Footer Credits Text', 'egnitech-one' ); ?></label>
<p class="description">
<?php
esc_html_e( 'Customize the "EgniTech · Built with WordPress" text. HTML allowed.', 'egnitech-one' );
?>
</p>
</div>
<div class="egnitech-option-control">
<textarea name="egnitech_one_footer_credits" rows="2" class="egnitech-textarea" placeholder='<a href="https://egnitech.com" rel="nofollow">EgniTech</a> &middot; Built with <a href="https://wordpress.org" rel="nofollow">WordPress</a>'><?php echo esc_textarea( $footer_credits ); ?></textarea>
</div>
</div>
</div>
</div>
</div> <!-- end tab-footer -->

<!-- ==================== CONTACT US TAB ==================== -->
<div id="tab-contact" class="egnitech-tab-panel">
    <?php if ( ! egnitech_one_is_contact_template_in_use() ) : ?>
        <div class="egnitech-premium-notice">
            <div class="egnitech-notice-illustration">
                <span class="dashicons dashicons-layout"></span>
            </div>
            <div class="egnitech-notice-content">
                <h3 class="egnitech-notice-title"><?php esc_html_e( 'Contact Template Not in Use', 'egnitech-one' ); ?></h3>
                <p class="egnitech-notice-text"><?php esc_html_e( 'The "EgniTech One Contact Us" page template is not currently assigned to any published page. Settings configured here will only take effect on pages using this specific template.', 'egnitech-one' ); ?></p>
                <div class="egnitech-notice-footer">
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="egnitech-btn egnitech-btn-primary">
                        <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Create a Contact Page', 'egnitech-one' ); ?>
                    </a>
                    <a href="https://developer.wordpress.org/themes/template-files-section/page-template-files/" target="_blank" class="egnitech-btn egnitech-btn-secondary">
                        <?php esc_html_e( 'Learn about Templates', 'egnitech-one' ); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="egnitech-card">
        <div class="egnitech-card-header">
            <h2 class="egnitech-card-title"><?php esc_html_e( 'Page Content', 'egnitech-one' ); ?></h2>
            <p class="egnitech-card-desc"><?php esc_html_e( 'Manage the text content on your Contact Us page.', 'egnitech-one' ); ?></p>
        </div>
        <div class="egnitech-card-body">
            <div class="egnitech-option-row egnitech-option-row-vertical">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Hero Title', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <textarea name="egnitech_one_contact_title" rows="2" class="egnitech-textarea" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?>><?php echo esc_textarea( $contact_title ); ?></textarea>
                </div>
            </div>
            <div class="egnitech-option-row egnitech-option-row-vertical">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Hero Description', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <textarea name="egnitech_one_contact_desc" rows="3" class="egnitech-textarea" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?>><?php echo esc_textarea( $contact_desc ); ?></textarea>
                </div>
            </div>
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Public Contact Email', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'The email address displayed publicly on your website.', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <input type="email" name="egnitech_one_contact_email" value="<?php echo esc_attr( $contact_email ); ?>" class="egnitech-input" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?> />
                </div>
            </div>
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Recipient Email (Incoming)', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Private address that receives form submissions. If empty, uses the Public Contact Email.', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <input type="email" name="egnitech_one_contact_recipient_email" value="<?php echo esc_attr( $recipient_email ); ?>" class="egnitech-input" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?> />
                </div>
            </div>
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Contact Phone', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <input type="text" name="egnitech_one_contact_phone" value="<?php echo esc_attr( $contact_phone ); ?>" class="egnitech-input" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?> />
                </div>
            </div>
        </div>
    </div>

    <div class="egnitech-card">
        <div class="egnitech-card-header">
            <h2 class="egnitech-card-title"><?php esc_html_e( 'Contact Form Fields', 'egnitech-one' ); ?></h2>
            <p class="egnitech-card-desc"><?php esc_html_e( 'Add, remove, or reorder fields in your contact form.', 'egnitech-one' ); ?></p>
        </div>
        <div class="egnitech-card-body">
            <div class="egnitech-scripts-manager">
                <div class="egnitech-scripts-header">
                    <h3><?php esc_html_e( 'Manage Form Fields', 'egnitech-one' ); ?></h3>
                    <button type="button" class="egnitech-btn egnitech-btn-primary" id="egnitech_add_contact_field_btn" <?php disabled( ! egnitech_one_is_contact_template_in_use() ); ?>>
                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Field', 'egnitech-one' ); ?>
                    </button>
                </div>

                <div class="egnitech-scripts-list" id="egnitech_contact_fields_list">
                    <?php
                    if ( ! empty( $contact_fields ) && is_array( $contact_fields ) ) {
                        foreach ( $contact_fields as $index => $field ) {
                            $id          = isset( $field['id'] ) ? $field['id'] : $index . '_' . wp_rand( 1000, 9999 );
                            $label       = isset( $field['label'] ) ? $field['label'] : __( 'Unnamed Field', 'egnitech-one' );
                            $type        = isset( $field['type'] ) ? $field['type'] : 'text';
                            $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
                            $is_required = isset( $field['required'] ) ? filter_var( $field['required'], FILTER_VALIDATE_BOOLEAN ) : true;

                            echo egnitech_one_generate_contact_field_item_html(
                                $id,
                                $label,
                                $type,
                                $placeholder,
                                $is_required
                            );
                        }
                    }
                    ?>
                </div>

                <input type="hidden" name="egnitech_one_contact_form_fields" id="egnitech_one_contact_form_fields" value="<?php echo esc_attr( wp_json_encode( $contact_fields ) ); ?>" />
            </div>
        </div>
    </div>
</div> <!-- end tab-contact -->

<!-- ==================== ADVANCED TAB ==================== -->
<div id="tab-advanced" class="egnitech-tab-panel">
<div class="egnitech-card">
<div class="egnitech-card-header">
<h2 class="egnitech-card-title"><?php esc_html_e( 'Custom Scripts & Analytics', 'egnitech-one' ); ?></h2>
<p class="egnitech-card-desc"><?php esc_html_e( 'Easily integrate third-party services like Google Analytics, Tag Manager, Facebook Pixel, or any custom code snippets.', 'egnitech-one' ); ?></p>
</div>
<div class="egnitech-card-body">
<div class="egnitech-scripts-manager">
<div class="egnitech-scripts-header">
<h3><?php esc_html_e( 'Manage Code Snippets', 'egnitech-one' ); ?></h3>
<button type="button" class="egnitech-btn egnitech-btn-primary" id="egnitech_add_script_btn">
<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Snippet', 'egnitech-one' ); ?>
</button>
</div>

<div class="egnitech-scripts-list" id="egnitech_scripts_list">
<?php
if ( ! empty( $custom_scripts ) && is_array( $custom_scripts ) ) {
    foreach ( $custom_scripts as $index => $script ) {
        $id = 'script_' . $index . '_' . wp_rand( 1000, 9999 );
        $name = isset( $script['name'] ) ? $script['name'] : __( 'Unnamed Script', 'egnitech-one' );
        $location = isset( $script['location'] ) ? $script['location'] : 'header';
        $load_type = isset( $script['load_type'] ) ? $script['load_type'] : 'normal';
        $code = isset( $script['code'] ) ? $script['code'] : '';
        // If string 'true'/'false' accidentally saved or boolean
        $is_active = isset( $script['is_active'] ) ? filter_var( $script['is_active'], FILTER_VALIDATE_BOOLEAN ) : true;
        
        echo egnitech_one_generate_script_item_html(
            $id,
            $name,
            $location,
            $load_type,
            $code,
            $is_active,
            false
        );
    }
}
?>
</div>

<input type="hidden" name="egnitech_one_custom_scripts" id="egnitech_one_custom_scripts" value="<?php echo esc_attr( wp_json_encode( $custom_scripts ) ); ?>" />

</div> <!-- .egnitech-scripts-manager -->
</div> <!-- .egnitech-card-body -->
</div> <!-- .egnitech-card -->
</div> <!-- end tab-advanced -->

<!-- ==================== INTEGRATIONS TAB ==================== -->
<div id="tab-integrations" class="egnitech-tab-panel">
    <div class="egnitech-card">
        <div class="egnitech-card-header">
            <h2 class="egnitech-card-title"><?php esc_html_e( 'Google reCAPTCHA v2', 'egnitech-one' ); ?></h2>
            <p class="egnitech-card-desc"><?php esc_html_e( 'Protect your contact form from spam with Google reCAPTCHA.', 'egnitech-one' ); ?></p>
        </div>
        <div class="egnitech-card-body">
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Enable reCAPTCHA', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Enable reCAPTCHA verification on the contact form.', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <label class="egnitech-switch">
                        <input type="checkbox" name="egnitech_one_recaptcha_enabled" id="egnitech_one_recaptcha_enabled" value="yes" <?php checked( 'yes', $recaptcha_enabled ); ?> />
                        <span class="egnitech-slider"></span>
                    </label>
                </div>
            </div>

            <div id="egnitech-recaptcha-keys-row" style="<?php echo 'yes' === $recaptcha_enabled ? '' : 'display: none;'; ?>">
                <div class="egnitech-option-row">
                    <div class="egnitech-option-info">
                        <label><?php esc_html_e( 'Site Key', 'egnitech-one' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Your Google reCAPTCHA v2 "I\'m not a robot" Site Key.', 'egnitech-one' ); ?></p>
                    </div>
                    <div class="egnitech-option-control">
                        <input type="text" name="egnitech_one_recaptcha_site_key" value="<?php echo esc_attr( $recaptcha_site_key ); ?>" class="egnitech-input" placeholder="<?php esc_attr_e( 'Enter Site Key here', 'egnitech-one' ); ?>" />
                    </div>
                </div>

                <div class="egnitech-option-row">
                    <div class="egnitech-option-info">
                        <label><?php esc_html_e( 'Secret Key', 'egnitech-one' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Your Google reCAPTCHA v2 "I\'m not a robot" Secret Key.', 'egnitech-one' ); ?></p>
                    </div>
                    <div class="egnitech-option-control">
                        <input type="password" name="egnitech_one_recaptcha_secret_key" value="<?php echo esc_attr( $recaptcha_secret_key ); ?>" class="egnitech-input" placeholder="<?php esc_attr_e( 'Enter Secret Key here', 'egnitech-one' ); ?>" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- end tab-integrations -->

<!-- ==================== SMTP SETTINGS TAB ==================== -->
<div id="tab-smtp" class="egnitech-tab-panel">
    <div class="egnitech-card">
        <div class="egnitech-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="egnitech-card-title"><?php esc_html_e( 'SMTP Configuration', 'egnitech-one' ); ?></h2>
                <p class="egnitech-card-desc"><?php esc_html_e( 'Configure an external SMTP server to send emails. This fixes issues when your server cannot send mail directly.', 'egnitech-one' ); ?></p>
            </div>
            <div class="egnitech-card-actions">
                <label class="egnitech-switch" title="<?php esc_attr_e( 'Enable SMTP', 'egnitech-one' ); ?>">
                    <input type="checkbox" name="egnitech_one_smtp_enabled" id="egnitech_one_smtp_enabled" value="yes" <?php checked( 'yes', $smtp_enabled ); ?> data-toggle-target="#egnitech-smtp-details" />
                    <span class="egnitech-slider"></span>
                </label>
            </div>
        </div>
        <div class="egnitech-card-body" id="egnitech-smtp-details" style="<?php echo 'yes' === $smtp_enabled ? '' : 'display: none;'; ?>">
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'SMTP Host', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'e.g., smtp.gmail.com or smtp.sendgrid.net', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <input type="text" name="egnitech_one_smtp_host" value="<?php echo esc_attr( $smtp_host ); ?>" class="egnitech-input" placeholder="smtp.example.com" />
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'SMTP Port', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Typically 587 for TLS, 465 for SSL, or 25.', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <input type="number" name="egnitech_one_smtp_port" value="<?php echo esc_attr( $smtp_port ); ?>" class="egnitech-input" />
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Encryption', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <select name="egnitech_one_smtp_encryption" class="egnitech-input">
                        <option value="none" <?php selected( 'none', $smtp_encryption ); ?>><?php esc_html_e( 'None', 'egnitech-one' ); ?></option>
                        <option value="ssl" <?php selected( 'ssl', $smtp_encryption ); ?>><?php esc_html_e( 'SSL', 'egnitech-one' ); ?></option>
                        <option value="tls" <?php selected( 'tls', $smtp_encryption ); ?>><?php esc_html_e( 'TLS', 'egnitech-one' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'Authentication', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <label class="egnitech-switch">
                        <input type="checkbox" name="egnitech_one_smtp_auth" value="yes" <?php checked( 'yes', $smtp_auth ); ?> />
                        <span class="egnitech-slider"></span>
                    </label>
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'SMTP Username', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <input type="text" name="egnitech_one_smtp_username" value="<?php echo esc_attr( $smtp_username ); ?>" class="egnitech-input" />
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'SMTP Password', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <input type="password" name="egnitech_one_smtp_password" value="<?php echo esc_attr( $smtp_password ); ?>" class="egnitech-input" />
                </div>
            </div>
        </div>
    </div>

    <div class="egnitech-card">
        <div class="egnitech-card-header">
            <h2 class="egnitech-card-title"><?php esc_html_e( 'Sender Information', 'egnitech-one' ); ?></h2>
            <p class="egnitech-card-desc"><?php esc_html_e( 'Configure the "From" address for all outgoing emails.', 'egnitech-one' ); ?></p>
        </div>
        <div class="egnitech-card-body">
            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'From Email', 'egnitech-one' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Emails will appear to be sent from this address.', 'egnitech-one' ); ?></p>
                </div>
                <div class="egnitech-option-control">
                    <input type="email" name="egnitech_one_smtp_from_email" value="<?php echo esc_attr( $smtp_from_email ); ?>" class="egnitech-input" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                </div>
            </div>

            <div class="egnitech-option-row">
                <div class="egnitech-option-info">
                    <label><?php esc_html_e( 'From Name', 'egnitech-one' ); ?></label>
                </div>
                <div class="egnitech-option-control">
                    <input type="text" name="egnitech_one_smtp_from_name" value="<?php echo esc_attr( $smtp_from_name ); ?>" class="egnitech-input" />
                </div>
            </div>
        </div>
    </div>
</div> <!-- end tab-smtp -->

<!-- Persistent Save Bar -->
<div class="egnitech-options-footer">
<button type="submit" name="submit" id="submit" class="egnitech-btn egnitech-btn-primary egnitech-btn-large">
<?php esc_html_e( 'Save All Changes', 'egnitech-one' ); ?>
</button>
</div>

</form>
</div>
</div>
</div>
<?php
}
/**
 * AJAX handler for generating a new script item dynamically.
 */
function egnitech_one_ajax_get_new_script_html(): void {
    check_ajax_referer( 'egnitech_scripts_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
    }

    $script_id = isset( $_POST['script_id'] ) ? sanitize_text_field( wp_unslash( $_POST['script_id'] ) ) : (string) time();
    $name      = __( 'New Script', 'egnitech-one' );

    $html = egnitech_one_generate_script_item_html(
        $script_id,
        $name,
        'header',       // Default location
        'normal',       // Default load type
        '',             // Empty code
        true,           // Active
        true            // Expanded
    );

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_egnitech_one_get_new_script_html', 'egnitech_one_ajax_get_new_script_html' );

/**
 * AJAX handler for generating a new contact field item dynamically.
 */
function egnitech_one_ajax_get_new_contact_field_html(): void {
    check_ajax_referer( 'egnitech_scripts_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
    }

    $field_id = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : (string) time();
    $label    = __( 'New Field', 'egnitech-one' );

    $html = egnitech_one_generate_contact_field_item_html(
        $field_id,
        $label,
        'text',         // Default type
        '',             // Empty placeholder
        true            // Required
    );

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_egnitech_one_get_new_contact_field_html', 'egnitech_one_ajax_get_new_contact_field_html' );

/**
 * Function to generate contact field item HTML
 */
function egnitech_one_generate_contact_field_item_html( string|int $id, string $label, string $type = 'text', string $placeholder = '', bool $is_required = true ): string {
    $item_id = 'field_' . $id;
    $required_attr = $is_required ? 'checked="checked"' : '';

    $types = array(
        'text'     => __( 'Text', 'egnitech-one' ),
        'email'    => __( 'Email', 'egnitech-one' ),
        'tel'      => __( 'Phone', 'egnitech-one' ),
        'textarea' => __( 'Textarea', 'egnitech-one' ),
    );

    $highlights = '<span class="egnitech-highlight-badge highlight-type">' . esc_html( isset( $types[$type] ) ? $types[$type] : $type ) . '</span>';
    
    ob_start();
    ?>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Field Label', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <input type="text" class="egnitech-input field-label-input" value="<?php echo esc_attr( $label ); ?>" />
        </div>
    </div>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Field Type', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <select class="egnitech-input field-type-select">
                <?php foreach ( $types as $val => $name ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $type, $val ); ?>><?php echo esc_html( $name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Placeholder', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <input type="text" class="egnitech-input field-placeholder-input" value="<?php echo esc_attr( $placeholder ); ?>" />
        </div>
    </div>
    <div class="egnitech-option-row">
        <div class="egnitech-option-info">
            <label><?php esc_html_e( 'Required', 'egnitech-one' ); ?></label>
        </div>
        <div class="egnitech-option-control">
            <label class="egnitech-switch">
                <input type="checkbox" class="field-required-toggle" <?php echo esc_attr( $required_attr ); ?> />
                <span class="egnitech-slider"></span>
            </label>
        </div>
    </div>
    <?php
    $content = ob_get_clean();

    return egnitech_one_render_admin_card_item( array(
        'class'        => 'egnitech-contact-field-item',
        'data_id'      => $id,
        'title'        => $label,
        'highlights'   => $highlights,
        'content'      => $content,
        'delete_title' => __( 'Delete Field', 'egnitech-one' ),
        'is_expanded'  => false
    ) );
}
