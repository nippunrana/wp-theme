# Header, footer, sidebar and templates

Code: `templates/`, `parts/`, `patterns/` (header, footer, sidebar, query loop), the layout rules in `style.css`, the body classes and `render_block` filters in `inc/theme-setup.php`. The product page has its own file: `woocommerce-product-page.md`.

## How it is built

- **Each template part is one line that calls a pattern** (`parts/header.html` calls `patterns/header.php`). The pattern is PHP so it can read Theme Options when the page is rendered: the dark logo, the footer copyright and the credits.
- **Blog layout, sidebar position and sticky header are body classes** (`egnitech_one_body_classes`) that `style.css` reacts to. The template files are the same for every setting.

## Traps

- **Saving a template or template part in the Site Editor copies it into the database, and that copy wins from then on.** On that site, edits to the matching file in `templates/` or `parts/`, or to the pattern behind it, do nothing. The pattern's PHP is frozen at whatever it printed on the day of the save, so the dark logo, footer copyright and credits stop following Theme Options. Before debugging a template, check whether the site holds a saved copy (**Appearance > Editor**, or `wp post list --post_type=wp_template,wp_template_part`). Resetting it in the Site Editor returns it to the file.
- **The sidebar is always rendered and only hidden.** Every blog and page template includes the sidebar part. With Sidebar set to "none", a rule in `style.css` hides it, but its search form, recent posts and category list are still built and sent with every such page. Removing that CSS rule shows a sidebar on every site.
- **A date, author, category or tag block that prints nothing is probably a Theme Options switch, not a bug.** `egnitech_one_filter_post_meta_blocks` empties those blocks everywhere on the front end, in any template or pattern, when the matching **Blog** toggle is off. (The Tags toggle has its own quirk: `theme-options.md`.)
- **The sticky header needs the header to be the first thing inside `.wp-site-blocks`** (the selector in `style.css`). A template that puts anything above the header part loses stickiness with no error.
- **Theme breadcrumbs are printed at `wp_body_open`, which is above the header**, on every singular page. That includes product pages, where the product pattern already carries WooCommerce's own breadcrumb block. Both kinds are hidden on small screens by `style.css`.
