# AI Context — EgniTech One (Parent Theme)
-
> [!IMPORTANT]
> **Context Selection Rule:**
> If you are working within an active child theme, you MUST ignore this file and use the `ai-context.md` in that child theme's directory instead.

This file provides the **architectural foundation** and **design system rules** for the EgniTech One parent theme. This is the core engine; for technical implementation procedures (creating templates, patterns, or asset loading), refer to the local `wp-block-theme` skill located in the directory `egnitech-one/.agent/skills/wp-block-theme/`.

## Project Identity
- **Theme Name**: `EgniTech One` (Folder: `egnitech-one`)
- **Role**: Core Foundation / Parent Theme.
- **Performance Target**: Site-wide assets must remain under **50KB**.

### Theme Metadata (Skill Variables)
- **THEME_SLUG**: `egnitech-one`
- **THEME_NAME**: `EgniTech One`
- **TEXT_DOMAIN**: `egnitech-one`
- **IS_CHILD**: `false`

## Core Development Principles
1. **Child Theme First — only when one is active**: If the site runs a child theme, implement site-specific features, templates, or styles there instead of modifying this core. This repo ships no child theme, and homegears.in runs this theme directly. **Never** create a child theme unless the user asks, and **never** commit one to this repo. To create one, follow `docs/child-theme/create-child-theme.md`.
2. **Native-First**: Always prioritize native WordPress block capabilities and `theme.json` settings over custom CSS or PHP workarounds.
3. **Vanilla JS Only**: Use ES6+ for performance and modern compatibility. jQuery is permitted only where required for WooCommerce event interop (e.g. `found_variation`, `added_to_cart`), and must always be guarded with `typeof jQuery !== 'undefined'` — never declared as a script dependency.

## Implementation Workflow
For all development tasks involving templates, patterns, or `theme.json`:
1. **Use the `wp-block-theme` skill** located in `egnitech-one/.agent/skills/wp-block-theme/`. It contains the expert logic for HTML conversion, CSS scoping, and the atomic asset pipeline.
2. **Modular Assets**: Follow the "Pattern-First" asset model. Section-specific CSS/JS lives alongside its pattern in `patterns/{slug}/` (e.g. `style.css`, `index.js`). Never `wp_enqueue_style()`/`wp_enqueue_script()` directly inside a pattern's PHP file. Instead:
   - CSS is registered via `register_block_style()` with a `style_handle` in `inc/theme-setup.php`, so it loads only when that block style is used.
   - JS/CSS that must load conditionally (e.g. only on product pages) is registered and enqueued from `inc/enqueue-assets.php`, gated on the relevant condition.

## Design System & `theme.json`
The foundation uses a modern `theme.json` (v3) approach:
- **Typography**: Fluid typography is enabled (`settings.typography.fluid: true`), so WordPress generates `clamp()` output automatically. Never hardcode pixel values; reference theme presets (e.g., `var(--wp--preset--font-size--large)`).
- **Spacing**: Slug-based spacing scales (`20` to `80`). 
  - Inside `theme.json`, reference them using `var:preset|spacing|[slug]` tokens.
  - Inside CSS files, reference them using standard CSS variables e.g., `var(--wp--preset--spacing--[slug])`.

## Dark/Light Mode System (CRITICAL)
EgniTech One handles color schemes via a foundational system:
- **Mechanics**: Controls the `:root` pseudo-class using `color-scheme: light dark` and a persistent `data-scheme` attribute on the `<html>` root.
- **Implementation**: DO NOT use media queries for dark modes. Use the native modern CSS `light-dark()` function.
- **Example**: `color: light-dark(var(--wp--preset--color--base), var(--wp--preset--color--contrast));`
- **Off by default — light only**: Dark mode (and the visitor toggle) exists only after the **Dark Mode** switch in **Theme Options > General > Appearance** is turned on and saved. This applies to the parent and to any child theme alike. Light only is the owner's decision; **never** make dark mode the default. Once the switch is on, the first scheme follows the visitor's device (**Dark Mode Default** = `system`); that is also the owner's decision, so **never** change that default to `light` in code.
  - To check it programmatically, use `egnitech_one_is_dark_mode_enabled()`. It reads only that setting.
  - **Never** add another way to switch dark mode on (`add_theme_support`, a `theme.json` flag, a constant), and **never** define `egnitech_one_is_dark_mode_enabled()` in a child theme — it would replace the parent's and ignore the Theme Options switch.
  - **Never** make that function read `theme.json` settings (`wp_get_global_settings()`): `egnitech_one_filter_theme_json()` calls it while those settings are being built, which loops forever.
  - When disabled, `:root` color-scheme is forced to `light` to natively resolve all `light-dark()` colors to their light mode values with zero runtime overhead.
  - Saving the switch with a new value clears the whole FastCGI page cache (`inc/fastcgi-cache-purger.php`), so cached pages don't keep the old scheme.

## Google reCAPTCHA v2 System
The parent theme contains a built-in Integrations tab under Theme Options to configure Google reCAPTCHA v2.
- **Retrieval Helper**: Retrieve site-wide reCAPTCHA settings using the global helper function `egnitech_one_get_recaptcha_settings()`.
- **Returned Schema**: Returns `array{enabled: bool, site_key: string, secret_key: string}`.
- **Example Usage**:
  ```php
  $recaptcha = egnitech_one_get_recaptcha_settings();
  if ( $recaptcha['enabled'] ) {
      $site_key = $recaptcha['site_key'];
      // Integrate Site Key in recaptcha HTML element
  }
  ```

## Built Systems vs Roadmap
Each file in `docs/built-systems/` holds only what the code cannot say: bans, decisions and traps. Read the row's file before touching that system.

| System | Status | Pointer | Notes |
| :--- | :--- | :--- | :--- |
| Theme Options | Built | [docs/built-systems/theme-options.md](docs/built-systems/theme-options.md) | One form rewrites every setting on each save. Read before adding a setting, hiding a field, or saving options from code or WP-CLI |
| Page cache & purging | Built | [docs/built-systems/page-cache.md](docs/built-systems/page-cache.md) | nginx keeps whole pages; the theme only deletes them, and only some. First stop when a change "is not showing" |
| Assets & cache-busting | Built | [docs/built-systems/assets-and-versioning.md](docs/built-systems/assets-and-versioning.md) | No build step. Read before editing any CSS or JS: most files need a `Version` bump to reach returning visitors |
| Dark / light mode | Built | [docs/built-systems/dark-mode.md](docs/built-systems/dark-mode.md) | Traps only; the bans are in the section above |
| Header, footer, sidebar & templates | Built | [docs/built-systems/templates-and-patterns.md](docs/built-systems/templates-and-patterns.md) | A Site Editor save freezes a template and the PHP inside its pattern |
| WooCommerce product page | Built | [docs/built-systems/woocommerce-product-page.md](docs/built-systems/woocommerce-product-page.md) | Template, custom gallery, trust badges, add-to-cart and the cart drawer |
| Bulk discounts | Built | [docs/built-systems/bulk-discounts.md](docs/built-systems/bulk-discounts.md) | Changes what customers are charged. The same sum lives in three places |
| Custom scripts | Built | [docs/built-systems/custom-scripts.md](docs/built-systems/custom-scripts.md) | Header and footer code from Theme Options, printed unescaped |
| SMTP mail | Built | [docs/built-systems/smtp.md](docs/built-systems/smtp.md) | Also sets the sender of every email, SMTP on or off |
| reCAPTCHA keys | Built | `inc/helpers.php` | Stores the keys and hands them out (section above). Nothing in this theme draws a challenge or checks an answer |
| Font overview | Built | `inc/font-manager.php` | Read-only list in Theme Options. Fonts are changed in the Site Editor; this file installs nothing, whatever its header says |
| Scroll-to-top, reading progress, breadcrumbs | Built | `inc/enqueue-assets.php`, `inc/theme-setup.php` | Switches under Theme Options > General. Their output is part of the cached page |
| Child themes | Built | [docs/child-theme/README.md](docs/child-theme/README.md) | None ships here. Several parent behaviours change under a child: see the assets and dark-mode files |

No `Roadmap` rows yet: nothing planned is recorded in this repo. Add one before describing planned work anywhere.

**Reading a setting:** new code uses `egnitech_one_get_option()` (`inc/helpers.php`) instead of `get_option()`. Most existing modules still call `get_option()` directly.

## Environment & Deployment Constraints
- **On the homegears.in server this repo is cloned straight into `wp-content/themes/`, so that working copy is the live site.** A saved file is in production at once. There is no build step and no staging copy. Another site's copy of the theme changes only when that copy is updated.
- **Two caches sit outside WordPress.** nginx keeps whole pages for logged-out visitors, and tells browsers to keep CSS and JS indefinitely. A change that "does not show" is nearly always one of them: see the page-cache and assets files above.
- **WP-CLI on the server runs as `ubuntu` and outside wp-admin**, which changes what a command does. Read the page-cache and theme-options files before changing content or settings from the command line.
- **Nothing stops a browser downloading the non-PHP files in this folder**, these documents included (checked on homegears.in). **Never** write a password, key, token or server credential into any file here.

## Coding Standards
1. **Text Domain**: Always use `egnitech-one` for core localization.
2. **CSS Scoping (MANDATORY)**: Scope all custom CSS strictly to the block's specific class or variation class (e.g., `.is-style-{slug}`). Avoid global rules or catch-all page wrappers to prevent style bleed.
3. **Safe Containers**: Use `core/group` for complex layouts instead of `core/columns` to avoid Site Editor validation errors.
4. **SVGs**: Provide explicit `width` and `height` attributes on inline `<svg>` tags to prevent layout shifts in the Site Editor.
5. **PHP 8.0–8.3 & WP 7.0 Compliance**: Minimum supported version is PHP 8.0 (per the `Requires PHP` header in `style.css`); write code compatible across PHP 8.0–8.3. All core PHP files (such as `functions.php`, files in `inc/`) must include `declare(strict_types=1);` on line 2, and declare strict types for all parameters and return types. Any `theme.json` must use `"version": 3`.

---
*Final AI Reminder*: This is the CORE theme. Changes here affect all child-theme instances. Maintain stability and extreme performance.
