# FSE Architecture Reference — WordPress Block Themes

## Table of Contents
1. [Theme Initialization Requirements](#theme-initialization-requirements)
2. [Directory Structure](#directory-structure)
3. [Template Hierarchy & Fallback Logic](#template-hierarchy--fallback-logic)
4. [Block Markup Rules](#block-markup-rules)
5. [PHP Pattern Rules](#php-pattern-rules)
6. [Asset Pipeline (functions.php)](#asset-pipeline-functionsphp)
7. [Site Editor Sync Behaviour](#site-editor-sync-behaviour)
8. [Child Theme Rules](#child-theme-rules)
9. [Query Loop Block](#query-loop-block)

---

## Theme Initialization Requirements

A theme MUST have all three of the following to appear in the WordPress dashboard:

| File | Purpose |
|---|---|
| `style.css` | Theme metadata (Theme Name, Version, Author, etc.) |
| `theme.json` | Global settings and styles engine |
| `templates/index.html` | Absolute fallback template — required for theme validity |

`functions.php` is optional in a pure block theme but almost always needed for enqueueing custom
assets, registering pattern categories, and adding editor styles.

---

## Directory Structure

```
theme-name/
├── style.css                     ← Theme metadata header
├── theme.json                    ← Global settings & styles
├── functions.php                 ← Optional: asset enqueueing, hooks
│
├── templates/                    ← Page-level view templates
│   ├── index.html                ← Required: absolute fallback
│   ├── singular.html             ← Posts + static pages (unified)
│   ├── single.html               ← Individual posts only
│   ├── page.html                 ← Static pages only
│   ├── archive.html              ← Archive/category views
│   ├── search.html               ← Search results
│   ├── 404.html                  ← Error page
│   └── {custom-slug}.html        ← Custom templates (registered in theme.json)
│
├── parts/                        ← Reusable template parts
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html
│
├── patterns/                     ← Block patterns (PHP files)
│   └── my-section.php
│
└── assets/
    ├── fonts/
    ├── images/
    └── css/
```

For **modular templates** (custom landing pages), co-locate assets:

```
templates/
  my-landing-page.html            ← Template entry point
  my-landing-page/
    style.css                     ← Scoped CSS (loaded via functions.php + add_editor_style)
    index.js                      ← Template-specific JS
```

---

## Template Hierarchy & Fallback Logic

WordPress resolves which template to use by walking this chain (first match wins):

```
Custom Template (page attribute) 
  → front-page.html 
  → home.html 
  → page-{slug}.html 
  → page-{ID}.html 
  → page.html 
  → singular.html 
  → index.html
```

For archives:
```
category-{slug}.html → category-{ID}.html → category.html → archive.html → index.html
```

For search:
```
search.html → index.html
```

**Custom templates** (created via `customTemplates` in `theme.json`) appear in the Page
Attributes panel and bypass the hierarchy when explicitly assigned.

---

## Block Markup Rules

### The Golden Rule

Every piece of raw HTML inside a template or pattern MUST be wrapped in block comment tags.
WordPress strips any content outside recognized block boundaries when saving through the editor.

```html
<!-- Correct: raw HTML wrapped in wp:html -->
<!-- wp:html -->
<section class="hero">
  <h1>Welcome</h1>
</section>
<!-- /wp:html -->

<!-- Correct: native WP block — no wp:html needed -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Welcome</h1>
<!-- /wp:heading -->

<!-- WRONG: raw HTML outside any block wrapper — will be stripped -->
<section class="hero">
  <h1>Welcome</h1>
</section>
```

### Common Block Syntax Reference

```html
<!-- Pattern reference -->
<!-- wp:pattern {"slug":"theme-slug/pattern-name"} /-->

<!-- Post content (required in all page templates) -->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->

<!-- Group block (div wrapper) -->
<!-- wp:group {"tagName":"section","className":"my-section"} -->
<section class="wp-block-group my-section">
  <!-- inner blocks -->
</section>
<!-- /wp:group -->

<!-- Template part reference -->
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->

<!-- Image block -->
<!-- wp:image {"id":123,"sizeSlug":"full"} -->
<figure class="wp-block-image size-full">
  <img src="..." alt="..." />
</figure>
<!-- /wp:image -->

<!-- Query loop (for archive/search templates) -->
<!-- wp:query {"queryId":1,"query":{"inherit":true}} -->
<div class="wp-block-query">
  <!-- wp:post-template -->
    <!-- wp:post-title /-->
    <!-- wp:post-excerpt /-->
  <!-- /wp:post-template -->
</div>
<!-- /wp:query -->
```

### Block Attributes JSON

Block attributes go in the opening comment, not as HTML attributes:

```html
<!-- wp:group {
  "tagName": "section",
  "align": "full",
  "className": "hero-section",
  "style": {"spacing": {"padding": {"top": "4rem", "bottom": "4rem"}}}
} -->
```

---

## PHP Pattern Rules

### Required Header

Every PHP pattern file MUST start with this header comment (PHP comment, not HTML):

```php
<?php
/**
 * Title: Human Readable Title
 * Slug: theme-text-domain/pattern-slug
 * Categories: category-slug
 * Keywords: keyword1, keyword2
 * Block Types: core/group
 * Inserter: true
 */
?>
```

- `Title` — shown in the Block Inserter
- `Slug` — must be unique; format: `{text-domain}/{slug}`
- `Categories` — must be a registered category slug
- `Inserter: false` — hides the pattern from the inserter (useful for templates-only patterns)

### Image URL Pattern (always use PHP)

```php
<img
  src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.webp"
  alt="<?php esc_attr_e( 'Hero image', 'text-domain' ); ?>"
>
```

Never hardcode `http://` or relative paths — they break across environments and when child
theme overrides parent.

### Pattern Category Registration

Register custom categories in `functions.php` before patterns use them:

```php
function mytheme_register_pattern_categories() {
    register_block_pattern_category(
        'my-theme',
        array( 'label' => __( 'My Theme', 'my-theme' ) )
    );
}
add_action( 'init', 'mytheme_register_pattern_categories' );
```

---

## Asset Pipeline (functions.php)

### Full functions.php Template

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Enqueue parent and child stylesheets.
 */
function mytheme_child_enqueue_styles() {
    wp_enqueue_style(
        'mytheme-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'mytheme_child_enqueue_styles', 9 );

/**
 * Theme setup: editor styles, pattern categories.
 */
function mytheme_child_setup() {
    add_theme_support( 'editor-styles' );
    add_editor_style( 'templates/my-landing-page/style.css' );

    register_block_pattern_category(
        'mytheme-child',
        array( 'label' => __( 'My Theme Child', 'mytheme-child' ) )
    );
}
add_action( 'after_setup_theme', 'mytheme_child_setup' );

/**
 * Template-specific asset enqueueing.
 */
function mytheme_child_custom_assets() {
    if ( is_page_template( 'my-landing-page' ) ) {
        wp_enqueue_style(
            'my-landing-page-style',
            get_stylesheet_directory_uri() . '/templates/my-landing-page/style.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'my-landing-page-script',
            get_stylesheet_directory_uri() . '/templates/my-landing-page/index.js',
            array(),  // dependencies (e.g. 'jquery')
            '1.0.0',
            true      // load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mytheme_child_custom_assets' );
```

### Key Notes

- `get_template_directory_uri()` → **parent** theme directory
- `get_stylesheet_directory_uri()` → **child** (or current) theme directory
- Always pass the parent theme's version as the stylesheet version to bust cache on parent updates
- Priority `9` for child enqueue ensures it loads before default priority `10` hooks

---

## Site Editor Sync Behaviour

The Site Editor stores user customisations in the **database**. If a theme file changes but the
editor isn't reflecting it, the database version is taking precedence.

### How to force file-based version

1. Open the Site Editor (Appearance → Editor)
2. Navigate to the affected template or style
3. Click the three-dot menu (⋮)
4. Select **"Reset to defaults"** or **"Clear Customizations"**

This removes the database override and forces WordPress to re-read the file.

### When this matters most

- After editing a `templates/*.html` file
- After editing `theme.json` styles
- After moving a template part to a new area in `theme.json`

### Programmatic cache busting (development)

During active development, append a unique version string to style/script handles to force
browser cache invalidation:

```php
wp_enqueue_style( 'my-style', get_stylesheet_directory_uri() . '/style.css', array(), time() );
```

Replace `time()` with a static version string before deploying to production.

---

## Child Theme Rules

1. **Never modify the parent theme directory.** All files go in the child theme.
2. **Template override**: Copy `templates/{name}.html` from the parent to the same path in the
   child and modify. WordPress always prefers the child theme's version.
3. **Part override**: Same process — copy from parent `parts/` to child `parts/`.
4. **Pattern override**: Create a pattern with the same `Slug` in the child theme to override
   the parent's pattern.
5. **theme.json merging**: Child's `theme.json` is deep-merged with the parent's. Arrays like
   `palette` are concatenated; scalars are overridden. You don't need to copy the entire parent
   `theme.json` — only define what you want to change or add.
6. **functions.php**: The child's `functions.php` loads IN ADDITION to the parent's — it does
   not replace it. Do not re-declare parent functions.

---

## Query Loop Block

The Query Loop block powers archive, category, and search templates. The critical setting:

**"Inherit query from template"** (set in the block's sidebar)

- **On (true)**: The block reads its query parameters from the current URL (category slug,
  search term, pagination). Use this for `archive.html` and `search.html`.
- **Off (false)**: The block ignores the URL and always shows a static list of recent posts.
  This BREAKS search and archive functionality — only use it for manually curated post grids.

In block markup, this setting corresponds to `"inherit": true` in the query attribute:

```html
<!-- wp:query {"queryId":1,"query":{"inherit":true,"postType":"post"}} -->
```
