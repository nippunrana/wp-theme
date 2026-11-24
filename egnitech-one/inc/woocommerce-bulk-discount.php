<?php
declare(strict_types=1);

/**
 * WooCommerce Bulk Discount System.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a custom tab for Bulk Discounts in WooCommerce Product Data.
 *
 * @param array $tabs Product data tabs.
 * @return array Modified product data tabs.
 */
function egnitech_one_add_bulk_discount_tab( array $tabs ): array {
	$tabs['egnitech_bulk_discount'] = array(
		'label'    => __( 'Bulk Discounts', 'egnitech-one' ),
		'target'   => 'egnitech_bulk_discount_data',
		'class'    => array( 'show_if_simple', 'show_if_variable' ),
		'priority' => 50,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'egnitech_one_add_bulk_discount_tab', 10, 1 );

/**
 * Render the Bulk Discounts settings panel.
 */
function egnitech_one_render_bulk_discount_panel(): void {
	?>
	<div id="egnitech_bulk_discount_data" class="panel woocommerce_options_panel" style="display: none;">
		<div class="options_group">
			<h2 style="padding: 0 12px; margin-bottom: 10px; font-size: 16px; font-weight: 600; color: #111;">
				<?php esc_html_e( 'Quantity Bulk Discounts (Max 3 Tiers)', 'egnitech-one' ); ?>
			</h2>
			<p style="padding: 0 12px; margin-bottom: 20px; color: #666; font-style: italic;">
				<?php esc_html_e( 'Configure quantity-based discount tiers. Only the highest eligible discount will be applied. Discount percentages do not accumulate.', 'egnitech-one' ); ?>
			</p>
			<?php
			for ( $i = 1; $i <= 3; $i++ ) {
				echo '<div class="egnitech-discount-tier-wrapper" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px; padding-bottom: 5px;">';
				echo '<h3 style="padding: 0 12px; margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #222;">' . sprintf( esc_html__( 'Discount Tier %d', 'egnitech-one' ), $i ) . '</h3>';

				woocommerce_wp_text_input( array(
					'id'                => "_bulk_discount_tier_{$i}_qty",
					'label'             => __( 'Minimum Quantity', 'egnitech-one' ),
					'placeholder'       => 'e.g. 2',
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
					),
				) );

				woocommerce_wp_text_input( array(
					'id'                => "_bulk_discount_tier_{$i}_val",
					'label'             => __( 'Discount Percentage (%)', 'egnitech-one' ),
					'placeholder'       => 'e.g. 10',
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '0',
						'max'  => '100',
						'step' => '0.1',
					),
				) );

				woocommerce_wp_text_input( array(
					'id'                => "_bulk_discount_tier_{$i}_label",
					'label'             => __( 'Benefit Text (Optional)', 'egnitech-one' ),
					'placeholder'       => 'e.g. Starter Pack — Save on extra devices',
					'type'              => 'text',
				) );

				echo '</div>';
			}
			?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'egnitech_one_render_bulk_discount_panel' );

/**
 * Save Bulk Discount settings.
 *
 * @param int $product_id Product ID.
 */
function egnitech_one_save_bulk_discount_fields( int $product_id ): void {
	for ( $i = 1; $i <= 3; $i++ ) {
		$qty_key   = "_bulk_discount_tier_{$i}_qty";
		$val_key   = "_bulk_discount_tier_{$i}_val";
		$label_key = "_bulk_discount_tier_{$i}_label";

		if ( isset( $_POST[ $qty_key ] ) ) {
			$qty_val = sanitize_text_field( $_POST[ $qty_key ] );
			update_post_meta( $product_id, $qty_key, '' !== $qty_val ? (string) absint( $qty_val ) : '' );
		}
		if ( isset( $_POST[ $val_key ] ) ) {
			$val_val = sanitize_text_field( $_POST[ $val_key ] );
			// The field's 0-100 limit is only a browser hint, and a percentage above 100 would make the price negative.
			update_post_meta( $product_id, $val_key, '' !== $val_val ? (string) min( 100.0, max( 0.0, floatval( $val_val ) ) ) : '' );
		}
		if ( isset( $_POST[ $label_key ] ) ) {
			update_post_meta( $product_id, $label_key, sanitize_text_field( $_POST[ $label_key ] ) );
		}
	}
}
add_action( 'woocommerce_process_product_meta', 'egnitech_one_save_bulk_discount_fields', 10, 1 );

/**
 * Apply bulk discounts dynamically to cart item prices.
 *
 * @param WC_Cart $cart WooCommerce cart object.
 */
function egnitech_one_apply_bulk_discounts( WC_Cart $cart ): void {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		$product_id = $cart_item['product_id'];
		$quantity   = $cart_item['quantity'];

		$applied_discount = 0.0;
		$max_met_qty      = -1;

		// Loop through tiers to find the highest met quantity tier
		for ( $i = 1; $i <= 3; $i++ ) {
			$t_qty = (int) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_qty", true );
			$t_val = (float) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_val", true );

			if ( $t_qty > 0 && $t_val > 0 && $quantity >= $t_qty ) {
				if ( $t_qty > $max_met_qty ) {
					$max_met_qty      = $t_qty;
					$applied_discount = $t_val;
				}
			}
		}

		if ( $applied_discount > 0.0 ) {
			// Retrieve clean base price of the product (or variation)
			$product_obj = wc_get_product( $cart_item['data']->get_id() );
			if ( $product_obj ) {
				$base_price = (float) $product_obj->get_price();
				$discounted_price = $base_price * ( 1.0 - ( $applied_discount / 100.0 ) );
				
				// Apply discounted price to cart item
				$cart_item['data']->set_price( $discounted_price );
			}
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'egnitech_one_apply_bulk_discounts', 10, 1 );

/**
 * Render the bulk discount selector on the single product page frontend.
 */
function egnitech_one_render_bulk_discount_selector(): void {
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}

	$product_id = $product->get_id();

	// Retrieve tiers
	$active_tiers = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$qty   = (int) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_qty", true );
		$val   = (float) get_post_meta( $product_id, "_bulk_discount_tier_{$i}_val", true );

		if ( $qty > 0 && $val > 0 ) {
			$active_tiers[] = array(
				'qty'   => $qty,
				'pct'   => $val,
			);
		}
	}

	if ( empty( $active_tiers ) ) {
		return;
	}

	// Sort tiers in ascending order of quantity so they list logically (Buy 2+, Buy 3+, Buy 5+)
	usort( $active_tiers, static function( array $a, array $b ): int {
		return $a['qty'] <=> $b['qty'];
	} );

	// WooCommerce's display price, which follows the shop's tax display setting. Variations reach the
	// script as display_price, built the same way. The cart keeps the raw price (see bulk-discounts.md).
	$base_price = (float) wc_get_price_to_display( $product );
	$currency_symbol = get_woocommerce_currency_symbol();
	$currency_pos = get_option( 'woocommerce_currency_pos', 'left' );

	/**
	 * Helper function to format price inside loop.
	 */
	$format_price = static function( float $price ): string {
		return strip_tags( wc_price( $price ) );
	};

	?>
	<div class="egnitech-bulk-discount-wrapper" id="egnitech-bulk-discount" 
		data-default-price="<?php echo esc_attr( (string) $base_price ); ?>"
		data-base-price="<?php echo esc_attr( (string) $base_price ); ?>"
		data-currency-symbol="<?php echo esc_attr( $currency_symbol ); ?>"
		data-currency-pos="<?php echo esc_attr( $currency_pos ); ?>"
		data-price-decimals="<?php echo esc_attr( (string) wc_get_price_decimals() ); ?>"
		data-decimal-separator="<?php echo esc_attr( wc_get_price_decimal_separator() ); ?>"
		data-thousand-separator="<?php echo esc_attr( wc_get_price_thousand_separator() ); ?>"
		data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
		data-cart-url="<?php echo esc_url( rest_url( 'wc/store/v1/cart' ) ); ?>">
		
		<div class="egnitech-bulk-discount-header">
			<h3 class="egnitech-bulk-discount-heading">
				<svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7V6a5 5 0 0 1 10 0v1h2.1a1 1 0 0 1 1 .92l1.03 12.4A1.6 1.6 0 0 1 19.53 22H4.47a1.6 1.6 0 0 1-1.6-1.68L3.9 7.92A1 1 0 0 1 4.9 7H7Zm2 0h6V6a3 3 0 0 0-6 0v1Z"/></svg>
				<?php esc_html_e( 'Buy More, Save More!', 'egnitech-one' ); ?>
			</h3>
			<span class="egnitech-bulk-discount-upto"><?php echo sprintf( esc_html__( 'Save up to %g%%', 'egnitech-one' ), max( array_column( $active_tiers, 'pct' ) ) ); ?></span>
		</div>

		<!-- Filled in by bulk-discount.js when this product is already in the cart. Never fill it in PHP: the page is cached for every visitor. -->
		<div class="egnitech-bulk-cart-notice" hidden>
			<span class="egnitech-bulk-cart-notice-text">
				<strong class="egnitech-bulk-cart-notice-title"></strong>
				<span class="egnitech-bulk-cart-notice-detail"></span>
			</span>
			<a class="egnitech-bulk-cart-notice-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'egnitech-one' ); ?></a>
		</div>
		
		<div class="egnitech-bulk-discount-grid">
			<!-- Tier 0: Base Price (1 Device) -->
			<div class="egnitech-bulk-discount-card active" data-qty="1" data-pct="0">
				<span class="egnitech-card-eyebrow"><?php esc_html_e( 'Standard', 'egnitech-one' ); ?></span>
				<span class="egnitech-card-qty"><?php esc_html_e( 'Buy', 'egnitech-one' ); ?> <span class="egnitech-qty-num">1</span></span>
				<!-- Empty unless the cart pushes Buy 1 into a tier; "Base pack" hides while it shows -->
				<span class="egnitech-card-save"></span>
				<span class="egnitech-card-note"><?php esc_html_e( 'Base pack', 'egnitech-one' ); ?></span>
				<div class="egnitech-card-pricing">
					<span class="egnitech-price-original"></span>
					<span class="egnitech-price-amount">
						<span class="egnitech-price-num"><?php echo esc_html( $format_price( $base_price ) ); ?></span>
					</span>
					<span class="egnitech-price-label"><?php echo sprintf( esc_html__( '%s each', 'egnitech-one' ), esc_html( $format_price( $base_price ) ) ); ?></span>
				</div>
			</div>

			<!-- Active Tiers -->
			<?php 
			$total_tiers = count( $active_tiers );
			foreach ( $active_tiers as $index => $tier ) : 
				$is_last = ( $index === $total_tiers - 1 );
				$qty = $tier['qty'];
				$pct = $tier['pct'];
				$discounted_price = $base_price * ( 1.0 - ( $pct / 100.0 ) );
				$total_discounted_price = $discounted_price * $qty;
				$total_original_price = $base_price * $qty;
				?>
				<div class="egnitech-bulk-discount-card<?php echo $is_last ? ' egnitech-last-tier' : ''; ?>" 
					data-qty="<?php echo esc_attr( (string) $qty ); ?>" 
					data-pct="<?php echo esc_attr( (string) $pct ); ?>"
					<?php echo $is_last ? 'data-is-last="true"' : ''; ?>>

					<?php if ( $is_last ) : ?>
						<span class="egnitech-card-top-badge is-best-value"><svg aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96L12 2.5z"/></svg><?php esc_html_e( 'Best Value', 'egnitech-one' ); ?></span>
					<?php elseif ( $index === 0 ) : ?>
						<span class="egnitech-card-top-badge"><?php esc_html_e( 'Most Popular', 'egnitech-one' ); ?></span>
					<?php endif; ?>
					<span class="egnitech-card-qty"><?php esc_html_e( 'Buy', 'egnitech-one' ); ?> <span class="egnitech-qty-num"><?php echo esc_html( (string) $qty ); ?></span></span>
					<span class="egnitech-card-save"><?php echo sprintf( esc_html__( 'Save Extra %g%%', 'egnitech-one' ), $pct ); ?></span>
					<div class="egnitech-card-pricing">
						<span class="egnitech-price-original"><?php echo esc_html( $format_price( $total_original_price ) ); ?></span>
						<span class="egnitech-price-amount">
							<span class="egnitech-price-num"><?php echo esc_html( $format_price( $total_discounted_price ) ); ?></span>
						</span>
						<span class="egnitech-price-label"><?php echo sprintf( esc_html__( '%s each', 'egnitech-one' ), esc_html( $format_price( $discounted_price ) ) ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Summary: bulk-discount.js keeps both halves in step with the stepper and the cart -->
		<div class="egnitech-bulk-discount-summary">
			<div class="egnitech-summary-selected">
				<span class="egnitech-summary-selected-label"><?php esc_html_e( 'Selected:', 'egnitech-one' ); ?></span>
				<strong class="egnitech-summary-selected-value"><?php esc_html_e( 'Buy', 'egnitech-one' ); ?> 1</strong>
			</div>
			<div class="egnitech-summary-text">
				<span class="egnitech-summary-label"><?php esc_html_e( 'Total:', 'egnitech-one' ); ?></span>
				<span class="egnitech-summary-total"><?php echo esc_html( $format_price( $base_price ) ); ?></span>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_form', 'egnitech_one_render_bulk_discount_selector', 15 );
