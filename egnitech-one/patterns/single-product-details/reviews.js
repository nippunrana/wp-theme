/**
 * EgniTech One — review form drawer (product pages).
 *
 * "Write a review" opens WooCommerce's own review form, which inc/woocommerce-reviews.php wraps in a
 * <dialog>. The form still posts to wp-comments-post.php exactly as WooCommerce prints it.
 * Cloudflare Turnstile's script is fetched on the first open, not with the page.
 * WooCommerce's rating <select> is kept for the post and drawn as five star buttons.
 *
 * @package EgniTech_One
 */
(function () {
	'use strict';

	const button = document.querySelector('.egnitech-reviews-write');
	const drawer = document.getElementById('egnitech-review-drawer');
	if (!button || !drawer || typeof drawer.showModal !== 'function') {
		return;
	}

	const root = document.documentElement;

	/* ----- Opening and closing ----- */

	button.addEventListener('click', function () {
		drawer.showModal();
		root.classList.add('egnitech-review-drawer-open');
		loadTurnstile();
	});

	// Slide out first, then close. A closed dialog leaves the top layer at once in Safari and
	// Firefox, so the slide-out must play while it is still open. With no transition running
	// (reduced motion) the list of animations is empty and the dialog closes straight away.
	function closeDrawer() {
		if (!drawer.open || drawer.classList.contains('is-closing')) {
			return;
		}
		drawer.classList.add('is-closing');
		Promise.all(drawer.getAnimations().map(function (animation) {
			return animation.finished;
		})).finally(function () {
			drawer.close();
		});
	}

	drawer.querySelector('.egnitech-review-drawer__close').addEventListener('click', closeDrawer);

	// A click on the backdrop lands on the <dialog> itself; the panel covers everything else.
	drawer.addEventListener('click', function (event) {
		if (event.target === drawer) {
			closeDrawer();
		}
	});

	// Esc. When the browser does not let the cancel be stopped, the dialog simply closes at once.
	drawer.addEventListener('cancel', function (event) {
		event.preventDefault();
		closeDrawer();
	});

	drawer.addEventListener('close', function () {
		drawer.classList.remove('is-closing');
		root.classList.remove('egnitech-review-drawer-open');
	});

	/* ----- Turnstile, on first open ----- */

	const form = drawer.querySelector('form');
	const turnstileWidget = drawer.querySelector('.cf-turnstile');
	let turnstileNotice = null;
	let turnstileLoading = false;

	// The check starts only when the drawer opens, so a fast visitor can press Submit before it
	// finishes. The server would refuse that review; hold it here instead and say why.
	if (form && turnstileWidget) {
		turnstileNotice = document.createElement('p');
		turnstileNotice.className = 'egnitech-review-drawer__error';
		turnstileNotice.setAttribute('role', 'alert');
		turnstileNotice.hidden = true;
		turnstileWidget.after(turnstileNotice);

		form.addEventListener('submit', function (event) {
			const token = form.querySelector('[name="cf-turnstile-response"]');
			if (token && token.value) {
				return;
			}
			event.preventDefault();
			turnstileNotice.textContent = drawer.dataset.turnstileError;
			turnstileNotice.hidden = false;
		});
	}

	function loadTurnstile() {
		const src = button.dataset.turnstileSrc;
		// No widget for logged-in visitors or with Turnstile off; no src when the page loaded it already.
		if (!src || !turnstileNotice || turnstileLoading || window.turnstile) {
			return;
		}
		turnstileLoading = true;

		const url = new URL(src);
		url.searchParams.set('render', 'explicit');

		const script = document.createElement('script');
		script.src = url.href;
		script.async = true;
		script.addEventListener('load', function () {
			window.turnstile.render(turnstileWidget, {
				sitekey: turnstileWidget.dataset.sitekey,
				action: turnstileWidget.dataset.action,
				theme: turnstileWidget.dataset.theme,
				callback: function () {
					turnstileNotice.hidden = true;
				},
			});
		});
		// Blocked or offline: let the next open try again.
		script.addEventListener('error', function () {
			script.remove();
			turnstileLoading = false;
		});
		document.head.append(script);
	}

	/* ----- Star rating ----- */

	const select = drawer.querySelector('select[name="rating"]');
	if (!select) {
		return; // Ratings are switched off in WooCommerce.
	}

	const options = Array.from(select.options).filter(function (option) {
		return option.value;
	}).sort(function (a, b) {
		return a.value - b.value;
	});
	const ratingLabel = select.id ? drawer.querySelector('label[for="' + select.id + '"]') : null;
	if (ratingLabel && !ratingLabel.id) {
		ratingLabel.id = select.id + '-label';
	}

	const group = document.createElement('div');
	group.className = 'egnitech-star-input';
	group.setAttribute('role', 'radiogroup');
	if (ratingLabel) {
		group.setAttribute('aria-labelledby', ratingLabel.id);
	}

	const caption = document.createElement('span');
	caption.className = 'egnitech-star-input__caption';
	caption.setAttribute('aria-hidden', 'true');

	const error = document.createElement('p');
	error.className = 'egnitech-review-drawer__error';
	error.setAttribute('role', 'alert');
	error.hidden = true;

	const stars = options.map(function (option) {
		const star = document.createElement('button');
		star.type = 'button';
		star.className = 'egnitech-star-input__star';
		star.setAttribute('role', 'radio');
		star.setAttribute('aria-label', option.textContent.trim());
		star.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		star.addEventListener('click', function () {
			choose(option.value);
		});
		group.append(star);
		return star;
	});

	function choose(value, focus) {
		select.value = value;
		select.dispatchEvent(new Event('change', { bubbles: true }));
		error.hidden = true;
		render();
		if (focus) {
			stars[options.findIndex(function (option) {
				return option.value === value;
			})].focus();
		}
	}

	function render() {
		const index = options.findIndex(function (option) {
			return option.value === select.value;
		});
		stars.forEach(function (star, i) {
			star.classList.toggle('is-on', i <= index);
			star.setAttribute('aria-checked', i === index ? 'true' : 'false');
			// One tab stop for the group: the chosen star, or the first.
			star.tabIndex = i === Math.max(index, 0) ? 0 : -1;
		});
		caption.textContent = index >= 0 ? options[index].textContent.trim() : '';
	}

	group.addEventListener('keydown', function (event) {
		const index = stars.indexOf(document.activeElement);
		if (index < 0) {
			return;
		}
		const keys = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1 };
		let next = index + (keys[event.key] || 0);
		if ('Home' === event.key) {
			next = 0;
		} else if ('End' === event.key) {
			next = stars.length - 1;
		} else if (!keys[event.key]) {
			return;
		}
		event.preventDefault();
		next = Math.min(Math.max(next, 0), stars.length - 1);
		choose(options[next].value, true);
	});

	// The select stays in the form (visually hidden) so WooCommerce's "required" still holds.
	// Its native bubble would point at nothing visible, so show the message under the stars instead.
	select.addEventListener('invalid', function (event) {
		event.preventDefault();
		error.textContent = drawer.dataset.ratingError;
		error.hidden = false;
		stars[0].focus();
	});

	// The label points at the hidden select: send its clicks to the stars instead.
	if (ratingLabel) {
		ratingLabel.addEventListener('click', function (event) {
			event.preventDefault();
			stars.find(function (star) {
				return 0 === star.tabIndex;
			}).focus();
		});
	}

	select.classList.add('egnitech-visually-hidden');
	select.tabIndex = -1;
	select.setAttribute('aria-hidden', 'true');
	select.after(group, caption, error);
	render();
})();
