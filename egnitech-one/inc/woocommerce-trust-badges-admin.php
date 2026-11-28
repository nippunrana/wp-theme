<?php
declare(strict_types=1);

/**
 * Product Trust Badges meta box markup: the colour controls, the badge rows, the row template
 * the script clones for "Add badge", and the icon and colour dialogs every row shares. The meta
 * box is registered and saved in inc/theme-setup.php; the data lives in
 * inc/woocommerce-trust-badges.php.
 * Styles and script: assets/{css,js}/admin-trust-badges.*.
 *
 * Everything sits inside the product edit form: every button is type="button", and nothing
 * here may contain a <form>.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The human name of a badge icon, for labels.
 *
 * @param string $name A theme icon ("lock") or a WordPress core icon ("core/shield").
 */
function egnitech_one_trust_badge_icon_label( string $name ): string {
	if ( str_starts_with( $name, 'core/' ) ) {
		return (string) ( WP_Icons_Registry::get_instance()->get_registered_icon( $name )['label'] ?? '' );
	}
	return egnitech_one_trust_badge_theme_icons()[ $name ]['label'] ?? '';
}

/**
 * Prints the whole meta box body.
 */
function egnitech_one_render_trust_badges_box( \WP_Post $post ): void {
	$badges = egnitech_one_get_trust_badges( $post->ID );
	$count  = count( $badges );

	/* translators: 1: number of badges, 2: most badges allowed. */
	$count_format = __( '%1$d of %2$d badges', 'egnitech-one' );

	// Strings the script needs, kept here so each is written once.
	$i18n = array(
		'count'      => $count_format,
		/* translators: 1: badge position, 2: number of badges. */
		'added'      => __( 'Badge added at position %1$d of %2$d.', 'egnitech-one' ),
		'removed'    => __( 'Badge removed.', 'egnitech-one' ),
		/* translators: 1: badge position, 2: number of badges. */
		'moved'      => __( 'Badge moved to position %1$d of %2$d.', 'egnitech-one' ),
		/* translators: %s: icon name. */
		'changeIcon' => __( 'Change icon (now: %s)', 'egnitech-one' ),
		/* translators: %s: colour name. */
		'changeColor' => __( 'Change colour (now: %s)', 'egnitech-one' ),
		'randomised' => __( 'Each badge has a new colour.', 'egnitech-one' ),
		/* translators: %s: colour name. */
		'allColor'   => __( 'Every badge is now %s.', 'egnitech-one' ),
	);
	?>
	<div class="egnitech-trust-badges<?php echo 0 === $count ? ' is-empty' : ''; ?>" data-limit="<?php echo (int) EGNITECH_ONE_TRUST_BADGE_LIMIT; ?>" data-next-index="<?php echo (int) $count; ?>" data-i18n="<?php echo esc_attr( (string) wp_json_encode( $i18n ) ); ?>">
		<p class="description">
			<?php esc_html_e( 'Shown under the Add to Cart button, in this order. Switch a badge off to hide it without deleting it. A badge left without text is removed when the product is saved.', 'egnitech-one' ); ?>
		</p>

		<div class="egnitech-trust-badges__colors">
			<button type="button" class="button egnitech-trust-badges__random">
				<span class="dashicons dashicons-randomize" aria-hidden="true"></span>
				<?php esc_html_e( 'Randomise colours', 'egnitech-one' ); ?>
			</button>
			<?php /* No name: the choice is applied to the rows' own fields and never posted. */ ?>
			<select class="egnitech-trust-badges__all-color" aria-label="<?php esc_attr_e( 'One colour for every badge', 'egnitech-one' ); ?>">
				<option value=""><?php esc_html_e( 'One colour for all…', 'egnitech-one' ); ?></option>
				<?php foreach ( egnitech_one_trust_badge_colors() as $color => $details ) : ?>
					<option value="<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $details['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<ul class="egnitech-trust-badges__list">
			<?php
			foreach ( $badges as $index => $badge ) {
				// A badge without a colour shows, and on the next save stores, its position's colour.
				$badge['color'] = egnitech_one_trust_badge_color( $badge['color'], $index );
				egnitech_one_render_trust_badge_item( (string) $index, $badge );
			}
			?>
		</ul>

		<p class="egnitech-trust-badges__empty">
			<?php esc_html_e( 'No badges on this product. Add one to show it under the Add to Cart button.', 'egnitech-one' ); ?>
		</p>

		<div class="egnitech-trust-badges__footer">
			<button type="button" class="button egnitech-trust-badges__add" <?php disabled( $count >= EGNITECH_ONE_TRUST_BADGE_LIMIT ); ?>>
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php esc_html_e( 'Add badge', 'egnitech-one' ); ?>
			</button>
			<span class="egnitech-trust-badges__count"><?php echo esc_html( sprintf( $count_format, $count, EGNITECH_ONE_TRUST_BADGE_LIMIT ) ); ?></span>
		</div>

		<template class="egnitech-trust-badges__template">
			<?php
			egnitech_one_render_trust_badge_item(
				'__INDEX__',
				array(
					'text'   => '',
					'icon'   => EGNITECH_ONE_TRUST_BADGE_ICON,
					'color'  => '', // The script picks one when the row is added.
					'active' => true,
				)
			);
			?>
		</template>

		<?php egnitech_one_render_trust_badge_icon_dialog(); ?>
		<?php egnitech_one_render_trust_badge_color_dialog(); ?>
	</div>
	<?php
}

/**
 * Prints one badge row. Also printed once inside the <template>, with index "__INDEX__",
 * which the script replaces with a fresh number. No ids in here: clones would repeat them.
 *
 * @param string                                                         $index Position in the posted list.
 * @param array{text: string, icon: string, color: string, active: bool} $badge The badge.
 */
function egnitech_one_render_trust_badge_item( string $index, array $badge ): void {
	$name  = 'egnitech_trust_badges[' . $index . ']';
	$color = egnitech_one_trust_badge_colors()[ $badge['color'] ] ?? null;
	?>
	<li class="egnitech-trust-badge-item">
		<span class="egnitech-trust-badge-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'egnitech-one' ); ?>">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.75"></circle><circle cx="15" cy="6" r="1.75"></circle><circle cx="9" cy="12" r="1.75"></circle><circle cx="15" cy="12" r="1.75"></circle><circle cx="9" cy="18" r="1.75"></circle><circle cx="15" cy="18" r="1.75"></circle></svg>
		</span>

		<label class="egnitech-trust-badge-switch" title="<?php esc_attr_e( 'Show on the product page', 'egnitech-one' ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[active]" value="1" <?php checked( $badge['active'] ); ?> />
			<span class="screen-reader-text"><?php esc_html_e( 'Show this badge on the product page', 'egnitech-one' ); ?></span>
		</label>

		<?php /* translators: %s: icon name. */ ?>
		<button type="button" class="egnitech-trust-badge-item__icon" data-action="icon" aria-haspopup="dialog" aria-label="<?php echo esc_attr( sprintf( __( 'Change icon (now: %s)', 'egnitech-one' ), egnitech_one_trust_badge_icon_label( $badge['icon'] ) ) ); ?>">
			<span class="egnitech-trust-badge-item__svg"><?php echo egnitech_one_trust_badge_icon_svg( $badge['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme markup or core's sanitised SVG. ?></span>
			<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
		</button>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[icon]" value="<?php echo esc_attr( $badge['icon'] ); ?>" />

		<?php /* translators: %s: colour name. */ ?>
		<button type="button" class="egnitech-trust-badge-item__color" data-action="color" aria-haspopup="dialog" aria-label="<?php echo esc_attr( sprintf( __( 'Change colour (now: %s)', 'egnitech-one' ), $color['label'] ?? '' ) ); ?>" title="<?php esc_attr_e( 'Colour', 'egnitech-one' ); ?>"<?php echo $color ? ' style="--egnitech-badge-color: ' . esc_attr( $color['hex'] ) . '"' : ''; ?>></button>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[color]" value="<?php echo esc_attr( $badge['color'] ); ?>" />

		<input type="text" name="<?php echo esc_attr( $name ); ?>[text]" value="<?php echo esc_attr( $badge['text'] ); ?>" class="egnitech-trust-badge-item__text" aria-label="<?php esc_attr_e( 'Badge text', 'egnitech-one' ); ?>" placeholder="<?php esc_attr_e( 'Badge text, e.g. Free delivery', 'egnitech-one' ); ?>" />

		<button type="button" class="egnitech-trust-badge-item__tool" data-action="up" aria-label="<?php esc_attr_e( 'Move up', 'egnitech-one' ); ?>" title="<?php esc_attr_e( 'Move up', 'egnitech-one' ); ?>">
			<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
		</button>
		<button type="button" class="egnitech-trust-badge-item__tool" data-action="down" aria-label="<?php esc_attr_e( 'Move down', 'egnitech-one' ); ?>" title="<?php esc_attr_e( 'Move down', 'egnitech-one' ); ?>">
			<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
		</button>
		<button type="button" class="egnitech-trust-badge-item__tool is-remove" data-action="remove" aria-label="<?php esc_attr_e( 'Remove badge', 'egnitech-one' ); ?>" title="<?php esc_attr_e( 'Remove badge', 'egnitech-one' ); ?>">
			<span class="dashicons dashicons-trash" aria-hidden="true"></span>
		</button>
	</li>
	<?php
}

/**
 * Prints the icon dialog all rows share: a search box and the icons in two groups.
 * The search box has no name, so it is never posted with the product.
 */
function egnitech_one_render_trust_badge_icon_dialog(): void {
	$groups = array(
		__( 'Theme icons', 'egnitech-one' )     => wp_list_pluck( egnitech_one_trust_badge_theme_icons(), 'label' ),
		__( 'WordPress icons', 'egnitech-one' ) => egnitech_one_trust_badge_core_icons(),
	);
	?>
	<dialog class="egnitech-icon-dialog" aria-labelledby="egnitech-icon-dialog-title">
		<div class="egnitech-icon-dialog__inner">
			<div class="egnitech-icon-dialog__header">
				<h2 id="egnitech-icon-dialog-title" class="egnitech-icon-dialog__title"><?php esc_html_e( 'Choose an icon', 'egnitech-one' ); ?></h2>
				<button type="button" class="egnitech-icon-dialog__close" data-action="close" aria-label="<?php esc_attr_e( 'Close', 'egnitech-one' ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
			</div>

			<label class="screen-reader-text" for="egnitech-icon-dialog-search"><?php esc_html_e( 'Search icons', 'egnitech-one' ); ?></label>
			<input type="search" id="egnitech-icon-dialog-search" class="egnitech-icon-dialog__search" placeholder="<?php esc_attr_e( 'Search icons', 'egnitech-one' ); ?>" autocomplete="off" />

			<div class="egnitech-icon-dialog__body">
				<?php foreach ( $groups as $heading => $icons ) : ?>
					<section class="egnitech-icon-dialog__group">
						<h3 class="egnitech-icon-dialog__heading"><?php echo esc_html( $heading ); ?></h3>
						<div class="egnitech-icon-dialog__grid">
							<?php foreach ( $icons as $icon => $label ) : ?>
								<button type="button" class="egnitech-icon-dialog__icon" data-icon="<?php echo esc_attr( (string) $icon ); ?>" data-search="<?php echo esc_attr( strtolower( $label . ' ' . $icon ) ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $label ); ?>">
									<?php echo egnitech_one_trust_badge_icon_svg( (string) $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme markup or core's sanitised SVG. ?>
								</button>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
				<p class="egnitech-icon-dialog__none" hidden><?php esc_html_e( 'No icons match your search.', 'egnitech-one' ); ?></p>
			</div>
		</div>
	</dialog>
	<?php
}

/**
 * Prints the colour dialog all rows share. Each choice is drawn as the pill it makes:
 * the colour with the page's fixed near-black text.
 */
function egnitech_one_render_trust_badge_color_dialog(): void {
	?>
	<dialog class="egnitech-color-dialog" aria-labelledby="egnitech-color-dialog-title">
		<div class="egnitech-icon-dialog__header">
			<h2 id="egnitech-color-dialog-title" class="egnitech-icon-dialog__title"><?php esc_html_e( 'Choose a colour', 'egnitech-one' ); ?></h2>
			<button type="button" class="egnitech-icon-dialog__close" data-action="close" aria-label="<?php esc_attr_e( 'Close', 'egnitech-one' ); ?>">
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</button>
		</div>

		<div class="egnitech-color-dialog__grid">
			<?php foreach ( egnitech_one_trust_badge_colors() as $color => $details ) : ?>
				<button type="button" class="egnitech-color-dialog__swatch" data-color="<?php echo esc_attr( $color ); ?>" style="--egnitech-badge-color: <?php echo esc_attr( $details['hex'] ); ?>"><?php echo esc_html( $details['label'] ); ?></button>
			<?php endforeach; ?>
		</div>
	</dialog>
	<?php
}
