<?php
declare(strict_types=1);

/**
 * Contact Form block. Registered from PHP alone: WordPress builds its settings panel from
 * the attributes below. Its stylesheet, its script and Cloudflare's script load only on
 * pages that show the form. Sending is handled in inc/contact-form-submit.php.
 *
 * Everything printed here is the same for every visitor, because the page is cached:
 * never print a nonce, a timestamp or anything about the visitor.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the block, its stylesheet and its script module.
 */
function egnitech_one_register_contact_form_block(): void {
	wp_register_style(
		'egnitech-one-contact-form',
		get_theme_file_uri( 'inc/blocks/contact-form/style.css' ),
		array(),
		egnitech_one_asset_version( get_theme_file_path( 'inc/blocks/contact-form/style.css' ) )
	);

	wp_register_script_module(
		'egnitech-one/contact-form',
		get_theme_file_uri( 'inc/blocks/contact-form/view.js' ),
		array( '@wordpress/interactivity' ),
		egnitech_one_asset_version( get_theme_file_path( 'inc/blocks/contact-form/view.js' ) )
	);

	register_block_type(
		'egnitech-one/contact-form',
		array(
			'title'                  => __( 'Contact Form', 'egnitech-one' ),
			'description'            => __( 'A contact form. Messages are emailed to you and saved under Messages in the admin menu.', 'egnitech-one' ),
			'category'               => 'widgets',
			'icon'                   => 'email',
			'keywords'               => array( __( 'contact', 'egnitech-one' ), __( 'form', 'egnitech-one' ), __( 'enquiry', 'egnitech-one' ) ),
			'supports'               => array(
				'autoRegister'  => true,
				'interactivity' => true,
				'html'          => false,
			),
			'attributes'             => array(
				'showPhone'      => array(
					'type'    => 'boolean',
					'default' => true,
					'label'   => __( 'Show the phone field', 'egnitech-one' ),
				),
				'showSubject'    => array(
					'type'    => 'boolean',
					'default' => false,
					'label'   => __( 'Show the subject field', 'egnitech-one' ),
				),
				'buttonText'     => array(
					'type'    => 'string',
					'default' => '',
					'role'    => 'content',
					'label'   => __( 'Button text (empty: "Send message")', 'egnitech-one' ),
				),
				'successMessage' => array(
					'type'    => 'string',
					'default' => '',
					'role'    => 'content',
					'label'   => __( 'Thank-you message (empty: the standard one)', 'egnitech-one' ),
				),
				'recipient'      => array(
					'type'    => 'string',
					'default' => '',
					'role'    => 'content',
					'label'   => __( 'Send messages to (empty: the addresses in Theme Options)', 'egnitech-one' ),
				),
			),
			'style_handles'          => array( 'egnitech-one-contact-form' ),
			'view_script_module_ids' => array( 'egnitech-one/contact-form' ),
			'render_callback'        => 'egnitech_one_render_contact_form',
		)
	);
}
add_action( 'init', 'egnitech_one_register_contact_form_block' );

/**
 * Markup for one field: label, control and its error line.
 *
 * @param string $form_id  The form's element ID.
 * @param string $name     Field name, also the key of its error.
 * @param string $label    Visible label.
 * @param string $control  The control's tag and attributes, without id, name or ARIA.
 * @param bool   $required Whether the field must be filled in.
 * @return string Field markup.
 */
function egnitech_one_contact_field( string $form_id, string $name, string $label, string $control, bool $required ): string {
	$id = $form_id . '-' . $name;

	$control = sprintf(
		'<%1$s id="%2$s" name="%3$s" class="egnitech-contact-form__input"%4$s data-wp-bind--aria-invalid="state.hasError" data-wp-bind--aria-describedby="state.describedBy">',
		$control,
		esc_attr( $id ),
		esc_attr( $name ),
		$required ? ' required' : ''
	);
	if ( str_starts_with( $control, '<textarea' ) ) {
		$control .= '</textarea>';
	}

	$optional = $required ? '' : ' <span class="egnitech-contact-form__optional">' . esc_html__( '(optional)', 'egnitech-one' ) . '</span>';

	return sprintf(
		'<div class="egnitech-contact-form__field egnitech-contact-form__field--%1$s" %2$s data-wp-class--is-invalid="state.hasError"><label class="egnitech-contact-form__label" for="%3$s">%4$s%5$s</label>%6$s<p class="egnitech-contact-form__error" id="%3$s-error" hidden data-wp-bind--hidden="!state.hasError"><span class="egnitech-contact-form__sr">%7$s</span><span data-wp-text="state.error"></span></p></div>',
		esc_attr( $name ),
		wp_interactivity_data_wp_context(
			array(
				'field'   => $name,
				'errorId' => $id . '-error',
			)
		),
		esc_attr( $id ),
		esc_html( $label ),
		$optional,
		$control,
		esc_html__( 'Error:', 'egnitech-one' ) . ' '
	);
}

/**
 * Render the form.
 *
 * @param array $attributes Block attributes.
 * @return string Block markup.
 */
function egnitech_one_render_contact_form( array $attributes ): string {
	$form_id = wp_unique_id( 'egnitech-contact-' );
	$page_id = get_queried_object_id();

	// The outcome of a form sent without JavaScript, which comes back with ?egnitech_contact=.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the message to show.
	$status   = sanitize_key( wp_unslash( $_GET['egnitech_contact'] ?? '' ) );
	$is_error = in_array( $status, array( 'invalid', 'challenge', 'failed' ), true );

	$button = trim( (string) ( $attributes['buttonText'] ?? '' ) );
	$thanks = trim( (string) ( $attributes['successMessage'] ?? '' ) );
	$thanks = '' !== $thanks ? $thanks : egnitech_one_contact_status_message( 'sent' );
	$own    = egnitech_one_contact_parse_recipients( (string) ( $attributes['recipient'] ?? '' ) );

	wp_interactivity_state( 'egnitech-one/contact-form', array( 'networkError' => egnitech_one_contact_status_message( 'failed' ) ) );

	$fields = egnitech_one_contact_field( $form_id, 'name', __( 'Name', 'egnitech-one' ), 'input type="text" autocomplete="name" maxlength="100"', true )
		. egnitech_one_contact_field( $form_id, 'email', __( 'Email', 'egnitech-one' ), 'input type="email" autocomplete="email" maxlength="254" spellcheck="false"', true );
	if ( ! empty( $attributes['showPhone'] ) ) {
		$fields .= egnitech_one_contact_field( $form_id, 'phone', __( 'Phone', 'egnitech-one' ), 'input type="tel" autocomplete="tel" maxlength="30"', false );
	}
	if ( ! empty( $attributes['showSubject'] ) ) {
		$fields .= egnitech_one_contact_field( $form_id, 'subject', __( 'Subject', 'egnitech-one' ), 'input type="text" maxlength="150"', false );
	}

	$challenge = egnitech_one_turnstile_widget( 'contact' );

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( array( 'id' => $form_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?> data-wp-interactive="egnitech-one/contact-form" <?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core.
		array(
			'sending' => false,
			'sent'    => 'sent' === $status,
			'isError' => $is_error,
			'notice'  => 'sent' === $status ? $thanks : ( $is_error ? egnitech_one_contact_status_message( $status ) : '' ),
			'thanks'  => $thanks,
			'errors'  => new stdClass(),
		)
	);
	?>>
		<form class="egnitech-contact-form__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-wp-init="callbacks.init" data-wp-on--submit="actions.submit" data-wp-watch="callbacks.focusFirstError" data-wp-bind--hidden="context.sent">
			<input type="hidden" name="action" value="egnitech_one_contact">
			<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $page_id ); ?>">
			<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
			<?php if ( $own ) : ?>
				<input type="hidden" name="ref" value="<?php echo esc_attr( egnitech_one_contact_seal( $own, $page_id ) ); ?>">
			<?php endif; ?>

			<div class="egnitech-contact-form__grid">
				<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built. ?>
			</div>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
			echo egnitech_one_contact_field( $form_id, 'message', __( 'Message', 'egnitech-one' ), 'textarea rows="6" maxlength="5000"', true );
			?>

			<div class="egnitech-contact-form__field egnitech-contact-form__field--consent" <?php echo wp_interactivity_data_wp_context( array( 'field' => 'consent', 'errorId' => $form_id . '-consent-error' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?> data-wp-class--is-invalid="state.hasError">
				<div class="egnitech-contact-form__check">
					<input type="checkbox" id="<?php echo esc_attr( $form_id ); ?>-consent" name="consent" value="1" required data-wp-bind--aria-invalid="state.hasError" data-wp-bind--aria-describedby="state.describedBy">
					<label for="<?php echo esc_attr( $form_id ); ?>-consent"><?php echo wp_kses_post( egnitech_one_contact_consent_text( true ) ); ?></label>
				</div>
				<p class="egnitech-contact-form__error" id="<?php echo esc_attr( $form_id ); ?>-consent-error" hidden data-wp-bind--hidden="!state.hasError"><span class="egnitech-contact-form__sr"><?php esc_html_e( 'Error:', 'egnitech-one' ); ?> </span><span data-wp-text="state.error"></span></p>
			</div>

			<?php // A trap for bots: hidden from people and screen readers, so only a bot fills it in. ?>
			<div class="egnitech-contact-form__trap" aria-hidden="true">
				<label for="<?php echo esc_attr( $form_id ); ?>-hp"><?php esc_html_e( 'Leave this field empty', 'egnitech-one' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $form_id ); ?>-hp" name="egnitech_hp" value="" tabindex="-1" autocomplete="off" data-1p-ignore data-lpignore="true">
			</div>

			<?php if ( '' !== $challenge ) : ?>
				<div class="egnitech-contact-form__challenge">
					<?php echo $challenge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built. ?>
					<noscript><p class="egnitech-contact-form__error"><?php esc_html_e( 'Turn on JavaScript to complete the security check, then send your message.', 'egnitech-one' ); ?></p></noscript>
				</div>
			<?php endif; ?>

			<div class="egnitech-contact-form__actions">
				<button type="submit" class="wp-element-button egnitech-contact-form__submit" data-wp-bind--aria-disabled="context.sending">
					<span data-wp-bind--hidden="context.sending"><?php echo esc_html( '' !== $button ? $button : __( 'Send message', 'egnitech-one' ) ); ?></span>
					<span hidden data-wp-bind--hidden="!context.sending"><?php esc_html_e( 'Sending…', 'egnitech-one' ); ?></span>
				</button>
			</div>
		</form>

		<?php // Always in the page, so screen readers announce what is written into it. ?>
		<div class="egnitech-contact-form__notice" role="status" aria-atomic="true" tabindex="-1" data-wp-class--is-success="context.sent" data-wp-class--is-error="context.isError" data-wp-watch="callbacks.focusNotice" data-wp-text="context.notice"></div>
	</div>
	<?php
	return (string) ob_get_clean();
}
