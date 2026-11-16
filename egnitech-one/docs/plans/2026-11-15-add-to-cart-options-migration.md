# Plan: move the product page to WooCommerce's "Add to Cart + Options" block

- **Written:** 15 Nov 2026
- **Last reviewed:** 16 Nov 2026 (WooCommerce 11.3.0, WordPress 7.1.4)
- **Status:** Waiting. Do not start until the trigger below is met.

## Trigger

Start when WooCommerce drops "(Beta)" from the block. To check after any WooCommerce update, on the server:

```
grep '"title"' wp-content/plugins/woocommerce/assets/client/blocks/add-to-cart-with-options/block.json
```

Also read that release's notes for changes to the block's Interactivity API stores: WooCommerce said those "will likely change" before the block is marked stable.

## Why we are waiting (owner's decision, 15 Nov 2026)

- **The block is Beta and its JavaScript API is due to change.** Moving now would mean building Buy Now, the cart drawer and bulk discounts on that API, then rebuilding them.
- **WooCommerce's own Single Product template still uses `woocommerce/add-to-cart-form`**, the block we use. WooCommerce's plan is to mark that block "Classic/Legacy" and later hide it from the inserter once the new one is stable.

## Where we stand (15 Nov 2026)

- **The page uses `woocommerce/add-to-cart-form` with WooCommerce's built-in stepper** (`patterns/single-product-details.php`). The theme's own −/+ buttons are gone.
- **Theme CSS on the form uses only supported classes** (`patterns/single-product-details/style.css`), but it still overrides WooCommerce's internal grid to make the button fill the row.

## What depends on the current block (all of it must be rebuilt)

1. **The bulk-discount box** prints on `woocommerce_before_add_to_cart_form` (`inc/woocommerce-bulk-discount.php`). The new block still fires that hook, but the box then sits inside its `<form>`, and any `input`, `select`, `textarea` or `button` in hooked output switches the block to its page-reload fallback ("legacy mode").
2. **`bulk-discount.js` finds the form as `form.cart` and the quantity as `.qty`.** Outside legacy mode the new block's form has no `cart` class.
3. **The no-reload add to cart and the cart drawer** (`assets/js/woocommerce-mini-cart-auto-open.js`) intercept `form.cart` submits. The new block adds to the cart through its own store (`actions.addToCart`).
4. **Buy Now's redirect** sits in that same intercept. It needs whatever stable event or filter the new block offers. The full-page path (`woocommerce_add_to_cart_redirect`) should carry over.
5. **The full-width row** is theme CSS today. The new block's Product Button has a width setting, so the CSS override should go entirely.
6. **The "Buy Now" label** comes from `woocommerce_product_single_add_to_cart_text`. Confirm the new block's button reads that filter.
7. **The second Buy Now button** (Add to Cart and Buy Now both on) is a `<button>` printed on `woocommerce_after_add_to_cart_button`. Inside the new block that forces legacy mode, as in item 1, so it must be rebuilt on the new block's own markup. Its per-click checkout marker (`egnitech-buy-now`) must be carried over as well.

## Goal

- **No theme CSS on WooCommerce's internal markup:** button width and stepper come from block settings.
- **Bulk discounts, the drawer and Buy Now built on WooCommerce's stable, documented APIs only.**
- **Customers see the same thing.** Prices, bulk tiers and add-to-cart behaviour must not change.

## How to do it

1. **Re-check the trigger and this plan's assumptions** against the WooCommerce version in use, then update **Last reviewed** above.
2. **Build and test away from the live site.** On homegears.in the theme clone *is* production (`ai-context.md`).
3. **Test every product type and setting:**
   - product types: simple, variable, sold individually (no stepper), external and grouped;
   - bulk tiers 1/2/3; Add to Cart only, Buy Now only and both, each Buy Now in checkout and drawer mode; and the drawer opening;
   - widths of 1400, 600 and 375px.
4. **Remove whatever the move makes obsolete,** then update `docs/built-systems/woocommerce-product-page.md`, `bulk-discounts.md`, and this plan's status.

## Open WooCommerce issues seen while planning (re-check)

- [#66239](https://github.com/woocommerce/woocommerce/issues/66239): variation options disabled for scheduled products.
- [#69461](https://github.com/woocommerce/woocommerce/issues/69461): variation price shown above the button instead of updating the Product Price block.
- [gtm4wp #462](https://github.com/duracelltomi/gtm4wp/issues/462): a plugin's hidden input forces the block into legacy mode, the same trap as item 1 above.
