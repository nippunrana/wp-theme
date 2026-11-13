# WooCommerce product page

Code: `templates/single-product.html`, `patterns/single-product.php`, `patterns/single-product-details.php` and its `style.css`, `patterns/product-gallery.php` with its `index.js` and `style.css`, the shortcodes and `render_block` filters in `inc/theme-setup.php`, `inc/woocommerce-cart-drawer.php`, `assets/js/woocommerce-mini-cart-auto-open.js`. Quantity pricing is in `bulk-discounts.md`.

## How the page is put together

- **Template, then pattern, then pattern.** `single-product.html` calls `egnitech-one/single-product`, which calls `egnitech-one/single-product-details`, which calls either the custom gallery or WooCommerce's gallery block, depending on the **Gallery Layout** setting.
- **The trust badges, the description / specifications / reviews stack and the custom gallery are shortcodes** inside Shortcode blocks. That is how a block pattern gets per-product PHP.

## Traps

- **The three product patterns are registered twice.** WordPress registers them from their file headers. `egnitech_one_register_block_styles` then registers the same slugs again by including each file on `init`, and that second copy is the one used. Their PHP therefore runs on **every request, front end and admin, before WordPress knows which page it is serving**. PHP in those three files must never use the current post, `is_product()` or anything else from the main query. Put that work in a shortcode, as the existing ones do.
- **In Custom mode the WooCommerce gallery block is swapped when it is rendered** (`egnitech_one_render_product_gallery_block`): any `woocommerce/product-image-gallery` block, anywhere, prints the custom gallery instead. The custom gallery uses fixed element ids, so **only one can exist on a page**.
- **The custom gallery shows a chosen variation's picture itself** (`found_variation` and `reset_data` in `patterns/product-gallery/index.js`). A picture already in the gallery is scrolled to; one that is not takes the first slide until the choice is cleared. These are WooCommerce's jQuery events, so that block is guarded with `typeof jQuery` and must stay guarded. It has been run against the live gallery markup in a test harness, never on a real variable product: check the first one by hand.
- **The custom gallery downloads every image twice once the page has loaded**, the product-page size and the full original of each, so the lightbox opens at once (`preloadImages`). On a product with many large photos this is most of the page's weight.
- **The gallery script refuses to start inside any iframe**, to stay out of the editor. In an iframe-based preview tool it looks broken and is not.
- **The trust-badge fallback text is written for one shop.** A product with empty badge fields shows "Eco-Friendly UV Tech", "1 Year Warranty" and "Secure Checkout" (`egnitech_one_product_trust_badges_shortcode`). This is the parent theme: a site selling anything else must fill the three fields on every product, or the fallback must change.
- **An empty short description prints nothing.** `egnitech_one_render_product_short_description_block` stops WordPress building an excerpt out of the long description, and prints the short description with its HTML kept.
- **The related-products section is WooCommerce's own pattern, called by slug.** If a WooCommerce update renames or drops that pattern, the section disappears with no error.
- **Two product shortcodes call WooCommerce without checking it is active.** They are safe only because they appear solely in the product patterns. On an ordinary page of a site without WooCommerce, `[egnitech_product_gallery]` or `[egnitech_product_sections]` is a fatal error.

## Add to cart and the cart drawer

- **Every `form.cart` submit on the site is intercepted** and sent to WooCommerce's `add_to_cart` AJAX endpoint (`assets/js/woocommerce-mini-cart-auto-open.js`). On success the mini-cart drawer opens. On any failure the script falls back to the normal form submit.
- **Variable products never take the no-reload path.** The request carries the parent product's id, which that endpoint cannot add, so the script falls back to a full page submit. The drawer is then opened on the next page by the short-lived `egnitech_just_added_to_cart` cookie (`inc/woocommerce-cart-drawer.php`). Test a variable product by hand after touching either file.
- **The endpoint address is built from the domain root.** On a WordPress installed in a subfolder the request goes to the wrong place and every add-to-cart falls back to a reload.
- **The mini-cart's "open drawer on add" behaviour is forced when the block is rendered** (`egnitech_one_force_mini_cart_drawer_behavior`). Changing that setting on the Mini-Cart block in the Site Editor has no effect.
- **Opening the drawer depends on WooCommerce's internal class names** (the mini-cart button and the drawer overlay's hidden state). After a WooCommerce update that renames them, items are still added but the drawer stays shut.
- **The script loads on every front-end page of every site, WooCommerce or not**, and declares jQuery as a dependency there too (`assets-and-versioning.md`).
