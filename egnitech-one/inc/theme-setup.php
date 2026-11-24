<?php
declare(strict_types=1);

/**
 * Theme setup, configurations, and core hooks.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register block pattern category.
 */
if ( ! function_exists( 'egnitech_one_pattern_categories' ) ) {
	function egnitech_one_pattern_categories(): void {
		register_block_pattern_category(
			'egnitech-one',
			array(
				'label'       => __( 'EgniTech One', 'egnitech-one' ),
				'description' => __( 'Patterns for the EgniTech One theme.', 'egnitech-one' ),
			)
		);
	}
}
add_action( 'init', 'egnitech_one_pattern_categories' );

/**
 * Enqueue editor styles to distinguish dual logos.
 */
if ( ! function_exists( 'egnitech_one_add_editor_styles' ) ) {
	function egnitech_one_add_editor_styles(): void {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'editor-style.css' );
	}
}
add_action( 'after_setup_theme', 'egnitech_one_add_editor_styles' );

/**
 * Remove unnecessary default scripts/styles for performance.
 */
if ( ! function_exists( 'egnitech_one_dequeue_bloat' ) ) {
	function egnitech_one_dequeue_bloat(): void {
		// Remove WordPress emoji scripts.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );

		// Remove wlwmanifest and rsd_link.
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );

		// Remove WordPress version from head.
		remove_action( 'wp_head', 'wp_generator' );
	}
}
add_action( 'init', 'egnitech_one_dequeue_bloat' );

/**
 * Add body class based on Sticky Header theme option.
 *
 * @param array $classes CSS classes.
 * @return array Modified CSS classes.
 */
if ( ! function_exists( 'egnitech_one_body_classes' ) ) {
	function egnitech_one_body_classes( array $classes ): array {
		$sticky_header = get_option( 'egnitech_one_sticky_header', 'yes' );
		if ( 'yes' === $sticky_header ) {
			$classes[] = 'has-fixed-header';
		}

		// Blog layout class.
		$blog_layout = get_option( 'egnitech_one_blog_layout', 'list' );
		$classes[] = 'blog-layout-' . sanitize_html_class( (string) $blog_layout );

		// Sidebar class.
		$sidebar = get_option( 'egnitech_one_sidebar_position', 'none' );
		if ( 'none' !== $sidebar ) {
			$classes[] = 'has-sidebar';
			$classes[] = 'sidebar-' . sanitize_html_class( (string) $sidebar );
		}

		return $classes;
	}
}
add_filter( 'body_class', 'egnitech_one_body_classes' );

/**
 * Render breadcrumbs on single posts and pages if enabled.
 */
if ( ! function_exists( 'egnitech_one_render_breadcrumbs' ) ) {
	function egnitech_one_render_breadcrumbs(): void {
		if ( 'yes' !== get_option( 'egnitech_one_breadcrumbs', '' ) ) {
			return;
		}

		// Only show on singular content.
		if ( ! is_singular() ) {
			return;
		}

		$sep   = ' <span class="egnitech-breadcrumb-sep">›</span> ';
		$trail = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'egnitech-one' ) . '</a>';

		if ( is_single() ) {
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				$cat = $categories[0];
				$trail .= $sep . '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
			}
		}

		$trail .= $sep . '<span class="egnitech-breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

		echo '<nav class="egnitech-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'egnitech-one' ) . '">' . $trail . '</nav>' . "\n";
	}
}
add_action( 'wp_body_open', 'egnitech_one_render_breadcrumbs' );

/**
 * Register shortcode for the Dark Mode Toggle.
 * This prevents raw HTML from showing up in the Site Editor when using a Custom HTML block.
 *
 * @return string Toggle HTML.
 */
if ( ! function_exists( 'egnitech_one_dark_mode_toggle_shortcode' ) ) {
	function egnitech_one_dark_mode_toggle_shortcode(): string {
		if ( ! egnitech_one_is_dark_mode_enabled() ) {
			return '';
		}
		return '<button class="egnitech-dark-mode-toggle" aria-label="Switch to dark mode" type="button">
			<svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
			<svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
		</button>';
	}
}
add_shortcode( 'egnitech_dark_mode_toggle', 'egnitech_one_dark_mode_toggle_shortcode' );

/**
 * Ensure shortcodes are properly rendered inside block templates.
 * WordPress block themes sometimes block shortcode execution in patterns/templates.
 *
 * @param string $block_content Outer HTML block content.
 * @param array $block Block parameters.
 * @return string Modified block content.
 */
if ( ! function_exists( 'egnitech_one_force_render_shortcodes' ) ) {
	function egnitech_one_force_render_shortcodes( string $block_content, array $block ): string {
		if ( isset( $block['blockName'] ) && 'core/shortcode' === $block['blockName'] ) {
			return do_shortcode( $block_content );
		}
		return $block_content;
	}
}
add_filter( 'render_block', 'egnitech_one_force_render_shortcodes', 10, 2 );

/**
 * Filter theme.json data to strip dark mode color scheme if dark mode is disabled.
 *
 * @param WP_Theme_JSON_Data $theme_json_data Original theme.json data object.
 * @return WP_Theme_JSON_Data Modified theme.json data object.
 */
if ( ! function_exists( 'egnitech_one_filter_theme_json' ) ) {
	function egnitech_one_filter_theme_json( WP_Theme_JSON_Data $theme_json_data ): WP_Theme_JSON_Data {
		if ( ! egnitech_one_is_dark_mode_enabled() ) {
			$data = $theme_json_data->get_data();
			$css  = (string) ( $data['styles']['css'] ?? '' );

			// Where the line is missing (a child theme's theme.json is all this filter sees, or the line
			// was reworded), add a light-only rule instead: it comes last, so it wins.
			$data['styles']['css'] = str_contains( $css, 'color-scheme: light dark;' )
				? str_replace( 'color-scheme: light dark;', 'color-scheme: light;', $css )
				: $css . ' :root { color-scheme: light; }';
			return new WP_Theme_JSON_Data( $data );
		}
		return $theme_json_data;
	}
}
add_filter( 'wp_theme_json_data_theme', 'egnitech_one_filter_theme_json' );

/**
 * Hide post metadata blocks on the frontend based on theme settings.
 *
 * @param string $block_content HTML content of the block.
 * @param array  $block         Block attributes and data.
 * @return string               Modified block content.
 */
if ( ! function_exists( 'egnitech_one_filter_post_meta_blocks' ) ) {
	function egnitech_one_filter_post_meta_blocks( string $block_content, array $block ): string {
		if ( is_admin() || ! isset( $block['blockName'] ) ) {
			return $block_content;
		}

		$block_name = $block['blockName'];

		// Author visibility.
		if ( 'core/post-author' === $block_name || 'core/post-author-name' === $block_name ) {
			if ( 'yes' !== get_option( 'egnitech_one_meta_author', 'yes' ) ) {
				return '';
			}
		}

		// Date visibility.
		if ( 'core/post-date' === $block_name ) {
			if ( 'yes' !== get_option( 'egnitech_one_meta_date', 'yes' ) ) {
				return '';
			}
		}

		// Categories & Tags visibility.
		if ( 'core/post-terms' === $block_name ) {
			$term = $block['attrs']['term'] ?? '';
			if ( 'category' === $term ) {
				if ( 'yes' !== get_option( 'egnitech_one_meta_categories', 'yes' ) ) {
					return '';
				}
			} elseif ( 'post_tag' === $term ) {
				if ( 'yes' !== get_option( 'egnitech_one_meta_tags', 'yes' ) ) {
					return '';
				}
			}
		}

		return $block_content;
	}
}
add_filter( 'render_block', 'egnitech_one_filter_post_meta_blocks', 10, 2 );

/**
 * Register the block styles for the WooCommerce product page.
 * The product patterns need no code here: WordPress registers every file in patterns/ from its header.
 */
if ( ! function_exists( 'egnitech_one_register_block_styles' ) ) {
	function egnitech_one_register_block_styles(): void {
		wp_register_style(
			'egnitech-one-single-product-details',
			get_template_directory_uri() . '/patterns/single-product-details/style.css',
			array(),
			egnitech_one_asset_version( get_template_directory() . '/patterns/single-product-details/style.css' )
		);

		register_block_style(
			'core/group',
			array(
				'name'         => 'single-product-details',
				'label'        => __( 'Single Product Details', 'egnitech-one' ),
				'style_handle' => 'egnitech-one-single-product-details',
			)
		);

		wp_register_style(
			'egnitech-one-product-gallery',
			get_template_directory_uri() . '/patterns/product-gallery/style.css',
			array(),
			egnitech_one_asset_version( get_template_directory() . '/patterns/product-gallery/style.css' )
		);

		register_block_style(
			'core/group',
			array(
				'name'         => 'product-gallery',
				'label'        => __( 'Custom Product Gallery', 'egnitech-one' ),
				'style_handle' => 'egnitech-one-product-gallery',
			)
		);
	}
}
add_action( 'init', 'egnitech_one_register_block_styles' );

/**
 * Register Product Trust Badges Meta Box.
 */
if ( ! function_exists( 'egnitech_one_add_product_trust_badges_metabox' ) ) {
	function egnitech_one_add_product_trust_badges_metabox(): void {
		add_meta_box(
			'egnitech-product-trust-badges',
			__( 'Product Trust Badges', 'egnitech-one' ),
			'egnitech_one_render_product_trust_badges_metabox',
			'product',
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'egnitech_one_add_product_trust_badges_metabox' );

/**
 * Render Product Trust Badges Meta Box content.
 *
 * @param \WP_Post $post The current post object.
 */
if ( ! function_exists( 'egnitech_one_render_product_trust_badges_metabox' ) ) {
	function egnitech_one_render_product_trust_badges_metabox( \WP_Post $post ): void {
		// Add nonce for security.
		wp_nonce_field( 'egnitech_save_product_trust_badges', 'egnitech_product_trust_badges_nonce' );

		// The badge list, its row template and the icon dialog (inc/woocommerce-trust-badges-admin.php).
		egnitech_one_render_trust_badges_box( $post );
	}
}

/**
 * Save Product Trust Badges Meta Box data.
 *
 * @param int $post_id The ID of the post being saved.
 */
if ( ! function_exists( 'egnitech_one_save_product_trust_badges' ) ) {
	function egnitech_one_save_product_trust_badges( int $post_id ): void {
		// Check nonce.
		if ( ! isset( $_POST['egnitech_product_trust_badges_nonce'] ) || ! wp_verify_nonce( $_POST['egnitech_product_trust_badges_nonce'], 'egnitech_save_product_trust_badges' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save the list. The nonce shows the box was on the page, so no list posted means every
		// badge was removed: an empty list is stored, never deleted. Unslashed, cleaned and
		// capped by egnitech_one_sanitize_trust_badges(), registered with the meta.
		$badges = isset( $_POST['egnitech_trust_badges'] ) && is_array( $_POST['egnitech_trust_badges'] ) ? array_values( $_POST['egnitech_trust_badges'] ) : array();
		update_post_meta( $post_id, EGNITECH_ONE_TRUST_BADGES_META, $badges );
	}
}
add_action( 'save_post_product', 'egnitech_one_save_product_trust_badges' );

/**
 * Shortcode to render product trust badges dynamically.
 *
 * @return string HTML output of trust badges.
 */
if ( ! function_exists( 'egnitech_one_product_trust_badges_shortcode' ) ) {
	function egnitech_one_product_trust_badges_shortcode(): string {
		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return '';
		}

		return egnitech_one_render_trust_badges( $product_id );
	}
}
add_shortcode( 'egnitech_product_trust_badges', 'egnitech_one_product_trust_badges_shortcode' );

/**
 * Shortcode to render product description, specifications, and reviews dynamically.
 *
 * @return string HTML output of sections.
 */
if ( ! function_exists( 'egnitech_one_product_sections_shortcode' ) ) {
	function egnitech_one_product_sections_shortcode(): string {
		$product_id = get_the_ID();
		// The theme stays active when WooCommerce is switched off, and the shortcode may be typed into any post.
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$has_description = ! empty( $product->get_description() );
		$has_attributes  = $product->has_attributes() || $product->get_weight() || $product->get_length() || $product->get_width() || $product->get_height();
		$has_reviews     = comments_open( $product_id );

		ob_start();
		?>
		<div class="egnitech-product-sections">
			<?php if ( $has_description ) : ?>
				<div class="egnitech-product-section egnitech-section-description">
					<h3 class="egnitech-section-title"><?php esc_html_e( 'Description', 'egnitech-one' ); ?></h3>
					<?php echo do_blocks( '<!-- wp:woocommerce/product-description /-->' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_attributes ) : ?>
				<div class="egnitech-product-section egnitech-section-specifications">
					<h3 class="egnitech-section-title"><?php esc_html_e( 'Specifications', 'egnitech-one' ); ?></h3>
					<?php echo do_blocks( '<!-- wp:woocommerce/product-specifications /-->' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_reviews ) : ?>
				<div class="egnitech-product-section egnitech-section-reviews">
					<h3 class="egnitech-section-title"><?php esc_html_e( 'Customer Reviews', 'egnitech-one' ); ?></h3>
					<?php echo do_blocks( '<!-- wp:woocommerce/product-reviews /-->' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
add_shortcode( 'egnitech_product_sections', 'egnitech_one_product_sections_shortcode' );

/**
 * Configure WooCommerce gallery options based on theme options.
 */
if ( ! function_exists( 'egnitech_one_woocommerce_gallery_features' ) ) {
	function egnitech_one_woocommerce_gallery_features(): void {
		$gallery_layout = get_option( 'egnitech_one_woocommerce_gallery_layout', 'custom' );
		if ( 'custom' === $gallery_layout ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
			remove_theme_support( 'wc-product-gallery-lightbox' );
			remove_theme_support( 'wc-product-gallery-slider' );
			return;
		}

		// Hover zoom.
		$zoom_enabled = get_option( 'egnitech_one_woocommerce_gallery_zoom', 'yes' );
		if ( 'yes' === $zoom_enabled ) {
			add_theme_support( 'wc-product-gallery-zoom' );
		} else {
			remove_theme_support( 'wc-product-gallery-zoom' );
		}

		// Lightbox.
		$lightbox_enabled = get_option( 'egnitech_one_woocommerce_gallery_lightbox', 'yes' );
		if ( 'yes' === $lightbox_enabled ) {
			add_theme_support( 'wc-product-gallery-lightbox' );
		} else {
			remove_theme_support( 'wc-product-gallery-lightbox' );
		}

		// Slider.
		add_theme_support( 'wc-product-gallery-slider' );
	}
}
add_action( 'after_setup_theme', 'egnitech_one_woocommerce_gallery_features', 100 );

/**
 * Shortcode to render the custom modern product gallery.
 *
 * @return string HTML output of the custom gallery.
 */
if ( ! function_exists( 'egnitech_one_product_gallery_shortcode' ) ) {
	function egnitech_one_product_gallery_shortcode(): string {
		$product_id = get_the_ID();
		// The theme stays active when WooCommerce is switched off, and the shortcode may be typed into any post.
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$main_image_id  = $product->get_image_id();
		$attachment_ids = $product->get_gallery_image_ids();

		// If there's no main image and no gallery images, show a placeholder.
		if ( ! $main_image_id && empty( $attachment_ids ) ) {
			$placeholder_src = wc_placeholder_img_src( 'woocommerce_single' );
			return sprintf(
				'<div class="egnitech-custom-gallery egnitech-custom-gallery--empty"><div class="egnitech-gallery-main"><img src="%s" alt="%s" /></div></div>',
				esc_url( $placeholder_src ),
				esc_attr__( 'Placeholder image', 'egnitech-one' )
			);
		}

		// Compile all image IDs
		$image_ids = array();
		if ( $main_image_id ) {
			$image_ids[] = $main_image_id;
		}
		foreach ( $attachment_ids as $attachment_id ) {
			if ( ! in_array( $attachment_id, $image_ids, true ) ) {
				$image_ids[] = $attachment_id;
			}
		}

		// Get src URLs and alt text for all sizes.
		$images = array();
		foreach ( $image_ids as $id ) {
			$thumbnail = wp_get_attachment_image_src( $id, 'woocommerce_thumbnail' );
			$large     = wp_get_attachment_image_src( $id, 'woocommerce_single' );
			$full      = wp_get_attachment_image_src( $id, 'full' );
			
			$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
			if ( is_array( $alt ) ) {
				$alt = implode( ' ', $alt );
			}
			$alt = (string) $alt;
			if ( empty( $alt ) ) {
				$alt = (string) $product->get_name();
			}

			if ( $large && $thumbnail && $full ) {
				$images[] = array(
					'id'        => $id,
					'thumbnail' => $thumbnail[0],
					'large'     => $large[0],
					'full'      => $full[0],
					'alt'       => $alt,
					'width'     => $large[1],
					'height'    => $large[2],
				);
			}
		}

		if ( empty( $images ) ) {
			return '';
		}

		ob_start();
		$thumbnail_style = get_option( 'egnitech_one_woocommerce_gallery_thumbnail_style', 'bottom-carousel' );
		$show_lightbox_icon = get_option( 'egnitech_one_woocommerce_gallery_lightbox_icon', 'yes' );
		$gallery_classes = array(
			'egnitech-custom-gallery',
			'egnitech-custom-gallery--' . $thumbnail_style
		);
		if ( 'yes' !== $show_lightbox_icon ) {
			$gallery_classes[] = 'egnitech-gallery--no-lightbox-icon';
		}

		// One frame for the whole gallery, so swiping never changes its height: 3:4 when most
		// pictures are tall (a tie goes to the first picture), 1:1 otherwise.
		$is_tall = static fn ( array $img ): bool => $img['width'] > 0 && $img['height'] >= 1.15 * $img['width'];
		$tall    = count( array_filter( $images, $is_tall ) );
		$total   = count( $images );
		if ( 2 * $tall > $total || ( 2 * $tall === $total && $is_tall( $images[0] ) ) ) {
			$gallery_classes[] = 'egnitech-custom-gallery--portrait';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $gallery_classes ) ); ?>" id="egnitech-product-gallery" data-images="<?php echo esc_attr( wp_json_encode( $images ) ); ?>">
			<!-- Main Display Image Container -->
			<div class="egnitech-gallery-main-wrapper">
				<div class="egnitech-gallery-main">
					<?php foreach ( $images as $index => $img ) : ?>
						<div class="egnitech-gallery-slide" data-index="<?php echo esc_attr( (string) $index ); ?>">
							<button class="egnitech-gallery-trigger" type="button" aria-label="<?php esc_attr_e( 'Open image lightbox', 'egnitech-one' ); ?>" data-index="<?php echo esc_attr( (string) $index ); ?>">
								<img 
									src="<?php echo esc_url( $img['large'] ); ?>" 
									alt="<?php echo esc_attr( $img['alt'] ); ?>" 
									width="<?php echo esc_attr( (string) $img['width'] ); ?>" 
									height="<?php echo esc_attr( (string) $img['height'] ); ?>" 
									data-full="<?php echo esc_url( $img['full'] ); ?>"
									<?php
									// Only the first picture shows when the page opens: it loads first, the others when they are needed.
									echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
									?>
								/>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
				
				<?php if ( count( $images ) > 1 ) : ?>
					<!-- Navigation arrows for main image -->
					<button type="button" class="egnitech-gallery-arrow egnitech-gallery-arrow--prev" aria-label="<?php esc_attr_e( 'Previous image', 'egnitech-one' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button type="button" class="egnitech-gallery-arrow egnitech-gallery-arrow--next" aria-label="<?php esc_attr_e( 'Next image', 'egnitech-one' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				<?php endif; ?>
			</div>

			<?php if ( count( $images ) > 1 ) : ?>
				<!-- Mobile Fraction Pagination -->
				<div class="egnitech-gallery-mobile-pagination">
					<button type="button" class="egnitech-gallery-mobile-arrow egnitech-gallery-mobile-arrow--prev" aria-label="<?php esc_attr_e( 'Previous image', 'egnitech-one' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<span class="egnitech-gallery-mobile-fraction">
						<span class="egnitech-gallery-mobile-current">1</span>/<span class="egnitech-gallery-mobile-total"><?php echo count( $images ); ?></span>
					</span>
					<button type="button" class="egnitech-gallery-mobile-arrow egnitech-gallery-mobile-arrow--next" aria-label="<?php esc_attr_e( 'Next image', 'egnitech-one' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( count( $images ) > 1 ) : ?>
				<!-- Thumbnails Bottom Row -->
				<div class="egnitech-gallery-thumbs-container">
					<?php if ( 'bottom-carousel' === $thumbnail_style ) : ?>
						<button type="button" class="egnitech-thumbs-arrow egnitech-thumbs-arrow--prev" aria-label="<?php esc_attr_e( 'Scroll thumbnails left', 'egnitech-one' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
						</button>
					<?php endif; ?>

					<div class="egnitech-gallery-thumbs" role="tablist">
						<?php foreach ( $images as $index => $img ) : ?>
							<button 
								type="button" 
								class="egnitech-gallery-thumb-btn<?php echo 0 === $index ? ' is-active' : ''; ?>" 
								role="tab" 
								aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'View image %d', 'egnitech-one' ), $index + 1 ) ); ?>"
								data-index="<?php echo esc_attr( (string) $index ); ?>"
								style="--thumb-img: url('<?php echo esc_url( $img['thumbnail'] ); ?>');"
							>
								<img src="<?php echo esc_url( $img['thumbnail'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy" />
							</button>
						<?php endforeach; ?>
					</div>

					<?php if ( 'bottom-carousel' === $thumbnail_style ) : ?>
						<button type="button" class="egnitech-thumbs-arrow egnitech-thumbs-arrow--next" aria-label="<?php esc_attr_e( 'Scroll thumbnails right', 'egnitech-one' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</button>
						<div class="egnitech-gallery-thumbs-scrollbar">
							<div class="egnitech-gallery-thumbs-scrollbar-bar"></div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Lightbox Modal -->
			<div class="egnitech-gallery-lightbox" id="egnitech-gallery-lightbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Product Image Lightbox', 'egnitech-one' ); ?>" tabindex="-1">
				<div class="egnitech-lightbox-backdrop"></div>
				<div class="egnitech-lightbox-content">
					<button type="button" class="egnitech-lightbox-close" aria-label="<?php esc_attr_e( 'Close lightbox', 'egnitech-one' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
					</button>
					
					<div class="egnitech-lightbox-image-wrapper">
						<img class="egnitech-lightbox-image" id="egnitech-lightbox-active-image" src="" alt="" />
					</div>
					
					<button type="button" class="egnitech-lightbox-arrow egnitech-lightbox-arrow--prev" aria-label="<?php esc_attr_e( 'Previous image', 'egnitech-one' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button type="button" class="egnitech-lightbox-arrow egnitech-lightbox-arrow--next" aria-label="<?php esc_attr_e( 'Next image', 'egnitech-one' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					
					<div class="egnitech-lightbox-caption" id="egnitech-lightbox-caption"></div>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
add_shortcode( 'egnitech_product_gallery', 'egnitech_one_product_gallery_shortcode' );

/**
 * Swap default WooCommerce product gallery block with custom gallery block on frontend.
 *
 * @param string $block_content Block HTML.
 * @param array  $block         Block data.
 * @return string Modified Block HTML.
 */
if ( ! function_exists( 'egnitech_one_render_product_gallery_block' ) ) {
	function egnitech_one_render_product_gallery_block( string $block_content, array $block ): string {
		if ( is_admin() || ! isset( $block['blockName'] ) ) {
			return $block_content;
		}

		if ( 'woocommerce/product-image-gallery' === $block['blockName'] ) {
			$gallery_layout = get_option( 'egnitech_one_woocommerce_gallery_layout', 'custom' );
			if ( 'custom' === $gallery_layout ) {
				return do_shortcode( '[egnitech_product_gallery]' );
			}
		}

		return $block_content;
	}
}
add_filter( 'render_block', 'egnitech_one_render_product_gallery_block', 10, 2 );

/**
 * Render the product short description block without auto-generating excerpts
 * from main content when empty, and preserving HTML tags.
 *
 * @param string $block_content Block HTML.
 * @param array  $block         Block data.
 * @return string Modified block content.
 */
if ( ! function_exists( 'egnitech_one_render_product_short_description_block' ) ) {
	function egnitech_one_render_product_short_description_block( string $block_content, array $block ): string {
		if ( is_admin() || ! isset( $block['blockName'] ) ) {
			return $block_content;
		}

		$is_product_summary = false;
		if ( 'core/post-excerpt' === $block['blockName'] ) {
			$namespace = $block['attrs']['__woocommerceNamespace'] ?? '';
			if ( 'woocommerce/product-query/product-summary' === $namespace ) {
				$is_product_summary = true;
			}
		} elseif ( 'woocommerce/product-summary' === $block['blockName'] ) {
			$is_product_summary = true;
		}

		if ( $is_product_summary ) {
			$post_id = $block['context']['postId'] ?? get_the_ID();
			if ( $post_id ) {
				$post = get_post( $post_id );
				if ( $post && 'product' === $post->post_type ) {
					$excerpt = (string) $post->post_excerpt;
					if ( '' === trim( $excerpt ) ) {
						return '';
					}
					return '<div class="wp-block-post-excerpt">' . apply_filters( 'woocommerce_short_description', $excerpt ) . '</div>';
				}
			}
		}

		return $block_content;
	}
}
add_filter( 'render_block', 'egnitech_one_render_product_short_description_block', 10, 2 );

/**
 * Put the percentage saved ("↓45%") in front of the product page's sale price.
 *
 * Only where WooCommerce itself prints an old and a new price: a variable or grouped product
 * shows a range instead. Worked out from the display prices, so it follows the shop's tax
 * display setting.
 *
 * @param string $block_content Block HTML.
 * @param array  $block         Block data.
 * @return string Modified block content.
 */
if ( ! function_exists( 'egnitech_one_render_product_price_discount' ) ) {
	function egnitech_one_render_product_price_discount( string $block_content, array $block ): string {
		if (
			is_admin()
			|| 'woocommerce/product-price' !== ( $block['blockName'] ?? '' )
			|| empty( $block['attrs']['isDescendentOfSingleProductTemplate'] )
		) {
			return $block_content;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product || ! $product->is_on_sale() || $product->is_type( array( 'variable', 'grouped' ) ) ) {
			return $block_content;
		}

		$regular = (float) wc_get_price_to_display( $product, array( 'price' => (float) $product->get_regular_price() ) );
		$current = (float) wc_get_price_to_display( $product );
		$percent = $regular > 0 ? (int) round( ( $regular - $current ) / $regular * 100 ) : 0;
		if ( $percent < 1 ) {
			return $block_content;
		}

		$badge = sprintf(
			'<span class="egnitech-price-discount"><svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16M5 13l7 7 7-7"/></svg><span aria-hidden="true">%1$d%%</span><span class="screen-reader-text">%2$s</span></span>',
			$percent,
			/* translators: %d: percentage saved on the sale price. */
			esc_html( sprintf( __( '%d%% off.', 'egnitech-one' ), $percent ) )
		);

		return (string) preg_replace( '/(<div[^>]*\bwc-block-components-product-price\b[^>]*>)/', '$1' . $badge, $block_content, 1 );
	}
}
add_filter( 'render_block', 'egnitech_one_render_product_price_discount', 10, 2 );
