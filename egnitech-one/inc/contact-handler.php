<?php
/**
 * AJAX handler for the Contact Us form.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle contact form submission via AJAX.
 */
function egnitech_one_handle_contact_form() {
	// 1. Verify Nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'egnitech_contact_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'egnitech-one' ) ) );
	}

	// 2. Check reCAPTCHA if enabled
	$recaptcha_enabled = egnitech_one_get_option( 'egnitech_one_recaptcha_enabled', '' );
	if ( 'yes' === $recaptcha_enabled ) {
		$site_secret = egnitech_one_get_option( 'egnitech_one_recaptcha_secret_key', '' );
		$response_token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';

		if ( empty( $response_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Please complete the reCAPTCHA verification.', 'egnitech-one' ) ) );
		}

		$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
		$response = wp_remote_post( $verify_url, array(
			'body' => array(
				'secret'   => $site_secret,
				'response' => $response_token,
				'remoteip' => $_SERVER['REMOTE_ADDR'],
			),
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to connect to reCAPTCHA service. Please try again later.', 'egnitech-one' ) ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['success'] ) || ! $body['success'] ) {
			wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed. Please try again.', 'egnitech-one' ) ) );
		}
	}

	// 3. Process form fields (Sanitize)
	$fields_raw = egnitech_one_get_option( 'egnitech_one_contact_form_fields', '[]' );
	$fields_config = json_decode( $fields_raw, true );
	if ( empty( $fields_config ) ) {
		$fields_config = array(
			array( 'label' => 'Name', 'type' => 'text' ),
			array( 'label' => 'Email', 'type' => 'email' ),
			array( 'label' => 'Subject', 'type' => 'text' ),
			array( 'label' => 'Message', 'type' => 'textarea' ),
		);
	}

	$message_body = "";
	$user_email = "";
	$subject = "New Contact Form Submission";

	foreach ( $fields_config as $field ) {
		$id = sanitize_title( $field['label'] );
		if ( isset( $_POST[ $id ] ) ) {
			$val = sanitize_textarea_field( $_POST[ $id ] );
			$message_body .= "<strong>" . esc_html( $field['label'] ) . ":</strong> " . nl2br( esc_html( $val ) ) . "<br><br>";
			
			if ( 'email' === $field['type'] ) {
				$user_email = sanitize_email( $val );
			}
			if ( 'subject' === $id ) {
				$subject = sanitize_text_field( $val );
			}
		}
	}

	// 4. Send Email
	$recipient_email = egnitech_one_get_option( 'egnitech_one_contact_recipient_email', '' );
	if ( empty( $recipient_email ) ) {
		$recipient_email = egnitech_one_get_option( 'egnitech_one_contact_email', get_option( 'admin_email' ) );
	}

	$to      = $recipient_email;
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! empty( $user_email ) ) {
		$headers[] = 'Reply-To: ' . $user_email;
	}

	$mail_sent = wp_mail( $to, $subject, $message_body, $headers );

	if ( $mail_sent ) {
		wp_send_json_success( array( 'message' => __( 'Thanks for reaching out! We will get back to you soon.', 'egnitech-one' ) ) );
	} else {
		wp_send_json_error( array( 'message' => __( 'There was an error sending your message. Please try again later or contact us directly.', 'egnitech-one' ) ) );
	}
}
add_action( 'wp_ajax_egnitech_contact_submit', 'egnitech_one_handle_contact_form' );
add_action( 'wp_ajax_nopriv_egnitech_contact_submit', 'egnitech_one_handle_contact_form' );
