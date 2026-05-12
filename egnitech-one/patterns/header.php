<?php
/**
 * Title: Header
 * Slug: egnitech-one/header
 * Categories: egnitech-one
 * Block Types: core/template-part/header
 * Description: Site header with logo, navigation, and dark mode toggle.
 */
?>
<!-- wp:group {"className":"egnitech-site-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group egnitech-site-header">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"egnitech-header-logo-container","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group egnitech-header-logo-container">
			<!-- wp:site-logo {"className":"egnitech-light-logo"} /-->
			<?php 
			$dark_logo = get_option( 'egnitech_one_dark_logo_url' );
			if ( $dark_logo ) : ?>
				<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"egnitech-dark-logo"} -->
				<figure class="wp-block-image egnitech-dark-logo">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<img src="<?php echo esc_url( set_url_scheme( $dark_logo, 'https' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" />
					</a>
				</figure>
				<!-- /wp:image -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"24px"}}} /-->

			<!-- wp:shortcode -->
			[egnitech_dark_mode_toggle]
			<!-- /wp:shortcode -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
