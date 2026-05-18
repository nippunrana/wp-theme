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

