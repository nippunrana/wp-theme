/**
 * Quantity stepper: makes the + and − buttons printed beside WooCommerce's quantity box work.
 *
 * A script module, loaded on every product page (inc/enqueue-assets.php). The buttons themselves
 * come from inc/woocommerce-bulk-discount.php.
 */
document.addEventListener( 'click', ( event ) => {
	const button = event.target.closest( '.egnitech-qty-btn' );
	if ( ! button ) {
		return;
	}

	const input = button.closest( '.quantity' )?.querySelector( '.qty' );
	if ( ! input || 'number' !== input.type || input.disabled || input.readOnly ) {
		return;
	}

	const before = input.value;

	// The browser's own stepper: it obeys the box's min, max and step.
	if ( button.classList.contains( 'minus' ) ) {
		input.stepDown();
	} else {
		input.stepUp();
	}

	if ( input.value !== before ) {
		// WooCommerce and the bulk-discount cards listen for these.
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}
} );
