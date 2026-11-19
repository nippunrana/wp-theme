<?php
declare(strict_types=1);

/**
 * Contact form: checks a message sent from a Contact Form block, saves it under Messages
 * and emails it. The block is in inc/blocks/contact-form.php and the Messages screen in
 * inc/contact-messages.php. The site's address list is set in Theme Options > Integrations.
 *
 * The form posts to admin-post.php. The block's script asks for JSON; a form sent without
 * JavaScript is redirected back to its page. There is no nonce: the page is served from the
 * page cache, so a nonce would be shared by every visitor and expire within a day.
 * The Turnstile token does that job.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The valid addresses in a comma-separated list.
 *
 * @param string $list Addresses separated by commas.
 * @return string[] Valid addresses, each listed once.
 */
function egnitech_one_contact_parse_recipients( string $list ): array {
	$valid = array();
	foreach ( explode( ',', $list ) as $address ) {
		$address = trim( $address );
		if ( '' !== $address && is_email( $address ) ) {
			$valid[ strtolower( $address ) ] ??= $address;
		}
	}
	return array_values( $valid );
}

/**
 * Sanitize the "Send messages to" setting: keeps only the valid addresses.
 *
 * @param mixed $input The submitted value.
 * @return string Valid addresses separated by commas, or an empty string.
 */
function egnitech_one_sanitize_email_list( mixed $input ): string {
	return is_string( $input ) ? implode( ', ', egnitech_one_contact_parse_recipients( $input ) ) : '';
}

/**
 * Where a form's messages go: the form's own addresses, else the Theme Options list,
 * else the site's admin email.
 *
 * @param string $sealed The form's sealed address list, or an empty string.
 * @return string[] Recipient addresses.
 */
function egnitech_one_contact_recipients( string $sealed ): array {
	$own = '' !== $sealed ? egnitech_one_contact_unseal( $sealed ) : array();
	if ( $own ) {
		return $own;
	}

	$site = egnitech_one_contact_parse_recipients( (string) egnitech_one_get_option( 'egnitech_one_contact_recipients', '' ) );
	return $site ? $site : array( (string) get_option( 'admin_email' ) );
}

/* ----- A form's own addresses ----- */

/**
 * Key for sealing a form's own addresses into its page. It is derived from the site's salts,
 * so new salts only make forms on cached pages fall back to the Theme Options list.
 *
 * @return string Binary key.
 */
function egnitech_one_contact_seal_key(): string {
	return sodium_crypto_generichash( wp_salt( 'auth' ) . 'egnitech-one-contact', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
}

/**
 * Encrypt a form's own addresses, with the page the form is on, for the form's hidden field.
 * Encrypted rather than signed so the addresses never appear in the page source.
 *
 * @param string[] $recipients The form's addresses.
 * @param int      $page_id    The page the form is printed on.
 * @return string Sealed value.
 */
function egnitech_one_contact_seal( array $recipients, int $page_id ): string {
	$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
	$plain = (string) wp_json_encode(
		array(
			'page' => $page_id,
			'to'   => $recipients,
		)
	);
	return base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, egnitech_one_contact_seal_key() ) );
}

/**
 * Open a sealed address list. It is honoured only while its page is published, so an
 * address sealed in an editor preview or on a draft cannot be used to send mail.
 *
 * @param string $sealed Sealed value from the form.
 * @return string[] The form's addresses, or none when the value is not valid.
 */
function egnitech_one_contact_unseal( string $sealed ): array {
	$raw = base64_decode( $sealed, true );
	if ( false === $raw || strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
		return array();
	}

	$plain = sodium_crypto_secretbox_open(
		substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		egnitech_one_contact_seal_key()
	);
	$data  = false === $plain ? null : json_decode( $plain, true );
	if ( ! is_array( $data ) || ! is_int( $data['page'] ?? null ) || ! is_array( $data['to'] ?? null ) || 'publish' !== get_post_status( $data['page'] ) ) {
		return array();
	}

	return egnitech_one_contact_parse_recipients( implode( ',', array_filter( $data['to'], 'is_string' ) ) );
}

/* ----- Wording shared with the block ----- */

/**
 * The message shown for each outcome.
 *
 * @param string $code sent, invalid, challenge or failed.
 * @return string Translated message.
 */
function egnitech_one_contact_status_message( string $code ): string {
	return match ( $code ) {
		'sent'      => __( 'Thank you. Your message has been sent, and we will get back to you soon.', 'egnitech-one' ),
		'invalid'   => __( 'Some details are missing or not valid. Please check the form and try again.', 'egnitech-one' ),
		'challenge' => egnitech_one_turnstile_error_message(),
		default     => __( 'Sorry, your message could not be sent. Please try again later.', 'egnitech-one' ),
	};
}

/**
 * The statement a visitor ticks before sending. Saved with each message as a record of it.
 *
 * @param bool $with_link Link the Privacy Policy page (for the form), or name it (for the record).
 * @return string The statement; with a link it is HTML.
 */
function egnitech_one_contact_consent_text( bool $with_link ): string {
	if ( '' === get_privacy_policy_url() ) {
		return __( 'I agree that my details are stored and used to reply to my message.', 'egnitech-one' );
	}

	$policy = $with_link ? get_the_privacy_policy_link() : get_the_title( (int) get_option( 'wp_page_for_privacy_policy' ) );

	/* translators: %s: the Privacy Policy page's title, linked to that page on the form. */
	return sprintf( __( 'I agree that my details are stored and used to reply to my message, as explained in the %s.', 'egnitech-one' ), $policy );
}

/* ----- Checking a message ----- */

/**
 * Check the fields of a sent form. Over-long values are refused, never cut short.
 *
 * @param array $input The posted fields, unslashed. Any of them can be missing or an array.
 * @return array{values: array<string, string>, errors: array<string, string>} Clean values, and an error per failed field.
 */
function egnitech_one_contact_check_fields( array $input ): array {
	$raw = static fn( string $key ): string => is_string( $input[ $key ] ?? null ) ? trim( $input[ $key ] ) : '';

	$values = array(
		'name'    => sanitize_text_field( $raw( 'name' ) ),
		'email'   => $raw( 'email' ),
		'phone'   => sanitize_text_field( $raw( 'phone' ) ),
		'subject' => sanitize_text_field( $raw( 'subject' ) ),
		'message' => sanitize_textarea_field( $raw( 'message' ) ),
	);
	$errors = array();

	if ( '' === $values['name'] ) {
		$errors['name'] = __( 'Enter your name.', 'egnitech-one' );
	} elseif ( mb_strlen( $values['name'] ) > 100 ) {
		$errors['name'] = __( 'Your name must be 100 characters or fewer.', 'egnitech-one' );
	}

	if ( '' === $values['email'] ) {
		$errors['email'] = __( 'Enter your email address.', 'egnitech-one' );
	} elseif ( strlen( $values['email'] ) > 254 || ! is_email( $values['email'] ) ) {
		$errors['email'] = __( 'Enter a valid email address, like name@example.com.', 'egnitech-one' );
	}

	if ( '' !== $values['phone'] && ( mb_strlen( $values['phone'] ) > 30 || ! preg_match( '/^\+?[0-9 ().-]*[0-9][0-9 ().-]*$/', $values['phone'] ) ) ) {
		$errors['phone'] = __( 'Enter a phone number using digits, spaces and + - ( ) only.', 'egnitech-one' );
	}

	if ( mb_strlen( $values['subject'] ) > 150 ) {
		$errors['subject'] = __( 'The subject must be 150 characters or fewer.', 'egnitech-one' );
	}

	if ( '' === $values['message'] ) {
		$errors['message'] = __( 'Enter your message.', 'egnitech-one' );
	} elseif ( mb_strlen( $values['message'] ) > 5000 ) {
		/* translators: %s: the maximum number of characters. */
		$errors['message'] = sprintf( __( 'Your message must be %s characters or fewer.', 'egnitech-one' ), number_format_i18n( 5000 ) );
	}

	if ( '1' !== $raw( 'consent' ) ) {
		$errors['consent'] = __( 'Tick the box to agree before sending.', 'egnitech-one' );
	}

	return array(
		'values' => $values,
		'errors' => $errors,
	);
}

/**
 * Whether a message matches the site's Disallowed Comment Keys (Settings > Discussion).
 * Such a message is saved but not emailed, as WordPress does with such comments.
 *
 * @param array<string, string> $values Checked values.
 * @return bool True when the message matches.
 */
function egnitech_one_contact_is_disallowed( array $values ): bool {
	return wp_check_comment_disallowed_list(
		$values['name'],
		$values['email'],
		'',
		$values['subject'] . "\n" . $values['message'],
		sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
		sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) )
	);
}

/**
 * Whether a message carries three or more web addresses, which most spam does. Such a
 * message is still emailed, only marked: a customer may well paste product links.
 *
 * @param array<string, string> $values Checked values.
 * @return bool True when the message has many links.
 */
function egnitech_one_contact_has_many_links( array $values ): bool {
	return (int) preg_match_all( '#https?://|www\.#i', $values['subject'] . ' ' . $values['message'] ) >= 3;
}

/* ----- Saving and sending ----- */

/**
 * Save a message under Messages. Saved as private, never published: publishing would make
 * the page-cache purger clear the home page on every message.
 *
 * @param array<string, string> $values  Checked values.
 * @param int                   $page_id The page the form was sent from.
 * @param string                $mail    Starting email status: pending or held.
 * @return int The message's post ID, or 0 when it could not be saved.
 */
function egnitech_one_contact_save( array $values, int $page_id, string $mail ): int {
	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'egnitech_message',
				'post_status'  => 'private',
				'post_title'   => $values['name'],
				'post_content' => $values['message'],
				'meta_input'   => array(
					'_egnitech_email'   => strtolower( $values['email'] ),
					'_egnitech_phone'   => $values['phone'],
					'_egnitech_subject' => $values['subject'],
					'_egnitech_page'    => $page_id,
					'_egnitech_consent' => egnitech_one_contact_consent_text( false ),
					'_egnitech_mail'    => $mail,
					'_egnitech_unread'  => '1',
				),
			)
		),
		true
	);

	return is_wp_error( $post_id ) ? 0 : $post_id;
}

/**
 * Keep the reason wp_mail() gave for a failed send. Hooked to wp_mail_failed only around
 * the contact form's own send.
 *
 * @param WP_Error|null $error The failure, or null to read the last one.
 * @return string The last failure's message.
 */
function egnitech_one_contact_mail_error( ?WP_Error $error = null ): string {
	static $message = '';
	if ( null !== $error ) {
		$message = $error->get_error_message();
	}
	return $message;
}

/**
 * Email a message. It is sent from the site's own address (WordPress's default, or the From
 * Email under SMTP Settings) with the visitor as Reply-To. Sending as the visitor would fail
 * the receiving server's checks and land in spam. Nothing is ever sent to the visitor.
 *
 * @param int                   $post_id    The saved message, or 0.
 * @param array<string, string> $values     Checked values.
 * @param int                   $page_id    The page the form was sent from.
 * @param string[]              $recipients Where to send it.
 * @return bool Whether the mail server accepted it.
 */
function egnitech_one_contact_send( int $post_id, array $values, int $page_id, array $recipients ): bool {
	$plain = static fn( string $text ): string => wp_specialchars_decode( $text, ENT_QUOTES );
	$topic = '' !== $values['subject']
		? $values['subject']
		/* translators: %s: the visitor's name. */
		: sprintf( __( 'message from %s', 'egnitech-one' ), $values['name'] );

	$subject = sprintf(
		/* translators: 1: site name, 2: "[Possible spam] " or nothing, 3: the subject or "message from {name}". */
		__( '[%1$s] %2$sContact: %3$s', 'egnitech-one' ),
		$plain( get_bloginfo( 'name' ) ),
		egnitech_one_contact_has_many_links( $values ) ? __( '[Possible spam] ', 'egnitech-one' ) : '',
		$plain( $topic )
	);

	$lines = array(
		/* translators: %s: the visitor's name. */
		sprintf( __( 'Name: %s', 'egnitech-one' ), $plain( $values['name'] ) ),
		/* translators: %s: the visitor's email address. */
		sprintf( __( 'Email: %s', 'egnitech-one' ), $values['email'] ),
	);
	if ( '' !== $values['phone'] ) {
		/* translators: %s: the visitor's phone number. */
		$lines[] = sprintf( __( 'Phone: %s', 'egnitech-one' ), $plain( $values['phone'] ) );
	}
	if ( 'publish' === get_post_status( $page_id ) ) {
		/* translators: %s: the address of the page the form is on. */
		$lines[] = sprintf( __( 'Sent from: %s', 'egnitech-one' ), get_permalink( $page_id ) );
	}
	if ( '' !== $values['subject'] ) {
		/* translators: %s: the subject the visitor typed. */
		$lines[] = sprintf( __( 'Subject: %s', 'egnitech-one' ), $plain( $values['subject'] ) );
	}
	$lines[] = '';
	$lines[] = $plain( $values['message'] );
	$lines[] = '';
	$lines[] = '--';
	/* translators: %s: the visitor's name. */
	$lines[] = sprintf( __( 'Reply to this email to answer %s.', 'egnitech-one' ), $plain( $values['name'] ) );
	if ( $post_id ) {
		/* translators: %s: link to the message in wp-admin. */
		$lines[] = sprintf( __( 'Saved under Messages: %s', 'egnitech-one' ), admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
	}

	add_action( 'wp_mail_failed', 'egnitech_one_contact_mail_error' );
	// The address alone: wp_mail() splits Reply-To on commas, which a name can contain.
	$sent = wp_mail( $recipients, $subject, implode( "\n", $lines ), array( 'Reply-To: ' . $values['email'] ) );
	remove_action( 'wp_mail_failed', 'egnitech_one_contact_mail_error' );

	if ( $post_id ) {
		update_post_meta( $post_id, '_egnitech_mail', $sent ? 'sent' : 'failed' );
		if ( ! $sent ) {
			update_post_meta( $post_id, '_egnitech_mail_error', wp_slash( egnitech_one_contact_mail_error() ) );
		}
	}
	return $sent;
}

/* ----- The request ----- */

/**
 * Answer the form: JSON for the block's script, a redirect back to the page otherwise.
 *
 * @param string                $code   sent, invalid, challenge or failed.
 * @param array<string, string> $errors An error per failed field.
 */
function egnitech_one_contact_respond( string $code, array $errors = array() ): never {
	if ( wp_is_json_request() ) {
		wp_send_json(
			array(
				'success' => 'sent' === $code,
				'code'    => $code,
				'message' => egnitech_one_contact_status_message( $code ),
				'errors'  => (object) $errors,
			),
			array(
				'sent'      => 200,
				'invalid'   => 400,
				'challenge' => 403,
			)[ $code ] ?? 500
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- read-only: where to send the visitor back to.
	$page_id = is_string( $_POST['page_id'] ?? null ) ? absint( $_POST['page_id'] ) : 0;
	$form_id = is_string( $_POST['form_id'] ?? null ) ? sanitize_html_class( wp_unslash( $_POST['form_id'] ) ) : '';
	// phpcs:enable

	$back = 'publish' === get_post_status( $page_id ) ? get_permalink( $page_id ) : wp_get_referer();
	$back = add_query_arg( 'egnitech_contact', $code, $back ? $back : home_url( '/' ) );
	wp_safe_redirect( $back . ( '' !== $form_id ? '#' . $form_id : '' ), 303 );
	exit;
}

/**
 * Handle a message sent from a Contact Form block.
 * The checks run cheapest first, and Turnstile last: checking the token uses it up, so a
 * typo in the email must be reported before that, or the visitor's corrected retry fails.
 * A filled-in trap field gets the normal thank-you, so a bot learns nothing.
 */
function egnitech_one_contact_handle(): never {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the Turnstile token is the check (see file header).
	$input = wp_unslash( $_POST );

	if ( '' !== ( $input['egnitech_hp'] ?? '' ) ) {
		egnitech_one_contact_respond( 'sent' );
	}

	$checked = egnitech_one_contact_check_fields( $input );
	if ( $checked['errors'] ) {
		egnitech_one_contact_respond( 'invalid', $checked['errors'] );
	}

	if ( ! egnitech_one_turnstile_verify( 'contact' ) ) {
		egnitech_one_contact_respond( 'challenge' );
	}

	$values     = $checked['values'];
	$page_id    = is_string( $input['page_id'] ?? null ) ? absint( $input['page_id'] ) : 0;
	$disallowed = egnitech_one_contact_is_disallowed( $values );
	$post_id    = egnitech_one_contact_save( $values, $page_id, $disallowed ? 'held' : 'pending' );

	if ( $disallowed ) {
		egnitech_one_contact_respond( $post_id ? 'sent' : 'failed' );
	}

	$sealed = is_string( $input['ref'] ?? null ) ? $input['ref'] : '';
	$sent   = egnitech_one_contact_send( $post_id, $values, $page_id, egnitech_one_contact_recipients( $sealed ) );

	// A saved message counts as received even when the email failed: it waits under Messages.
	egnitech_one_contact_respond( $post_id || $sent ? 'sent' : 'failed' );
}
add_action( 'admin_post_nopriv_egnitech_one_contact', 'egnitech_one_contact_handle' );
add_action( 'admin_post_egnitech_one_contact', 'egnitech_one_contact_handle' );
