/**
 * Contact Form block: sends the form without reloading the page and shows the answer.
 * The markup and every message come from inc/blocks/contact-form.php; the server's checks
 * are in inc/contact-form-submit.php, which reports field errors before it uses the token.
 */
import { store, getContext, getElement, withSyncEvent } from '@wordpress/interactivity';

/**
 * Ask Cloudflare for a new token. Every answer except a field error used up the old one.
 *
 * @param {HTMLFormElement} form The contact form.
 */
function resetChallenge( form ) {
	const widget = form.querySelector( '.cf-turnstile' );
	if ( widget && window.turnstile ) {
		window.turnstile.reset( widget );
	}
}

const { state } = store( 'egnitech-one/contact-form', {
	state: {
		get error() {
			const context = getContext();
			return context.errors[ context.field ] || '';
		},
		get hasError() {
			return '' !== state.error;
		},
		get describedBy() {
			return state.hasError ? getContext().errorId : null;
		},
	},
	actions: {
		submit: withSyncEvent( function* ( event ) {
			event.preventDefault();
			const context = getContext();
			if ( context.sending ) {
				return;
			}

			const form = event.target;
			const body = new FormData( form );
			context.sending = true;
			context.isError = false;
			context.notice = '';
			context.errors = {};

			let result = null;
			try {
				// getAttribute: form.action would return the hidden field named "action".
				const response = yield fetch( form.getAttribute( 'action' ), {
					method: 'POST',
					body,
					headers: { Accept: 'application/json' },
					credentials: 'same-origin',
				} );
				result = yield response.json();
			} catch ( error ) {
				result = null;
			}
			context.sending = false;

			if ( result && result.success ) {
				form.reset();
				context.notice = context.thanks;
				context.sent = true;
			} else {
				context.errors = result && result.errors ? result.errors : {};
				context.notice = result && result.message ? result.message : state.networkError;
				context.isError = true;
			}

			if ( ! result || 'invalid' !== result.code ) {
				resetChallenge( form );
			}
		} ),
	},
	callbacks: {
		init() {
			// The server checks the fields and its messages appear under each one, so the
			// browser's own pop-ups are switched off. Without this script they still work.
			getElement().ref.noValidate = true;
		},
		focusFirstError() {
			if ( Object.keys( getContext().errors ).length ) {
				const field = getElement().ref.querySelector( '[aria-invalid="true"]' );
				if ( field ) {
					field.focus();
				}
			}
		},
		focusNotice() {
			// The form is hidden once sent, so move focus to the thank-you rather than lose it.
			if ( getContext().sent ) {
				getElement().ref.focus();
			}
		},
	},
} );
