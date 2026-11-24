/**
 * EgniTech One — Sticky Buy Now bar (product pages, single-column widths below 992px).
 *
 * As the page's own buy button slides up under the header, this bar rises from the bottom edge by
 * the same fraction, so the row seems to wrap round from top to bottom (owner's choice, 24 Nov 2026).
 * The bar has no add-to-cart logic of its own: its −, + and button press the real ones, so Buy Now,
 * the cart drawer, variation checks and bulk discounts behave exactly as they do on the page.
 * Its visible height is published as --egnitech-sticky-buy-offset, which lifts the scroll-to-top button.
 *
 * @package EgniTech_One
 */
(function () {
	'use strict';

	const form = document.querySelector('form.cart');
	if (!form || form.classList.contains('grouped_form')) {
		return;
	}

	// Buy Now when it is switched on, otherwise Add to Cart.
	const realButton = form.querySelector('.egnitech-buy-now-button') || form.querySelector('.single_add_to_cart_button');
	if (!realButton) {
		return;
	}

	const qtyInput = form.querySelector('input.qty');
	const realMinus = form.querySelector('.wc-block-components-quantity-selector__button--minus');
	const realPlus = form.querySelector('.wc-block-components-quantity-selector__button--plus');
	// A sold-individually product keeps an empty, hidden quantity box: no stepper in the bar then.
	const hasStepper = !!(qtyInput && realMinus && realPlus && realPlus.offsetParent !== null);
	const header = document.querySelector('.wp-site-blocks > header, .wp-site-blocks > .wp-block-template-part:first-child');
	const wide = window.matchMedia('(min-width: 992px)');
	const root = document.documentElement;

	// Build the bar.
	const bar = document.createElement('div');
	bar.className = 'egnitech-sticky-buy';

	let count = null;
	if (hasStepper) {
		const stepper = document.createElement('div');
		stepper.className = 'egnitech-sticky-buy__qty';

		const minus = document.createElement('button');
		minus.type = 'button';
		minus.className = 'egnitech-sticky-buy__step';
		minus.textContent = '−';
		minus.setAttribute('aria-label', realMinus.getAttribute('aria-label') || 'Reduce quantity');
		minus.addEventListener('click', () => {
			realMinus.click();
			syncCount();
		});

		count = document.createElement('span');
		count.className = 'egnitech-sticky-buy__count';
		count.setAttribute('aria-live', 'polite');

		const plus = document.createElement('button');
		plus.type = 'button';
		plus.className = 'egnitech-sticky-buy__step';
		plus.textContent = '+';
		plus.setAttribute('aria-label', realPlus.getAttribute('aria-label') || 'Increase quantity');
		plus.addEventListener('click', () => {
			realPlus.click();
			syncCount();
		});

		stepper.append(minus, count, plus);
		bar.append(stepper);
	}

	const button = document.createElement('button');
	button.type = 'button';
	button.className = 'egnitech-sticky-buy__button wp-element-button';
	button.addEventListener('click', () => {
		// An add already on its way: the cart script does not guard against a second submit.
		if (realButton.classList.contains('loading')) {
			return;
		}
		// No variation chosen yet: take the shopper back to the options instead.
		if (realButton.disabled || realButton.classList.contains('disabled')) {
			(form.querySelector('.variations') || form).scrollIntoView({ behavior: 'smooth', block: 'center' });
			return;
		}
		realButton.click();
	});
	bar.append(button);

	document.body.append(bar);
	document.body.classList.add('has-egnitech-sticky-buy');

	/** Show the real quantity. */
	function syncCount() {
		if (count) {
			count.textContent = qtyInput.value || '1';
		}
	}

	/** Copy the real button's label and its loading / disabled state. */
	function syncButton() {
		button.textContent = realButton.textContent.trim();
		button.classList.toggle('is-loading', realButton.classList.contains('loading'));
		button.classList.toggle('is-disabled', realButton.disabled || realButton.classList.contains('disabled'));
	}

	if (qtyInput) {
		qtyInput.addEventListener('input', syncCount);
		qtyInput.addEventListener('change', syncCount);
	}
	new MutationObserver(syncButton).observe(realButton, {
		attributes: true,
		attributeFilter: ['class', 'disabled'],
		childList: true,
		characterData: true,
		subtree: true,
	});

	// Position: the share of the real button hidden under the header (or above the screen when the
	// header is not sticky) is the share of the bar shown.
	let barHeight = 0;
	let ticking = false;

	function update() {
		ticking = false;
		let shown = 0;
		if (!wide.matches) {
			const rect = realButton.getBoundingClientRect();
			const top = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
			if (rect.height > 0) {
				shown = Math.min(1, Math.max(0, (top - rect.top) / rect.height));
			}
		}
		bar.style.transform = `translateY(${(1 - shown) * 100}%)`;
		bar.classList.toggle('is-shown', shown > 0);
		root.style.setProperty('--egnitech-sticky-buy-offset', `${shown * barHeight}px`);
	}

	function measure() {
		barHeight = bar.offsetHeight;
		root.style.setProperty('--egnitech-sticky-buy-height', `${barHeight}px`);
		update();
	}

	window.addEventListener('scroll', () => {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(update);
		}
	}, { passive: true });
	window.addEventListener('resize', measure);
	window.addEventListener('pageshow', measure);
	wide.addEventListener('change', measure);

	syncCount();
	syncButton();
	measure();
})();
