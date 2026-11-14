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
 * Whether the product page button is "Buy Now" (Theme Options > WooCommerce > Product Page Button).
 */
function egnitech_one_is_buy_now(): bool {
	return 'yes' === egnitech_one_get_option( 'egnitech_one_woocommerce_buy_now', '' );
}

/**
 * "Buy Now" label on the single product page button. External products keep their own text:
 * their button links to another shop and never reaches checkout here.
 */
function egnitech_one_buy_now_button_text( string $text, mixed $product = null ): string {
	if ( ! egnitech_one_is_buy_now() || ( $product instanceof WC_Product && $product->is_type( 'external' ) ) ) {
		return $text;
	}
	return __( 'Buy Now', 'egnitech-one' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'egnitech_one_buy_now_button_text', 10, 2 );

/**
 * Buy Now, full-page path (variable products, or when the no-reload add fails): go to checkout.
 * The no-reload path redirects in assets/js/woocommerce-mini-cart-auto-open.js.
 */
function egnitech_one_buy_now_redirect( mixed $url ): mixed {
	return egnitech_one_is_buy_now() && function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'egnitech_one_buy_now_redirect' );

/**
 * Cached pages carry the button label, so clear them when the setting changes.
 */
function egnitech_one_purge_cache_on_buy_now_change(): void {
	if ( function_exists( 'egnitech_one_purge_all_site_cache' ) ) {
		egnitech_one_purge_all_site_cache();
	}
}
add_action( 'update_option_egnitech_one_woocommerce_buy_now', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'add_option_egnitech_one_woocommerce_buy_now', 'egnitech_one_purge_cache_on_buy_now_change' );

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
	// Buy Now sends the shopper to checkout, where a drawer popping open would be in the way.
	if ( egnitech_one_is_buy_now() ) {
		return;
	}
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
		egnitech_one_asset_version( get_template_directory() . '/assets/js/woocommerce-mini-cart-auto-open.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'egnitech-one-mini-cart-open',
		'egnitechCart',
		array( 'checkoutUrl' => egnitech_one_is_buy_now() && function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '' )
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_mini_cart_script', 20 );
