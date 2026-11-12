# Dark and light mode: what the code hides

The bans and the owner's decisions are in `ai-context.md` (Dark/Light Mode System). They are not repeated here. This file adds only the traps.

Code: `egnitech_one_color_scheme_script` and `egnitech_one_enqueue_dark_mode` in `inc/enqueue-assets.php`, `egnitech_one_filter_theme_json` in `inc/theme-setup.php`, `assets/js/dark-mode-toggle.js`, the palette and `styles.css` in `theme.json`.

- **The scheme is chosen in the browser, never in PHP.** An inline script in `<head>` reads the visitor's saved choice from `localStorage` and sets `data-scheme` and `color-scheme` on `<html>` before the page paints. **Never** move that choice to a cookie read by PHP or to a body class: pages come from a shared page cache (`page-cache.md`), so one visitor's scheme would be served to everyone.
- **Light-only is enforced twice, and the two halves cover different cases.** With dark mode off, the inline script forces `light`, and `egnitech_one_filter_theme_json` rewrites the `color-scheme` line in `theme.json`'s `styles.css`. The rewrite is the half that still works with JavaScript off.
- **The rewrite is an exact text match** on `color-scheme: light dark;`. Reformatting that line in `theme.json` (different spacing, a different order) silently turns the rewrite off.
- **With a child theme that has its own `theme.json`, the rewrite does nothing.** WordPress runs the `wp_theme_json_data_theme` filter on the child's file alone and merges the parent's in afterwards, so the filter never sees the parent's `color-scheme: light dark` line. Such a site is light-only through the inline script alone: a visitor with JavaScript off and a dark device gets dark colours while dark mode is "off".
- **`data-scheme` is what CSS keys on for anything `light-dark()` cannot express.** The light and dark logos are swapped this way in `style.css`. The attribute is set even while dark mode is off (to `light`).
- **Only the first `.egnitech-dark-mode-toggle` on a page works.** `assets/js/dark-mode-toggle.js` binds one button. The header's toggle comes from the `[egnitech_dark_mode_toggle]` shortcode, which prints nothing while dark mode is off.
