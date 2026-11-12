# Bulk discounts ("Buy more, save more")

Code: `inc/woocommerce-bulk-discount.php` (the product-edit tab, cart pricing, the card selector), `patterns/single-product-details/bulk-discount.js`, styles in `patterns/single-product-details/style.css`. The script is loaded only on a product page whose product has at least one tier (`inc/enqueue-assets.php`).

## Rules the code enforces

- **Up to three tiers per product, each a minimum quantity and a percentage.** The highest tier whose minimum is met applies. Percentages never add up.
- **The discount is a lower unit price, not a discount line.** `egnitech_one_apply_bulk_discounts` sets each cart line's price before totals are worked out. The cart, checkout, order and reports show a cheaper item. There is no coupon or fee to look for.
- **The same sum is written three times**: in PHP for the cart (the one that charges money), in PHP for the cards' first paint, and in JavaScript for every repaint after that. **Change the rule in all three, or the page promises a price the cart does not charge.**

## Traps

- **Never work the discount out from the cart line's own price.** The function fetches a fresh copy of the product for its base price, because WooCommerce can work out totals several times in one request and the line's own price would be discounted again each time.
- **For the same reason it overwrites any price another plugin put on that line** (add-ons, other dynamic pricing). A discounted line always ends as base price minus the tier percentage.
- **Quantity is counted per cart line, while tiers are read from the parent product.** Two variations of one product are two lines: one red and one blue do not reach a "buy 2" tier, even though both share the same tiers.
- **The cards format money themselves once the script runs.** PHP prints prices with `wc_price()`. The script then rewrites the total and the last card straight away, and every card when a variation is chosen, with two fixed decimals and the currency symbol only. It ignores the shop's thousand separator, decimal separator and decimal count, so two formats can sit side by side. This is display only. The cart is right.
- **Simple products use the raw price; variations use WooCommerce's display price.** On a shop that shows prices with tax added or removed, the cards for a simple product and for a variation are worked out from different bases.
- **The + and − quantity buttons are printed on every classic WooCommerce quantity box, but only this script makes them work**, and it stops at once when the page has no tier selector. On a product without tiers the script is not loaded at all: the buttons are visible and do nothing.
- **The card text the script writes is fixed English** ("Saved … off", "each"), outside the theme's translations.
