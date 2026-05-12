---
name: wp-block-theme
description: >
  Expert WordPress Full Site Editing (FSE) block theme and template developer. Use this skill
  whenever the user wants to create, modify, or scaffold any part of a WordPress block theme:
  new templates (custom page templates, archive views, 404 pages), template parts (header,
  footer), block patterns (PHP-based reusable sections), or child theme extensions. Also triggers
  when the user wants to configure theme.json (colors, typography, spacing, layout), integrate
  custom CSS/JS assets scoped to a template, set up modular asset enqueuing in functions.php,
  or debug Site Editor sync issues. CRITICALLY: use this skill whenever the user provides raw
  HTML, CSS, or JS and asks to "convert it to a block theme template", "turn this into a
  WordPress template", "make this work in the Site Editor", or "add this design as a new page".
  If the user pastes HTML markup, a Figma export, or a static design file and wants it inside
  WordPress FSE — this is the skill to use.
---

# WordPress Block Theme Developer

A skill for creating and extending WordPress Full Site Editing (FSE) block themes — templates,
template parts, block patterns, theme.json design systems, and modular asset pipelines.

The most common entry point is **converting an existing HTML/CSS/JS design into a block theme
template**. This is covered in detail in Section 0. All other sections cover the underlying
building blocks you'll use during that process.

## How to approach a request

1. **Identify the deliverable** — Is this an HTML→template conversion, a new template from
   scratch, a pattern, a template part, a theme.json change, or an asset pipeline fix?
2. **Read the project context first** — Always read `AI_CONTEXT-Child.md` (and the parent's
   equivalent if it exists) before writing any files. This prevents violating child-theme rules.
3. **Follow the architecture rules** in `references/architecture.md` (read it now if you haven't).
4. **Execute** using the implementation checklists below.
5. **Verify** — run a quick filesystem audit and remind the user of the Site Editor "Clear
   Customizations" trick if they report that file changes are not showing.

---

## Core Architectural Rules (memorise these)

- **Never touch the parent theme directory.** All work goes in the child theme.
- **Every custom template must be registered** in the child theme's `theme.json` under
  `customTemplates` — otherwise WordPress won't offer it in the Page Attributes panel.
- **Every raw HTML section in a template or pattern must be wrapped** in a
  `<!-- wp:html --> ... <!-- /wp:html -->` block comment, or it will be stripped by the block
  validator.
- **CSS scoping is mandatory for landing page templates.** Wrap all content in a unique class
  (e.g. `.my-template-wrapper`) so custom resets don't bleed into the Block Editor UI.
- **Atomic Asset Loading**: For complex sections, use the "Pattern-First" asset model. Each
  pattern gets its own directory (e.g., `patterns/faq-section/`) containing `style.css` and
  `index.js`.
- **Dynamic Enqueuing**: Use the `render_block` filter in `functions.php` to conditionally
  enqueue pattern assets ONLY when they are rendered on the page.
- **Editor styles**: After adding a scoped stylesheet, register it in `functions.php` with
  `add_editor_style()` so the Site Editor iframe matches the frontend.


---

## 0. Converting HTML/CSS/JS into a Block Theme Template

This is the primary workflow when the user provides a finished design (HTML export, static page,
Figma handoff, etc.) and wants it running inside WordPress FSE.

### Why this process exists

WordPress's block editor strips any raw HTML it doesn't recognise as a registered block. A
static HTML file dropped straight into a template file will be silently gutted. The conversion
process wraps the HTML correctly, scopes the CSS so it doesn't pollute the Site Editor, and
wires up asset loading so everything works on the frontend AND inside the editor iframe.

### Input: what you receive from the user

The user will typically provide one or more of:

- A single HTML file (may include inline `<style>` and `<script>` tags)
- Separate HTML + CSS + JS files
- A description of the design with a slug/name for the new template

### Step 0 — Gather inputs & agree on a slug

Before writing any files, confirm:

1. **Template slug** — kebab-case identifier, e.g. `peptide-landing`. This becomes the filename
   and the CSS scoping class prefix. If the user doesn't provide one, derive it from the page
   title.
2. **Has images?** — If the HTML references local image paths, ask the user where they are or
   use placeholder paths that the user can swap out.
3. **Any WordPress dynamic content?** — Does the design need a blog feed, a form, a logged-in
   check? Flag these now; they'll need native WP blocks or shortcodes, not raw HTML.

### Step 1 — Analyse and decompose the HTML

Read the HTML and identify its logical sections. Common sections:

| Section type    | Examples                                    |
|-----------------|---------------------------------------------|
| Hero / banner   | Full-width header with headline + CTA       |
| Features grid   | Icon cards, 3-column benefit lists          |
| Testimonials    | Quote carousel, review cards                |
| CTA / footer    | Email signup, contact bar                   |
| Navigation      | Top nav — consider using `wp:navigation`    |

Each section becomes a **sub-pattern**. This keeps files manageable and makes each section
independently editable in the Site Editor.

### Step 2 — Extract and scope the CSS

1. Pull all CSS out of the HTML (inline `<style>` blocks + any referenced stylesheets).
2. Remove any CSS resets or `body`/`html` rules — these will conflict with WordPress.
3. Wrap **every rule** in the scoping class: `.{slug}-wrapper { ... }`.

   **Before:**
   ```css
   .hero { background: #1a1a2e; padding: 80px 0; }
   h1 { font-size: 3rem; color: white; }
   ```

   **After:**
   ```css
   .{slug}-wrapper .hero { background: #1a1a2e; padding: 80px 0; }
   .{slug}-wrapper h1 { font-size: 3rem; color: white; }
   ```

4. Save scoped CSS to the pattern's directory: `patterns/{slug}-{section}/style.css`.
   - If the section is a one-off for a specific template, you can still use
     `templates/{slug}/style.css` for "core" styles (layout, variables, navigation).


### Step 3 — Extract the JavaScript

1. Pull all JS out of the HTML (inline `<script>` blocks + external scripts).
2. Wrap in a `DOMContentLoaded` listener if not already:
   ```js
   document.addEventListener('DOMContentLoaded', function () {
     // original JS here
   });
   ```
3. Save to `patterns/{slug}-{section}/index.js`.

4. If the original JS depends on jQuery, note `'jquery'` as a dependency in `wp_enqueue_script`.

### Step 4 — Fix image paths

Any `<img src="...">` with a local path must use a PHP echo for the theme URL:

```php
<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.webp" alt="Hero">
```

Copy image files to `assets/images/` in the child theme.

### Step 5 — Build the master pattern

Create `patterns/{slug}.php`. Structure:

```php
<?php
/**
 * Title: {Human Readable Title}
 * Slug: egnitech-one-child/{slug}
 * Categories: egnitech-one-child
 * Keywords: {keywords from section names}
 * Inserter: false
 */
?>
<div class="{slug}-wrapper">

  <!-- wp:pattern {"slug":"egnitech-one-child/{slug}-hero"} /-->
  <!-- wp:pattern {"slug":"egnitech-one-child/{slug}-features"} /-->
  <!-- wp:pattern {"slug":"egnitech-one-child/{slug}-cta"} /-->

</div>
```

`Inserter: false` hides the master pattern from the Block Inserter — it's only called by the
template, not inserted manually by editors.

### Step 6 — Build sub-pattern files

For each logical section identified in Step 1, create `patterns/{slug}-{section}.php`.

**Critical rule:** Every contiguous block of raw HTML must be wrapped in `<!-- wp:html -->`.
Do NOT put the entire section in one giant `wp:html` block if it mixes raw HTML and native WP
blocks — wrap only the raw parts.

```php
<?php
/**
 * Title: {Title} — Hero
 * Slug: egnitech-one-child/{slug}-hero
 * Categories: egnitech-one-child
 * Keywords: hero, banner
 * Inserter: false
 */
?>
<!-- wp:html -->
<section class="hero">
  <h1>Your Headline</h1>
  <p>Subheadline text goes here.</p>
  <a href="#" class="cta-btn">Get Started</a>
</section>
<!-- /wp:html -->
```

### Step 7 — Create the template entry point

Create `templates/{slug}.html`:

```html
<!-- wp:pattern {"slug":"egnitech-one-child/{slug}"} /-->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
```

The pattern carries all the visual content; `wp:post-content` allows the editor to append
additional blocks if needed.

### Step 8 — Register in theme.json

Add to the `customTemplates` array in `theme.json`:

```json
"customTemplates": [
  {
    "name": "{slug}",
    "title": "{Human Readable Title}",
    "postTypes": ["page"]
  }
]
```

### Step 9 — Wire up asset enqueuing in functions.php

Add two things:

**Inside `after_setup_theme` hook:**
```php
add_editor_style( 'templates/{slug}/style.css' );
```

**The Asset Registry Pattern:**
```php
function egnitech_one_child_pattern_asset_loader( $block_content, $block ) {
    if ( isset( $block['attrs']['slug'] ) ) {
        $slug = $block['attrs']['slug'];
        
        $pattern_assets = array(
            'egnitech-one-child/faq-section' => array(
                'handle' => 'faq-section',
                'style'  => '/patterns/faq-section/style.css',
                'script' => '/patterns/faq-section/index.js',
            ),
            // Add more patterns here...
        );

        if ( isset( $pattern_assets[ $slug ] ) ) {
            $assets = $pattern_assets[ $slug ];
            if ( isset( $assets['style'] ) ) {
                wp_enqueue_style( $assets['handle'] . '-style', get_stylesheet_directory_uri() . $assets['style'] );
            }
            if ( isset( $assets['script'] ) ) {
                wp_enqueue_script( $assets['handle'] . '-script', get_stylesheet_directory_uri() . $assets['script'], array(), '1.0.0', true );
            }
        }
    }
    return $block_content;
}
add_filter( 'render_block', 'egnitech_one_child_pattern_asset_loader', 10, 2 );
```


### Step 10 — Final checklist before handing off

- [ ] `templates/{slug}.html` exists and references the master pattern
- [ ] `patterns/{slug}.php` (master) exists with `Inserter: false`
- [ ] All section sub-patterns exist in `patterns/`
- [ ] All raw HTML is wrapped in `<!-- wp:html --> ... <!-- /wp:html -->`
- [ ] `templates/{slug}/style.css` exists with ALL rules scoped to `.{slug}-wrapper`
- [ ] `templates/{slug}/index.js` exists (even if empty, to avoid 404)
- [ ] Image paths use `get_stylesheet_directory_uri()` — no hardcoded URLs
- [ ] `theme.json` has the `customTemplates` entry
- [ ] `functions.php` enqueues the style and script with an `is_page_template()` guard
- [ ] `functions.php` calls `add_editor_style()` inside `after_setup_theme`
- [ ] Remind the user: assign the template to a page in Page Attributes, then clear Site Editor
      customisations if the editor doesn't reflect changes

### Common conversion pitfalls

| Problem | Symptom | Fix |
|---|---|---|
| Raw HTML stripped | Content disappears in editor | Wrap in `<!-- wp:html -->` |
| CSS bleeds into editor UI | Styles affect menus/toolbars | Scope every rule to `.{slug}-wrapper` |
| Body/html resets cause layout shift | Page jumps or wrong font | Remove `body`, `html`, `*` resets from scoped CSS |
| Images 404 | Broken images on frontend | Use `get_stylesheet_directory_uri()` |
| JS runs before DOM is ready | `null` errors in console | Wrap in `DOMContentLoaded` listener |
| Template not in dropdown | Can't assign template to page | Check `customTemplates` in `theme.json` |
| Editor doesn't match frontend | WYSIWYG mismatch | Call `add_editor_style()` in `after_setup_theme` |
| Animations fire in editor | Jarring editor experience | Gate animations on `document.body.classList.contains('wp-admin')` |

---

## 1. Creating a New Custom Page Template (from scratch)

### Files to create / modify

| File | Action |
|---|---|
| `templates/{slug}.html` | New — the template entry point |
| `templates/{slug}/style.css` | New — scoped styles for this template |
| `templates/{slug}/index.js` | New (if JS needed) |
| `patterns/{slug}.php` | New — the main pattern with all HTML content |
| `theme.json` | Modify — add entry under `customTemplates` |
| `functions.php` | Modify — enqueue assets + add_editor_style |

### Step-by-step checklist

- [ ] **Determine the slug** — kebab-case, e.g. `my-landing-page`.
- [ ] **Create `templates/{slug}.html`** — see Template HTML format below.
- [ ] **Create the main pattern** in `patterns/{slug}.php` — see Pattern PHP format.
- [ ] **Create `templates/{slug}/style.css`** — scope ALL rules to `.{slug}-wrapper`.
- [ ] **Register in `theme.json`** — add to `customTemplates` array.
- [ ] **Enqueue assets in `functions.php`** — use `is_page_template('{slug}')` guard.
- [ ] **Add editor style** — call `add_editor_style('templates/{slug}/style.css')` inside
  the `after_setup_theme` action hook in `functions.php`.

### Template HTML format (`templates/{slug}.html`)

```html
<!-- wp:pattern {"slug":"egnitech-one-child/{slug}"} /-->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
```

The pattern carries all the visual content; `wp:post-content` renders any blocks the editor adds.

### Pattern PHP format (`patterns/{slug}.php`)

```php
<?php
/**
 * Title: My Landing Page
 * Slug: egnitech-one-child/{slug}
 * Categories: egnitech-one-child
 * Keywords: landing, page, {keywords}
 */
?>
<div class="{slug}-wrapper">

  <!-- wp:html -->
  <section class="hero">
    <h1>Headline</h1>
  </section>
  <!-- /wp:html -->

  <!-- wp:pattern {"slug":"egnitech-one-child/another-pattern"} /-->

</div>
```

**Critical:** Every raw HTML block must be inside `<!-- wp:html --> ... <!-- /wp:html -->`.
Native WP blocks (like `wp:pattern`, `wp:image`, `wp:heading`) go directly in the file without
the `wp:html` wrapper.

### theme.json registration

```json
"customTemplates": [
  {
    "name": "{slug}",
    "title": "Human-Readable Title",
    "postTypes": ["page"]
  }
]
```

### functions.php asset enqueue pattern

```php
function egnitech_one_child_custom_assets() {
    if ( is_page_template( '{slug}' ) ) {
        wp_enqueue_style(
            '{slug}-style',
            get_stylesheet_directory_uri() . '/templates/{slug}/style.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            '{slug}-script',
            get_stylesheet_directory_uri() . '/templates/{slug}/index.js',
            array(),
            '1.0.0',
            true  // load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_child_custom_assets' );
```

---

## 2. Creating a Template Part (Header / Footer / etc.)

Template parts live in `parts/` and must be registered in `theme.json` under `templateParts`.

### Files

| File | Action |
|---|---|
| `parts/{name}.html` | New — the part markup |
| `theme.json` | Modify — add to `templateParts` |

### Part HTML format

A part is a standalone HTML file with standard WP block markup. No PHP wrapper needed unless
you're creating a PHP pattern to drive it.

```html
<!-- wp:group {"tagName":"header","className":"site-header"} -->
<header class="wp-block-group site-header">
  <!-- wp:site-logo /-->
  <!-- wp:navigation /-->
</header>
<!-- /wp:group -->
```

### theme.json registration

```json
"templateParts": [
  {
    "name": "{name}",
    "title": "Main Header",
    "area": "header"
  }
]
```

Valid `area` values: `header`, `footer`, `uncategorized`.

---

## 3. Creating a Standalone Block Pattern (Section / Component)

Patterns are reusable sections inserted via the Block Inserter or called from templates.

### Pattern PHP header (required)

```php
<?php
/**
 * Title: Hero Section
 * Slug: egnitech-one-child/hero-section
 * Categories: egnitech-one-child
 * Keywords: hero, banner, header
 * Block Types: core/group
 */
?>
```

All five header fields are recommended. `Block Types` is optional but improves discoverability.

### Image paths in patterns

Always use PHP to output image URLs — never hardcode paths:

```php
<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.png" alt="Hero">
```

---

## 4. theme.json Design System

Read `references/theme-json.md` for a full reference on color palettes, typography, spacing,
and block-specific targeting. Key rules:

- **Version 3** is current. Always use `"$schema": "https://schemas.wp.org/wp/6.7/theme.json"`.
- **CSS variable naming**: `var(--wp--preset--color--{slug})`, `var(--wp--preset--spacing--{slug})`.
- **Inject raw CSS**: Use `styles.css` property in `theme.json` for global rules, or
  `styles.blocks["core/group"].css` for block-scoped rules.
- **Child theme inheritance**: The child's `theme.json` merges with the parent's — it does not
  replace it. Only override what you need.

---

## 5. Debugging Common Issues

| Symptom | Cause | Fix |
|---|---|---|
| Template not in Page Attributes dropdown | Not registered in `theme.json` `customTemplates` | Add the entry; check the `name` matches the filename (without `.html`) |
| Raw HTML disappears in editor | Missing `<!-- wp:html -->` wrapper | Wrap all non-block HTML in `wp:html` comment tags |
| CSS not loading on frontend | Missing `wp_enqueue_style` / wrong handle | Check `functions.php` enqueue and `is_page_template()` guard |
| Editor doesn't match frontend | `add_editor_style()` not called | Add it inside `after_setup_theme` hook |
| theme.json changes ignored | Database override active | Go to Site Editor → Styles → ⋮ menu → "Reset to defaults" / "Clear Customizations" |
| Pattern images broken | Hardcoded path | Use `get_stylesheet_directory_uri()` in PHP pattern |
| Block validation error | Nesting issue or stray HTML | Validate markup; ensure every raw HTML is inside `wp:html` |

---

## 6. Modular Sub-Pattern Architecture

When a template is complex (e.g. a full landing page), decompose it into sub-patterns:

```
patterns/
  my-landing-page.php        ← master pattern (assembles sub-patterns)
  faq-section/
    style.css                ← Scoped CSS for FAQ only
    index.js                 ← FAQ JS (e.g. accordion)
  cta-section/
    style.css                ← Scoped CSS for CTA only
  hero-section/
    style.css                ← Scoped CSS for Hero only
```

This ensures that if you use the `faq-section` pattern on a different page, only the FAQ CSS/JS
loads, not the entire landing page bundle.


The master pattern calls each sub-pattern:

```php
<!-- wp:pattern {"slug":"egnitech-one-child/my-lp-hero"} /-->
<!-- wp:pattern {"slug":"egnitech-one-child/my-lp-features"} /-->
<!-- wp:pattern {"slug":"egnitech-one-child/my-lp-cta"} /-->
```

This keeps individual files manageable and makes each section independently editable in the
Site Editor.

---

## Reference Files

- `references/theme-json.md` — Full theme.json schema reference (colors, typography, spacing, layout)
- `references/architecture.md` — Detailed FSE architecture rules and WordPress template hierarchy
- `references/html-conversion.md` — Detailed HTML→block template conversion examples and edge cases
