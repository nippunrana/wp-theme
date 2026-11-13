# Using a Child Theme with EgniTech One

A child theme holds the changes one site needs (its own patterns, templates, styles, and PHP) without touching the EgniTech One core. Every site that uses a child theme still runs the same `egnitech-one` parent, so core fixes reach all sites at once.

**This repository ships no child theme.** It holds only the `egnitech-one` parent theme. When a site needs one, create it with [create-child-theme.md](create-child-theme.md). Each child theme is made per site and named after that site (e.g. `truinterview` for truinterview.in). It lives only on that site's server and is never committed to this repo.

> A site does not need a child theme. homegears.in, for example, runs `egnitech-one` directly.

---

## Installing and activating

1. Make sure `egnitech-one` is installed in `wp-content/themes/`. The child theme does not work without it.
2. Put the child theme folder next to it: `wp-content/themes/<child-slug>/`.
3. In **Appearance > Themes**, activate the child theme. Keep the parent installed but inactive.
4. **Appearance > Theme Options** keeps working. Those settings belong to the parent and apply to the child unchanged.

---

## How a child theme changes the site

| To change | Do this in the child theme |
| :--- | :--- |
| A template or template part | Copy the file from the parent to the same path in the child (e.g. `templates/page.html`, `parts/header.html`) and edit the copy. The child's file wins. |
| Design tokens (`theme.json`) | Add only what differs. Settings merge with the parent key by key, **but arrays such as `palette`, `fontSizes` and `spacingSizes` replace the parent's array completely**. Copy the parent entries first, then add yours. |
| Styles | The parent loads its own `style.css`, then the child's `style.css` after it, so the child's rules win. **Never** enqueue the parent's `style.css` from the child: it would load twice. |
| A section or pattern | Add it under `patterns/`, with its CSS/JS beside it (`patterns/<slug>/style.css`, `index.js`), registered through `register_block_style()`. Prefix pattern slugs and categories with the child's slug. |
| PHP behaviour | Add it to the child's `functions.php`. The child's `functions.php` loads **before** the parent's, so any parent function wrapped in `if ( ! function_exists() )` can be replaced by defining it first. |

The `wp-block-theme` skill (`egnitech-one/.agent/skills/wp-block-theme/`) has the full procedures for templates, patterns, and the asset pipeline.

---

## Dark mode

Dark mode works the same with or without a child theme: every site is **light only** until **Dark Mode** is switched on under **Appearance > Theme Options > General** and saved. When it is off, the theme forces `color-scheme: light`, and every `light-dark()` value shows its light colour.

There is no code switch. Don't use `add_theme_support()` or a `theme.json` flag for it, and **never** define `egnitech_one_is_dark_mode_enabled()` in the child theme: the child's version would replace the parent's and ignore the Theme Options switch.

Once it is on, write colours with `light-dark()`, for example `color: light-dark(var(--wp--preset--color--base), var(--wp--preset--color--contrast));`. Never use dark-mode media queries.

---

## Text domains and shared settings

* Use the child's own text domain for every new translatable string. Use `egnitech-one` only for strings the parent already defines.
* reCAPTCHA keys are set once, in the parent's **Theme Options > Integrations** tab. Read them with `egnitech_one_get_recaptcha_settings()`. Only `site_key` may reach the browser; `secret_key` is for server-side checks only.

---

## Rules

* **Never** edit files in `egnitech-one/` while working on a child theme. Changes there reach every site.
* **Never** change `Template: egnitech-one` in the child's `style.css` or any `egnitech_one_*` function name. They point at the parent.
* **Never** define `egnitech_one_is_dark_mode_enabled()` in the child theme. Dark mode is switched only in Theme Options.
* The coding standards (strict types, scoped CSS, no jQuery, sized SVGs) are the parent's. See `egnitech-one/ai-context.md`.
