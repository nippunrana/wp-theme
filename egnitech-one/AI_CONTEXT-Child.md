# AI Context — EgniTech One Child Theme

This file provides the **environmental ground rules** and **project identity** for the EgniTech One Child theme. For technical implementation procedures (creating templates, patterns, or asset loading), refer to the `wp-block-theme` skill.

## Project Identity
- **Site Domain**: `peptidebeacon.com`
- **Child Theme**: `EgniTech One Child` (Folder: `egnitech-one-child`)
- **Parent Theme**: `EgniTech One` (Folder: `egnitech-one`)
- **Architecture**: WordPress Full Site Editing (FSE) / Block Theme.

## Child Theme Principles (CRITICAL)
1. **Parent Protection**: NEVER modify files in the parent theme directory (`egnitech-one`). All work MUST stay within the child theme folder.
2. **Inheritance & Overrides**: 
   - This theme inherits all templates, parts, and functions from the parent. 
   - To override, mirror the file path from the parent into the child theme.
   - For components, use the **Pattern-First Asset Model**: Assets (`style.css`, `index.js`) must live inside the specific pattern directory (e.g., `patterns/contact-us/style.css`).

## Implementation Workflow
For all development tasks involving templates, patterns, `theme.json`, or asset enqueuing:
1. **Use the `wp-block-theme` skill**. It contains the expert logic for HTML conversion, CSS scoping, and the atomic asset pipeline.
2. **Follow the Pattern-First Model**: New patterns should use modular asset bundles (scoped `style.css` and `index.js` inside the pattern directory) registered via `register_block_style()` as detailed in the skill.

## Project-Specific Systems

### Dark/Light Mode System
Inherited from the parent. Use the `light-dark()` CSS function for any new styles to ensure they respect the theme's native color scheme toggle.
- **Example**: `color: light-dark(#333, #fff);`

### Text Domains & Localization
- Use `egnitech-one` when referencing parent-defined strings or functions.
- Use `egnitech-one-child` for new strings unique to this child theme.

## Coding Standards
1. **Vanilla JS Only**: No jQuery. Use ES6+. For complex interactions, use the **Interactivity API**.
2. **CSS Scoping (MANDATORY)**: All custom CSS must be scoped to the block's variation class (e.g., `.is-style-{slug}`). NEVER use catch-all page wrappers (e.g., `.{slug}-wrapper`) as they cause style bleed.
3. **Performance**: Maintain the lean, high-performance philosophy of the parent theme.

## Technical References
Refer to the following in the `wp-block-theme` skill for deep-dives:
- `references/html-conversion.md`: The 10-step process for converting designs to FSE.
- `references/architecture.md`: Scoping rules and the asset pipeline.
- `references/theme-json.md`: Global design system tokens.

---
*Final AI Reminder*: You are extending a production site. Always verify changes in the child theme and never touch the parent core.
