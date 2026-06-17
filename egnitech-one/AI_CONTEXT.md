# AI Context — EgniTech One (Parent Theme)

> [!IMPORTANT]
> **Context Selection Rule:**
> If you are working within a child theme (e.g., inside `egnitech-one-child/`, or if the user's objective is to extend/override the theme for the active site domain), you MUST ignore this file and use `AI_CONTEXT-Child.md` instead.

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
1. **Child Theme First**: Whenever possible, implement features, templates, or styles in the `egnitech-one-child` theme instead of modifying this core.
2. **Native-First**: Always prioritize native WordPress block capabilities and `theme.json` settings over custom CSS or PHP workarounds.
3. **Vanilla JS Only**: No jQuery. Use only ES6+ for performance and modern compatibility.

## Implementation Workflow
For all development tasks involving templates, patterns, or `theme.json`:
1. **Use the `wp-block-theme` skill** located in `egnitech-one/.agent/skills/wp-block-theme/`. It contains the expert logic for HTML conversion, CSS scoping, and the atomic asset pipeline.
2. **Modular Assets**: Follow the "Pattern-First" asset model. Enqueue section-specific assets inside PHP pattern files so they load only when rendered.

## Design System & `theme.json`
The foundation uses a modern `theme.json` (v3) approach:
- **Typography**: Uses fluid sizing (`clamp()`) and system-first fonts. Never hardcode pixel values; reference theme presets (e.g., `var(--wp--preset--font-size--large)`).
- **Spacing**: Slug-based spacing scales (`20` to `80`). 
  - Inside `theme.json`, reference them using `var:preset|spacing|[slug]` tokens.
  - Inside CSS files, reference them using standard CSS variables e.g., `var(--wp--preset--spacing--[slug])`.

## Dark/Light Mode System (CRITICAL)
EgniTech One handles color schemes via a foundational system:
- **Mechanics**: Controls the `:root` pseudo-class using `color-scheme: light dark` and a persistent `data-scheme` attribute on the `<html>` root.
- **Implementation**: DO NOT use media queries for dark modes. Use the native modern CSS `light-dark()` function.
- **Example**: `color: light-dark(var(--wp--preset--color--base), var(--wp--preset--color--contrast));`
- **Child Theme Behavior**: Disabled by default for active child themes to prevent visual regressions.
  - To check support programmatically, use `egnitech_one_is_dark_mode_enabled()`.
  - Child themes can opt-in via PHP (`add_theme_support('egnitech-one-dark-mode')`) or via `theme.json` (`"settings": { "custom": { "darkMode": true } }`).
  - When disabled, `:root` color-scheme is forced to `light` to natively resolve all `light-dark()` colors to their light mode values with zero runtime overhead.

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

## Coding Standards
1. **Text Domain**: Always use `egnitech-one` for core localization.
2. **CSS Scoping (MANDATORY)**: Scope all custom CSS strictly to the block's specific class or variation class (e.g., `.is-style-{slug}`). Avoid global rules or catch-all page wrappers to prevent style bleed.
3. **Safe Containers**: Use `core/group` for complex layouts instead of `core/columns` to avoid Site Editor validation errors.
4. **SVGs**: Provide explicit `width` and `height` attributes on inline `<svg>` tags to prevent layout shifts in the Site Editor.
5. **PHP 8.3 & WP 7.0 Compliance**: All core PHP files (such as `functions.php`, files in `inc/`) must include `declare(strict_types=1);` on line 2, and declare strict types for all parameters and return types. Any `theme.json` must use `"version": 3`.

---
*Final AI Reminder*: This is the CORE theme. Changes here affect all child-theme instances. Maintain stability and extreme performance.
