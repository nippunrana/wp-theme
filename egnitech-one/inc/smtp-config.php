<?php
declare(strict_types=1);

/**
 * SMTP Configuration for EgniTech One.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configure PHPMailer with SMTP settings.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
 */
function egnitech_one_smtp_init( PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
	// 1. Apply Sender Information globally (if provided)
	$from_email = get_option( 'egnitech_one_smtp_from_email' );
	$from_name  = get_option( 'egnitech_one_smtp_from_name', get_bloginfo( 'name' ) );

	if ( ! empty( $from_email ) ) {
		$phpmailer->From     = (string) $from_email;
		$phpmailer->FromName = (string) $from_name;
	}

	// 2. Apply SMTP settings ONLY if enabled
	$enabled = get_option( 'egnitech_one_smtp_enabled', 'no' );
	if ( 'yes' !== $enabled ) {
		return;
	}

	$host = get_option( 'egnitech_one_smtp_host' );

	// Only proceed if a host is configured
	if ( empty( $host ) ) {
		return;
	}

	$port       = get_option( 'egnitech_one_smtp_port', 587 );
	$encryption = get_option( 'egnitech_one_smtp_encryption', 'tls' );
	$auth       = get_option( 'egnitech_one_smtp_auth', 'yes' );
	$user       = get_option( 'egnitech_one_smtp_username' );
	$pass       = get_option( 'egnitech_one_smtp_password' );

	$phpmailer->isSMTP();
	$phpmailer->Host       = (string) $host;
	$phpmailer->Port       = (int) $port;
	$phpmailer->SMTPAuth   = ( 'yes' === $auth );
	$phpmailer->Username   = (string) $user;
	$phpmailer->Password   = (string) $pass;
	$phpmailer->SMTPSecure = ( 'none' === $encryption ) ? '' : (string) $encryption;
}
add_action( 'phpmailer_init', 'egnitech_one_smtp_init' );
