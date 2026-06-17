<?php
declare(strict_types=1);

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
if ( ! function_exists( 'egnitech_one_enqueue_styles' ) ) {
	function egnitech_one_enqueue_styles(): void {
		wp_enqueue_style(
			'egnitech-one-style',
			get_theme_file_uri( 'style.css' ),
			array(),
			(string) wp_get_theme()->get( 'Version' )
		);

		// Dynamic Header Padding Implementation
		$desktop_top = get_option( 'egnitech_one_header_padding_desktop_top', '8' );
		$desktop_bot = get_option( 'egnitech_one_header_padding_desktop_bottom', '8' );
		$mobile_top  = get_option( 'egnitech_one_header_padding_mobile_top', '4' );
		$mobile_bot  = get_option( 'egnitech_one_header_padding_mobile_bottom', '4' );

		$custom_css = sprintf(
			'.egnitech-site-header { padding-top: %1$dpx !important; padding-bottom: %2$dpx !important; } ' .
			'@media (min-width: 768px) { .egnitech-site-header { padding-top: %3$dpx !important; padding-bottom: %4$dpx !important; } }',
			absint( $mobile_top ),
			absint( $mobile_bot ),
			absint( $desktop_top ),
			absint( $desktop_bot )
		);

		// Dynamic Logo Widths
		$logo_desktop = get_option( 'egnitech_one_logo_width_desktop', '' );
		$logo_mobile  = get_option( 'egnitech_one_logo_width_mobile', '' );

		if ( ! empty( $logo_mobile ) ) {
			$custom_css .= sprintf(
				' .egnitech-header-logo-container .egnitech-light-logo img, .egnitech-header-logo-container .egnitech-dark-logo img { max-width: %1$dpx !important; width: %1$dpx !important; height: auto !important; }',
				absint( $logo_mobile )
			);
		}
		if ( ! empty( $logo_desktop ) ) {
			$custom_css .= sprintf(
				' @media (min-width: 768px) { .egnitech-header-logo-container .egnitech-light-logo img, .egnitech-header-logo-container .egnitech-dark-logo img { max-width: %1$dpx !important; width: %1$dpx !important; height: auto !important; } }',
				absint( $logo_desktop )
			);
		}

		wp_add_inline_style( 'egnitech-one-style', $custom_css );

		// Enqueue WooCommerce product page custom style if on single product page.
		if ( function_exists( 'is_product' ) && is_product() ) {
			$product_style_path = 'patterns/single-product-details/style.css';
			$product_style_ver  = file_exists( get_theme_file_path( $product_style_path ) ) ? (string) filemtime( get_theme_file_path( $product_style_path ) ) : (string) wp_get_theme()->get( 'Version' );
			wp_enqueue_style(
				'egnitech-one-single-product-details',
				get_theme_file_uri( $product_style_path ),
				array(),
				$product_style_ver
			);

			// Enqueue bulk discount script if product has active discount tiers.
			$product_id = get_the_ID();
			$has_tiers  = false;
			if ( $product_id ) {
				for ( $i = 1; $i <= 3; $i++ ) {
					$qty = (int) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_qty", true );
					$val = (float) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_val", true );
					if ( $qty > 0 && $val > 0 ) {
						$has_tiers = true;
						break;
					}
				}
			}

			if ( $has_tiers ) {
				$bulk_js_path = 'patterns/single-product-details/bulk-discount.js';
				$bulk_js_ver  = file_exists( get_theme_file_path( $bulk_js_path ) ) ? (string) filemtime( get_theme_file_path( $bulk_js_path ) ) : (string) wp_get_theme()->get( 'Version' );
				wp_enqueue_script(
					'egnitech-one-bulk-discount',
					get_theme_file_uri( $bulk_js_path ),
					array( 'jquery' ),
					$bulk_js_ver,
					array(
						'strategy'  => 'defer',
						'in_footer' => true,
					)
				);
			}

			$gallery_layout = get_option( 'egnitech_one_woocommerce_gallery_layout', 'custom' );
			if ( 'custom' === $gallery_layout ) {
				wp_enqueue_style(
					'egnitech-one-product-gallery',
					get_theme_file_uri( 'patterns/product-gallery/style.css' ),
					array(),
					(string) wp_get_theme()->get( 'Version' )
				);

				wp_enqueue_script(
					'egnitech-one-product-gallery',
					get_theme_file_uri( 'patterns/product-gallery/index.js' ),
					array(),
					(string) wp_get_theme()->get( 'Version' ),
					array(
						'strategy'  => 'defer',
						'in_footer' => true,
					)
				);
			} else {
				// Add inline script to trigger window resize on load to fix Flexslider layout in grid/sticky containers.
				wp_add_inline_script(
					'wc-single-product',
					'jQuery(window).on("load", function() { setTimeout(function() { jQuery(window).trigger("resize"); }, 100); });'
				);
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_styles' );

/**
 * Prevent FOUC by instantly applying dark mode before body renders.
 */
if ( ! function_exists( 'egnitech_one_color_scheme_script' ) ) {
	function egnitech_one_color_scheme_script(): void {
		if ( ! egnitech_one_is_dark_mode_enabled() ) {
			?>
			<script>
				(function() {
					document.documentElement.style.colorScheme = 'light';
					document.documentElement.setAttribute('data-scheme', 'light');
				})();
			</script>
			<?php
			return;
		}

		$admin_default = get_option( 'egnitech_one_dark_mode_default', 'system' );
		?>
		<script>
			(function() {
				var STORAGE_KEY = 'egnitech-one-color-scheme';
				var saved = localStorage.getItem(STORAGE_KEY);
				var adminDefault = '<?php echo esc_js( (string) $admin_default ); ?>';
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
}
add_action( 'wp_head', 'egnitech_one_color_scheme_script', 5 );

/**
 * Enqueue dark mode toggle script and styles.
 */
if ( ! function_exists( 'egnitech_one_enqueue_dark_mode' ) ) {
	function egnitech_one_enqueue_dark_mode(): void {
		if ( ! egnitech_one_is_dark_mode_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'egnitech-one-dark-mode',
			get_theme_file_uri( 'assets/css/dark-mode-toggle.css' ),
			array(),
			(string) wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'egnitech-one-dark-mode',
			get_theme_file_uri( 'assets/js/dark-mode-toggle.js' ),
			array(),
			(string) wp_get_theme()->get( 'Version' ),
			array(
				'strategy' => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_dark_mode' );

/**
 * Enqueue scroll-to-top button if enabled.
 */
if ( ! function_exists( 'egnitech_one_enqueue_scroll_to_top' ) ) {
	function egnitech_one_enqueue_scroll_to_top(): void {
		if ( 'yes' !== get_option( 'egnitech_one_scroll_to_top', 'yes' ) ) {
			return;
		}

		wp_enqueue_style(
			'egnitech-one-scroll-top',
			get_theme_file_uri( 'assets/css/scroll-to-top.css' ),
			array(),
			(string) wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'egnitech-one-scroll-top',
			get_theme_file_uri( 'assets/js/scroll-to-top.js' ),
			array(),
			(string) wp_get_theme()->get( 'Version' ),
			array(
				'strategy' => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_scroll_to_top' );

/**
 * Enqueue admin assets for the Theme Options page only.
 *
 * @param string $hook The admin page hook.
 */
if ( ! function_exists( 'egnitech_one_admin_scripts' ) ) {
	function egnitech_one_admin_scripts( string $hook ): void {
		if ( 'appearance_page_egnitech-one-options' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		$admin_css_ver = file_exists( get_theme_file_path( 'assets/css/admin-options.css' ) ) ? (string) filemtime( get_theme_file_path( 'assets/css/admin-options.css' ) ) : (string) wp_get_theme()->get( 'Version' );
		wp_enqueue_style(
			'egnitech-one-admin',
			get_theme_file_uri( 'assets/css/admin-options.css' ),
			array(),
			$admin_css_ver
		);

		$admin_js_ver = file_exists( get_theme_file_path( 'assets/js/admin-options.js' ) ) ? (string) filemtime( get_theme_file_path( 'assets/js/admin-options.js' ) ) : (string) wp_get_theme()->get( 'Version' );
		wp_enqueue_script(
			'egnitech-one-admin',
			get_theme_file_uri( 'assets/js/admin-options.js' ),
			array(),
			$admin_js_ver,
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
}
add_action( 'admin_enqueue_scripts', 'egnitech_one_admin_scripts' );

/**
 * Enqueue reading progress bar if enabled.
 */
if ( ! function_exists( 'egnitech_one_enqueue_reading_progress' ) ) {
	function egnitech_one_enqueue_reading_progress(): void {
		if ( 'yes' !== get_option( 'egnitech_one_reading_progress', 'yes' ) ) {
			return;
		}

		wp_enqueue_style(
			'egnitech-one-reading-progress',
			get_theme_file_uri( 'assets/css/reading-progress.css' ),
			array(),
			(string) wp_get_theme()->get( 'Version' )
		);

		// Dynamic Reading Progress Height Implementation
		$height     = get_option( 'egnitech_one_reading_progress_height', 2 );
		$custom_css = '.egnitech-progress-bar { height: ' . absint( $height ) . 'px !important; }';
		wp_add_inline_style( 'egnitech-one-reading-progress', $custom_css );

		wp_enqueue_script(
			'egnitech-one-reading-progress',
			get_theme_file_uri( 'assets/js/reading-progress.js' ),
			array(),
			(string) wp_get_theme()->get( 'Version' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_reading_progress' );
