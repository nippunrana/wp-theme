<?php
declare(strict_types=1);

/**
 * WooCommerce Cart Drawer Auto-Open System.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set a cookie when a product is added to the cart to open the drawer on page load as a fallback.
 *
 * @param mixed $cart_item_key The cart item key.
 * @param mixed $product_id    The product ID.
 * @param mixed $quantity      The quantity.
 * @param mixed $variation_id  The variation ID.
 * @param mixed $variation     The variation data.
 * @param mixed $cart_item_data The cart item data.
 * @return void
 */
function egnitech_one_set_add_to_cart_cookie(
	mixed $cart_item_key,
	mixed $product_id,
	mixed $quantity,
	mixed $variation_id,
	mixed $variation,
	mixed $cart_item_data
): void {
	setcookie( 'egnitech_just_added_to_cart', '1', time() + 20, '/' );
}
add_action( 'woocommerce_add_to_cart', 'egnitech_one_set_add_to_cart_cookie', 10, 6 );

/**
 * Force the mini-cart block to open the drawer when a product is added via AJAX.
 * We do this by setting the addToCartBehaviour attribute to 'open_drawer' on render.
 *
 * @param array $block_parsed The parsed block data.
 * @return array The modified block data.
 */
function egnitech_one_force_mini_cart_drawer_behavior( array $block_parsed ): array {
	if ( isset( $block_parsed['blockName'] ) && 'woocommerce/mini-cart' === $block_parsed['blockName'] ) {
		if ( ! isset( $block_parsed['attrs'] ) ) {
			$block_parsed['attrs'] = array();
		}
		$block_parsed['attrs']['addToCartBehaviour'] = 'open_drawer';
	}
	return $block_parsed;
}
add_filter( 'render_block_data', 'egnitech_one_force_mini_cart_drawer_behavior', 10, 1 );

/**
 * Enqueue the mini-cart auto-open script on the frontend.
 *
 * @return void
 */
function egnitech_one_enqueue_mini_cart_script(): void {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'egnitech-one-mini-cart-open',
		get_template_directory_uri() . '/assets/js/woocommerce-mini-cart-auto-open.js',
		array( 'jquery' ),
		(string) wp_get_theme()->get( 'Version' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_mini_cart_script', 20 );
