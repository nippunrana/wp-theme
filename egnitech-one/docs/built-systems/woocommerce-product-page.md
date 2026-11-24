# WooCommerce product page

Code: `templates/single-product.html`, `patterns/single-product.php`, `patterns/single-product-details.php` and its `style.css`, `patterns/product-gallery.php` with its `index.js` and `style.css`, the shortcodes and `render_block` filters in `inc/theme-setup.php`, `inc/woocommerce-cart-drawer.php`, `assets/js/woocommerce-mini-cart-auto-open.js`, `patterns/single-product-details/sticky-buy-now.js` and its `.css`, `inc/woocommerce-trust-badges.php` and `inc/woocommerce-trust-badges-admin.php` with `assets/{css,js}/admin-trust-badges.*`. Quantity pricing is in `bulk-discounts.md`.

## How the page is put together

- **Template, then pattern, then pattern.** `single-product.html` calls `egnitech-one/single-product`, which calls `egnitech-one/single-product-details`, which calls either the custom gallery or WooCommerce's gallery block, depending on the **Gallery Layout** setting.
- **The trust badges, the description / specifications / reviews stack and the custom gallery are shortcodes** inside Shortcode blocks. That is how a block pattern gets per-product PHP.

## Traps

- **The product patterns are registered once, from their file headers, like every other pattern.** **Never** register them again with `register_block_pattern()`. A second, manual registration was removed on 15 Nov 2026: it replaced the file-header copy, dropped `Inserter: false`, ran each file's PHP on every request, and overwrote a child theme's pattern of the same slug.
- **A pattern file's PHP runs when the pattern is first rendered, and also whenever the editor lists patterns**, where there is no current post. PHP in a pattern file must never use the current post, `is_product()` or anything else from the main query. Put that work in a shortcode, as the existing ones do.
- **In Custom mode the WooCommerce gallery block is swapped when it is rendered** (`egnitech_one_render_product_gallery_block`): any `woocommerce/product-image-gallery` block, anywhere, prints the custom gallery instead. The custom gallery uses fixed element ids, so **only one can exist on a page**.
- **The custom gallery shows a chosen variation's picture itself** (`found_variation` and `reset_data` in `patterns/product-gallery/index.js`). A picture already in the gallery is scrolled to; one that is not takes the first slide until the choice is cleared. These are WooCommerce's jQuery events, so that block is guarded with `typeof jQuery` and must stay guarded. It has been run against the live gallery markup in a test harness, never on a real variable product: check the first one by hand.
- **The custom gallery downloads every image twice once the page has loaded**, the product-page size and the full original of each, so the lightbox opens at once (`preloadImages`). On a product with many large photos this is most of the page's weight.
- **Only the first gallery picture loads with the page.** It is marked high priority and the others are lazy (`egnitech_one_product_gallery_shortcode`); `preloadImages` fetches those once the page has loaded, so they are ready before a visitor swipes. **Never** add `srcset` or `sizes` to these pictures without changing `setFirstImage()` in `patterns/product-gallery/index.js` as well: a variation's picture is swapped in through `src` alone, and a browser holding a `srcset` ignores a new `src`, so the old picture would stay on screen.
- **Each product's gallery has one frame, 1:1 or 3:4, picked in PHP from its pictures' shapes** (`egnitech_one_product_gallery_shortcode`). Every picture is shown whole inside it, with a light fill around one that does not match (owner's decision, 24 Nov 2026).
  - **Never** pick the frame per slide or in JavaScript: the gallery's height would change while swiping, or after the page loads.
  - **Never** go back to cropping (`object-fit: cover`): it cut the edges off rectangular photos.
  - **3:4 applies on desktop too, by the owner's choice.** There the gallery is taller than most laptop screens, so its thumbnails can sit below the fold.
  - A variation's picture swapped in by the script was not counted when the frame was picked; it gets the fill like any other mismatch.
- **The gallery script refuses to start inside any iframe**, to stay out of the editor. In an iframe-based preview tool it looks broken and is not.
- **The trust-badge defaults are written for one shop.** A product whose badge list was never saved shows "Eco-Friendly UV Tech", "1 Year Warranty" and "Secure Checkout" (`egnitech_one_trust_badge_defaults()`), and its meta box starts with those three rows, so saving it stores them. This is the parent theme: a site selling anything else must edit or remove them on every product, or the defaults must change.
- **The title and price have fixed sizes, not `clamp()` or a fluid preset**, because the owner asked for the same look on every device (24 Nov 2026). Fluid presets change with screen width, which is why this is an exception to the theme's typography rule.
  - **The only step is the title: 1.25rem on phones and tablets, 1.75rem from 992px** (the two-column desktop layout), at the owner's request (24 Nov 2026). The price stays one size everywhere.
  - **The "↓45%" badge is a fixed green, not Accent, and the old price keeps its ₹** (owner's choices, 24 Nov 2026; the design reference shows the old price without ₹).
  - The badge appears only where WooCommerce prints an old and a new price (`egnitech_one_render_product_price_discount`), so variable and grouped products have none.
  - Decimals come from WooCommerce's settings, never from the theme. With ".00" the row wraps on phones.
- **Never size or bold the spans inside the price's `<del>` or `<ins>`.** They must inherit from the row. A rule that reached them made the old price as big as the new one while `<del>` still drew its strike line for smaller text, until 24 Nov 2026.
- **An empty short description prints nothing.** `egnitech_one_render_product_short_description_block` stops WordPress building an excerpt out of the long description, and prints the short description with its HTML kept.
- **The related-products section is WooCommerce's own pattern, called by slug.** If a WooCommerce update renames or drops that pattern, the section disappears with no error.

## Trust badges

- **Each product has an ordered list of up to 10 badges**, each with text, an icon and an on/off switch, edited in its Product Trust Badges box: add, remove, reorder (owner's request, 24 Nov 2026). It is one meta value, `_egnitech_trust_badges`. With none switched on the shortcode prints nothing, not an empty strip.
- **Every write is cleaned by the meta's sanitize callback** (`register_post_meta()` in `inc/woocommerce-trust-badges.php`): badges without text are dropped, an icon that is not offered becomes the tick, and the list is cut to 10. The save handler passes the posted list through unchanged. **Never** clean it only in the save handler or the script: other write paths (WP-CLI, imports) would skip it.
- **No stored list and an empty stored list mean different things.** No list: the product was never saved since the list existed, so it shows its three old fixed rows, read from their old keys (`_egnitech_trust_badge_{1,2,3}` with `_icon` and `_active`) and the old fallbacks. Empty list: the badges were removed on purpose, so none show.
  - **Never** delete the key when the list is empty, or the three defaults come back.
  - **Never** delete the old keys without converting them first: products not saved since 24 Nov 2026 still read them.
  - With the box on the page (the nonce), no posted list means every badge was removed. Quick Edit posts no nonce and leaves the list alone.
- **Icons are stored by name, never as SVG markup**: a theme icon (`lock`) or a WordPress core icon (`core/shield`). A core icon a WordPress update drops becomes the tick when the list is next read.
- **The picker offers the theme's own icons and WordPress's core icon collection** (owner's decision, 24 Nov 2026). The product page prints only the chosen icons, inline. **Never** load an icon font or a whole icon set on the product page: product-page weight is the owner's concern, and Dashicons alone is a 59KB stylesheet.
  - **zap, shield and lock are the Feather drawings the badges first shipped with.** The rest are Lucide. **Never** swap those three for Lucide's redrawn versions: every product still on its defaults would change.
  - **Core icons are solid and drawn smaller inside their square**, so `.trust-icon--solid` fills and enlarges them on the page and in the picker.
  - **Only the `core/` collection is offered.** In WordPress 7.0 the registry cannot take new icons; `wp_register_icon()` is 7.1-only, so the theme's icons live in its own file.
- **The editor is plain JavaScript and native HTML** (owner's choice, 24 Nov 2026, over WordPress's React components, which would break the theme's vanilla-JS rule). It sits inside the product form, so:
  - every button is `type="button"`, there is no nested `<form>`, and Enter in the icon search picks an icon instead of saving the product;
  - the row `<template>` holds no `id`s, and each new row gets an index no other row uses. The form posts rows in on-screen order, which is the page's order.
  - its script depends on WordPress's `wp-a11y` (no jQuery) to announce adds, moves and removals to screen readers. Without that dependency the announcements stop silently.
- **One icon `<dialog>` serves every row.** A grid per row would be over a thousand icons at 10 rows. The dialog opens in the browser's top layer, so WooCommerce's `overflow: hidden` on product meta boxes cannot clip it.
- **A row is draggable only while its handle is held**, so text in its field can still be selected with the mouse. Dragging has been tested with simulated events only; the Move up / Move down buttons are the keyboard and touch path.

## Add to cart and the cart drawer

- **Product Page Button** (Theme Options > WooCommerce) has three settings: Add to Cart on/off, Buy Now on/off, and what Buy Now does (go to checkout, or open the drawer as Add to Cart does). They live in the theme by the owner's choice, so switching themes turns them off. Shop and category buttons keep "Add to cart" and the drawer, with one exception: with Buy Now the only button and set to checkout, a full-page `?add-to-cart=` link (a shop button without JavaScript) also goes to checkout, because every full-page add counts as Buy Now then.
  - **With both switches off, the page still shows Add to Cart** (owner's decision, 16 Nov 2026). **Never** let the page render with no button.
  - **Buy Now alone relabels WooCommerce's own button.** With both on, the theme prints a second button after WooCommerce's (`egnitech_one_print_buy_now_button`). It comes after so that Enter in the quantity field still means Add to Cart. It has WooCommerce's `single_add_to_cart_button` class so the variation script disables it until a choice is made. It carries the product id under `add-to-cart`, because that is the only way a simple product sends it.
  - **With both buttons on, going to checkout is decided per click.** In checkout mode the Buy Now button's `formaction` adds `egnitech-buy-now` to the address, and `egnitech_one_is_buy_now_request()` reads it for the redirect and the drawer cookie. `form.submit()` ignores `formaction`, so the cart script copies it onto the form before **each** fallback submit. A fallback left without it sends Buy Now on a variable product, which always falls back, to the drawer instead of checkout.
  - **Change the no-reload path (`assets/js/woocommerce-mini-cart-auto-open.js`) and the full-page path (`woocommerce_add_to_cart_redirect`) together.** Buy Now going to checkout also stops the drawer cookie being set, or the drawer would pop open over checkout.
  - **The two-button row and the outlined Add to Cart are theme CSS** (`patterns/single-product-details/style.css`). On phones (480px and below) Add to Cart stays beside the stepper and Buy Now takes the full row under it (owner's decision, 16 Nov 2026). Both buttons are placed in explicit columns, because a sold-individually product keeps an empty quantity box that would otherwise take the first cell. **Never** give that row a row gap: WooCommerce's float-clearing pseudo-elements are grid items there and turn into empty rows.
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
- **The two fallbacks differ on purpose.**
  - **When no usable answer comes back** (offline, blocked, a server error), the script adds the clicked button's name and value to the form before submitting (`submitWithButton`). `form.submit()` sends no button, and a simple product's id travels only on its button. A bare `form.submit()` there reloaded the page without adding the product, until 15 Nov 2026. **Never** go back to it.
  - **When WooCommerce answers that it could not add the product**, the script resubmits without the button. A variable product's form carries everything in its own fields. A refused simple product (sold individually and already in the cart, say) already has WooCommerce's message on screen, and sending the button would show that message twice.
  - **Known edge:** if the server adds the product but its answer is unreadable (a PHP notice printed before the JSON), the fallback adds it a second time. The drawer then shows both.
- **Variable products never take the no-reload path.** The request carries the parent product's id, which that endpoint cannot add, so the script falls back to a full page submit. The drawer is then opened on the next page by the short-lived `egnitech_just_added_to_cart` cookie (`inc/woocommerce-cart-drawer.php`). Test a variable product by hand after touching either file.
- **On that next page the script keeps asking the drawer to open, for up to five seconds, until it reports open.** The mini-cart ignores clicks until its own script has started, which is usually after the theme's. **Never** reduce this to a single click: that is why the drawer stayed shut after a variable product was added, until 15 Nov 2026.
- **The cookie is set only by a full-page form post.** No-reload (AJAX) and Store API adds open the drawer on the page where they happen. **Never** set the cookie for them: the drawer would open again on whatever page the shopper visits in the next 20 seconds.
- **The endpoint address is built from the domain root.** On a WordPress installed in a subfolder the request goes to the wrong place and every add-to-cart falls back to a reload.
- **The mini-cart's "open drawer on add" behaviour is forced when the block is rendered** (`egnitech_one_force_mini_cart_drawer_behavior`). Changing that setting on the Mini-Cart block in the Site Editor has no effect.
- **Opening the drawer depends on WooCommerce's internal class names** (the mini-cart button and the drawer overlay's hidden state). After a WooCommerce update that renames them, items are still added but the drawer stays shut.
- **The script loads on every front-end page while WooCommerce is active**, and declares jQuery as a dependency (`assets-and-versioning.md`). Without WooCommerce it is not loaded at all.

## Sticky Buy Now bar

- **The bar has no cart logic of its own. Its −, + and button press the page's real ones** (`sticky-buy-now.js`). That is why Buy Now's checkout marker, the cart drawer, variation checks and bulk-discount prices all behave as they do on the page. **Never** give the bar its own quantity field, form or add-to-cart request: everything in the section above would have to be built twice.
- **It moves with the scroll, not on a timer** (owner's choice, 24 Nov 2026). The share of the real button hidden under the header is the share of the bar shown, so the row seems to wrap round from top to bottom. A bar resting part-shown is intended.
- **Below 992px only, with one action: Buy Now when it is on, otherwise Add to Cart** (owner's choices, 24 Nov 2026). From 992px the page has two columns.
- **The bar ignores taps while the real button is adding.** The cart script itself has no such guard, so a fast second tap on the page's own button can still send a second add.
