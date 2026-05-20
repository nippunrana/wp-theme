# EgniTech One

EgniTech One is a modern, lightweight, and performance-first WordPress FSE (Full Site Editing) block parent theme. Designed for speed, simplicity, and ease of custom extension, it ships with native dark/light mode support, clean typography, a modular architecture, and zero jQuery dependencies.

## 🚀 Key Features

* **Performance-First (< 50KB Weight):** Asset-optimized codebase keeping page load times minimal and Core Web Vitals scores high.
* **Native Dark/Light Mode:** Integrates native CSS `light-dark()` functionality, respecting browser/OS color scheme preferences. Fully toggleable without layout shifts or FOUC (Flash of Unstyled Content).
* **Zero jQuery Dependency:** Handcrafted utilizing modern ES6+ vanilla JavaScript.
* **FSE Ready:** Built entirely with Full Site Editing capabilities to customize pages, templates, and template parts visually.
* **Custom Admin Options Panel:** A robust settings page under **Appearance > Theme Options** allowing administrators to control layouts, logo widths, typography, footer credits, and other settings.
* **Performance Script Manager:** Allows normal, after-DOM, or 3-second delayed script insertion to optimize tracking/analytics loading and maximize page speed scores.
* **SMTP Integration:** Global SMTP options to securely handle outbound emails via standard WordPress PHPMailer filters.
* **Modern Design Foundations:** System-first font presets, fluid typography (`clamp()`), and slug-based spacing scales defined inside `theme.json` (v3).

---

## 📁 Repository Structure

```text
wp-theme/
├── egnitech-one/                   # Parent Theme Root Directory
│   ├── assets/                     # Scoped CSS, JS, and font resources
│   │   ├── css/                    # Component stylesheet files (Admin, Dark Mode, etc.)
│   │   ├── fonts/                  # Local typography assets (Inter Variable)
│   │   └── js/                     # Modular script assets (toggle, progress bar, etc.)
│   ├── inc/                        # Core PHP sub-modules & class definitions
│   │   ├── admin-options.php       # Theme options settings & UI registration
│   │   ├── custom-scripts.php      # Custom script insertion class
│   │   ├── enqueue-assets.php      # Asset enqueuing logic
│   │   ├── font-manager.php        # Font library & site-editor typography loader
│   │   ├── helpers.php             # Core utility functions
│   │   ├── smtp-config.php         # SMTP mailer configuration hook
│   │   └── theme-setup.php         # General theme features & bloat cleanup hooks
│   ├── parts/                      # FSE HTML header & footer parts
│   ├── patterns/                   # Core block pattern templates (PHP wrapper)
│   ├── templates/                  # Block template layouts (404, archive, home, page, etc.)
│   ├── functions.php               # Theme initialization entrypoint
│   ├── style.css                   # Critical base styles & WP Metadata
│   └── theme.json                  # Global design tokens and settings (version 3)
```

---

## 🛠️ Installation & Requirements

* **WordPress:** Version 6.7 or higher (tested up to 6.9+)
* **PHP:** Version 7.4 or higher (compliant up to PHP 8.3)

1. Clone or upload the `egnitech-one` directory to your WordPress installation's `/wp-content/themes/` directory.
2. Log in to the WordPress Admin dashboard.
3. Navigate to **Appearance > Themes** and activate **EgniTech One**.
4. Configure global options via **Appearance > Theme Options**.

---

## 💻 Development & Child Theme Extension

To extend the theme, it is strongly recommended to use a child theme (e.g., `egnitech-one-child`) to protect the core engine files from being modified directly.

### Guidelines for Child Themes:
1. **Never Modify Parent Files:** All template modifications, custom blocks, and overrides should live inside the child theme directory.
2. **Strict PHP Typing:** All PHP code must start with `declare(strict_types=1);` on line 2, with fully typed parameters and return values.
3. **Vanilla JS Scoping:** Scope custom scripts utilizing the WordPress Interactivity API or modular ES6+. Avoid enqueuing heavy external libraries.
4. **Pattern-First Assets:** Load styling and scripts dynamically inside specific patterns using `register_block_style()` instead of adding massive styling sheets globally.
