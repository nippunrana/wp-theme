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
		// Always the parent's own stylesheet: get_theme_file_uri() would hand back a child theme's.
		wp_enqueue_style(
			'egnitech-one-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			egnitech_one_asset_version( get_parent_theme_file_path( 'style.css' ) )
		);

		// A child theme's style.css loads after it, so the child's rules win.
		if ( is_child_theme() ) {
			wp_enqueue_style(
				'egnitech-one-child-style',
				get_stylesheet_uri(),
				array( 'egnitech-one-style' ),
				egnitech_one_asset_version( get_stylesheet_directory() . '/style.css' )
			);
		}

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
			// Registered, with its address and version, in inc/theme-setup.php.
			wp_enqueue_style( 'egnitech-one-single-product-details' );

			// Sticky Buy Now bar below 992px; the script builds it from the add-to-cart form.
			$sticky_buy_path = 'patterns/single-product-details/sticky-buy-now';
			wp_enqueue_style(
				'egnitech-one-sticky-buy-now',
				get_theme_file_uri( $sticky_buy_path . '.css' ),
				array(),
				egnitech_one_asset_version( get_theme_file_path( $sticky_buy_path . '.css' ) )
			);
			wp_enqueue_script(
				'egnitech-one-sticky-buy-now',
				get_theme_file_uri( $sticky_buy_path . '.js' ),
				array(),
				egnitech_one_asset_version( get_theme_file_path( $sticky_buy_path . '.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
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
				$bulk_js_ver  = egnitech_one_asset_version( get_theme_file_path( $bulk_js_path ) );
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
				// Registered, with its address and version, in inc/theme-setup.php.
				wp_enqueue_style( 'egnitech-one-product-gallery' );

				wp_enqueue_script(
					'egnitech-one-product-gallery',
					get_theme_file_uri( 'patterns/product-gallery/index.js' ),
					array(),
					egnitech_one_asset_version( get_theme_file_path( 'patterns/product-gallery/index.js' ) ),
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
 * Text colour for anything drawn on the Accent colour (buttons, text selection): white or
 * black, whichever reads better (one of the two always reaches 4.5:1) on the accent in each mode. It is worked out from the palette
 * in use on every page, so it follows Theme Options palettes and Site Editor edits alike.
 * theme.json falls back to Base where this variable is missing.
 */
if ( ! function_exists( 'egnitech_one_enqueue_on_accent_color' ) ) {
	function egnitech_one_enqueue_on_accent_color(): void {
		$accent = '';
		foreach ( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) as $entry ) {
			if ( 'accent' === ( $entry['slug'] ?? '' ) ) {
				$accent = (string) $entry['color'];
			}
		}

		if ( preg_match( '/^light-dark\(\s*(#[0-9a-f]{6})\s*,\s*(#[0-9a-f]{6})\s*\)$/i', $accent, $m ) ) {
			$modes = array( $m[1], $m[2] );
		} elseif ( preg_match( '/^#[0-9a-f]{6}$/i', $accent ) ) {
			$modes = array( $accent, $accent );
		} else {
			return; // A CSS variable or rgb() value can't be measured here; the Base fallback applies.
		}

		$on = array_map(
			static fn( string $bg ): string => egnitech_one_contrast_ratio( $bg, '#ffffff' ) >= egnitech_one_contrast_ratio( $bg, '#000000' ) ? '#ffffff' : '#000000',
			$modes
		);

		wp_register_style( 'egnitech-one-on-accent', false, array(), null );
		wp_enqueue_style( 'egnitech-one-on-accent' );
		wp_add_inline_style( 'egnitech-one-on-accent', sprintf( ':root{--egnitech-on-accent:light-dark(%s, %s);}', $on[0], $on[1] ) );
	}
}
// Front end and the block editor alike.
add_action( 'enqueue_block_assets', 'egnitech_one_enqueue_on_accent_color' );

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
			egnitech_one_asset_version( get_theme_file_path( 'assets/css/dark-mode-toggle.css' ) )
		);

		wp_enqueue_script(
			'egnitech-one-dark-mode',
			get_theme_file_uri( 'assets/js/dark-mode-toggle.js' ),
			array(),
			egnitech_one_asset_version( get_theme_file_path( 'assets/js/dark-mode-toggle.js' ) ),
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
			egnitech_one_asset_version( get_theme_file_path( 'assets/css/scroll-to-top.css' ) )
		);

		wp_enqueue_script(
			'egnitech-one-scroll-top',
			get_theme_file_uri( 'assets/js/scroll-to-top.js' ),
			array(),
			egnitech_one_asset_version( get_theme_file_path( 'assets/js/scroll-to-top.js' ) ),
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

		$admin_css_ver = egnitech_one_asset_version( get_theme_file_path( 'assets/css/admin-options.css' ) );
		wp_enqueue_style(
			'egnitech-one-admin',
			get_theme_file_uri( 'assets/css/admin-options.css' ),
			array(),
			$admin_css_ver
		);

		$admin_js_ver = egnitech_one_asset_version( get_theme_file_path( 'assets/js/admin-options.js' ) );
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
				'error'         => __( 'Failed to add script.', 'egnitech-one' ),
			),
		) );
	}
}
add_action( 'admin_enqueue_scripts', 'egnitech_one_admin_scripts' );

/**
 * Enqueue the trust badge icon picker's assets on the product edit screen only.
 *
 * @param string $hook The admin page hook.
 */
if ( ! function_exists( 'egnitech_one_trust_badges_admin_assets' ) ) {
	function egnitech_one_trust_badges_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_current_screen()?->post_type ) {
			return;
		}

		wp_enqueue_style(
			'egnitech-one-admin-trust-badges',
			get_theme_file_uri( 'assets/css/admin-trust-badges.css' ),
			array(),
			egnitech_one_asset_version( get_theme_file_path( 'assets/css/admin-trust-badges.css' ) )
		);

		wp_enqueue_script(
			'egnitech-one-admin-trust-badges',
			get_theme_file_uri( 'assets/js/admin-trust-badges.js' ),
			array( 'wp-a11y' ),
			egnitech_one_asset_version( get_theme_file_path( 'assets/js/admin-trust-badges.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'egnitech_one_trust_badges_admin_assets' );

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
			egnitech_one_asset_version( get_theme_file_path( 'assets/css/reading-progress.css' ) )
		);

		// Dynamic Reading Progress Height Implementation
		$height     = get_option( 'egnitech_one_reading_progress_height', 2 );
		$custom_css = '.egnitech-progress-bar { height: ' . absint( $height ) . 'px !important; }';
		wp_add_inline_style( 'egnitech-one-reading-progress', $custom_css );

		wp_enqueue_script(
			'egnitech-one-reading-progress',
			get_theme_file_uri( 'assets/js/reading-progress.js' ),
			array(),
			egnitech_one_asset_version( get_theme_file_path( 'assets/js/reading-progress.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_reading_progress' );
