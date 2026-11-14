# Theme Options

Code: `inc/admin-options.php` (settings, sanitizers, the page), `assets/js/admin-options.js`, getters in `inc/helpers.php`. The dark-mode decisions are in `ai-context.md`.

## How saving works

- **One form saves every setting.** All settings sit in one group and the page posts to WordPress's `options.php`, which rewrites **every** registered setting in the group on each save and uses an empty value for any field the form did not send. Three consequences:
  - **Never register a setting without printing its field in that form.** It is blanked on the next save, whoever saves and whatever they meant to change.
  - **A setting must be registered under exactly the condition its field is printed under.** The five WooCommerce gallery settings are registered only while WooCommerce is active, which is when their tab is printed. Before that, saving with WooCommerce switched off silently reset the product gallery. Two older cases still break the rule: the FastCGI Cache settings (their tab is printed on nginx only) and **Dark Mode Default** (printed only while dark mode is on, so switching dark mode off returns it to `system`).
  - **An unticked box is stored as an empty string, never `no`.** Test `'yes' === …` only. A test for `'no'` is true only until the first save.
- **Some sanitizers do work as well as cleaning.** The two width sanitizers write to the site's Global Styles record and the logo sanitizer sets the Site Logo. They run on every save, changed or not, and they do not check that the field was sent: a missing width field becomes the 320px minimum and a missing logo field removes the Site Logo.
- **Those side effects exist only inside wp-admin.** `functions.php` loads `inc/admin-options.php` and `inc/font-manager.php` only when `is_admin()` is true. From WP-CLI, cron, the REST API or the front end, the sanitizers are not registered: `wp option update egnitech_one_content_width 1000` stores a number and changes nothing. **Never** call a function from those two files in front-end code. It does not exist there.

## Decisions

- **The page needs `manage_options` to open and to save** (menu, page and AJAX handler), the same capability `options.php` demands. **Never** lower it. It used to open for `edit_theme_options`, which let roles that could not save, such as WooCommerce's Shop Manager, read everything on it.
- **Secret fields are write-only.** The SMTP password and the reCAPTCHA secret key are never printed into the form. A blank field keeps the saved value and a typed value is stored exactly as typed (`egnitech_one_sanitize_secret`). **Never** print a stored secret into any page, script or REST response. Keep `autocomplete="new-password"` on those two fields: without it a browser can fill in the administrator's own login password, and the next save stores it as the SMTP password. The screen has no way to blank a saved secret; overwrite it, or delete the option.
- **The Global Styles sync must pass its JSON through `wp_slash()`.** **Never** remove it. WordPress strips backslashes from post content on save, and without the slashing a Global Styles record containing a quote, a line break or a non-English letter (custom CSS written in the Site Editor, a font with a quoted name) is saved unreadable, which looks like every style vanishing after a Theme Options save.

## Where a value really lives

- **Content width and wide width live in Global Styles, not in the two width options.** Nothing reads `egnitech_one_content_width` or `egnitech_one_wide_width`. The form shows the merged Global Styles value and the sanitizers write it back (`egnitech_one_sync_layout_to_global_styles`). So:
  - A width set in the Site Editor and one set in Theme Options are the same value, and the last one saved wins.
  - Once a site's Global Styles record carries a width, editing `settings.layout` in `theme.json` changes nothing on that site.
  - If a site has no Global Styles record yet, the sync returns without writing and without an error, and the width fields appear to do nothing.
- **The light logo is the WordPress Site Logo.** The field is filled from the `custom_logo` theme mod and saved back to it. `egnitech_one_light_logo_id` is only a copy. The dark logo is a plain address the header pattern prints itself.
- **Footer copyright, credits and the dark logo are read inside pattern PHP**, so the database-copy trap in `templates-and-patterns.md` applies to them.

## Traps

- **A default is written in up to three places, and the registered one exists only inside wp-admin**: in `register_setting`, in the page's own `get_option()` call, and in the front-end reader. A setting whose saved value equals its registered default is never written to the database at all, so the front end falls back to *its* default. Where the three disagree, the screen and the site disagree: the Tags toggle once showed "off" while tags were still printed. **When adding a setting, give all three the same default.**
- **Custom Scripts survive a save only for users allowed to post unfiltered HTML.** For anyone else the sanitizer returns an empty string, and because every setting is rewritten on each save, such a user saving any tab **deletes all custom scripts**. This covers administrators on multisite and any site with `DISALLOW_UNFILTERED_HTML` set.
- **Every setting a page view reads is named in `egnitech_one_prime_options()`** (`inc/helpers.php`), which fetches them all with one query. A setting left out still works, but while it has never been saved it costs one more database query on every page not served from cache. **When adding a setting the front end reads, add its name there.** The function runs on page views only (`template_redirect`): **never** hook it to `init`, where it would add a query to every REST, AJAX and admin request instead of saving any.
