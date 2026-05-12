<?php
/**
 * Title: Blog Query Loop
 * Slug: egnitech-one/query-loop
 * Categories: egnitech-one
 * Block Types: core/query
 * Description: Blog post listing with title, date, excerpt, and featured image.
 */
?>
<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true}} -->
<div class="wp-block-query">
	<!-- wp:post-template {"layout":{"type":"default"}} -->

		<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"},"margin":{"bottom":"var:preset|spacing|60"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
			<!-- wp:post-title {"isLink":true,"fontSize":"x-large"} /-->

			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"16px"}}} -->
			<div class="wp-block-group">
				<!-- wp:post-date {"fontSize":"small"} /-->
				<!-- wp:post-terms {"term":"category","fontSize":"small"} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:post-excerpt {"moreText":"Continue reading →","excerptLength":35,"fontSize":"medium"} /-->
		</div>
		<!-- /wp:group -->

	<!-- /wp:post-template -->

	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
	<div class="wp-block-group">
		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
			<!-- wp:query-pagination-previous {"label":"← Newer posts"} /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next {"label":"Older posts →"} /-->
		<!-- /wp:query-pagination -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:query -->
