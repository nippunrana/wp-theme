# theme.json Reference — WordPress Block Themes

## Table of Contents
1. [Root Structure](#root-structure)
2. [Color Palettes](#color-palettes)
3. [Gradients & Duotones](#gradients--duotones)
4. [Typography](#typography)
5. [Spacing & Layout](#spacing--layout)
6. [Block-Specific Targeting](#block-specific-targeting)
7. [Custom CSS Injection](#custom-css-injection)
8. [CSS Variable Naming Convention](#css-variable-naming-convention)
9. [Child Theme Merging](#child-theme-merging)

---

## Root Structure

```json
{
  "$schema": "https://schemas.wp.org/wp/6.7/theme.json",
  "version": 3,
  "settings": {},
  "styles": {},
  "customTemplates": [],
  "templateParts": []
}
```

**Critical:** JSON must be perfectly valid. A single extra comma causes WordPress to silently
ignore the entire file. Always validate before saving.

---

## Color Palettes

```json
"settings": {
  "color": {
    "palette": [
      { "slug": "primary",    "color": "#1a1a2e", "name": "Primary Dark" },
      { "slug": "accent",     "color": "#e94560", "name": "Accent Red"   },
      { "slug": "neutral",    "color": "#f5f5f5", "name": "Neutral Light"},
      { "slug": "white",      "color": "#ffffff", "name": "White"        }
    ],
    "defaultPalette": false,
    "custom": false
  }
}
```

- `defaultPalette: false` — hides the 20+ WordPress default colors from editors.
- `custom: false` — disables the freeform color picker, enforcing the design system.
- Use logical semantic slugs (primary, accent, muted) over visual ones (red, blue).

---

## Gradients & Duotones

### Gradients

```json
"settings": {
  "color": {
    "gradients": [
      {
        "slug": "hero-gradient",
        "name": "Hero Gradient",
        "gradient": "linear-gradient(135deg, #1a1a2e 0%, #e94560 100%)"
      }
    ],
    "defaultGradients": false,
    "customGradient": false
  }
}
```

### Duotones (for images/covers)

```json
"settings": {
  "color": {
    "duotone": [
      {
        "slug": "brand-duotone",
        "name": "Brand Duotone",
        "colors": ["#1a1a2e", "#e94560"]
      }
    ]
  }
}
```

The first color maps to Shadows, the second to Highlights.
Supported blocks: `core/image`, `core/cover`, `core/site-logo`.

---

## Typography

### Font Sizes

Use standard slugs for maximum compatibility:

```json
"settings": {
  "typography": {
    "fontSizes": [
      { "slug": "small",   "size": "0.875rem", "name": "Small"   },
      { "slug": "medium",  "size": "1rem",     "name": "Medium"  },
      { "slug": "large",   "size": "1.5rem",   "name": "Large"   },
      { "slug": "xlarge",  "size": "2rem",     "name": "X-Large" },
      { "slug": "xxlarge", "size": "3rem",     "name": "XX-Large"}
    ],
    "customFontSize": false
  }
}
```

`customFontSize: false` prevents users from entering arbitrary pixel values.

### Local Font Integration

```json
"settings": {
  "typography": {
    "fontFamilies": [
      {
        "slug": "inter",
        "name": "Inter",
        "fontFamily": "Inter, sans-serif",
        "fontFace": [
          {
            "fontFamily": "Inter",
            "fontWeight": "400",
            "fontStyle": "normal",
            "src": ["file:./assets/fonts/Inter-Regular.woff2"]
          },
          {
            "fontFamily": "Inter",
            "fontWeight": "700",
            "fontStyle": "normal",
            "src": ["file:./assets/fonts/Inter-Bold.woff2"]
          }
        ]
      }
    ]
  }
}
```

Store font files in `assets/fonts/`. Paths are relative to `theme.json`.

### Typography UI Controls

```json
"settings": {
  "typography": {
    "fontStyle":       true,
    "fontWeight":      true,
    "letterSpacing":   true,
    "lineHeight":      true,
    "textDecoration":  false,
    "textTransform":   false
  }
}
```

---

## Spacing & Layout

### Spacing Scale

```json
"settings": {
  "spacing": {
    "spacingSizes": [
      { "slug": "xs",  "size": "0.5rem",  "name": "XS"  },
      { "slug": "sm",  "size": "1rem",    "name": "SM"  },
      { "slug": "md",  "size": "2rem",    "name": "MD"  },
      { "slug": "lg",  "size": "4rem",    "name": "LG"  },
      { "slug": "xl",  "size": "6rem",    "name": "XL"  }
    ],
    "units": ["px", "rem", "%", "vw"]
  }
}
```

To completely hide the spacing UI: `"spacingSizes": []`.

### Layout (Content Width)

```json
"settings": {
  "layout": {
    "contentSize": "800px",
    "wideSize": "1200px"
  }
}
```

- `contentSize` — default column width for constrained blocks.
- `wideSize` — maximum width for wide-aligned blocks (wide/full-width toggle).

### Root Padding Aware Alignments

```json
"settings": {
  "useRootPaddingAwareAlignments": true
}
```

When `true`, WordPress applies padding via a `.has-global-padding` class on group blocks instead
of on `body`. This lets "Full Width" blocks extend edge-to-edge while inner content still respects
the gutter. Essential for landing pages with full-bleed sections.

---

## Block-Specific Targeting

### In `settings` (controls editor UI for a block)

```json
"settings": {
  "blocks": {
    "core/post-date": {
      "typography": {
        "fontStyle": false,
        "fontWeight": false
      }
    }
  }
}
```

### In `styles` (applies CSS to a block type globally)

```json
"styles": {
  "blocks": {
    "core/heading": {
      "typography": {
        "fontFamily": "var(--wp--preset--font-family--inter)",
        "fontWeight": "700"
      },
      "color": {
        "text": "var(--wp--preset--color--primary)"
      }
    },
    "core/button": {
      "color": {
        "background": "var(--wp--preset--color--accent)",
        "text": "var(--wp--preset--color--white)"
      },
      "border": {
        "radius": "4px"
      }
    }
  }
}
```

### Element-Level Targeting

```json
"styles": {
  "elements": {
    "link": {
      "color": { "text": "var(--wp--preset--color--accent)" },
      "typography": { "textDecoration": "none" },
      ":hover": {
        "typography": { "textDecoration": "underline" }
      }
    },
    "h1": { "typography": { "fontSize": "var(--wp--preset--font-size--xxlarge)" } },
    "h2": { "typography": { "fontSize": "var(--wp--preset--font-size--xlarge)" } }
  }
}
```

---

## Custom CSS Injection

### Global CSS (all pages)

```json
"styles": {
  "css": ".site-header { backdrop-filter: blur(10px); }"
}
```

### Block-scoped CSS

```json
"styles": {
  "blocks": {
    "core/group": {
      "css": "& .inner-content { max-width: 600px; margin: 0 auto; }"
    }
  }
}
```

Use `&` as the self-referencing selector (like Sass nesting). This scopes the CSS to the block's
root element.

---

## CSS Variable Naming Convention

WordPress auto-generates CSS custom properties for every preset:

| Preset type    | CSS variable pattern                          | Example                                   |
|----------------|-----------------------------------------------|-------------------------------------------|
| Color          | `var(--wp--preset--color--{slug})`            | `var(--wp--preset--color--accent)`        |
| Font family    | `var(--wp--preset--font-family--{slug})`      | `var(--wp--preset--font-family--inter)`   |
| Font size      | `var(--wp--preset--font-size--{slug})`        | `var(--wp--preset--font-size--large)`     |
| Spacing        | `var(--wp--preset--spacing--{slug})`          | `var(--wp--preset--spacing--md)`          |
| Gradient       | `var(--wp--preset--gradient--{slug})`         | `var(--wp--preset--gradient--hero-gradient)` |

Always use these variables in your CSS rather than hardcoded values. This keeps the design system
consistent and allows future theme changes to propagate everywhere automatically.

---

## Child Theme Merging

The child theme's `theme.json` **merges with** (not replaces) the parent's `theme.json`.

- Array-type settings (e.g. `palette`, `fontFamilies`) are **concatenated**.
- Scalar settings (e.g. `contentSize`, `custom`) are **overridden** by the child.
- To effectively remove a parent palette entry, set `defaultPalette: false` and redefine only
  your colors in the child.

**Practical child theme.json example:**

```json
{
  "$schema": "https://schemas.wp.org/wp/6.7/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "color": {
      "custom": true,
      "link": true
    }
  },
  "customTemplates": [
    {
      "name": "my-landing-page",
      "title": "My Landing Page",
      "postTypes": ["page"]
    }
  ],
  "templateParts": []
}
```

Keep child `theme.json` minimal — only override what diverges from the parent.
