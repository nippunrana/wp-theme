# AI Context — EgniTech One (Parent Theme)

This file provides the **architectural foundation** and **design system rules** for the EgniTech One parent theme. This is the core engine; for technical implementation procedures (creating templates, patterns, or asset loading), refer to the `wp-block-theme` skill.

## Project Identity
- **Theme Name**: `EgniTech One` (Folder: `egnitech-one`)
- **Role**: Core Foundation / Parent Theme.
- **Performance Target**: Site-wide assets must remain under **50KB**.

## Core Development Principles
1. **Child Theme First**: Whenever possible, implement features, templates, or styles in the `egnitech-one-child` theme instead of modifying this core.
2. **Native-First**: Always prioritize native WordPress block capabilities and `theme.json` settings over custom CSS or PHP workarounds.
3. **Vanilla JS Only**: No jQuery. Use only ES6+ for performance and modern compatibility.

## Implementation Workflow
For all development tasks involving templates, patterns, or `theme.json`:
1. **Use the `wp-block-theme` skill**. It contains the expert logic for HTML conversion, CSS scoping, and the atomic asset pipeline.
2. **Modular Assets**: Follow the "Pattern-First" asset model. Enqueue section-specific assets inside PHP pattern files so they load only when rendered.

## Design System & `theme.json`
The foundation uses a modern `theme.json` (v3) approach:
- **Typography**: Uses fluid sizing (`clamp()`) and system-first fonts. Never hardcode pixel values; reference theme presets (e.g., `var(--wp--preset--font-size--large)`).
- **Spacing**: Slug-based spacing scales (`20` to `80`). Use `var:preset|spacing|[slug]` tokens.

## Dark/Light Mode System (CRITICAL)
EgniTech One handles color schemes via a foundational system:
- **Mechanics**: Controls the `:root` pseudo-class using `color-scheme: light dark` and a persistent `data-scheme` attribute on the `<html>` root.
- **Implementation**: DO NOT use media queries for dark modes. Use the native modern CSS `light-dark()` function.
- **Example**: `color: light-dark(var(--wp--preset--color--base), var(--wp--preset--color--contrast));`

## Coding Standards
1. **Text Domain**: Always use `egnitech-one` for core localization.
2. **Safe Containers**: Use `core/group` for complex layouts instead of `core/columns` to avoid Site Editor validation errors.
3. **SVGs**: Provide explicit `width` and `height` attributes on inline `<svg>` tags to prevent layout shifts in the Site Editor.

---
*Final AI Reminder*: This is the CORE theme. Changes here affect all child-theme instances. Maintain stability and extreme performance.

