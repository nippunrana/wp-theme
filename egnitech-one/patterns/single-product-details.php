<?php
/**
 * Title: Single Product Details
 * Slug: egnitech-one/single-product-details
 * Categories: egnitech-one
 * Inserter: false
 */

$gallery_layout = get_option( 'egnitech_one_woocommerce_gallery_layout', 'custom' );
?>
<!-- wp:group {"metadata":{"name":"Product Details Grid"},"className":"is-style-single-product-details","layout":{"type":"constrained"},"align":"wide"} -->
<div class="wp-block-group alignwide is-style-single-product-details">
	<!-- wp:group {"className":"egnitech-product-gallery-column"} -->
	<div class="wp-block-group egnitech-product-gallery-column">
		<?php if ( 'custom' === $gallery_layout ) : ?>
			<!-- wp:pattern {"slug":"egnitech-one/product-gallery"} /-->
		<?php else : ?>
			<!-- wp:woocommerce/product-image-gallery /-->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"egnitech-product-info-column"} -->
	<div class="wp-block-group egnitech-product-info-column">
		<!-- wp:post-title {"level":1,"__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

		<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true} /-->

		<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true} /-->

		<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->

		<!-- wp:woocommerce/add-to-cart-form /-->

		<!-- wp:shortcode -->
		[egnitech_product_trust_badges]
		<!-- /wp:shortcode -->

		<!-- wp:woocommerce/product-meta -->
		<div class="wp-block-woocommerce-product-meta">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group">
				<!-- wp:woocommerce/product-sku /-->
				<!-- wp:post-terms {"term":"product_cat","prefix":"Category: "} /-->
				<!-- wp:post-terms {"term":"product_tag","prefix":"Tags: "} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:woocommerce/product-meta -->

		<!-- wp:shortcode -->
		[egnitech_product_sections]
		<!-- /wp:shortcode -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
