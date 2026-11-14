# Logo palettes

Code: `inc/palette/` (loaded from `functions.php` in wp-admin only), `assets/js/admin-palettes.js`, `assets/css/admin-palettes.css`. UI: Theme Options > General > Appearance > Palette Suggestions.

## Bans

- **Never apply a palette from the Theme Options form, a `register_setting()` sanitizer or an option hook.** That form rewrites every setting on each save (`theme-options.md`), so the palette would be re-applied, and Site Editor colour edits overwritten, whenever any tab is saved. Applying is its own AJAX action.
- **Never register `egnitech_one_palette_history` or `egnitech_one_palette_applied` with `register_setting()`.** The form does not print them, so the next save of any tab would blank them.
- **Never give the "Also replace colours changed in the Site Editor" switch a `name` attribute.** It would be posted to `options.php` with the form.
- **Never hand an SVG to Imagick.** Rendering SVG can make the server read local files or fetch URLs. SVG logos get the "unreadable" message instead.
- **Never hard-code a text colour on an Accent background, or use Base for it.** Use `var(--egnitech-on-accent, …)`: no single colour reads on every accent (black on the default teal, white on a dark blue).
- **Never add or rename a slot here.** The six theme.json slugs are what every pattern and stylesheet reads. Generated palettes are always `light-dark()` pairs; only "Switch back" may restore a plain Site Editor colour.

## Decisions

- **Extraction runs in PHP (GD, then Imagick), not in the browser** (owner's choice, 15 Nov 2026). Browser canvas reading fails for logos served from a CDN without CORS headers. With neither library, or an unreadable file type, the screen says what is missing and shows the ready-made palettes; it never errors.
- **Brand colours are scored as in Google's Material Color Utilities**: share of the logo within 15° of hue, plus a bonus for vividness. Raw "most used colour" picks the background. Greys, near-black and near-white are never brand colours. A solid *neutral* background is left out of the shares; a *colourful* one is kept, because white lettering on a red square means red is the brand colour.
- **Accent is darkened (light mode) or lightened (dark mode) to 4.5:1 against Surface, and so against Base too**, keeping its hue, because Accent is also used as text and icon colour (links, prices, header icon hovers). A colour that already passes is kept exact.
- **Text drawn on Accent never comes from the palette.** `--egnitech-on-accent` is worked out on every page from the Accent in use, white or black per mode, one of which always reaches 4.5:1 (`egnitech_one_enqueue_on_accent_color` in `inc/enqueue-assets.php`), so it suits any palette, including Site Editor edits. Buttons site-wide are Accent with this text (owner's decision, 15 Nov 2026), hovering to Contrast with Base text.
- **The toggle governs Site Editor edits** (owner's choice). A slot counts as edited when it differs from both `theme.json` and the palette this screen last wrote. "Switch back" from the history always replaces all six, because a history entry is an exact snapshot.
- **History keeps the three palettes most recently replaced**, never the one in use.
- **Choosing "Theme default" removes the palette from Global Styles instead of copying it**, so later `theme.json` palette edits reach the site again.

## Traps

- **Without `unfiltered_html`, WordPress strips `light-dark()` from Global Styles on save.** Apply refuses up front for such users (on multisite, everyone but network admins). If anything else changes a value on save, the old record is written back and an error shown.
- **Extraction results are cached in a transient keyed on the file's path and modification time and on `EGNITECH_ONE_LOGO_COLORS_VERSION`.** Bump that constant when the extraction logic changes, or existing logos keep their old suggestions.
- **`--egnitech-on-accent` is printed only when Accent is a `light-dark()` pair or a plain six-digit hex.** Any other value (a CSS variable, `rgb()`) can't be measured, and buttons fall back to Base text.
