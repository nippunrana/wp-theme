<?php
/**
 * Title: Sidebar
 * Slug: egnitech-one/sidebar
 * Categories: egnitech-one
 * Description: Sidebar content containing Search, Latest Posts, and Categories list.
 * Inserter: false
 */
?>
<!-- wp:group {"tagName":"aside","className":"is-style-sidebar","layout":{"type":"constrained"},"lock":{"move":true,"remove":true}} -->
<aside class="wp-block-group is-style-sidebar">
	<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search the site...","buttonText":"Search","buttonUseIcon":true,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} /-->

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--50)">
		<!-- wp:heading {"level":3,"fontSize":"medium"} -->
		<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Recent Posts', 'egnitech-one' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:latest-posts {"postsToShow":5,"displayPostDate":true} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":3,"fontSize":"medium"} -->
		<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Categories', 'egnitech-one' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:categories {"showPostCounts":true} /-->
	</div>
	<!-- /wp:group -->
</aside>
<!-- /wp:group -->
