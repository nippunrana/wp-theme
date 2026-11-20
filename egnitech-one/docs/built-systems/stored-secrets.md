# Stored secrets

Code: `inc/secrets.php` (the key, encrypt, read, admin notices) and `egnitech_one_sanitize_secret()` in `inc/admin-options.php`. Covers the SMTP password and the Turnstile and reCAPTCHA secret keys.

- **They are encrypted in the options table with a key made from `LOGGED_IN_KEY` and `LOGGED_IN_SALT` in `wp-config.php`.** This is Google Site Kit's model (owner's decision, 20 Nov 2026: no manual setup step for site owners). A copy of the database alone does not reveal them. Anything that runs PHP on the server can still read them.
- **Never** read those two constants through `wp_salt()`, and **never** store the key, or anything made from it, in the database. Where the constants are missing, `wp_salt()` returns values WordPress keeps in the database, which would put the key next to what it locks.
- **The theme never writes `wp-config.php`** (owner's decision, 20 Nov 2026). Where the two constants are missing or still the sample placeholder, secrets are saved as typed and Theme Options shows a warning.
- **Changing `LOGGED_IN_KEY` or `LOGGED_IN_SALT` makes every saved secret unreadable, and the theme cannot recover them**: the old key went with the old values. Security plugins' "change salts" or "log out everyone" buttons do this, and so does moving the site with a new `wp-config.php`.
  - The Dashboard and Theme Options then name the secrets to enter again.
  - Until then, Turnstile is off: an unreadable secret counts as missing, so logins are not refused. SMTP mail cannot sign in.
  - Putting the old values back in `wp-config.php` restores everything.
  - **Never** make an unreadable Turnstile secret count as set. Turnstile would stay on with no usable secret and refuse every login.
- **Always read these secrets through `egnitech_one_read_secret()`.** `get_option()` returns the encrypted text. A child theme that replaces `egnitech_one_smtp_init()`, or reads these options itself, gets the encrypted text too. `egnitech_one_get_recaptcha_settings()` still returns the plain key.
- **A value without the `egnitech-enc:v1:` prefix is used as it is.** That covers one saved before encryption existed, one saved on a site that cannot encrypt, and one written by WP-CLI. It is encrypted at the next Theme Options save. That first save changes the stored Turnstile secret, which clears the whole page cache once.
- **The sanitizer passes an already-encrypted value through untouched. Never remove that.** Each encryption uses a fresh random nonce, so re-encrypting would rewrite the option, and clear the page cache, on every save. WordPress also runs the sanitizer twice when an option is saved for the first time (seen on 7.1.4), so the second run receives the value the first one encrypted.
- **A blank field over an unreadable secret keeps it as it is**, so putting the old salts back still recovers it.
- **Rolling the theme back past this change breaks a site that already holds encrypted values.** Older code sends the encrypted text as the secret, so Turnstile stays on and refuses every login, and SMTP fails. Before checking out an older commit on a live clone, switch Turnstile off (`wp option update egnitech_one_turnstile_enabled no`), then enter the secrets again after the rollback.
- **WordPress core's own Secrets API was withdrawn from 7.2 on 17 Nov 2026.** If a later release ships it, move these secrets to it instead of extending this file.
