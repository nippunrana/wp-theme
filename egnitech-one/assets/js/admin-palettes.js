/**
 * EgniTech One — palette suggestions on Theme Options > General > Appearance.
 * Data comes from inc/palette/palette-admin.php (window.egnitechPalettes).
 */
( () => {
	'use strict';

	const data = window.egnitechPalettes;
	const root = document.getElementById( 'egnitech-palette-suggestions' );
	const notice = document.getElementById( 'egnitech-palette-notice' );
	const replaceToggle = document.getElementById( 'egnitech-palette-replace' );
	if ( ! data || ! root ) {
		return;
	}

	const t = data.i18n;
	let busy = false;

	/* A light-dark(#a, #b) value shows as a diagonal split, like the overview swatches. */
	const swatchBackground = ( value ) => {
		const match = /light-dark\(\s*(#[0-9a-f]+)\s*,\s*(#[0-9a-f]+)\s*\)/i.exec( value || '' );
		return match ? `linear-gradient(135deg, ${ match[ 1 ] } 50%, ${ match[ 2 ] } 50%)` : value;
	};

	const isCurrent = ( colors ) =>
		data.slots.every( ( slug ) => ! ( slug in colors ) || colors[ slug ] === data.current[ slug ] );

	const el = ( tag, className, text ) => {
		const node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	};

	const showNotice = ( text, isError ) => {
		if ( ! notice ) {
			return;
		}
		notice.textContent = text;
		notice.className = 'egnitech-palette-notice' + ( isError ? ' is-error' : '' );
		notice.hidden = ! text;
	};

	const post = ( action, fields ) => {
		const body = new FormData();
		body.append( 'action', action );
		body.append( 'security', data.nonce );
		Object.keys( fields ).forEach( ( key ) => body.append( key, fields[ key ] ) );
		return fetch( data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } )
			.then( ( response ) => response.json() );
	};

	/* Keep the "Theme Color Palette" overview in step without a page reload. */
	const updateOverview = () => {
		document.querySelectorAll( '.egnitech-palette-swatch[data-slug]' ).forEach( ( swatch ) => {
			const color = swatch.querySelector( '.egnitech-palette-color' );
			const value = data.current[ swatch.getAttribute( 'data-slug' ) ];
			if ( color && value ) {
				color.style.background = swatchBackground( value );
			}
		} );
	};

	let render = () => {};

	const apply = ( palette, replace ) => {
		busy = true;
		render();
		showNotice( t.applying, false );
		post( 'egnitech_one_apply_palette', {
			colors: JSON.stringify( palette.colors ),
			label: palette.label,
			replace: replace ? '1' : '',
		} )
			.then( ( res ) => {
				if ( ! res.success ) {
					throw new Error( ( res.data && res.data.message ) || t.failed );
				}
				data.current = res.data.current;
				data.history = res.data.history;
				updateOverview();
				let text = t.applied;
				if ( res.data.skipped.length ) {
					text += ' ' + t.skipped.replace( '%s', res.data.skipped.join( ', ' ) );
				}
				showNotice( text, false );
			} )
			.catch( ( err ) => showNotice( err.message || t.failed, true ) )
			.then( () => {
				busy = false;
				render();
			} );
	};

	const card = ( palette, isHistory ) => {
		const item = el( 'div', 'egnitech-palette-card' );
		const swatches = el( 'div', 'egnitech-palette-card-swatches' );
		data.slots.forEach( ( slug ) => {
			if ( ! palette.colors[ slug ] ) {
				return;
			}
			const dot = el( 'span', 'egnitech-palette-card-dot' );
			dot.style.background = swatchBackground( palette.colors[ slug ] );
			dot.title = slug;
			swatches.appendChild( dot );
		} );
		item.appendChild( swatches );
		item.appendChild( el( 'span', 'egnitech-palette-card-label', palette.label ) );

		const current = isCurrent( palette.colors );
		let label = t.apply;
		if ( current ) {
			label = t.inUse;
		} else if ( isHistory ) {
			label = t.restore;
		}
		const button = el( 'button', 'egnitech-btn egnitech-btn-secondary', label );
		button.type = 'button';
		button.disabled = current || busy;
		button.addEventListener( 'click', () => {
			// A history entry is an exact snapshot, so it always replaces every colour.
			apply( palette, isHistory || ( replaceToggle && replaceToggle.checked ) );
		} );
		item.appendChild( button );
		return item;
	};

	const section = ( title, palettes, message, isHistory ) => {
		const wrap = el( 'div', 'egnitech-palette-section' );
		wrap.appendChild( el( 'h4', 'egnitech-palette-section-title', title ) );
		if ( message ) {
			wrap.appendChild( el( 'p', 'egnitech-palette-message', message ) );
		}
		if ( palettes.length ) {
			const grid = el( 'div', 'egnitech-palette-grid' );
			palettes.forEach( ( palette ) => grid.appendChild( card( palette, isHistory ) ) );
			wrap.appendChild( grid );
		}
		root.appendChild( wrap );
	};

	render = () => {
		root.textContent = '';
		section( t.fromLogo, data.logo.palettes, data.logo.message, false );
		section( t.presets, data.presets, '', false );
		if ( data.history.length ) {
			section( t.history, data.history, '', true );
		}
	};

	/* A new logo picked under Branding & Logo gets its own suggestions straight away, before saving. */
	const logoInput = document.getElementById( 'egnitech_one_light_logo_id' );
	if ( logoInput ) {
		logoInput.addEventListener( 'change', () => {
			data.logo = { palettes: [], message: t.reading };
			render();
			post( 'egnitech_one_logo_palettes', { attachment_id: logoInput.value || '0' } )
				.then( ( res ) => {
					if ( ! res.success ) {
						throw new Error( t.failed );
					}
					data.logo = res.data;
				} )
				.catch( ( err ) => {
					data.logo = { palettes: [], message: err.message };
				} )
				.then( render );
		} );
	}

	render();
} )();
