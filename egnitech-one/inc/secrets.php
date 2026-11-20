<?php
declare(strict_types=1);

/**
 * Stored secrets: the SMTP password and the Turnstile and reCAPTCHA secret keys.
 *
 * They are encrypted in the options table with a key made from WordPress's LOGGED_IN_KEY and
 * LOGGED_IN_SALT in wp-config.php, so a copy of the database alone does not reveal them. Where those
 * two are not set, secrets are saved as typed and Theme Options says so. See
 * docs/built-systems/stored-secrets.md.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marks an encrypted value. A value without it was saved as typed: before encryption existed, on a
 * site that cannot encrypt, or from WP-CLI.
 */
const EGNITECH_ONE_SECRET_PREFIX = 'egnitech-enc:v1:';

/**
 * The secrets this file protects.
 *
 * @return array<string, string> Option name => label.
 */
function egnitech_one_secret_options(): array {
	return array(
		'egnitech_one_turnstile_secret_key' => __( 'Cloudflare Turnstile secret key', 'egnitech-one' ),
		'egnitech_one_smtp_password'        => __( 'SMTP password', 'egnitech-one' ),
		'egnitech_one_recaptcha_secret_key' => __( 'reCAPTCHA secret key', 'egnitech-one' ),
	);
}

/**
 * The encryption key, made from LOGGED_IN_KEY and LOGGED_IN_SALT in wp-config.php.
 * Read from the constants, never through wp_salt(): where they are missing, wp_salt() returns values
 * WordPress keeps in the database, which would put the key next to what it locks.
 *
 * @return string|null 32-byte key, or null when this site cannot encrypt.
 */
function egnitech_one_secrets_key(): ?string {
	if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
		return null;
	}

	$material = '';
	foreach ( array( 'LOGGED_IN_KEY', 'LOGGED_IN_SALT' ) as $name ) {
		$value = defined( $name ) ? constant( $name ) : '';
		if ( ! is_string( $value ) || '' === $value || 'put your unique phrase here' === $value ) {
			return null;
		}
		$material .= $value;
	}

	// A key of the theme's own, derived from the two: the salts are never used as the key itself.
	return hash_hmac( 'sha256', 'egnitech-one-secrets', $material, true );
}

/**
 * Prepare a secret for saving: encrypted where this site can encrypt, as typed where it cannot.
 * A value that is already encrypted passes through untouched. Each encryption uses a fresh nonce,
 * so encrypting it again would change the stored value on every save.
 *
 * @param string $plain The secret as typed, or a value already encrypted.
 * @return string The value to store.
 */
function egnitech_one_encrypt_secret( string $plain ): string {
	$key = egnitech_one_secrets_key();
	if ( null === $key || '' === $plain || str_starts_with( $plain, EGNITECH_ONE_SECRET_PREFIX ) ) {
		return $plain;
	}

	$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
	return EGNITECH_ONE_SECRET_PREFIX . base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, $key ) );
}

/**
 * The plain value of a stored secret. Always read the three secrets through this:
 * get_option() returns the encrypted text.
 *
 * @param string $option Option name.
 * @return string|null The secret ('' when none is saved), or null when it is encrypted and this
 *                     site's key cannot open it (LOGGED_IN_KEY or LOGGED_IN_SALT has changed).
 */
function egnitech_one_read_secret( string $option ): ?string {
	$stored = (string) get_option( $option, '' );
	if ( ! str_starts_with( $stored, EGNITECH_ONE_SECRET_PREFIX ) ) {
		return $stored;
	}

	$key = egnitech_one_secrets_key();
	$raw = base64_decode( substr( $stored, strlen( EGNITECH_ONE_SECRET_PREFIX ) ), true );
	if ( null === $key || false === $raw || strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
		return null;
	}

	$plain = sodium_crypto_secretbox_open(
		substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		$key
	);
	return false === $plain ? null : $plain;
}

/**
 * Dashboard and Theme Options: name each saved secret this site can no longer decrypt, and on
 * Theme Options warn when secrets are saved without encryption. The theme cannot recover an
 * unreadable secret: its key went with the old salts.
 */
function egnitech_one_secrets_notices(): void {
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || ! $screen || ! in_array( $screen->id, array( 'dashboard', 'appearance_page_egnitech-one-options' ), true ) ) {
		return;
	}

	$unreadable = array();
	foreach ( egnitech_one_secret_options() as $option => $label ) {
		if ( null === egnitech_one_read_secret( $option ) ) {
			$unreadable[] = $label;
		}
	}

	if ( $unreadable ) {
		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p><p>%3$s <a href="%4$s">%5$s</a></p></div>',
			esc_html__( 'EgniTech One: saved secrets can no longer be read.', 'egnitech-one' ),
			/* translators: %s: comma-separated list of secrets, e.g. "SMTP password". */
			esc_html( sprintf( __( 'WordPress\'s security keys in wp-config.php (LOGGED_IN_KEY or LOGGED_IN_SALT) have changed, so these can no longer be decrypted: %s.', 'egnitech-one' ), implode( ', ', $unreadable ) ) ),
			esc_html__( 'Until they are entered again, Cloudflare Turnstile is switched off and SMTP email cannot sign in. Putting the old keys back in wp-config.php also restores them.', 'egnitech-one' ),
			esc_url( admin_url( 'themes.php?page=egnitech-one-options' ) ),
			esc_html__( 'Enter them again in Theme Options', 'egnitech-one' )
		);
	}

	if ( 'appearance_page_egnitech-one-options' === $screen->id && null === egnitech_one_secrets_key() ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'EgniTech One: the SMTP password and the Turnstile and reCAPTCHA secret keys are saved without encryption, because wp-config.php does not define LOGGED_IN_KEY and LOGGED_IN_SALT (or PHP\'s sodium library is missing). Anyone with a copy of the database can read them.', 'egnitech-one' )
		);
	}
}
add_action( 'admin_notices', 'egnitech_one_secrets_notices' );
