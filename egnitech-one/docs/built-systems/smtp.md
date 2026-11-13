# SMTP mail

Code: `inc/smtp-config.php` (the `phpmailer_init` hook) and the **SMTP Settings** tab in `inc/admin-options.php`.

- **The From address and name apply even when SMTP is switched off.** If **From Email** is filled in, every message WordPress sends leaves with that sender, on whatever transport is in use.
- **It beats every other sender setting.** The hook runs after WordPress and plugins have set the sender, so WooCommerce's own "From" address and any `wp_mail_from` filter lose to it. A WooCommerce email arriving from an unexpected address is this field.
- **SMTP is used only when the switch is on and a host is saved.** With the switch on and the host empty, mail goes out by the server's default method without a warning.
- **Nothing records a failed send.** There is no `wp_mail_failed` handler and no test-email button. A wrong password shows up only as mail that never arrives.
- **The password is stored as text in the options table, exactly as typed**, and is never shown again on the settings screen (`theme-options.md`).
