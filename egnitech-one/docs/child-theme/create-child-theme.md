# Creating a Child Theme for EgniTech One

Step-by-step guide to creating a child theme for one site. For what a child theme can do once it exists, read [README.md](README.md).

The repository no longer ships a child theme, so **this file is the only copy of the starter files**. Change them here when the starter itself should change.

## Before you start

* **Only create a child theme when the user asks for one.** A site can run `egnitech-one` directly.
* Pick the site's slug: lowercase and hyphenated, usually the domain without its ending (e.g. `truinterview` for truinterview.in). Below, `<slug>` is that slug, `<Site Name>` is the display name, and `<domain>` is the site's folder in `/var/www/`.

---

## 1. Create the folder

Create `wp-content/themes/<slug>/` next to `egnitech-one/`. Do it while logged in as `ubuntu`, not through `sudo` (see `.agent/rules/file-ownership.md`).

If `wp-content/themes/` is a git repo on that server (as on homegears.in), keep the child theme out of it. Add it to that server's local exclude file, which is never committed or pushed:

```bash
echo "/<slug>/" >> /var/www/<domain>/html/wp-content/themes/.git/info/exclude
```

---

## 2. Add the five starter files

Create these files in the new folder with exactly this content. Step 3 renames them for the site.

### `style.css`

The `Template: egnitech-one` line is what makes WordPress treat this as a child of EgniTech One.

```css
/*
Theme Name: EgniTech One Child
Theme URI: https://egnitech.com/themes/egnitech-one-child
Author: Nippun Rana
Author URI: https://egnitech.com
Description: Child theme for EgniTech One. Extend or override the parent theme for a specific site without touching the core.
Template: egnitech-one
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Version: 1.0.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: egnitech-one-child
Tags: full-site-editing, block-patterns
*/
```

### `functions.php`

The guarded `egnitech_one_is_dark_mode_enabled()` override is **mandatory**. Keep it exactly as it is.

**Why:** the parent's version of this function (`egnitech-one/inc/helpers.php`) reads the site's `theme.json` settings through `wp_get_global_settings()`. The parent also filters `theme.json` data through `egnitech_one_filter_theme_json()` (`egnitech-one/inc/theme-setup.php`), and that filter calls the same function. In a child theme the lookup therefore triggers itself while the settings are still being built, in a loop that never ends. The `$is_resolving` guard breaks the loop. The child's `functions.php` loads before the parent's, so this definition replaces the parent's.

```php
<?php
declare(strict_types=1);

/**
 * EgniTech One Child Theme — functions and definitions.
 *
 * @package EgniTech_One_Child
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override the parent theme's dark mode helper to prevent infinite recursion
 * when resolving global settings in the child theme.
 *
 * @return bool True if dark mode is enabled, false otherwise.
 */
if ( ! function_exists( 'egnitech_one_is_dark_mode_enabled' ) ) {
	function egnitech_one_is_dark_mode_enabled(): bool {
		static $is_resolving = false;
		if ( $is_resolving ) {
			return false;
		}

		// If parent theme is active, dark mode is always enabled.
		if ( ! is_child_theme() ) {
			return true;
		}

		// Check PHP theme support declaration.
		if ( current_theme_supports( 'egnitech-one-dark-mode' ) ) {
			return true;
		}

		// Check JSON declaration in theme.json's settings.custom.darkMode.
		$is_resolving = true;
		$custom_settings = wp_get_global_settings( array( 'custom' ) );
		$is_resolving = false;

		if ( is_array( $custom_settings ) && ! empty( $custom_settings['darkMode'] ) ) {
			return true;
		}

		return false;
	}
}

add_action( 'after_setup_theme', function (): void {
	register_block_pattern_category(
		'egnitech-one-child',
		[ 'label' => __( 'EgniTech One Child', 'egnitech-one-child' ) ]
	);
} );
```

### `theme.json`

Add only what this site changes; everything else comes from the parent. Arrays such as `palette` replace the parent's array completely (see [README.md](README.md)).

```json
{
    "$schema": "https://schemas.wp.org/wp/7.0/theme.json",
    "version": 3,
    "settings": {},
    "styles": {}
}
```

### `index.php`

```php
<?php
declare(strict_types=1);

// Silence is golden.
```

### `ai-context.md`

Agents working in the child theme read this file instead of the parent's; `egnitech-one/ai-context.md` sends them here.

````markdown
# AI Context — EgniTech One Child Theme

> [!IMPORTANT]
> **Context Selection Rule:**
> If you are working within the parent theme core (e.g., inside `egnitech-one/` folder, and the objective is to modify the core foundation), you MUST ignore this file and use the parent theme's `ai-context.md` (`egnitech-one/ai-context.md`) instead.

This file provides the **environmental ground rules** and **project identity** for the child theme. For technical implementation procedures (creating templates, patterns, or asset loading), refer to the local `wp-block-theme` skill located in the directory `egnitech-one/.agent/skills/wp-block-theme/`.

## Project Identity
- **Child Theme**: `THEME_NAME` (Folder: `THEME_SLUG`)
- **Parent Theme**: `EgniTech One` (Folder: `egnitech-one`)
- **Architecture**: WordPress Full Site Editing (FSE) / Block Theme.

### Theme Metadata (Skill Variables)
> Per-domain deployment: update only the values in this block; the rest of this file references them as `THEME_SLUG` / `THEME_NAME` / `TEXT_DOMAIN`.
- **THEME_SLUG**: `egnitech-one-child`
- **THEME_NAME**: `EgniTech One Child`
- **TEXT_DOMAIN**: `egnitech-one-child`
- **PARENT_THEME_SLUG**: `egnitech-one`
- **IS_CHILD**: `true`

## Child Theme Principles (CRITICAL)
1. **Parent Protection**: NEVER modify files in the parent theme directory (`egnitech-one`). All work MUST stay within the child theme folder.
2. **Inheritance & Overrides**: 
   - This theme inherits all templates, parts, and functions from the parent. 
   - To override, mirror the file path from the parent into the child theme.
   - For components, use the **Pattern-First Asset Model**: Assets (`style.css`, `index.js`) must live inside the specific pattern directory (e.g., `patterns/contact-us/style.css`).

## Deploying to a New Domain
This child theme's folder/slug is renamed per site (e.g. `egnitech-one-child` → `truinterview` for truinterview.in). When cloning it for a new domain:
1. Rename the theme folder to the domain slug (e.g. `truinterview`).
2. Update the **Theme Metadata (Skill Variables)** block above (`THEME_SLUG`, `THEME_NAME`, `TEXT_DOMAIN`).
3. Update the `style.css` header: `Theme Name`, `Text Domain`, `Theme URI`, `Description`.
4. Update `functions.php`: the text-domain strings and the block pattern category slug/label.
5. **Do NOT change**: `Template: egnitech-one` in `style.css`, any `egnitech_one_*` function names, the `egnitech-one-dark-mode` support flag, or the `egnitech-one` text domain used for parent-defined strings — these all point at the parent theme and must stay as-is.
6. **Verify**: `grep -rn "egnitech-one-child"` inside the child theme folder should return no results once renamed.

## Implementation Workflow
For all development tasks involving templates, patterns, `theme.json`, or asset enqueuing:
1. **Use the `wp-block-theme` skill** located in `egnitech-one/.agent/skills/wp-block-theme/`. It contains the expert logic for HTML conversion, CSS scoping, and the atomic asset pipeline.
2. **Follow the Pattern-First Model**: New patterns should use modular asset bundles (scoped `style.css` and `index.js` inside the pattern directory) registered via `register_block_style()` as detailed in the skill.

## Project-Specific Systems

### Dark/Light Mode System
By default, the parent theme's native dark/light mode is **disabled** to avoid style conflicts and regressions.
- **Opt-In Mechanisms**:
  1. **PHP**: Call `add_theme_support( 'egnitech-one-dark-mode' );` inside an `after_setup_theme` action hook in `functions.php`.
  2. **JSON**: Set `"custom": { "darkMode": true }` under `"settings"` in the child theme's `theme.json`.
- **Writing Styles**: When enabled, write scoped styles using the `light-dark()` CSS function to dynamically support the scheme toggle. When disabled, the browser will natively resolve all `light-dark()` properties to their light mode values automatically.
- **Example**: `color: light-dark(var(--wp--preset--color--base), var(--wp--preset--color--contrast));`

### Text Domains & Localization
- Use `egnitech-one` only when translating existing strings defined by the parent theme's translation files.
- Use `TEXT_DOMAIN` for all new translatable strings unique to the child theme.

### Google reCAPTCHA v2 System
Google reCAPTCHA v2 keys are managed globally under the parent theme's options page ("Integrations" tab).
- **Accessing Keys**: Call the global helper function `egnitech_one_get_recaptcha_settings()`.
- **Returned Schema**: Returns `array{enabled: bool, site_key: string, secret_key: string}`.
- **Usage inside Child Theme**: Use this helper to easily fetch and render the captcha keys site-wide in forms or patterns:
  ```php
  $recaptcha = egnitech_one_get_recaptcha_settings();
  if ( $recaptcha['enabled'] ) {
      // Use $recaptcha['site_key'] and $recaptcha['secret_key']
  }
  ```
- **Security**: Only `site_key` may be output client-side (e.g. in a `data-sitekey` attribute). `secret_key` is for server-side verification requests only and must never appear in markup or JS.

## Coding Standards
1. **Vanilla JS Only**: No jQuery. Use ES6+. For complex interactions, use the **Interactivity API**.
2. **CSS Scoping (MANDATORY)**: All custom CSS must be scoped to the block's variation class (e.g., `.is-style-{slug}`). NEVER use catch-all page wrappers (e.g., `.{slug}-wrapper`) as they cause style bleed.
3. **Performance**: Maintain the lean, high-performance philosophy of the parent theme.
4. **SVGs**: Provide explicit `width` and `height` attributes on inline `<svg>` tags to prevent layout shifts in the Site Editor.
5. **PHP 8.3 & WP 7.0 Compliance (MANDATORY)**: All child theme PHP files (including `functions.php`, helper scripts, or template overrides) must include `declare(strict_types=1);` on line 2, and use native type hints for all parameters and return types. Any custom `theme.json` in the child theme must inherit from parent settings and use `"version": 3`.

## Technical References
Refer to the following in the local `wp-block-theme` skill (under `egnitech-one/.agent/skills/wp-block-theme/`) for deep-dives:
- `references/html-conversion.md`: The 10-step process for converting designs to FSE.
- `references/architecture.md`: Scoping rules and the asset pipeline.
- `references/theme-json.md`: Global design system tokens.

---
*Final AI Reminder*: You are extending a production site. Always verify changes in the child theme and never touch the parent core.
````

---

## 3. Rename for the site

1. **`ai-context.md`:** in the **Theme Metadata (Skill Variables)** block, set `THEME_SLUG` and `TEXT_DOMAIN` to `<slug>` and `THEME_NAME` to `<Site Name>`.
2. **`style.css`:** update `Theme Name`, `Theme URI`, `Description` and `Text Domain`.
3. **`functions.php`:** update the block pattern category slug (`'egnitech-one-child'`), its label (`'EgniTech One Child'`) and the text domain beside it.
4. **Do NOT change:** `Template: egnitech-one` in `style.css`, any `egnitech_one_*` function name, the `egnitech-one-dark-mode` support flag, or the `egnitech-one` text domain used for parent-defined strings. These all point at the parent theme.

---

## 4. Check and activate

1. **Nothing still carries the starter name.** This must print nothing. `ai-context.md` is skipped because its own deployment notes mention the starter name on purpose:
    ```bash
    grep -rniE "egnitech-one-child|egnitech one child" --exclude=ai-context.md /var/www/<domain>/html/wp-content/themes/<slug>/
    ```
2. **The web user can manage the files.** This must print nothing:
    ```bash
    sudo -u www-data find /var/www/<domain>/html/wp-content/themes/<slug> ! -writable
    ```
    If it prints anything, repair the folder:
    ```bash
    T=/var/www/<domain>/html/wp-content/themes/<slug>; sudo chown -R ubuntu:www-data "$T" && sudo chmod -R g+w "$T" && sudo find "$T" -type d -exec chmod g+s {} +
    ```
3. **Activate it** in **Appearance > Themes**. Then load the home page and one inner page; both must render normally.
4. **Dark mode** stays off until you turn it on (see [README.md](README.md)).
