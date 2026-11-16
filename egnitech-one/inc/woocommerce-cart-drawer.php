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
 * Whether product pages show a Buy Now button (Theme Options > WooCommerce > Product Page Button).
 */
function egnitech_one_is_buy_now(): bool {
	return 'yes' === egnitech_one_get_option( 'egnitech_one_woocommerce_buy_now', '' );
}

/**
 * Whether product pages show an Add to Cart button. With Buy Now off as well it still does:
 * a page with no button could not sell anything.
 */
function egnitech_one_shows_add_to_cart_button(): bool {
	return ! egnitech_one_is_buy_now() || 'yes' === egnitech_one_get_option( 'egnitech_one_woocommerce_add_to_cart_button', 'yes' );
}

/**
 * Where Buy Now sends the shopper: checkout, or '' when Buy Now is off or set to open the
 * cart drawer as Add to Cart does.
 */
function egnitech_one_buy_now_checkout_url(): string {
	if ( ! egnitech_one_is_buy_now() || 'cart' === egnitech_one_get_option( 'egnitech_one_woocommerce_buy_now_behaviour', 'checkout' ) || ! function_exists( 'wc_get_checkout_url' ) ) {
		return '';
	}
	return wc_get_checkout_url();
}

/**
 * Whether this full-page add to cart should go on to checkout. With Buy Now the only button,
 * every add does. With both buttons, only a click on Buy Now: its formaction adds the
 * egnitech-buy-now query argument, and the cart script copies that onto its fallback submits.
 */
function egnitech_one_is_buy_now_request(): bool {
	if ( '' === egnitech_one_buy_now_checkout_url() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a routing flag, not data.
	return ! egnitech_one_shows_add_to_cart_button() || isset( $_GET['egnitech-buy-now'] );
}

/**
 * Buy Now as the only button: relabel WooCommerce's own button. External products keep their
 * own text: their button links to another shop and never reaches checkout here.
 */
function egnitech_one_buy_now_button_text( string $text, mixed $product = null ): string {
	if ( egnitech_one_shows_add_to_cart_button() || ( $product instanceof WC_Product && $product->is_type( 'external' ) ) ) {
		return $text;
	}
	return __( 'Buy Now', 'egnitech-one' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'egnitech_one_buy_now_button_text', 10, 2 );

/**
 * Both buttons on: print Buy Now after WooCommerce's Add to Cart button, inside its form.
 * After it, so pressing Enter in the quantity field still means Add to Cart.
 * It carries WooCommerce's button classes so the variation script disables it until a choice is
 * made, and the product id under add-to-cart, which is the only place a simple product sends it.
 * Its formaction, printed only when Buy Now goes to checkout, is what marks the click as Buy Now.
 */
function egnitech_one_print_buy_now_button(): void {
	global $product;
	if ( ! egnitech_one_is_buy_now() || ! egnitech_one_shows_add_to_cart_button() || ! $product instanceof WC_Product || $product->is_type( 'external' ) ) {
		return;
	}
	$formaction = '';
	if ( '' !== egnitech_one_buy_now_checkout_url() ) {
		$formaction = sprintf( ' formaction="%s"', esc_url( add_query_arg( 'egnitech-buy-now', '1', apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ) ) );
	}
	$element_class = wc_wp_theme_get_element_class_name( 'button' );
	printf(
		'<button type="submit" name="add-to-cart" value="%1$s"%2$s class="single_add_to_cart_button button alt%3$s egnitech-buy-now-button">%4$s</button>',
		esc_attr( (string) $product->get_id() ),
		$formaction, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		esc_attr( $element_class ? ' ' . $element_class : '' ),
		esc_html__( 'Buy Now', 'egnitech-one' )
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'egnitech_one_print_buy_now_button' );

/**
 * Buy Now, full-page path (variable products, or when the no-reload add fails): go to checkout.
 * The no-reload path redirects in assets/js/woocommerce-mini-cart-auto-open.js.
 */
function egnitech_one_buy_now_redirect( mixed $url ): mixed {
	return egnitech_one_is_buy_now_request() ? egnitech_one_buy_now_checkout_url() : $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'egnitech_one_buy_now_redirect' );

/**
 * Cached pages carry the buttons and the cart script's settings, so clear them when any of
 * the three settings changes.
 */
function egnitech_one_purge_cache_on_buy_now_change(): void {
	if ( function_exists( 'egnitech_one_purge_all_site_cache' ) ) {
		egnitech_one_purge_all_site_cache();
	}
}
add_action( 'update_option_egnitech_one_woocommerce_add_to_cart_button', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'add_option_egnitech_one_woocommerce_add_to_cart_button', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'update_option_egnitech_one_woocommerce_buy_now', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'add_option_egnitech_one_woocommerce_buy_now', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'update_option_egnitech_one_woocommerce_buy_now_behaviour', 'egnitech_one_purge_cache_on_buy_now_change' );
add_action( 'add_option_egnitech_one_woocommerce_buy_now_behaviour', 'egnitech_one_purge_cache_on_buy_now_change' );

/**
 * Set a cookie when a product is added to the cart by a full-page form post, so the drawer opens
 * on the page that follows.
 *
 * @return void
 */
function egnitech_one_set_add_to_cart_cookie(): void {
	// Buy Now sends the shopper to checkout, where a drawer popping open would be in the way.
	if ( egnitech_one_is_buy_now_request() ) {
		return;
	}
	// An AJAX or REST add opens the drawer on the page it came from. A cookie set there would open
	// it again on whichever page the shopper visits next.
	if ( wp_doing_ajax() || wp_is_serving_rest_request() ) {
		return;
	}
	// The cart-drawer script reads and clears this cookie, so it cannot be HttpOnly and its path must stay '/'.
	setcookie(
		'egnitech_just_added_to_cart',
		'1',
		array(
			'expires'  => time() + 20,
			'path'     => '/',
			'secure'   => is_ssl(),
			'samesite' => 'Lax',
		)
	);
}
add_action( 'woocommerce_add_to_cart', 'egnitech_one_set_add_to_cart_cookie', 10, 0 );

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
	// There is no cart to open without WooCommerce: skip the script, and the jQuery it depends on.
	if ( ! class_exists( 'WooCommerce' ) ) {
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
		array(
			'checkoutUrl' => egnitech_one_buy_now_checkout_url(),
			'buyNowOnly'  => ! egnitech_one_shows_add_to_cart_button(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'egnitech_one_enqueue_mini_cart_script', 20 );
