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
- **Off by default — light only**: Dark mode (and the visitor toggle) exists only after the **Dark Mode** switch in **Theme Options > General > Appearance** is turned on and saved. This applies to the parent and to any child theme alike. Light only is the owner's decision; **never** make dark mode the default.
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

## Module Map (`inc/`)
Every option/setting read across these modules goes through the generic getter `egnitech_one_get_option( string $option, mixed $default = '' ): mixed` (`inc/helpers.php`) — use it instead of calling `get_option()` directly for theme settings.
- **`inc/theme-setup.php`**: Theme supports, `register_block_style()` registrations, dark-mode `theme.json` CSS rewrite (forces light when dark mode is off).
- **`inc/enqueue-assets.php`**: Global + conditional frontend asset loading (dark-mode bootstrap script, pattern-specific CSS/JS gated by context).
- **`inc/admin-options.php`**: Theme Options admin page/framework (tabs including Integrations/reCAPTCHA).
- **`inc/helpers.php`**: Shared getters — `egnitech_one_get_option()`, `egnitech_one_is_dark_mode_enabled()`, `egnitech_one_get_recaptcha_settings()`.
- **`inc/font-manager.php`**: Custom font registration/management.
- **`inc/custom-scripts.php`**: Admin-configurable custom header/footer script injection.
- **`inc/smtp-config.php`**: SMTP mail delivery configuration.
- **`inc/fastcgi-cache-purger.php`**: Nginx FastCGI cache purge on content changes, admin-bar controls, cache size reporting.
- **`inc/woocommerce-cart-drawer.php`**: WooCommerce slide-out cart drawer.
- **`inc/woocommerce-bulk-discount.php`**: WooCommerce bulk/quantity discount pricing logic (pairs with `patterns/single-product-details/bulk-discount.js`).

## Coding Standards
1. **Text Domain**: Always use `egnitech-one` for core localization.
2. **CSS Scoping (MANDATORY)**: Scope all custom CSS strictly to the block's specific class or variation class (e.g., `.is-style-{slug}`). Avoid global rules or catch-all page wrappers to prevent style bleed.
3. **Safe Containers**: Use `core/group` for complex layouts instead of `core/columns` to avoid Site Editor validation errors.
4. **SVGs**: Provide explicit `width` and `height` attributes on inline `<svg>` tags to prevent layout shifts in the Site Editor.
5. **PHP 8.0–8.3 & WP 7.0 Compliance**: Minimum supported version is PHP 8.0 (per the `Requires PHP` header in `style.css`); write code compatible across PHP 8.0–8.3. All core PHP files (such as `functions.php`, files in `inc/`) must include `declare(strict_types=1);` on line 2, and declare strict types for all parameters and return types. Any `theme.json` must use `"version": 3`.

---
*Final AI Reminder*: This is the CORE theme. Changes here affect all child-theme instances. Maintain stability and extreme performance.
