# Bulk discounts ("Buy more, save more")

Code: `inc/woocommerce-bulk-discount.php` (the product-edit tab, cart pricing, the card selector), `patterns/single-product-details/bulk-discount.js`, styles in `patterns/single-product-details/style.css`. The script is loaded only on a product page whose product has at least one tier (`inc/enqueue-assets.php`).

## Rules the code enforces

- **Up to three tiers per product, each a minimum quantity and a percentage.** The highest tier whose minimum is met applies. Percentages never add up.
- **The discount is a lower unit price, not a discount line.** `egnitech_one_apply_bulk_discounts` sets each cart line's price before totals are worked out. The cart, checkout, order and reports show a cheaper item. There is no coupon or fee to look for.
- **The same sum is written three times**: in PHP for the cart (the one that charges money), in PHP for the cards' first paint, and in JavaScript for every repaint after that. **Change the rule in all three, or the page promises a price the cart does not charge.**

- **The cards and the total are priced on what is already in the cart plus what is being added** (owner's decision, 16 Nov 2026), because the cart charges the tier the whole line reaches. Seven in the cart and "Buy 1" shows the eight-unit price, with an "already in your cart" notice above the cards. Without it the page promised full price and the cart charged less, or the reverse.
  - **The cart is read in the browser from the Store API, never in PHP**: the page is cached for every logged-out visitor (`page-cache.md`). The first paint is the plain tier prices; the script corrects it once the cart answers.
  - **It reads the cart only when WooCommerce's `woocommerce_items_in_cart` cookie is present**, so an empty-cart visit costs no extra request. It reads again on `wc-blocks_added_to_cart`, `wc-blocks_removed_from_cart` and a back-forward-cache `pageshow`.
  - **It matches the cart line the cart itself uses**: the product id, or for a variable product the chosen variation's id, and nothing until a variation is chosen. **Never** sum lines across variations: the cart does not.

## Traps

- **Never work the discount out from the cart line's own price.** The function fetches a fresh copy of the product for its base price, because WooCommerce can work out totals several times in one request and the line's own price would be discounted again each time.
- **For the same reason it overwrites any price another plugin put on that line** (add-ons, other dynamic pricing). A discounted line always ends as base price minus the tier percentage.
- **Quantity is counted per cart line, while tiers are read from the parent product.** Two variations of one product are two lines: one red and one blue do not reach a "buy 2" tier, even though both share the same tiers.
- **The cards format money themselves once the script runs.** PHP prints prices with `wc_price()`. The script then rewrites every card and the total as soon as it runs, with two fixed decimals and the currency symbol only. It ignores the shop's thousand separator, decimal separator and decimal count, so the first paint (`₹1,995.00`) changes to `₹1995.00`. This is display only. The cart is right.
- **Simple products use the raw price; variations use WooCommerce's display price.** On a shop that shows prices with tax added or removed, the cards for a simple product and for a variation are worked out from different bases.
- **The + and − quantity buttons are WooCommerce's own stepper**, switched on in the product pattern (`woocommerce-product-page.md`). Each click fires a bubbling `change` event on the quantity input, and the cards repaint from that. **Never** put quantity-button handlers into `bulk-discount.js`: both would fire and each click would step twice.
- **Phones never show the line under "Buy N"** (a tier's Benefit Text, or Buy 1's fixed text). It was cut off there (owner's decision, 15 Nov 2026). Wider screens still show it, so **never** remove the Benefit Text field as unused.
- **Phones show no line under the total**: neither "Saved … off" nor the Buy 1 "Buy 2+ and save" hint, because both repeat what the cards already say (owner's decision, 16 Nov 2026). Tablets and wider still show both.
- **Every text the script writes is fixed English**, outside the theme's translations: the card and summary texts ("Saved … off", "each", "Save Extra", "Adding … in cart:", which replaces the translated "Total Cost:") and the in-cart notice.
