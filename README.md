# EgniTech One

[![WordPress](https://img.shields.io/badge/WordPress-v7.0%2B-blue.svg?style=flat-square&logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-v8.4%2B-purple.svg?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg?style=flat-square)](http://www.gnu.org/licenses/gpl-2.0.html)
[![Aesthetics](https://img.shields.io/badge/Performance-%3C%2050KB-brightgreen.svg?style=flat-square)](#)

**EgniTech One** is a modern, lightweight, and performance-first WordPress FSE (Full Site Editing) block parent theme. Designed for speed, flexibility, and painless child-theme extension, it delivers native light/dark mode transitions, fluid typography, a modular administration system, and zero jQuery dependencies.

---

## 📖 Table of Contents
* [Key Highlights](#-key-highlights)
* [File Structure](#-file-structure)
* [Architectural Deep Dives](#-architectural-deep-dives)
  * [1. Native Light/Dark Mode Engine](#1-native-lightdark-mode-engine)
  * [2. Performance Script Manager](#2-performance-script-manager)
  * [3. SMTP Global Pipeline](#3-smtp-global-pipeline)
  * [4. Layout Synchronization](#4-layout-synchronization)
* [Admin Theme Options Registry](#-admin-theme-options-registry)
* [Installation & Requirements](#%EF%B8%8F-installation--requirements)
* [Developer Guidelines & Child Themes](#-developer-guidelines--child-themes)
  * [Coding Standards](#coding-standards)
  * [Activating Dark Mode](#activating-dark-mode)

---

## 🚀 Key Highlights

*   **Ultralight Payload (< 50KB):** Asset-optimized codebase keeping page load times minimal and Core Web Vitals scores high.
*   **FSE Native (Version 3 `theme.json`):** Visually customize templates, parts, and styles in the Site Editor without drag-and-drop page builders.
*   **Anti-FOUC Color Scheme:** Uses native `light-dark()` CSS styling paired with a render-blocking head snippet to ensure zero Flash of Unstyled Content (FOUC).
*   **Performance Script Manager:** Allows normal, DOMContentLoaded-deferred, or 3-second delayed script insertion to optimize tracking/analytics loading.
*   **Robust Options Panel:** Dedicated dashboard under **Appearance > Theme Options** to configure layout constraints, paddings, metadata visibility, custom logos, SMTP, and scripts.

---

## 📁 File Structure

```text
wp-theme/
└── egnitech-one/                   # Parent Theme Root Directory
    ├── assets/                     # Scoped CSS, JS, and font resources
    │   ├── css/                    # Component stylesheets (admin, dark-mode-toggle, scroll-top, etc.)
    │   ├── fonts/                  # Local typography assets (Inter Variable)
    │   └── js/                     # Modular vanilla scripts (toggle, scroll, progress-bar)
    ├── docs/child-theme/           # How to use and create a child theme (none ships in this repo)
    ├── inc/                        # Core PHP helper files and configuration modules
    │   ├── admin-options.php       # Theme Options panel structure, callbacks, and validation
    │   ├── custom-scripts.php      # Custom Script loader and base64 worker injector
    │   ├── enqueue-assets.php      # Global and contextual style/script enqueuing
    │   ├── font-manager.php        # Font library cataloging and Global Styles parser
    │   ├── helpers.php             # Core utility APIs and option getters
    │   ├── smtp-config.php         # SMTP mailer hooks filtering PHPMailer
    │   └── theme-setup.php         # Theme capability registration and bloat removal
    ├── parts/                      # FSE HTML template parts (header, footer, sidebar)
    ├── patterns/                   # Dynamic block patterns (header, footer, query, sidebar)
    ├── templates/                  # Block template layouts (404, page, single, archive, index, etc.)
    ├── functions.php               # Theme bootstrapping entrypoint
    ├── style.css                   # Critical base styles & WordPress Theme Metadata
    └── theme.json                  # Global design tokens, layout sizes, and spacing definitions
```

---

## 🛠️ Architectural Deep Dives

### 1. Native Light/Dark Mode Engine
Color variables are defined inside [theme.json](egnitech-one/theme.json) using the CSS `light-dark()` function:
```json
{
  "color": "light-dark(#ffffff, #0f0f0f)",
  "name": "Base",
  "slug": "base"
}
```
*   **Off by default:** Sites are light only until **Dark Mode** is switched on under **Appearance > Theme Options > General** and saved (see [Activating Dark Mode](#activating-dark-mode)).
*   **Prevention of FOUC:** The helper [egnitech_one_color_scheme_script()](egnitech-one/inc/enqueue-assets.php) binds to the `wp_head` action at priority `5`. It immediately reads local storage, the theme options default preference, or system-wide media preferences, assigning `data-scheme` and `color-scheme` to `<html>` before the DOM renders.
*   **Theme Toggle:** Triggered by [egnitech-one/assets/js/dark-mode-toggle.js](egnitech-one/assets/js/dark-mode-toggle.js), updating local storage and root document attributes gracefully with transition layers.

### 2. Performance Script Manager
Managed via the [EgniTech_One_Custom_Scripts](egnitech-one/inc/custom-scripts.php) class, scripts added inside the options panel are categorized, consolidated into 6 internal blocks, and dynamically handled:
1.  **Normal:** Rendered immediately on `wp_head` or `wp_footer`.
2.  **After DOM:** Embedded as base64-encoded strings and decoded using a single vanilla JS worker dynamically appending nodes on `DOMContentLoaded` via `document.createRange().createContextualFragment()`.
3.  **Delayed:** Injected exactly `3000ms` after page load to bypass render blocks and protect Core Web Vitals (LCP/INP).

### 3. SMTP Global Pipeline
Bypasses default server mail relays by hooking directly into `phpmailer_init` inside [inc/smtp-config.php](egnitech-one/inc/smtp-config.php). If enabled:
*   Standard PHPMailer variables are filtered dynamically with values specified under the **SMTP Settings** panel (Port, Host, Encryption, Username, Password).
*   Enforces global sender name and email sanitization across all outbound notifications.

### 4. Layout Synchronization
Modifying layout dimensions in the WordPress Admin Options automatically synchronizes with the Block Editor. 
The helper [egnitech_one_sync_layout_to_global_styles()](egnitech-one/inc/admin-options.php) decodes the active theme's global styles custom post type (`wp_global_styles`), updates the `settings.layout.contentSize` or `wideSize` parameters, and updates the database entry to ensure layout widths mirror perfectly between front-end and editor styling.

---

## 🎛️ Admin Theme Options Registry

These settings are managed under **Appearance > Theme Options** in the WordPress admin dashboard.

| Category | Option Key | Type | Default | Purpose / Synchronized Output |
| :--- | :--- | :--- | :--- | :--- |
| **General** | `egnitech_one_content_width` | `int` | `900` | Syncs content width constraints to Global Styles (`wp_global_styles` post). |
| | `egnitech_one_wide_width` | `int` | `1280` | Syncs wide-alignment constraints to Global Styles. |
| | `egnitech_one_scroll_to_top` | `string` | `yes` | Loads a smooth scroll-to-top layout helper element. |
| | `egnitech_one_dark_mode_enabled` | `string` | `no` | Turns on light/dark mode and the visitor toggle; off means light only. Saving a new value clears the page cache. |
| | `egnitech_one_dark_mode_default` | `string` | `system` | Initial mode choice when dark mode is on: `system`, `light`, or `dark`. |
| | `egnitech_one_reading_progress` | `string` | `yes` | Toggles progress bar tracking reading percentage on posts. |
| | `egnitech_one_breadcrumbs` | `string` | `no` | Conditionally outputs standard post path navigation. |
| **Header** | `egnitech_one_sticky_header` | `string` | `yes` | Forces first block navigation to remain sticky on top. |
| | `egnitech_one_dark_logo_url` | `string` | `""` | Dedicated dark mode logo url fallback. |
| | `egnitech_one_light_logo_id` | `int` | `0` | Default site logo ID synced directly with `custom_logo` theme mod. |
| | `egnitech_one_logo_width_desktop` | `int` | `0` | Custom desktop logo scaling max-width (px). |
| | `egnitech_one_logo_width_mobile` | `int` | `0` | Custom mobile logo scaling max-width (px). |
| **Blog** | `egnitech_one_blog_layout` | `string` | `list` | Layout rendering choice: `list`, `grid-2`, or `grid-3`. |
| | `egnitech_one_sidebar_position` | `string` | `none` | Layout configurations: `none`, `left`, or `right` sidebar position. |
| | `egnitech_one_meta_author` | `string` | `yes` | Toggles theme rendering for author metadata on pages. |
| | `egnitech_one_meta_date` | `string` | `yes` | Toggles theme rendering for date metadata on pages. |
| **Footer** | `egnitech_one_footer_copyright` | `string` | `""` | Customizable HTML copyright statement. |
| | `egnitech_one_footer_credits` | `string` | *EgniTech link* | Customizable developer metadata/credits markup. |
| **SMTP** | `egnitech_one_smtp_enabled` | `string` | `no` | Toggle SMTP mail pipeline filter activation. |
| | `egnitech_one_smtp_host` | `string` | `""` | Outbound SMTP server hostname. |
| | `egnitech_one_smtp_port` | `int` | `587` | Outbound mail port (587, 465, etc.). |
| | `egnitech_one_smtp_encryption` | `string` | `tls` | TLS, SSL, or None security specification. |
| | `egnitech_one_smtp_auth` | `string` | `yes` | Toggle SMTP server user authentication logic. |

---

## 🛠️ Installation & Requirements

*   **WordPress:** Version 7.0 or higher
*   **PHP:** Version 8.4 or higher

1.  Download or clone the `egnitech-one` theme folder.
2.  Upload it to `/wp-content/themes/` inside your WordPress installation directory.
3.  Log in to the WordPress Admin dashboard.
4.  Navigate to **Appearance > Themes** and activate **EgniTech One**.
5.  Configure global options via **Appearance > Theme Options**.

This repository ships no child theme. If a site needs one, create it with [egnitech-one/docs/child-theme/create-child-theme.md](egnitech-one/docs/child-theme/create-child-theme.md).

---

## 💻 Developer Guidelines & Child Themes

### Coding Standards
For developers building templates, custom block styles, or patterns inside child themes:
1.  **PHP Typing:** Every PHP file must begin with `declare(strict_types=1);` on line 2. All functions must define parameter types and return type declarations.
2.  **Asset Scoping:** Custom styles must be registered utilizing block variation classes (e.g., `.is-style-{slug}`) to avoid cascading global pollution.
3.  **No jQuery:** Scripts must rely on vanilla ES6+ module parameters or the WordPress Interactivity API.
4.  **Inline SVGs:** Always define explicit `width` and `height` properties on inline SVGs to avoid Layout Shifts (CLS) in the site editor.
5.  **Layouts:** Use `core/group` for columns/grid wrapping to avoid layout validator warnings in the block editor.

### Activating Dark Mode
Every site is **light only** by default, with or without a child theme. To give visitors light and dark mode with a toggle:

1.  Go to **Appearance > Theme Options > General**.
2.  In the **Appearance** card, switch **Dark Mode** on and click **Save All Changes**.
3.  After saving, **Dark Mode Default** appears, where you pick the scheme new visitors see first.

Saving the switch with a new value clears the page cache, so visitors see the change straight away. There is no code switch: don't use `add_theme_support()` or a `theme.json` flag, and never define `egnitech_one_is_dark_mode_enabled()` in a child theme (see [egnitech-one/docs/child-theme/README.md](egnitech-one/docs/child-theme/README.md)).

When disabled, the theme forces the document `color-scheme` to `light`, natively rendering all `light-dark()` values to their fallback mode with zero parsing delay.


