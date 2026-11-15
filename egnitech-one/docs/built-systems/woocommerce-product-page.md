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
- **Only the first gallery picture loads with the page.** It is marked high priority and the others are lazy (`egnitech_one_product_gallery_shortcode`); `preloadImages` fetches those once the page has loaded, so they are ready before a visitor swipes. **Never** add `srcset` or `sizes` to these pictures without changing `setFirstImage()` in `patterns/product-gallery/index.js` as well: a variation's picture is swapped in through `src` alone, and a browser holding a `srcset` ignores a new `src`, so the old picture would stay on screen.
- **The gallery script refuses to start inside any iframe**, to stay out of the editor. In an iframe-based preview tool it looks broken and is not.
- **The trust-badge fallback text is written for one shop.** A product with empty badge fields shows "Eco-Friendly UV Tech", "1 Year Warranty" and "Secure Checkout" (`egnitech_one_product_trust_badges_shortcode`). This is the parent theme: a site selling anything else must fill the three fields on every product, or the fallback must change.
- **An empty short description prints nothing.** `egnitech_one_render_product_short_description_block` stops WordPress building an excerpt out of the long description, and prints the short description with its HTML kept.
- **The related-products section is WooCommerce's own pattern, called by slug.** If a WooCommerce update renames or drops that pattern, the section disappears with no error.

## Add to cart and the cart drawer

- **Buy Now** (Theme Options > WooCommerce > Product Page Button) relabels the single-product button and sends the shopper to checkout on both paths: the no-reload path in `assets/js/woocommerce-mini-cart-auto-open.js`, and the full-page path through `woocommerce_add_to_cart_redirect` (`inc/woocommerce-cart-drawer.php`). It also stops the drawer cookie being set, or the drawer would pop open over checkout. Shop and category buttons keep "Add to cart" and the drawer. Change the two paths together. It lives in the theme by the owner's choice, so switching themes turns it off.
- **The quantity stepper is WooCommerce's own** (`quantitySelectorStyle: "stepper"` on the add-to-cart-form block in `patterns/single-product-details.php`). WooCommerce hides it when the quantity is fixed. **Never** add theme-made − and + buttons back: the theme's own were removed on 15 Nov 2026. **A site whose Single Product template was saved in the Site Editor keeps the block's old markup without the stepper setting, and so has no − and + buttons at all.** Turn the stepper on in that block's settings, or reset the template (`templates-and-patterns.md`).
- **Theme CSS for the add-to-cart area may target only:**
  - the block's class (`.wc-block-add-to-cart-form`);
  - WordPress's `.wp-element-button`;
  - WooCommerce's `wc-block-components-*` classes.

  **Never** write CSS against legacy WooCommerce classes (`form.cart`, `.quantity`, `.qty`, `.single_add_to_cart_button`, `.variations_button`, `.price`, `.button`). They are internal and change between releases (`wp-block-theme` skill). JavaScript still reads some of them (`bulk-discount.js`).
- **The row still overrides WooCommerce's internal grid** (`patterns/single-product-details/style.css`).
  - WooCommerce lays out the form, and a variable product's variation area, as column-flow grids that pin the button to the start and add empty implicit columns that shorten the row. It also shrinks a grouped form to its table.
  - Row flow on both levels, the grouped form's width restored, and a stepper-less button spanning the row make the button end at the bulk-discount box's edge. Measured on 15 Nov 2026 for simple, sold-individually, variable and grouped products.
  - A WooCommerce update can bring the gap back. Moving to Add to Cart + Options removes this override (`docs/plans/`).

- **Every `form.cart` submit on the site is intercepted** and sent to WooCommerce's `add_to_cart` AJAX endpoint (`assets/js/woocommerce-mini-cart-auto-open.js`). On success the mini-cart drawer opens. On any failure the script falls back to the normal form submit.
- **Variable products never take the no-reload path.** The request carries the parent product's id, which that endpoint cannot add, so the script falls back to a full page submit. The drawer is then opened on the next page by the short-lived `egnitech_just_added_to_cart` cookie (`inc/woocommerce-cart-drawer.php`). Test a variable product by hand after touching either file.
- **The endpoint address is built from the domain root.** On a WordPress installed in a subfolder the request goes to the wrong place and every add-to-cart falls back to a reload.
- **The mini-cart's "open drawer on add" behaviour is forced when the block is rendered** (`egnitech_one_force_mini_cart_drawer_behavior`). Changing that setting on the Mini-Cart block in the Site Editor has no effect.
- **Opening the drawer depends on WooCommerce's internal class names** (the mini-cart button and the drawer overlay's hidden state). After a WooCommerce update that renames them, items are still added but the drawer stays shut.
- **The script loads on every front-end page while WooCommerce is active**, and declares jQuery as a dependency (`assets-and-versioning.md`). Without WooCommerce it is not loaded at all.
