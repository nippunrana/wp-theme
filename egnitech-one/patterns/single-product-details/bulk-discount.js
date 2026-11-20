/**
 * WooCommerce Bulk Discount frontend script.
 */
document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('egnitech-bulk-discount');
	if (!container) return;

	// Find the parent WooCommerce product add to cart form
	const cartForm = document.querySelector('form.cart');
	if (!cartForm) return;

	// Find WooCommerce quantity input
	const qtyInput = cartForm.querySelector('.qty, input[name="quantity"]');
	if (!qtyInput) return;

	const cards = container.querySelectorAll('.egnitech-bulk-discount-card');
	const summaryLabel = container.querySelector('.egnitech-summary-label');
	const summaryTotal = container.querySelector('.egnitech-summary-total');
	const summarySavings = container.querySelector('.egnitech-summary-savings');
	const notice = container.querySelector('.egnitech-bulk-cart-notice');
	const noticeTitle = container.querySelector('.egnitech-bulk-cart-notice-title');
	const noticeDetail = container.querySelector('.egnitech-bulk-cart-notice-detail');

	// The PHP texts, restored whenever the cart has none of this product.
	const originalLabel = summaryLabel ? summaryLabel.textContent : '';
	cards.forEach(card => {
		const saveEl = card.querySelector('.egnitech-card-save');
		if (saveEl) saveEl.dataset.original = saveEl.textContent;
	});

	// The cart charges the tier reached by the whole cart line, so the cards are priced on
	// what is already in it plus what is being added. Lines are matched by product or
	// variation id, exactly as the cart counts them (bulk-discounts.md).
	const productId = parseInt(container.getAttribute('data-product-id'), 10);
	const cartUrl = container.getAttribute('data-cart-url');
	const isVariable = cartForm.classList.contains('variations_form');
	let variationId = 0;
	let cartItems = [];
	let inCartQty = 0;

	const lastTierCard = container.querySelector('.egnitech-last-tier');
	const lastTierBaseQty = lastTierCard ? parseInt(lastTierCard.getAttribute('data-qty'), 10) : 0;

	// Parse active tiers from card elements
	const tiers = [];
	cards.forEach(card => {
		const qty = parseInt(card.getAttribute('data-qty'), 10);
		const pct = parseFloat(card.getAttribute('data-pct') || '0');
		tiers.push({ el: card, qty, pct });
	});

	// Sort tiers in descending order of quantity threshold to find matching tier easily
	tiers.sort((a, b) => b.qty - a.qty);

	let basePrice = parseFloat(container.getAttribute('data-base-price'));
	const currencySymbol = container.getAttribute('data-currency-symbol') || '$';
	const currencyPos = container.getAttribute('data-currency-pos') || 'left';

	// The shop's WooCommerce currency settings, printed by PHP. A page cached before these attributes
	// existed has none of them, so fall back to WooCommerce's defaults. Only a missing attribute falls
	// back: an empty thousand separator is a real setting.
	const attrOr = (name, fallback) => container.getAttribute(name) ?? fallback;
	const priceDecimals = parseInt(attrOr('data-price-decimals', '2'), 10);
	const decimalSeparator = attrOr('data-decimal-separator', '.');
	const thousandSeparator = attrOr('data-thousand-separator', ',');

	/**
	 * Formats price as WooCommerce's wc_price() does, with the shop's own currency settings.
	 *
	 * @param {number} amount Price amount.
	 * @return {string} Formatted price string.
	 */
	function formatPrice(amount) {
		const [whole, fraction] = amount.toFixed(priceDecimals).split('.');
		const formatted = whole.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator)
			+ (fraction ? decimalSeparator + fraction : '');
		// A non-breaking space, as wc_price() uses, so the symbol never wraps away from the number.
		switch (currencyPos) {
			case 'left':
				return currencySymbol + formatted;
			case 'left_space':
				return currencySymbol + ' ' + formatted;
			case 'right':
				return formatted + currencySymbol;
			case 'right_space':
				return formatted + ' ' + currencySymbol;
			default:
				return currencySymbol + formatted;
		}
	}

	/**
	 * Discount percentage the cart charges for a line of this many units.
	 *
	 * @param {number} qty Units in the cart line.
	 * @return {number} Percentage, 0 when no tier is reached.
	 */
	function tierPctFor(qty) {
		for (const tier of tiers) {
			if (qty >= tier.qty) return tier.pct;
		}
		return 0;
	}

	/**
	 * Current quantity in the stepper, at least 1.
	 *
	 * @return {number} Quantity.
	 */
	function currentQty() {
		const qty = parseInt(qtyInput.value, 10);
		return (isNaN(qty) || qty < 1) ? 1 : qty;
	}

	/**
	 * Show or hide the "already in your cart" notice.
	 */
	function updateNotice() {
		if (!notice) return;
		if (inCartQty < 1) {
			notice.hidden = true;
			return;
		}

		const pct = tierPctFor(inCartQty);
		noticeTitle.textContent = `${inCartQty} already in your cart` + (pct > 0 ? ` · ${pct}% off unlocked` : '');
		// tiers[0] is the largest tier: once it is reached, every extra unit costs the same.
		noticeDetail.textContent = (tiers.length && inCartQty >= tiers[0].qty)
			? `Extra units cost ${formatPrice(basePrice * (1.0 - pct / 100.0))} each`
			: 'Prices below count them towards your discount';
		notice.hidden = false;
	}

	/**
	 * Update layout states, highlighted cards, and pricing totals.
	 *
	 * @param {number} qty Current quantity.
	 */
	function updatePricing(qty) {
		// Find matching discount tier
		let activeTier = null;
		for (const tier of tiers) {
			if (qty >= tier.qty) {
				activeTier = tier;
				break;
			}
		}

		// Highlight matching card, remove active from others
		cards.forEach(card => card.classList.remove('active'));
		if (activeTier) {
			activeTier.el.classList.add('active');
		} else {
			// Default to Tier 0 (Buy 1)
			const baseCard = container.querySelector('.egnitech-bulk-discount-card[data-qty="1"]');
			if (baseCard) baseCard.classList.add('active');
		}

		// Price the cards. The last card follows the stepper once it passes that tier.
		cards.forEach(card => {
			let cardQty = parseInt(card.getAttribute('data-qty'), 10);
			if (card === lastTierCard) {
				cardQty = (qty >= lastTierBaseQty) ? qty : lastTierBaseQty;
				card.setAttribute('data-qty', String(cardQty));
				const qtyNumEl = card.querySelector('.egnitech-qty-num');
				if (qtyNumEl) qtyNumEl.textContent = String(cardQty);
			}

			const pct = tierPctFor(inCartQty + cardQty);
			const unitPrice = basePrice * (1.0 - pct / 100.0);

			const priceNumEl = card.querySelector('.egnitech-price-num');
			if (priceNumEl) {
				priceNumEl.textContent = formatPrice(unitPrice * cardQty);
			}

			// Struck-through full price, only when there is a saving to compare with.
			const priceOriginalEl = card.querySelector('.egnitech-price-original');
			if (priceOriginalEl) {
				priceOriginalEl.textContent = pct > 0 ? formatPrice(basePrice * cardQty) : '';
			}

			const priceLabelEl = card.querySelector('.egnitech-price-label');
			if (priceLabelEl) {
				priceLabelEl.textContent = `(${formatPrice(unitPrice)} each)`;
			}

			const saveEl = card.querySelector('.egnitech-card-save');
			if (saveEl) {
				if (inCartQty > 0) {
					saveEl.textContent = pct > 0 ? `(Save Extra ${pct}%)` : '';
				} else {
					saveEl.textContent = saveEl.dataset.original;
				}
			}
		});

		// Totals for the whole cart line this add will make.
		const lineQty = inCartQty + qty;
		const discountPct = tierPctFor(lineQty);
		const unitPrice = basePrice * (1.0 - discountPct / 100.0);
		const savedAmount = (basePrice - unitPrice) * lineQty;

		if (summaryLabel) {
			summaryLabel.textContent = inCartQty > 0 ? `Adding ${qty} → ${lineQty} in cart:` : originalLabel;
		}

		if (summaryTotal) {
			summaryTotal.textContent = formatPrice(unitPrice * lineQty);
		}

		// Update savings text callout. Never hide it: on phones it sits on its own line and the page would jump.
		if (summarySavings) {
			if (inCartQty > 0) {
				summarySavings.classList.remove('is-hint');
				summarySavings.textContent = `(${lineQty} × ${formatPrice(unitPrice)} each`
					+ (savedAmount > 0 ? ` - saved ${formatPrice(savedAmount)})` : ')');
			} else if (savedAmount > 0) {
				summarySavings.classList.remove('is-hint');
				summarySavings.textContent = `(Saved ${formatPrice(savedAmount)} - ${discountPct}% off)`;
			} else {
				summarySavings.classList.add('is-hint');
				summarySavings.textContent = summarySavings.dataset.hint;
			}
		}

		updateNotice();
	}

	/**
	 * Read this product's line from the cart and repaint.
	 */
	function loadCart() {
		fetch(cartUrl, { credentials: 'same-origin' })
			.then(response => {
				if (!response.ok) throw new Error('Cart request failed');
				return response.json();
			})
			.then(cart => {
				cartItems = Array.isArray(cart.items) ? cart.items : [];
				applyCart();
			})
			.catch(() => {
				// Leave the cards as they are: without the cart they show the plain tier prices.
			});
	}

	/**
	 * Take the in-cart quantity for the current product or chosen variation and repaint.
	 * A variable product counts nothing until a variation is chosen.
	 */
	function applyCart() {
		const id = isVariable ? variationId : productId;
		const line = id ? cartItems.find(item => item.id === id) : null;
		inCartQty = line ? line.quantity : 0;
		updatePricing(currentQty());
	}

	/**
	 * Read the cart only when WooCommerce's cookie says it holds something, so an
	 * empty-cart visitor does not cost the server an uncached request on every product page.
	 */
	function syncCart() {
		if (document.cookie.includes('woocommerce_items_in_cart=')) {
			loadCart();
		} else {
			cartItems = [];
			applyCart();
		}
	}

	// Setup card click listeners
	cards.forEach(card => {
		card.addEventListener('click', () => {
			const qty = parseInt(card.getAttribute('data-qty'), 10);
			qtyInput.value = qty;
			
			// Dispatch change event to notify WooCommerce form scripts
			qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
			qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
			
			updatePricing(qty);
		});
	});

	// Handle changes from standard WooCommerce quantity inputs (e.g. typing or click on +/- buttons)
	qtyInput.addEventListener('input', () => {
		let qty = parseInt(qtyInput.value, 10);
		if (isNaN(qty) || qty < 1) qty = 1;
		updatePricing(qty);
	});
	qtyInput.addEventListener('change', () => {
		let qty = parseInt(qtyInput.value, 10);
		if (isNaN(qty) || qty < 1) qty = 1;
		updatePricing(qty);
	});

	/**
	 * Repaint every card and the totals from the current base price.
	 */
	function repaintCards() {
		container.setAttribute('data-base-price', String(basePrice));
		applyCart();
	}

	// WooCommerce Variable Product Variation Form integration
	if (typeof jQuery !== 'undefined') {
		jQuery(cartForm).on('found_variation', (event, variation) => {
			if (variation && variation.display_price) {
				basePrice = parseFloat(variation.display_price);
				variationId = variation.variation_id || 0;
				repaintCards();
			}
		});

		jQuery(cartForm).on('reset_data', () => {
			// Restore default base product price
			basePrice = parseFloat(container.getAttribute('data-default-price'));
			variationId = 0;
			repaintCards();
		});
	}

	// Re-read the cart after any add or removal on this page (the no-reload add-to-cart and
	// the cart drawer), and when the page comes back from the browser's back-forward cache.
	document.addEventListener('wc-blocks_added_to_cart', loadCart);
	document.addEventListener('wc-blocks_removed_from_cart', loadCart);
	window.addEventListener('pageshow', event => {
		if (event.persisted) syncCart();
	});

	// Perform initial price calculations, then correct them for what is already in the cart.
	updatePricing(currentQty());
	syncCart();
});
