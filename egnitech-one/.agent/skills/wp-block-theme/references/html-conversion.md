# HTML → Block Theme Template: Conversion Reference

## Table of Contents
1. [Full Worked Example](#full-worked-example)
2. [CSS Scoping Patterns](#css-scoping-patterns)
3. [JavaScript Patterns](#javascript-patterns)
4. [Image Path Conversion](#image-path-conversion)
5. [Navigation Conversion](#navigation-conversion)
6. [Forms and Third-Party Embeds](#forms-and-third-party-embeds)
7. [Animations and Scroll Effects](#animations-and-scroll-effects)
8. [CSS Reset Handling](#css-reset-handling)
9. [Multi-File Input](#multi-file-input)
10. [Edge Cases and Gotchas](#edge-cases-and-gotchas)

---

## Full Worked Example

### Input: Static HTML file

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Peptide Landing</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #0a0a1a; color: #fff; }

    .hero { padding: 120px 40px; text-align: center; background: linear-gradient(135deg, #1a1a2e, #16213e); }
    .hero h1 { font-size: 3.5rem; margin-bottom: 1rem; }
    .hero p  { font-size: 1.25rem; opacity: 0.8; }

    .features { display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; padding: 80px 40px; }
    .card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
  </style>
</head>
<body>
  <section class="hero">
    <h1>Unlock Peak Performance</h1>
    <p>Science-backed peptides, delivered to your door.</p>
    <a href="/shop" class="btn-primary">Shop Now</a>
  </section>

  <section class="features">
    <div class="card"><h3>Purity</h3><p>99%+ certified purity.</p></div>
    <div class="card"><h3>Speed</h3><p>Next-day UK delivery.</p></div>
    <div class="card"><h3>Support</h3><p>Expert guidance.</p></div>
  </section>

  <script>
    document.querySelectorAll('.card').forEach(card => {
      card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-4px)');
      card.addEventListener('mouseleave', () => card.style.transform = '');
    });
  </script>
</body>
</html>
```

### Slug: `peptide-landing`

---

### Output: `templates/peptide-landing.html`

```html
<!-- wp:pattern {"slug":"egnitech-one-child/peptide-landing"} /-->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
```

---

### Output: `patterns/peptide-landing.php` (master)

```php
<?php
/**
 * Title: Peptide Landing Page
 * Slug: egnitech-one-child/peptide-landing
 * Categories: egnitech-one-child
 * Keywords: peptide, landing, performance
 * Inserter: false
 */
?>
<div class="peptide-landing-wrapper">
  <!-- wp:pattern {"slug":"egnitech-one-child/peptide-landing-hero"} /-->
  <!-- wp:pattern {"slug":"egnitech-one-child/peptide-landing-features"} /-->
</div>
```

---

### Output: `patterns/peptide-landing-hero.php`

```php
<?php
/**
 * Title: Peptide Landing — Hero
 * Slug: egnitech-one-child/peptide-landing-hero
 * Categories: egnitech-one-child
 * Keywords: hero, banner, headline
 * Inserter: false
 */
?>
<!-- wp:html -->
<section class="hero">
  <h1>Unlock Peak Performance</h1>
  <p>Science-backed peptides, delivered to your door.</p>
  <a href="/shop" class="btn-primary">Shop Now</a>
</section>
<!-- /wp:html -->
```

---

### Output: `patterns/peptide-landing-features.php`

```php
<?php
/**
 * Title: Peptide Landing — Features
 * Slug: egnitech-one-child/peptide-landing-features
 * Categories: egnitech-one-child
 * Keywords: features, cards, grid
 * Inserter: false
 */
?>
<!-- wp:html -->
<section class="features">
  <div class="card"><h3>Purity</h3><p>99%+ certified purity.</p></div>
  <div class="card"><h3>Speed</h3><p>Next-day UK delivery.</p></div>
  <div class="card"><h3>Support</h3><p>Expert guidance.</p></div>
</section>
<!-- /wp:html -->
```

---

### Output: `templates/peptide-landing/style.css`

```css
/* ============================================================
   Scoped styles for the Peptide Landing template.
   ALL rules are prefixed with .peptide-landing-wrapper to
   prevent bleeding into the WordPress Block Editor UI.
   ============================================================ */

.peptide-landing-wrapper .hero {
  padding: 120px 40px;
  text-align: center;
  background: linear-gradient(135deg, #1a1a2e, #16213e);
}

.peptide-landing-wrapper .hero h1 {
  font-size: 3.5rem;
  margin-bottom: 1rem;
  color: #fff;
}

.peptide-landing-wrapper .hero p {
  font-size: 1.25rem;
  opacity: 0.8;
  color: #fff;
}

.peptide-landing-wrapper .btn-primary {
  display: inline-block;
  margin-top: 2rem;
  padding: 1rem 2.5rem;
  background: var(--wp--preset--color--accent, #e94560);
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
}

.peptide-landing-wrapper .features {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  padding: 80px 40px;
}

.peptide-landing-wrapper .card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 2rem;
  transition: transform 0.2s ease;
}

.peptide-landing-wrapper .card:hover {
  transform: translateY(-4px);
}
```

> Note: The hover effect is now pure CSS — this is better than JavaScript for simple transforms.

---

### Output: `templates/peptide-landing/index.js`

```js
/* Peptide Landing — interactive enhancements */
document.addEventListener('DOMContentLoaded', function () {
  // Hover effect moved to CSS (.card:hover). JS kept for more complex interactions.
  // Add any JS-only interactions here.
});
```

---

### Output: `theme.json` addition

```json
"customTemplates": [
  {
    "name": "peptide-landing",
    "title": "Peptide Landing Page",
    "postTypes": ["page"]
  }
]
```

---

### Output: `functions.php` additions

```php
// Inside the existing after_setup_theme hook:
add_editor_style( 'templates/peptide-landing/style.css' );

// New enqueue function:
function egnitech_one_child_peptide_landing_assets() {
    if ( is_page_template( 'peptide-landing' ) ) {
        wp_enqueue_style(
            'peptide-landing-style',
            get_stylesheet_directory_uri() . '/templates/peptide-landing/style.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'peptide-landing-script',
            get_stylesheet_directory_uri() . '/templates/peptide-landing/index.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_child_peptide_landing_assets' );
```

---

## CSS Scoping Patterns

### What to strip from the original CSS

Remove these before scoping — they will conflict with WordPress global styles:

```css
/* REMOVE — resets that break WP */
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: ...; }
html { scroll-behavior: smooth; }

/* REMOVE — font-face declarations (move to theme.json fontFamilies instead) */
@font-face { font-family: 'Inter'; src: url(...); }

/* KEEP — everything else, just add the scope prefix */
.hero { ... }
```

### What to keep (just prefix it)

```css
/* Keep layout, color, spacing — just prefix */
.peptide-landing-wrapper .hero { ... }
.peptide-landing-wrapper .card { ... }
```

### Handling `:root` variables

```css
/* Original */
:root {
  --accent: #e94560;
  --bg: #0a0a1a;
}

/* Converted — move to theme.json color palette OR scope under the wrapper */
.peptide-landing-wrapper {
  --accent: #e94560;
  --bg: #0a0a1a;
}
```

Prefer moving design tokens to `theme.json` color/spacing palettes so they become available
throughout the editor. Keep them as CSS custom properties on the wrapper only if they are
too template-specific.

### Media queries — no change needed

Media queries don't need the wrapper prefix added to the query itself, only to the selectors
within:

```css
/* Correct */
@media (max-width: 768px) {
  .peptide-landing-wrapper .features {
    grid-template-columns: 1fr;
  }
}
```

---

## JavaScript Patterns

### DOMContentLoaded wrapper

Always wrap in this guard — WordPress sometimes loads scripts before the DOM is fully parsed:

```js
document.addEventListener('DOMContentLoaded', function () {
  // your code
});
```

### Scoping JS selectors

If the original JS uses broad selectors like `document.querySelectorAll('.card')`, scope them
to the template wrapper to avoid accidental matches in the editor sidebar:

```js
// Original
document.querySelectorAll('.card').forEach(...);

// Safer — scoped to the template
const wrapper = document.querySelector('.peptide-landing-wrapper');
if (!wrapper) return;
wrapper.querySelectorAll('.card').forEach(...);
```

### Skipping animations in the editor

Some animations look jarring inside the Site Editor iframe. Gate them:

```js
document.addEventListener('DOMContentLoaded', function () {
  if (document.body.classList.contains('wp-admin')) return;
  // animations here — won't run in editor
});
```

### jQuery dependency

If the original uses `$` or `jQuery`, add the dependency in `functions.php`:

```php
wp_enqueue_script(
    'my-script',
    get_stylesheet_directory_uri() . '/templates/my-template/index.js',
    array( 'jquery' ),  // ← dependency
    '1.0.0',
    true
);
```

And wrap the JS:

```js
(function ($) {
  $(document).ready(function () {
    // original jQuery code
  });
})(jQuery);
```

---

## Image Path Conversion

### Static HTML path → PHP dynamic path

```html
<!-- Original -->
<img src="images/hero.webp" alt="Hero">
<img src="/assets/hero.webp" alt="Hero">
<img src="https://my-old-site.com/hero.webp" alt="Hero">

<!-- Converted (all cases) -->
<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.webp" alt="Hero">
```

### Background images in inline styles

```html
<!-- Original -->
<div style="background-image: url('images/bg.jpg')">

<!-- Converted -->
<div style="background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/bg.jpg')">
```

### Background images in CSS

Keep these in the scoped CSS file — no PHP needed there since the CSS is loaded via
`wp_enqueue_style` which resolves relative URLs relative to the stylesheet:

```css
.peptide-landing-wrapper .hero {
  background-image: url('../../../assets/images/bg.jpg');
  /* Path is relative to the CSS file location */
  /* templates/peptide-landing/style.css → ../../.. = theme root */
}
```

Alternatively, use `get_stylesheet_directory_uri()` in a PHP-generated inline style to avoid
counting `../` hops.

---

## Navigation Conversion

A static `<nav>` bar in the HTML design should be evaluated case-by-case:

| Scenario | Recommended approach |
|---|---|
| Template is a full-page landing (no WP nav needed) | Keep as raw HTML in `wp:html`, style as-is |
| Template should use the site's WP menus | Replace with `<!-- wp:navigation /-->` block |
| Template needs a custom fixed nav | Keep in a dedicated `{slug}-nav.php` sub-pattern |

**Using native WP navigation block:**

```html
<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","justifyContent":"space-between"}} /-->
```

This renders the menu registered in WP Admin → Appearance → Menus (or created in the Site Editor).

---

## Forms and Third-Party Embeds

### Contact forms (CF7, WPForms, Gravity Forms)

Do **not** paste raw form HTML into `wp:html`. Use a shortcode block instead:

```html
<!-- wp:shortcode -->
[contact-form-7 id="123" title="Contact Form"]
<!-- /wp:shortcode -->
```

### iframes (Google Maps, Calendly, etc.)

Iframes are valid inside `wp:html`:

```html
<!-- wp:html -->
<div class="map-wrapper">
  <iframe
    src="https://maps.google.com/..."
    width="100%"
    height="400"
    frameborder="0"
    allowfullscreen
    loading="lazy"
  ></iframe>
</div>
<!-- /wp:html -->
```

### Video embeds

Prefer the native WP embed block for YouTube/Vimeo:

```html
<!-- wp:embed {"url":"https://youtu.be/abc123","type":"video","providerNameSlug":"youtube"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube">
  <div class="wp-block-embed__wrapper">
    https://youtu.be/abc123
  </div>
</figure>
<!-- /wp:embed -->
```

For `<video>` tags with local sources, use `wp:html`.

---

## Animations and Scroll Effects

### CSS animations (preferred)

Move CSS `@keyframes` and `animation` properties into the scoped stylesheet. They work fine:

```css
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0);    }
}

.peptide-landing-wrapper .hero h1 {
  animation: fadeUp 0.6s ease forwards;
}
```

### Intersection Observer (scroll-triggered)

Works fine in `index.js` when properly guarded:

```js
document.addEventListener('DOMContentLoaded', function () {
  if (document.body.classList.contains('wp-admin')) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  const wrapper = document.querySelector('.peptide-landing-wrapper');
  if (!wrapper) return;
  wrapper.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
});
```

### GSAP / external animation libraries

If the HTML uses GSAP or similar, enqueue the library before your script:

```php
wp_enqueue_script(
    'gsap',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
    array(),
    '3.12.2',
    true
);
wp_enqueue_script(
    'my-template-script',
    get_stylesheet_directory_uri() . '/templates/my-template/index.js',
    array( 'gsap' ),  // ← depends on gsap
    '1.0.0',
    true
);
```

---

## CSS Reset Handling

The most dangerous part of any HTML→WordPress conversion is CSS resets. WordPress injects its
own global styles; a `* { margin: 0; box-sizing: border-box; }` under `.wrapper` will clobber
editor controls.

### Rules to always strip

```css
/* ALWAYS STRIP before scoping */
*, *::before, *::after { ... }
html { ... }
body { ... }
:root { ... }  /* move variables to theme.json or to .wrapper */
```

### Rules that are borderline — check before keeping

```css
/* Check: heading resets affect WP editor headings */
h1, h2, h3, h4, h5, h6 { margin: 0; }

/* Safe IF scoped */
.peptide-landing-wrapper h1 { margin: 0; }
```

### Transition for designers new to WordPress

Tell designers: "Your reset CSS is now WordPress's job. We only style what's inside our wrapper."

---

## Multi-File Input

When the user provides separate HTML, CSS, and JS files:

1. Parse the HTML to find `<link rel="stylesheet">` and `<script src="">` tags.
2. Those referenced files are the CSS/JS to convert.
3. Any additional CDN links (fonts, icon libraries) need to be enqueued in `functions.php`:

```php
// Example: user's HTML had <link href="https://fonts.googleapis.com/css2?family=Inter...">
function egnitech_one_child_my_template_fonts() {
    if ( is_page_template( 'my-template' ) ) {
        wp_enqueue_style(
            'my-template-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap',
            array(),
            null
        );
    }
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_child_my_template_fonts' );
```

Better: download the fonts locally and register them in `theme.json fontFamilies` instead.

---

## Edge Cases and Gotchas

### `<head>` meta tags

Ignore `<title>`, `<meta>`, `<link rel="icon">` etc. from the HTML — WordPress handles these.
Only take content from within the `<body>` tag.

### Inline SVGs

Inline SVGs work inside `wp:html` with no changes:

```html
<!-- wp:html -->
<svg viewBox="0 0 24 24" fill="none" ...>
  <path d="..." />
</svg>
<!-- /wp:html -->
```

### `<script type="application/ld+json">` (structured data)

Move to `functions.php` using `wp_head` hook:

```php
function my_template_structured_data() {
    if ( is_page_template( 'my-template' ) ) {
        echo '<script type="application/ld+json">' . wp_json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => get_bloginfo('name'),
        ]) . '</script>';
    }
}
add_action( 'wp_head', 'my_template_structured_data' );
```

### Sections that need dynamic WP data

If a section needs the latest posts, a product list, or a custom field value, it cannot be raw
HTML in `wp:html`. Options:

1. Use a native WP block (Query Loop, Post Title, etc.)
2. Use a shortcode: `[my_custom_shortcode]` inside `<!-- wp:shortcode -->`
3. Register a custom block (advanced — beyond the scope of this skill)

### CSS `calc()` and `clamp()` — no changes needed

These work as-is inside scoped CSS:

```css
.peptide-landing-wrapper .hero h1 {
  font-size: clamp(2rem, 5vw, 4rem);
}
```

### Tailwind CSS utility classes in the HTML

If the HTML uses Tailwind classes (`text-xl`, `flex`, `gap-4`), you have two options:

1. **Strip Tailwind, rewrite with vanilla CSS** — preferred for long-term maintainability.
2. **Include the Tailwind CDN** via `wp_enqueue_style` scoped to the template — acceptable for
   quick prototypes but adds a large payload.

Never include the Tailwind CDN globally — it will break the WordPress editor UI.
