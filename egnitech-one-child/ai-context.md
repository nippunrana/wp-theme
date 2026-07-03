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
