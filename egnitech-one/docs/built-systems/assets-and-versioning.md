# Assets and cache-busting

Code: `egnitech_one_asset_version()` in `inc/helpers.php`, `inc/enqueue-assets.php` (what loads where), the `register_block_style()` calls in `inc/theme-setup.php`, `inc/woocommerce-cart-drawer.php`. How pattern assets are meant to be wired is in `ai-context.md` (Implementation Workflow).

- **There is no build step.** No bundler, no minifier, no generated files. The file you edit is the file the browser receives, and on a server where the theme folder is live, saving it is deploying it.

## Decisions

- **Every stylesheet and script is versioned by its own modification time**, through `egnitech_one_asset_version()`. Its address changes the moment the file does, so no browser keeps a stale copy and nobody has to remember to bump anything. **Never** pass the theme `Version`, a literal such as `'1.0.0'`, or nothing as the version of a theme asset. The servers tell browsers to keep CSS and JS indefinitely, and edits to the product-page stylesheet once failed to reach returning visitors for exactly this reason (owner's request, 12 Nov 2026). The examples in the `wp-block-theme` skill still show literals. This rule overrides them.
- **The path given to `egnitech_one_asset_version()` must be the file the address serves.** `get_theme_file_uri()` goes with `get_theme_file_path()` (a child theme's copy wins). `get_template_directory_uri()` and `get_parent_theme_file_uri()` go with the parent's folder. Mixing the pairs stamps one file with another's time.
- **A handle is defined once.** The two product-page stylesheets are registered in `inc/theme-setup.php`, where their block style needs them, and `inc/enqueue-assets.php` enqueues them by handle alone. WordPress keeps the first registration of a handle and silently ignores a later address or version, which is how the stale-stylesheet bug stayed hidden.
- **The parent always loads its own `style.css`, then a child theme's.** `egnitech-one-style` is the parent's file under every theme. With a child theme active, the child's `style.css` follows it as `egnitech-one-child-style`. **Never** enqueue the parent's `style.css` from a child theme: it would load twice.
- **The quantity stepper is a script module** (`wp_enqueue_script_module()`), the form the `wp-block-theme` skill prescribes for new scripts. The older scripts are classic deferred ones.

## Traps

- **A changed file reaches visitors only when the page that links to it is rebuilt.** Cached pages keep the old address until they expire. **After editing any CSS or JS on a live site, clear the page cache** (`page-cache.md`).
- **Two address helpers are mixed, and they differ under a child theme.** `get_theme_file_uri()` serves the child's copy of a file when one exists. `get_template_directory_uri()` always serves the parent's. The product-page handles registered in `inc/theme-setup.php` and the cart-drawer script use the second, so a child theme cannot replace those files by copying them.
- **Pattern CSS has two loaders.** A stylesheet tied to a block style through `style_handle` loads wherever a block with that style is rendered. The two product-page stylesheets are also enqueued on every single-product page by `inc/enqueue-assets.php`. Removing one loader does not take the file off the page.
- **jQuery loads on every front-end page.** `inc/woocommerce-cart-drawer.php` enqueues its script site-wide, WooCommerce or not, with `jquery` as a dependency, and the bulk-discount script declares it too. This conflicts with the jQuery rule in `ai-context.md` and is unresolved. Until the owner settles it, **never** add another `jquery` dependency.
