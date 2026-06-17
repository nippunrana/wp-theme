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

	// Setup custom quantity stepper buttons listeners
	const qtyParent = qtyInput.parentElement;
	if (qtyParent) {
		const minusBtn = qtyParent.querySelector('.egnitech-qty-btn.minus');
		const plusBtn = qtyParent.querySelector('.egnitech-qty-btn.plus');

		if (minusBtn) {
			minusBtn.addEventListener('click', (e) => {
				e.preventDefault();
				let val = parseInt(qtyInput.value, 10);
				if (isNaN(val)) val = 1;
				const min = parseInt(qtyInput.getAttribute('min'), 10) || 1;
				const step = parseInt(qtyInput.getAttribute('step'), 10) || 1;
				if (val > min) {
					qtyInput.value = val - step;
					qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
					qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
		}

		if (plusBtn) {
			plusBtn.addEventListener('click', (e) => {
				e.preventDefault();
				let val = parseInt(qtyInput.value, 10);
				if (isNaN(val)) val = 1;
				const max = parseInt(qtyInput.getAttribute('max'), 10) || Infinity;
				const step = parseInt(qtyInput.getAttribute('step'), 10) || 1;
				if (val < max) {
					qtyInput.value = val + step;
					qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
					qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
		}
	}

	const cards = container.querySelectorAll('.egnitech-bulk-discount-card');
	const summaryTotal = container.querySelector('.egnitech-summary-total');
	const summarySavings = container.querySelector('.egnitech-summary-savings');

	const lastTierCard = container.querySelector('.egnitech-last-tier');
	const lastTierBaseQty = lastTierCard ? parseInt(lastTierCard.getAttribute('data-qty'), 10) : 0;

	// Parse active tiers from card elements
	const tiers = [];
	cards.forEach(card => {
		const qty = parseInt(card.getAttribute('data-qty'), 10);
		const pct = parseFloat(card.getAttribute('data-pct') || '0', 10);
		tiers.push({ el: card, qty, pct });
	});

	// Sort tiers in descending order of quantity threshold to find matching tier easily
	tiers.sort((a, b) => b.qty - a.qty);

	let basePrice = parseFloat(container.getAttribute('data-base-price'));
	const currencySymbol = container.getAttribute('data-currency-symbol') || '$';
	const currencyPos = container.getAttribute('data-currency-pos') || 'left';

	/**
	 * Formats price according to WooCommerce currency settings.
	 *
	 * @param {number} amount Price amount.
	 * @return {string} Formatted price string.
	 */
	function formatPrice(amount) {
		const formatted = amount.toFixed(2);
		switch (currencyPos) {
			case 'left':
				return currencySymbol + formatted;
			case 'left_space':
				return currencySymbol + ' ' + formatted;
			case 'right':
				return formatted + currencySymbol;
			case 'right_space':
				return formatted + ' ' + currencySymbol;
			default:
				return currencySymbol + formatted;
		}
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

		// Calculate prices
		const discountPct = activeTier ? activeTier.pct : 0;
		const unitPrice = basePrice * (1.0 - discountPct / 100.0);
		const totalPrice = unitPrice * qty;
		const regularTotal = basePrice * qty;
		const savedAmount = regularTotal - totalPrice;

		// Update subtotal display
		if (summaryTotal) {
			summaryTotal.textContent = formatPrice(totalPrice);
		}

		// Update savings text callout
		if (summarySavings) {
			if (savedAmount > 0) {
				summarySavings.style.display = 'inline-block';
				summarySavings.textContent = `(Saved ${formatPrice(savedAmount)} - ${discountPct}% off)`;
			} else {
				summarySavings.style.display = 'none';
			}
		}

		// Update last tier card dynamically
		if (lastTierCard) {
			const lastTierPct = parseFloat(lastTierCard.getAttribute('data-pct') || '0', 10);
			const lastTierUnitPrice = basePrice * (1.0 - lastTierPct / 100.0);
			const dynamicQty = (qty >= lastTierBaseQty) ? qty : lastTierBaseQty;

			// Update card attribute
			lastTierCard.setAttribute('data-qty', String(dynamicQty));

			const qtyNumEl = lastTierCard.querySelector('.egnitech-qty-num');
			if (qtyNumEl) {
				qtyNumEl.textContent = String(dynamicQty);
			}

			const priceOriginalEl = lastTierCard.querySelector('.egnitech-price-original');
			if (priceOriginalEl) {
				priceOriginalEl.textContent = formatPrice(basePrice * dynamicQty);
			}

			const priceNumEl = lastTierCard.querySelector('.egnitech-price-num');
			if (priceNumEl) {
				priceNumEl.textContent = formatPrice(lastTierUnitPrice * dynamicQty);
			}

			const priceLabelEl = lastTierCard.querySelector('.egnitech-price-label');
			if (priceLabelEl) {
				priceLabelEl.textContent = `(${formatPrice(lastTierUnitPrice)} each)`;
			}
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

	// WooCommerce Variable Product Variation Form integration
	if (typeof jQuery !== 'undefined') {
		jQuery(cartForm).on('found_variation', (event, variation) => {
			if (variation && variation.display_price) {
				basePrice = parseFloat(variation.display_price);
				container.setAttribute('data-base-price', String(basePrice));
				
				// Recalculate price text on each card
				cards.forEach(card => {
					const qty = parseInt(card.getAttribute('data-qty'), 10);
					const pct = parseFloat(card.getAttribute('data-pct') || '0', 10);
					const unitPrice = basePrice * (1.0 - pct / 100.0);
					const totalDiscounted = unitPrice * qty;
					const totalOriginal = basePrice * qty;
					
					const priceNumEl = card.querySelector('.egnitech-price-num');
					if (priceNumEl) {
						priceNumEl.textContent = formatPrice(totalDiscounted);
					}

					const priceOriginalEl = card.querySelector('.egnitech-price-original');
					if (priceOriginalEl) {
						priceOriginalEl.textContent = formatPrice(totalOriginal);
					}

					const priceLabelEl = card.querySelector('.egnitech-price-label');
					if (priceLabelEl) {
						priceLabelEl.textContent = `(${formatPrice(unitPrice)} each)`;
					}
				});

				// Update totals
				let qty = parseInt(qtyInput.value, 10);
				if (isNaN(qty) || qty < 1) qty = 1;
				updatePricing(qty);
			}
		});

		jQuery(cartForm).on('reset_data', () => {
			// Restore default base product price
			basePrice = parseFloat(container.getAttribute('data-default-price'));
			container.setAttribute('data-base-price', String(basePrice));

			// Recalculate cards
			cards.forEach(card => {
				const qty = parseInt(card.getAttribute('data-qty'), 10);
				const pct = parseFloat(card.getAttribute('data-pct') || '0', 10);
				const unitPrice = basePrice * (1.0 - pct / 100.0);
				const totalDiscounted = unitPrice * qty;
				const totalOriginal = basePrice * qty;
				
				const priceNumEl = card.querySelector('.egnitech-price-num');
				if (priceNumEl) {
					priceNumEl.textContent = formatPrice(totalDiscounted);
				}

				const priceOriginalEl = card.querySelector('.egnitech-price-original');
				if (priceOriginalEl) {
					priceOriginalEl.textContent = formatPrice(totalOriginal);
				}

				const priceLabelEl = card.querySelector('.egnitech-price-label');
				if (priceLabelEl) {
					priceLabelEl.textContent = `(${formatPrice(unitPrice)} each)`;
				}
			});

			let qty = parseInt(qtyInput.value, 10);
			if (isNaN(qty) || qty < 1) qty = 1;
			updatePricing(qty);
		});
	}

	// Perform initial price calculations
	let initialQty = parseInt(qtyInput.value, 10);
	if (isNaN(initialQty) || initialQty < 1) initialQty = 1;
	updatePricing(initialQty);
});
