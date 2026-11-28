<?php
declare(strict_types=1);

/**
 * Product page reviews: a summary row (average, count, "Write a review") above WooCommerce's own
 * review list, with WooCommerce's own review form wrapped in a slide-in drawer.
 *
 * Everything that decides who may review, which fields are required and whether a review waits
 * for moderation stays WooCommerce's and WordPress's: the list and form are WooCommerce's classic
 * reviews template, printed by the Product Reviews block. The theme only adds the summary and the
 * <dialog> around the form, and styles both (patterns/single-product-details/reviews.css and .js).
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The reviews section of the product page.
 *
 * @param WC_Product $product The product shown.
 * @return string Section markup.
 */
function egnitech_one_render_product_reviews( WC_Product $product ): string {
	// Turnstile's script is loaded by reviews.js when the drawer first opens, unless something
	// else on the page already needs it at load.
	$turnstile_queued = wp_script_is( 'egnitech-one-turnstile' );

	// Scoped to this render, so no other comment form on the site gets a drawer.
	add_action( 'comment_form_before', 'egnitech_one_review_drawer_open' );
	add_action( 'comment_form_after', 'egnitech_one_review_drawer_close' );
	$forms_before = did_action( 'comment_form_before' );

	$reviews = do_blocks( '<!-- wp:woocommerce/product-reviews /-->' );

	// WooCommerce prints a message instead of the form when only verified owners may review.
	$has_form = did_action( 'comment_form_before' ) > $forms_before;
	remove_action( 'comment_form_before', 'egnitech_one_review_drawer_open' );
	remove_action( 'comment_form_after', 'egnitech_one_review_drawer_close' );

	$turnstile_src = '';
	if ( ! $turnstile_queued && wp_script_is( 'egnitech-one-turnstile' ) ) {
		wp_dequeue_script( 'egnitech-one-turnstile' );
		$turnstile_src = (string) wp_scripts()->registered['egnitech-one-turnstile']->src;
	}

	$count   = $product->get_review_count();
	$average = (float) $product->get_average_rating();

	ob_start();
	?>
	<div class="egnitech-product-section egnitech-section-reviews">
		<div class="egnitech-reviews-summary">
			<div class="egnitech-reviews-summary__text">
				<h3 class="egnitech-section-title"><?php esc_html_e( 'Reviews', 'egnitech-one' ); ?></h3>
				<?php if ( $count > 0 && $average > 0 && wc_review_ratings_enabled() ) : ?>
					<div class="egnitech-reviews-score">
						<span class="egnitech-reviews-score__average"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
						<?php
						printf(
							'<span class="egnitech-stars" role="img" aria-label="%1$s" style="--egnitech-rating: %2$s%%"></span>',
							/* translators: %s: average rating, e.g. 4.5 */
							esc_attr( sprintf( __( 'Rated %s out of 5', 'egnitech-one' ), number_format_i18n( $average, 1 ) ) ),
							esc_attr( (string) round( $average / 5 * 100, 1 ) )
						);
						?>
						<span class="egnitech-reviews-score__count">
							<?php
							/* translators: %s: number of reviews */
							echo esc_html( sprintf( _n( 'Based on %s review', 'Based on %s reviews', $count, 'egnitech-one' ), number_format_i18n( $count ) ) );
							?>
						</span>
					</div>
				<?php elseif ( $count > 0 ) : ?>
					<p class="egnitech-reviews-score__count">
						<?php
						/* translators: %s: number of reviews */
						echo esc_html( sprintf( _n( '%s review', '%s reviews', $count, 'egnitech-one' ), number_format_i18n( $count ) ) );
						?>
					</p>
				<?php else : ?>
					<p class="egnitech-reviews-score__count"><?php esc_html_e( 'No reviews yet. Be the first to share your thoughts.', 'egnitech-one' ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $has_form ) : ?>
				<button type="button" class="wp-element-button egnitech-reviews-write" aria-haspopup="dialog" aria-controls="egnitech-review-drawer"<?php echo $turnstile_src ? ' data-turnstile-src="' . esc_url( $turnstile_src ) . '"' : ''; ?>>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
					<?php esc_html_e( 'Write a review', 'egnitech-one' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php echo $reviews; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's block output. ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Open the drawer around WooCommerce's review form (comment_form_before).
 * A closed <dialog> is not rendered, so the form costs the page nothing until it opens.
 */
function egnitech_one_review_drawer_open(): void {
	?>
	<dialog id="egnitech-review-drawer" class="egnitech-review-drawer" aria-labelledby="egnitech-review-drawer-title" data-rating-error="<?php esc_attr_e( 'Please select a rating.', 'egnitech-one' ); ?>">
		<div class="egnitech-review-drawer__panel">
			<div class="egnitech-review-drawer__header">
				<div>
					<h2 id="egnitech-review-drawer-title" class="egnitech-review-drawer__title"><?php esc_html_e( 'Write a review', 'egnitech-one' ); ?></h2>
					<p class="egnitech-review-drawer__product"><?php echo esc_html( get_the_title() ); ?></p>
				</div>
				<button type="button" class="egnitech-review-drawer__close" aria-label="<?php esc_attr_e( 'Close', 'egnitech-one' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18M6 6l12 12"/></svg>
				</button>
			</div>
			<div class="egnitech-review-drawer__body">
	<?php
}

/**
 * Close the drawer opened by egnitech_one_review_drawer_open() (comment_form_after).
 */
function egnitech_one_review_drawer_close(): void {
	?>
			</div>
		</div>
	</dialog>
	<?php
}
