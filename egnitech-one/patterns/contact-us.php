<?php
/**
 * Title: Contact Us page
 * Slug: egnitech-one/contact-us
 * Categories: egnitech-one
 * Keywords: contact, form, enquiry, email, phone, address
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1200
 * Description: A contact page: a short introduction, then your contact details beside a card with the contact form.
 */
?>
<!-- wp:group {"className":"is-style-contact-us","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-contact-us">
	<!-- wp:paragraph {"textColor":"secondary","fontSize":"large"} -->
	<p class="has-secondary-color has-text-color has-large-font-size"><?php esc_html_e( 'Questions, quotes or feedback? Send us a message and we will get back to you within one working day.', 'egnitech-one' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|60"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Contact details', 'egnitech-one' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"textColor":"secondary","fontSize":"small","style":{"typography":{"fontWeight":"500","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
				<p class="has-secondary-color has-text-color has-small-font-size" style="font-weight:500;letter-spacing:0.06em;text-transform:uppercase"><?php esc_html_e( 'Email', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><a href="mailto:hello@example.com">hello@example.com</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"textColor":"secondary","fontSize":"small","style":{"typography":{"fontWeight":"500","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
				<p class="has-secondary-color has-text-color has-small-font-size" style="font-weight:500;letter-spacing:0.06em;text-transform:uppercase"><?php esc_html_e( 'Phone', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><a href="tel:+910000000000">+91 00000 00000</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"textColor":"secondary","fontSize":"small","style":{"typography":{"fontWeight":"500","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
				<p class="has-secondary-color has-text-color has-small-font-size" style="font-weight:500;letter-spacing:0.06em;text-transform:uppercase"><?php esc_html_e( 'Address', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Street and number, City, State, PIN code', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"textColor":"secondary","fontSize":"small","style":{"typography":{"fontWeight":"500","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
				<p class="has-secondary-color has-text-color has-small-font-size" style="font-weight:500;letter-spacing:0.06em;text-transform:uppercase"><?php esc_html_e( 'Hours', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Monday to Friday, 10 am to 6 pm', 'egnitech-one' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<?php // Each side's border is set separately: a uniform border colour adds has-border-color, which this palette's "border" slug turns into a text colour. ?>
		<!-- wp:group {"style":{"layout":{"columnSpan":2},"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"},"border":{"radius":"12px","top":{"color":"var:preset|color|border","width":"1px"},"right":{"color":"var:preset|color|border","width":"1px"},"bottom":{"color":"var:preset|color|border","width":"1px"},"left":{"color":"var:preset|color|border","width":"1px"}}},"backgroundColor":"surface","layout":{"type":"default"}} -->
		<div class="wp-block-group has-surface-background-color has-background" style="border-radius:12px;border-top-color:var(--wp--preset--color--border);border-top-width:1px;border-right-color:var(--wp--preset--color--border);border-right-width:1px;border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;border-left-color:var(--wp--preset--color--border);border-left-width:1px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:heading {"fontSize":"x-large"} -->
			<h2 class="wp-block-heading has-x-large-font-size"><?php esc_html_e( 'Send us a message', 'egnitech-one' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:egnitech-one/contact-form /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
