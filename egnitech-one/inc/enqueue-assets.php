<?php
/**
 * Enqueue scripts and styles for both frontend and admin.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue theme stylesheet.
 */
function egnitech_one_enqueue_styles() {
	wp_enqueue_style(
		'egnitech-one-style',
		get_theme_file_uri( 'style.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'egnitech-one-shared',
		get_theme_file_uri( 'assets/css/shared-styles.css' ),
		array( 'egnitech-one-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_styles' );

/**
 * Prevent FOUC by instantly applying dark mode before body renders.
 */
function egnitech_one_color_scheme_script() {
	$admin_default = get_option( 'egnitech_one_dark_mode_default', 'system' );
	?>
	<script>
		(function() {
			var STORAGE_KEY = 'egnitech-one-color-scheme';
			var saved = localStorage.getItem(STORAGE_KEY);
			var adminDefault = '<?php echo esc_js( $admin_default ); ?>';
			var scheme;
			if (saved) {
				scheme = saved;
			} else if (adminDefault && adminDefault !== 'system') {
				scheme = adminDefault;
			} else {
				scheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
			}
			document.documentElement.style.colorScheme = scheme;
			document.documentElement.setAttribute('data-scheme', scheme);
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'egnitech_one_color_scheme_script', 5 );

/**
 * Expose contact form AJAX data and reCAPTCHA settings to the frontend.
 */
function egnitech_one_contact_form_frontend_data() {
	$recaptcha_enabled = get_option( 'egnitech_one_recaptcha_enabled', '' );
	$site_key = get_option( 'egnitech_one_recaptcha_site_key', '' );
	
	// Enqueue Google reCAPTCHA API script ONLY if enabled
	if ( 'yes' === $recaptcha_enabled && ! empty( $site_key ) ) {
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, array( 'strategy' => 'async', 'in_footer' => false ) );
	}

	?>
	<script id="egnitech-contact-form-js-extra">
		window.egnitechContactForm = {
			ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( wp_create_nonce( 'egnitech_contact_nonce' ) ); ?>',
			recaptcha: {
				enabled: <?php echo 'yes' === $recaptcha_enabled ? 'true' : 'false'; ?>,
				siteKey: '<?php echo esc_js( $site_key ); ?>'
			},
			i18n: {
				sending: '<?php echo esc_js( __( 'Sending...', 'egnitech-one' ) ); ?>',
				fillAll: '<?php echo esc_js( __( 'Please fill in all required fields.', 'egnitech-one' ) ); ?>',
				error:   '<?php echo esc_js( __( 'An error occurred. Please try again.', 'egnitech-one' ) ); ?>'
			}
		};
	</script>
	<?php
}
add_action( 'wp_head', 'egnitech_one_contact_form_frontend_data' );

/**
 * Enqueue dark mode toggle script and styles.
 */
function egnitech_one_enqueue_dark_mode() {
	wp_enqueue_style(
		'egnitech-one-dark-mode',
		get_theme_file_uri( 'assets/css/dark-mode-toggle.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'egnitech-one-dark-mode',
		get_theme_file_uri( 'assets/js/dark-mode-toggle.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		array(
			'strategy' => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_dark_mode' );

/**
 * Enqueue scroll-to-top button if enabled.
 */
function egnitech_one_enqueue_scroll_to_top() {
	if ( 'yes' !== get_option( 'egnitech_one_scroll_to_top', 'yes' ) ) {
		return;
	}

	wp_enqueue_style(
		'egnitech-one-scroll-top',
		get_theme_file_uri( 'assets/css/scroll-to-top.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'egnitech-one-scroll-top',
		get_theme_file_uri( 'assets/js/scroll-to-top.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		array(
			'strategy' => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_scroll_to_top' );

/**
 * Enqueue admin assets for the Theme Options page only.
 */
function egnitech_one_admin_scripts( $hook ) {
	if ( 'appearance_page_egnitech-one-options' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'egnitech-one-admin',
		get_theme_file_uri( 'assets/css/admin-options.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'egnitech-one-admin',
		get_theme_file_uri( 'assets/js/admin-options.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_localize_script( 'egnitech-one-admin', 'egnitechAdmin', array(
		'selectLogoTitle' => __( 'Select Dark Mode Logo', 'egnitech-one' ),
		'useLogoText'     => __( 'Use this logo', 'egnitech-one' ),
		'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
		'nonce'           => wp_create_nonce( 'egnitech_scripts_nonce' ),
		'i18n'            => array(
			'loading'       => __( 'Loading…', 'egnitech-one' ),
			'confirmDelete' => __( 'Are you sure you want to delete this code snippet?', 'egnitech-one' ),
		),
	) );
}
add_action( 'admin_enqueue_scripts', 'egnitech_one_admin_scripts' );

/**
 * Enqueue reading progress bar if enabled.
 */
function egnitech_one_enqueue_reading_progress() {
	if ( 'yes' !== get_option( 'egnitech_one_reading_progress', 'yes' ) ) {
		return;
	}

	wp_enqueue_style(
		'egnitech-one-reading-progress',
		get_theme_file_uri( 'assets/css/reading-progress.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'egnitech-one-reading-progress',
		get_theme_file_uri( 'assets/js/reading-progress.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_reading_progress' );

/**
 * Automatically enqueue CSS and JS for modular templates stored in subfolders.
 * If the current template is 'templates/my-template/index.html', it will look for:
 * - /templates/my-template/style.css
 * - /templates/my-template/index.js
 */
function egnitech_one_enqueue_modular_assets() {
	if ( ! is_singular() && ! is_page_template() ) {
		return;
	}

	// Get the template slug or name.
	$template_slug = get_page_template_slug();
	
	// If no custom template slug, try to identify core templates.
	if ( ! $template_slug ) {
		if ( is_single() ) {
			$template_slug = 'single';
		} elseif ( is_page() ) {
			$template_slug = 'page';
		} elseif ( is_home() ) {
			$template_slug = 'home';
		}
	}

	if ( ! $template_slug ) {
		return;
	}

	// Normalize slug (WordPress sometimes returns 'templates/slug.php' or just 'slug').
	$template_slug = str_replace( '.html', '', $template_slug );
	$template_slug = str_replace( 'templates/', '', $template_slug );

	// Support for modular subfolders: templates/{slug}/
	$base_path = 'templates/' . $template_slug . '/';
	
	// Check for modular CSS.
	$css_file = $base_path . 'style.css';
	if ( file_exists( get_theme_file_path( $css_file ) ) ) {
		wp_enqueue_style(
			'egnitech-modular-' . sanitize_title( $template_slug ),
			get_theme_file_uri( $css_file ),
			array( 'egnitech-one-style' ),
			filemtime( get_theme_file_path( $css_file ) )
		);
	}

	// Check for modular JS.
	$js_file = $base_path . 'index.js';
	if ( file_exists( get_theme_file_path( $js_file ) ) ) {
		wp_enqueue_script(
			'egnitech-modular-' . sanitize_title( $template_slug ),
			get_theme_file_uri( $js_file ),
			array(),
			filemtime( get_theme_file_path( $js_file ) ),
			array(
				'strategy' => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_modular_assets' );
