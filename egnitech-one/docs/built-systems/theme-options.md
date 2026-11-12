# Theme Options

Code: `inc/admin-options.php` (settings, sanitizers, the page), `assets/js/admin-options.js`, getters in `inc/helpers.php`. The dark-mode decisions are in `ai-context.md`.

## How saving works

- **One form saves every setting.** All settings sit in one group and the page posts to WordPress's `options.php`, which rewrites **every** setting in the group on each save and uses an empty value for any field the form did not send. Three consequences:
  - **Never register a setting without printing its field in that form.** It is blanked on the next save, whoever saves and whatever they meant to change.
  - **Never hide a field behind a condition its setting does not share.** The WooCommerce tab is printed only while WooCommerce is active, so saving Theme Options with WooCommerce switched off blanks the five gallery settings, and the product page comes back on WooCommerce's default gallery. **Dark Mode Default** is printed only while dark mode is on, so switching dark mode off returns it to `system`.
  - **An unticked box is stored as an empty string, never `no`.** Test `'yes' === …` only. A test for `'no'` is true only until the first save.
- **Some sanitizers do work as well as cleaning.** The two width sanitizers write to the site's Global Styles record and the logo sanitizer sets the Site Logo. They run on every save, changed or not, and they do not check that the field was sent: a missing width field becomes the 320px minimum and a missing logo field removes the Site Logo.
- **Those side effects exist only inside wp-admin.** `functions.php` loads `inc/admin-options.php` and `inc/font-manager.php` only when `is_admin()` is true. From WP-CLI, cron, the REST API or the front end, the sanitizers are not registered: `wp option update egnitech_one_content_width 1000` stores a number and changes nothing. **Never** call a function from those two files in front-end code. It does not exist there.

## Where a value really lives

- **Content width and wide width live in Global Styles, not in the two width options.** Nothing reads `egnitech_one_content_width` or `egnitech_one_wide_width`. The form shows the merged Global Styles value and the sanitizers write it back (`egnitech_one_sync_layout_to_global_styles`). So:
  - A width set in the Site Editor and one set in Theme Options are the same value, and the last one saved wins.
  - Once a site's Global Styles record carries a width, editing `settings.layout` in `theme.json` changes nothing on that site.
  - If a site has no Global Styles record yet, the sync returns without writing and without an error, and the width fields appear to do nothing.
- **The light logo is the WordPress Site Logo.** The field is filled from the `custom_logo` theme mod and saved back to it. `egnitech_one_light_logo_id` is only a copy. The dark logo is a plain address the header pattern prints itself.
- **Footer copyright, credits and the dark logo are read inside pattern PHP**, so the database-copy trap in `templates-and-patterns.md` applies to them.

## Traps

- **The Global Styles sync saves its JSON without slashing it first.** WordPress strips backslashes from post content on save, so a Global Styles record containing a quote, a line break or a non-English letter (custom CSS written in the Site Editor, a font with a quoted name) is corrupted the next time anyone saves Theme Options. Until `egnitech_one_sync_layout_to_global_styles` passes its JSON through `wp_slash()`, read "our styles vanished after saving Theme Options" as this. The record's revisions hold the last good copy.
- **A default is written in up to three places, and the registered one exists only inside wp-admin**: in `register_setting`, in the page's own `get_option()` call, and in the front-end reader. A setting whose saved value equals its registered default is never written to the database at all, so the front end falls back to *its* default. Where the three disagree, the screen and the site disagree. Tags is the live example: the box shows unticked and saves as unticked, while the front end still prints tags until the box has been ticked, saved, unticked and saved again. **When adding a setting, give all three the same default.**
- **Viewing and saving need different permissions.** The page opens for anyone with `edit_theme_options`. `options.php` accepts the save only from `manage_options`, and the theme adds no `option_page_capability_…` filter. A role with the first and not the second (WooCommerce's Shop Manager is one) sees every field and gets a permission error on save. **Never** add a field whose stored value such a role must not see without first raising the page's capability.
- **Custom Scripts survive a save only for users allowed to post unfiltered HTML.** For anyone else the sanitizer returns an empty string, and because every setting is rewritten on each save, such a user saving any tab **deletes all custom scripts**. This covers administrators on multisite and any site with `DISALLOW_UNFILTERED_HTML` set.
- **Passwords and keys are cleaned with `sanitize_text_field`.** It trims spaces, strips anything shaped like an HTML tag and removes percent-encoded sequences such as `%2F`. An SMTP password or reCAPTCHA secret containing those is stored altered, with no warning at save time, and fails later.
