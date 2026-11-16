/**
 * WooCommerce Mini-Cart Auto-Open and Single Product AJAX Add-to-Cart.
 *
 * @package EgniTech_One
 */

(function() {
	'use strict';

	/**
	 * Whether the mini-cart drawer is showing.
	 *
	 * @return {boolean} True when the drawer is open.
	 */
	function isMiniCartDrawerOpen() {
		const overlay = document.querySelector('.wc-block-components-drawer__screen-overlay');
		return !!overlay && !overlay.classList.contains('wc-block-components-drawer__screen-overlay--is-hidden');
	}

	/**
	 * Helper function to open the mini-cart drawer.
	 *
	 * @return {boolean} True if successful, false otherwise.
	 */
	function openMiniCartDrawer() {
		// 1. Try opening via Interactivity API if available
		if (typeof wp !== 'undefined' && wp.interactivity) {
			try {
				const store = wp.interactivity.store('woocommerce/mini-cart');
				if (store && store.state) {
					if (!store.state.isOpen) {
						store.state.isOpen = true;
					}
					return true;
				}
			} catch (e) {
				console.warn('Interactivity API open failed, falling back to click:', e);
			}
		}

		// 2. Fallback: Click the mini-cart button
		const miniCartButton = document.querySelector('.wc-block-mini-cart__button');
		if (miniCartButton && !isMiniCartDrawerOpen()) {
			miniCartButton.click();
			return true;
		}
		return false;
	}

	/**
	 * Check if we just added a product via page reload (cookie fallback).
	 */
	function checkAddToCartCookie() {
		const cookieName = 'egnitech_just_added_to_cart';
		if (document.cookie.split(';').some((item) => item.trim().startsWith(cookieName + '='))) {
			// Clear the cookie
			document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

			// The mini-cart ignores clicks until its own script has started, which is often after this
			// one runs. So keep asking, for up to five seconds, until the drawer reports open: the
			// button only ever opens the drawer, so asking twice does no harm.
			openMiniCartDrawer();
			let attempts = 0;
			const interval = setInterval(() => {
				attempts++;
				if (isMiniCartDrawerOpen() || attempts > 50) {
					clearInterval(interval);
					return;
				}
				openMiniCartDrawer();
			}, 100);
		}
	}

	/**
	 * Submit the form the ordinary way, as a click on its button would have.
	 * form.submit() sends no button, and a simple product's id travels only on its
	 * add-to-cart button, so the button's name and value are added as a field first.
	 *
	 * @param {HTMLFormElement}   form   The add-to-cart form.
	 * @param {HTMLButtonElement} button The button that was clicked.
	 */
	function submitWithButton(form, button) {
		if (button.name && !form.querySelector('input[name="' + CSS.escape(button.name) + '"]')) {
			const field = document.createElement('input');
			field.type = 'hidden';
			field.name = button.name;
			field.value = button.value;
			form.appendChild(field);
		}
		form.submit();
	}

	/**
	 * Listen for AJAX add to cart event (jQuery-based archives).
	 */
	function initAjaxListener() {
		if (typeof jQuery !== 'undefined') {
			jQuery(document.body).on('added_to_cart', function() {
				// Delay slightly to ensure mini-cart has updated its state
				setTimeout(openMiniCartDrawer, 150);
			});
		}
	}

	/**
	 * Intercept single product add-to-cart form submissions and process them via AJAX.
	 */
	function initSingleProductAjaxForm() {
		document.addEventListener('submit', function(event) {
			const form = event.target;
			if (!form || !form.classList.contains('cart')) {
				return;
			}

			// Don't intercept if it's an external product form
			if (form.action && (form.action.includes('http') && !form.action.includes(window.location.hostname))) {
				return;
			}

			// The button that was clicked: with Buy Now beside Add to Cart (Theme Options) the form has two.
			const submitButton = event.submitter && event.submitter.classList.contains('single_add_to_cart_button')
				? event.submitter
				: form.querySelector('.single_add_to_cart_button');
			if (!submitButton) {
				return;
			}

			// Don't intercept if the button is disabled (e.g. variations not selected)
			if (submitButton.disabled || submitButton.classList.contains('disabled')) {
				return;
			}

			event.preventDefault();

			// Buy Now beside Add to Cart marks its request through its formaction, which form.submit()
			// ignores: the fallbacks below copy it onto the form, so they still reach checkout. The form's
			// own address is kept first: after Back, a page restored from the browser's cache would
			// otherwise still carry Buy Now's, and send Add to Cart to checkout.
			if (!form.dataset.egnitechAction) {
				form.dataset.egnitechAction = form.action;
			}
			const fallbackAction = submitButton.hasAttribute('formaction') ? submitButton.formAction : form.dataset.egnitechAction;
			const config = window.egnitechCart || {};
			const toCheckout = config.checkoutUrl && (config.buyNowOnly || submitButton.classList.contains('egnitech-buy-now-button'));

			// Add visual loading state
			submitButton.classList.add('loading');
			submitButton.style.opacity = '0.7';
			const originalButtonText = submitButton.innerHTML;
			submitButton.innerHTML = 'Adding to cart…';

			const formData = new FormData(form);
			
			// Find the product ID before we delete 'add-to-cart' to prevent double addition
			let productId = form.querySelector('[name="add-to-cart"]')?.value || submitButton.value;
			if (!productId && submitButton.name === 'add-to-cart') {
				productId = submitButton.value;
			}
			if (!productId) {
				productId = form.querySelector('[name="product_id"]')?.value;
			}

			// Delete 'add-to-cart' parameter to prevent WC_Form_Handler from executing in the same request
			formData.delete('add-to-cart');

			// Add product_id parameter required by WC_AJAX
			if (productId && !formData.has('product_id')) {
				formData.append('product_id', productId);
			}


			const url = window.location.origin + '/?wc-ajax=add_to_cart';

			fetch(url, {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
			.then((response) => {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				return response.json();
			})
			.then((data) => {
				if (data.error && data.product_url) {
					// WooCommerce answered but could not add this one (a variable product, or a refusal it
					// has already put on screen). Resubmit without the button: the form's own fields carry
					// what a variable product needs, and a refused simple product is not tried twice.
					form.action = fallbackAction;
					form.submit();
					return;
				}

				// Buy Now set to go to checkout (Theme Options). Before the cart events below,
				// because the mini-cart opens its drawer on those.
				if (toCheckout) {
					window.location.href = config.checkoutUrl;
					return;
				}

				// Success! Trigger WooCommerce cart updates
				const wcEvent = new CustomEvent('wc-blocks_added_to_cart');
				document.dispatchEvent(wcEvent);

				if (typeof jQuery !== 'undefined') {
					jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, jQuery(form)]);
				}

				// Slide open the Mini-Cart drawer
				openMiniCartDrawer();
			})
			.catch((error) => {
				console.error('AJAX add-to-cart error:', error);
				// No usable answer came back (offline, blocked, or a server error), so nothing was
				// reported to the shopper: submit the form the ordinary way, so the product is still added.
				form.action = fallbackAction;
				submitWithButton(form, submitButton);
			})
			.finally(() => {
				// Restore button state
				submitButton.classList.remove('loading');
				submitButton.style.opacity = '';
				submitButton.innerHTML = originalButtonText;
			});
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			checkAddToCartCookie();
			initAjaxListener();
			initSingleProductAjaxForm();
		});
	} else {
		checkAddToCartCookie();
		initAjaxListener();
		initSingleProductAjaxForm();
	}
})();
