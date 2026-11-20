<?php
declare(strict_types=1);

/**
 * Cloudflare Turnstile: shows the challenge on the login, registration, lost-password and
 * comment forms (and on WooCommerce's My Account forms), and checks the answer with Cloudflare
 * when the form is sent. Keys are set in Theme Options > Integrations.
 *
 * Each check runs only for a request from its own form. The same hooks also run for XML-RPC
 * logins and for the "Send password reset" action in wp-admin, which carry no token.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Markup for one Turnstile widget. Also queues Cloudflare's script for the current page.
 * Print it inside a <form>: the widget adds the hidden cf-turnstile-response field to it.
 *
 * @param string $action Names the form: letters, digits, "_" and "-", 32 at most.
 *                       Pass the same value to egnitech_one_turnstile_verify().
 * @return string Widget markup, or an empty string while Turnstile is off.
 */
function egnitech_one_turnstile_widget( string $action ): string {
	$settings = egnitech_one_get_turnstile_settings();
	if ( ! $settings['enabled'] ) {
		return '';
	}

	// Cloudflare's own address with no version: Cloudflare forbids caching or proxying this file.
	wp_enqueue_script(
		'egnitech-one-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	return sprintf(
		'<div class="cf-turnstile" data-sitekey="%1$s" data-action="%2$s" data-theme="%3$s"></div>',
		esc_attr( $settings['site_key'] ),
		esc_attr( $action ),
		egnitech_one_is_dark_mode_enabled() ? 'auto' : 'light'
	);
}

/**
 * Check the Turnstile answer sent with the current form.
 * Fails closed: a missing, expired, reused or other-form token fails, and so does any
 * request to Cloudflare that does not come back with a clear yes.
 *
 * @param string $action The action the form's widget was printed with.
 * @return bool True when Cloudflare accepts the token, or while Turnstile is off.
 */
function egnitech_one_turnstile_verify( string $action ): bool {
	// Cloudflare's answer per token. Cloudflare accepts a token only once, and WordPress runs
	// some checks twice in one request (every comment is validated twice).
	static $results = array();

	if ( ! egnitech_one_get_turnstile_settings()['enabled'] ) {
		return true;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the token is the check.
	$token = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );
	if ( '' === $token ) {
		return false;
	}

	if ( ! isset( $results[ $token ] ) ) {
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => (string) egnitech_one_read_secret( 'egnitech_one_turnstile_secret_key' ),
					'response' => $token,
				),
			)
		);
		$body = 200 === wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;

		$results[ $token ] = is_array( $body ) ? $body : array();
	}
	$result = $results[ $token ];

	// Cloudflare's test keys answer without an action; real widgets always send one.
	return true === ( $result['success'] ?? false ) && $action === ( $result['action'] ?? $action );
}

/**
 * The message shown when the check fails.
 *
 * @return string Translated message.
 */
function egnitech_one_turnstile_error_message(): string {
	return __( 'Please complete the security check and try again.', 'egnitech-one' );
}

/* ----- Widgets ----- */

/**
 * Print the widget on the wp-login.php and WooCommerce login forms.
 */
function egnitech_one_turnstile_print_login_widget(): void {
	echo egnitech_one_turnstile_widget( 'login' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
}
add_action( 'login_form', 'egnitech_one_turnstile_print_login_widget' );
add_action( 'woocommerce_login_form', 'egnitech_one_turnstile_print_login_widget' );

/**
 * Add the widget to wp_login_form() (the Login/out block's form), which also posts to
 * wp-login.php: without it, every login from that form would fail.
 *
 * @param string $content Markup printed after the password field.
 * @return string The markup with the widget added.
 */
function egnitech_one_turnstile_login_form_middle( string $content ): string {
	return $content . egnitech_one_turnstile_widget( 'login' );
}
add_filter( 'login_form_middle', 'egnitech_one_turnstile_login_form_middle' );

/**
 * Print the widget on the wp-login.php and WooCommerce registration forms.
 */
function egnitech_one_turnstile_print_register_widget(): void {
	echo egnitech_one_turnstile_widget( 'register' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
}
add_action( 'register_form', 'egnitech_one_turnstile_print_register_widget' );
add_action( 'woocommerce_register_form', 'egnitech_one_turnstile_print_register_widget' );

/**
 * Print the widget on the wp-login.php and WooCommerce lost-password forms.
 */
function egnitech_one_turnstile_print_lost_password_widget(): void {
	echo egnitech_one_turnstile_widget( 'lostpassword' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
}
add_action( 'lostpassword_form', 'egnitech_one_turnstile_print_lost_password_widget' );
add_action( 'woocommerce_lostpassword_form', 'egnitech_one_turnstile_print_lost_password_widget' );

/**
 * Print the widget on the comment form, after the name and email fields.
 * That hook runs for logged-out visitors only, who are the only ones checked.
 */
function egnitech_one_turnstile_print_comment_widget(): void {
	echo egnitech_one_turnstile_widget( 'comment' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
}
add_action( 'comment_form_after_fields', 'egnitech_one_turnstile_print_comment_widget' );

/**
 * Widen the widget to fit the wp-login.php form, which is narrower than its 300px and hides overflow.
 */
function egnitech_one_turnstile_login_styles(): void {
	if ( egnitech_one_get_turnstile_settings()['enabled'] ) {
		wp_add_inline_style( 'login', '.login form .cf-turnstile { margin: 0 -14px 16px; }' );
	}
}
add_action( 'login_enqueue_scripts', 'egnitech_one_turnstile_login_styles' );

/* ----- Checks ----- */

/**
 * Check a login sent from wp-login.php (or from any form posting to it).
 * Runs after the password check, so a wrong password and a failed challenge look the same.
 *
 * @param mixed $user WP_User, WP_Error or null from earlier authentication.
 * @return mixed The same value, or a WP_Error when the check fails.
 */
function egnitech_one_turnstile_check_login( mixed $user ): mixed {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only test of which form was sent.
	if ( ! did_action( 'login_init' ) || empty( $_POST['log'] ) ) {
		return $user;
	}

	return egnitech_one_turnstile_verify( 'login' ) ? $user : new WP_Error( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message() );
}
add_filter( 'authenticate', 'egnitech_one_turnstile_check_login', 21 );

/**
 * Check a login sent from WooCommerce's login form.
 *
 * @param WP_Error $errors Validation errors so far.
 * @return WP_Error The errors, with one added when the check fails.
 */
function egnitech_one_turnstile_check_wc_login( WP_Error $errors ): WP_Error {
	if ( ! egnitech_one_turnstile_verify( 'login' ) ) {
		$errors->add( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message() );
	}
	return $errors;
}
add_filter( 'woocommerce_process_login_errors', 'egnitech_one_turnstile_check_wc_login' );

/**
 * Check a registration sent from wp-login.php.
 *
 * @param WP_Error $errors Registration errors so far.
 * @return WP_Error The errors, with one added when the check fails.
 */
function egnitech_one_turnstile_check_registration( WP_Error $errors ): WP_Error {
	if ( did_action( 'login_init' ) && ! egnitech_one_turnstile_verify( 'register' ) ) {
		$errors->add( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message() );
	}
	return $errors;
}
add_filter( 'registration_errors', 'egnitech_one_turnstile_check_registration' );

/**
 * Check a registration sent from WooCommerce's registration form.
 * Account creation at checkout uses a different path and is not checked.
 *
 * @param WP_Error $errors Validation errors so far.
 * @return WP_Error The errors, with one added when the check fails.
 */
function egnitech_one_turnstile_check_wc_registration( WP_Error $errors ): WP_Error {
	if ( ! egnitech_one_turnstile_verify( 'register' ) ) {
		$errors->add( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message() );
	}
	return $errors;
}
add_filter( 'woocommerce_process_registration_errors', 'egnitech_one_turnstile_check_wc_registration' );

/**
 * Check a lost-password request sent from wp-login.php or from WooCommerce's form.
 * The same hook runs when an administrator sends a reset link from wp-admin; that is not checked.
 *
 * @param WP_Error $errors Errors so far.
 */
function egnitech_one_turnstile_check_lost_password( WP_Error $errors ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only test of which form was sent.
	$from_form = did_action( 'login_init' ) || isset( $_POST['wc_reset_password'] );

	if ( $from_form && ! egnitech_one_turnstile_verify( 'lostpassword' ) ) {
		$errors->add( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message() );
	}
}
add_action( 'lostpassword_post', 'egnitech_one_turnstile_check_lost_password' );

/**
 * Check a comment sent from the comment form by a logged-out visitor.
 * Runs after WordPress's own checks (required fields, duplicates, flooding), so a comment
 * refused for another reason does not use up the visitor's single-use token. Only
 * wp-comments-post.php fires pre_comment_on_post: replies from wp-admin and the REST API are not checked.
 *
 * @param mixed $approved Approval status, or a WP_Error from an earlier check.
 * @return mixed The same value, or a WP_Error when the check fails.
 */
function egnitech_one_turnstile_check_comment( mixed $approved ): mixed {
	if ( is_wp_error( $approved ) || ! did_action( 'pre_comment_on_post' ) || is_user_logged_in() ) {
		return $approved;
	}

	// The 403 is what makes wp-comments-post.php show the message rather than a blank page.
	return egnitech_one_turnstile_verify( 'comment' ) ? $approved : new WP_Error( 'egnitech_one_turnstile', egnitech_one_turnstile_error_message(), 403 );
}
add_filter( 'pre_comment_approved', 'egnitech_one_turnstile_check_comment', 99 );

/* ----- Page cache ----- */

/**
 * Clear the whole page cache when the switch or a key changes, since that adds or removes the
 * widget. Cached posts would otherwise keep a comment form without it, and their comments would
 * be refused. A key saved alone counts too: Turnstile starts once both keys are saved.
 * One save can change all three settings, so the cache is cleared once per request.
 */
function egnitech_one_turnstile_purge_cache(): void {
	static $purged = false;
	if ( $purged ) {
		return;
	}
	$purged = true;
	egnitech_one_purge_all_site_cache();
}
add_action( 'update_option_egnitech_one_turnstile_enabled', 'egnitech_one_turnstile_purge_cache' );
add_action( 'add_option_egnitech_one_turnstile_enabled', 'egnitech_one_turnstile_purge_cache' );
add_action( 'update_option_egnitech_one_turnstile_site_key', 'egnitech_one_turnstile_purge_cache' );
add_action( 'add_option_egnitech_one_turnstile_site_key', 'egnitech_one_turnstile_purge_cache' );
add_action( 'update_option_egnitech_one_turnstile_secret_key', 'egnitech_one_turnstile_purge_cache' );
add_action( 'add_option_egnitech_one_turnstile_secret_key', 'egnitech_one_turnstile_purge_cache' );
