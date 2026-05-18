<?php
/**
 * Title: Footer
 * Slug: egnitech-one/footer
 * Categories: egnitech-one
 * Block Types: core/template-part/footer
 * Description: Minimal site footer with copyright and credits.
 * Inserter: false
 */

$footer_copyright = egnitech_one_get_footer_copyright();
$footer_credits   = egnitech_one_get_footer_credits();
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"border":{"top":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"fontSize":"small","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}}} -->
		<p class="has-small-font-size"><?php echo $footer_credits; ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"small","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}},"textColor":"secondary"} -->
		<p class="has-secondary-color has-text-color has-small-font-size"><?php echo $footer_copyright; ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
