/**
 * Product Trust Badges meta box (product edit screen): add, remove and reorder badges, choose
 * each badge's icon and colour from shared dialogs, and randomise or unify all colours.
 *
 * Random colours are picked here, once, and saved. The product page never picks one: it is
 * cached whole, so a colour picked there would freeze into the cache and change on each purge.
 *
 * The rows are ordinary fields of the product form, named egnitech_trust_badges[n][…]. The
 * form posts them in on-screen order, which is the order the product page shows. A new row is
 * a clone of the box's <template> with "__INDEX__" replaced by a number no other row uses.
 */
( () => {
	const box = document.querySelector( '.egnitech-trust-badges' );
	if ( ! box ) {
		return;
	}

	const list = box.querySelector( '.egnitech-trust-badges__list' );
	const addButton = box.querySelector( '.egnitech-trust-badges__add' );
	const counter = box.querySelector( '.egnitech-trust-badges__count' );
	const template = box.querySelector( '.egnitech-trust-badges__template' );
	const dialog = box.querySelector( '.egnitech-icon-dialog' );
	const search = dialog.querySelector( '.egnitech-icon-dialog__search' );
	const noMatch = dialog.querySelector( '.egnitech-icon-dialog__none' );
	const icons = [ ...dialog.querySelectorAll( '.egnitech-icon-dialog__icon' ) ];
	const colorDialog = box.querySelector( '.egnitech-color-dialog' );
	const swatches = [ ...colorDialog.querySelectorAll( '.egnitech-color-dialog__swatch' ) ];
	const allColor = box.querySelector( '.egnitech-trust-badges__all-color' );
	const limit = Number( box.dataset.limit );
	const i18n = JSON.parse( box.dataset.i18n );
	let nextIndex = Number( box.dataset.nextIndex );
	let editing = null; // The row whose icon the dialog is choosing.
	let coloring = null; // The row whose colour the colour dialog is choosing.
	let dragged = null;

	const rows = () => [ ...list.children ];

	// Fills a translated string's %1$d, %2$d or %s.
	const fill = ( format, ...values ) => values.reduce( ( text, value, i ) => text.replace( `%${ i + 1 }$d`, value ).replace( '%s', value ), format );

	const announce = ( message ) => window.wp?.a11y?.speak( message );
	const announcePosition = ( row ) => announce( fill( i18n.moved, rows().indexOf( row ) + 1, rows().length ) );

	// Counter, Add button, empty state, and the move buttons at either end.
	const refresh = () => {
		const all = rows();
		counter.textContent = fill( i18n.count, all.length, limit );
		addButton.disabled = all.length >= limit;
		box.classList.toggle( 'is-empty', 0 === all.length );
		all.forEach( ( row, i ) => {
			row.querySelector( '[data-action="up"]' ).disabled = 0 === i;
			row.querySelector( '[data-action="down"]' ).disabled = i === all.length - 1;
		} );
	};

	// Keeps focus on a moved row: on the same button, or its partner once the row reaches an end.
	const afterMove = ( row, button ) => {
		refresh();
		const partner = row.querySelector( 'up' === button.dataset.action ? '[data-action="down"]' : '[data-action="up"]' );
		( button.disabled ? partner : button ).focus();
		announcePosition( row );
	};

	const colorOf = ( row ) => row.querySelector( 'input[name$="[color]"]' ).value;

	// Stores a colour on a row and shows it on the row's colour button.
	const setColor = ( row, color ) => {
		const swatch = swatches.find( ( item ) => item.dataset.color === color );
		const button = row.querySelector( '[data-action="color"]' );
		row.querySelector( 'input[name$="[color]"]' ).value = color;
		button.style.setProperty( '--egnitech-badge-color', swatch.style.getPropertyValue( '--egnitech-badge-color' ) );
		button.setAttribute( 'aria-label', fill( i18n.changeColor, swatch.textContent.trim() ) );
	};

	// The palette in random order (Fisher–Yates).
	const shuffled = () => {
		const colors = swatches.map( ( swatch ) => swatch.dataset.color );
		for ( let i = colors.length - 1; i > 0; i-- ) {
			const j = Math.floor( Math.random() * ( i + 1 ) );
			[ colors[ i ], colors[ j ] ] = [ colors[ j ], colors[ i ] ];
		}
		return colors;
	};

	// A new badge's colour: one no other badge uses, or failing that, not the last badge's.
	const newColor = () => {
		const used = rows().map( colorOf );
		const last = used.at( -1 );
		const colors = shuffled();
		return colors.find( ( color ) => ! used.includes( color ) ) || colors.find( ( color ) => color !== last );
	};

	const filterIcons = () => {
		const query = search.value.trim().toLowerCase();
		let matches = 0;
		dialog.querySelectorAll( '.egnitech-icon-dialog__group' ).forEach( ( group ) => {
			let shown = 0;
			group.querySelectorAll( '.egnitech-icon-dialog__icon' ).forEach( ( icon ) => {
				icon.hidden = '' !== query && ! icon.dataset.search.toLowerCase().includes( query );
				shown += icon.hidden ? 0 : 1;
			} );
			group.hidden = 0 === shown;
			matches += shown;
		} );
		noMatch.hidden = matches > 0;
	};

	const openDialog = ( row ) => {
		editing = row;
		const current = row.querySelector( 'input[name$="[icon]"]' ).value;
		icons.forEach( ( icon ) => icon.setAttribute( 'aria-pressed', String( icon.dataset.icon === current ) ) );
		search.value = '';
		filterIcons();
		dialog.showModal();
		search.focus();
	};

	const chooseIcon = ( icon ) => {
		const button = editing.querySelector( '[data-action="icon"]' );
		editing.querySelector( 'input[name$="[icon]"]' ).value = icon.dataset.icon;
		button.querySelector( '.egnitech-trust-badge-item__svg' ).replaceChildren( icon.querySelector( 'svg' ).cloneNode( true ) );
		button.setAttribute( 'aria-label', fill( i18n.changeIcon, icon.getAttribute( 'aria-label' ) ) );
		dialog.close();
	};

	addButton.addEventListener( 'click', () => {
		if ( rows().length >= limit ) {
			return;
		}
		const row = template.content.firstElementChild.cloneNode( true );
		row.querySelectorAll( '[name]' ).forEach( ( field ) => {
			field.name = field.name.replace( '__INDEX__', String( nextIndex ) );
		} );
		nextIndex++;
		setColor( row, newColor() );
		list.append( row );
		refresh();
		row.querySelector( '.egnitech-trust-badge-item__text' ).focus();
		announce( fill( i18n.added, rows().length, rows().length ) );
	} );

	list.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( 'button[data-action]' );
		if ( ! button || button.disabled ) {
			return;
		}
		const row = button.closest( '.egnitech-trust-badge-item' );

		if ( 'icon' === button.dataset.action ) {
			openDialog( row );
		} else if ( 'color' === button.dataset.action ) {
			coloring = row;
			swatches.forEach( ( swatch ) => swatch.setAttribute( 'aria-pressed', String( swatch.dataset.color === colorOf( row ) ) ) );
			colorDialog.showModal();
			( swatches.find( ( swatch ) => 'true' === swatch.getAttribute( 'aria-pressed' ) ) || swatches[ 0 ] ).focus();
		} else if ( 'up' === button.dataset.action ) {
			list.insertBefore( row, row.previousElementSibling );
			afterMove( row, button );
		} else if ( 'down' === button.dataset.action ) {
			list.insertBefore( row.nextElementSibling, row );
			afterMove( row, button );
		} else if ( 'remove' === button.dataset.action ) {
			const next = row.nextElementSibling || row.previousElementSibling;
			row.remove();
			refresh();
			( next?.querySelector( '[data-action="remove"]' ) || addButton ).focus();
			announce( i18n.removed );
		}
	} );

	// Dragging works from the handle only: a row is draggable just while its handle is held,
	// so the mouse can still select text in the row's field.
	list.addEventListener( 'pointerdown', ( event ) => {
		const handle = event.target.closest( '.egnitech-trust-badge-item__handle' );
		if ( handle ) {
			handle.closest( '.egnitech-trust-badge-item' ).draggable = true;
		}
	} );

	list.addEventListener( 'pointerup', () => {
		rows().forEach( ( row ) => {
			row.draggable = false;
		} );
	} );

	list.addEventListener( 'dragstart', ( event ) => {
		const row = event.target.closest?.( '.egnitech-trust-badge-item' );
		if ( ! row?.draggable ) {
			return;
		}
		dragged = row;
		dragged.classList.add( 'is-dragging' );
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', '' ); // Firefox starts no drag without data.
	} );

	list.addEventListener( 'dragover', ( event ) => {
		if ( ! dragged ) {
			return;
		}
		event.preventDefault();
		const over = event.target.closest?.( '.egnitech-trust-badge-item' );
		if ( ! over || over === dragged ) {
			return;
		}
		const { top, height } = over.getBoundingClientRect();
		list.insertBefore( dragged, event.clientY < top + height / 2 ? over : over.nextElementSibling );
	} );

	list.addEventListener( 'drop', ( event ) => {
		if ( dragged ) {
			event.preventDefault();
		}
	} );

	list.addEventListener( 'dragend', () => {
		if ( ! dragged ) {
			return;
		}
		dragged.classList.remove( 'is-dragging' );
		dragged.draggable = false;
		refresh();
		announcePosition( dragged );
		dragged = null;
	} );

	dialog.addEventListener( 'click', ( event ) => {
		const icon = event.target.closest( '.egnitech-icon-dialog__icon' );
		if ( icon ) {
			chooseIcon( icon );
		} else if ( event.target === dialog || event.target.closest( '[data-action="close"]' ) ) {
			dialog.close(); // The backdrop, or the close button.
		}
	} );

	// However the dialog closes (Escape included), focus goes back to the row's icon button.
	dialog.addEventListener( 'close', () => {
		editing?.querySelector( '[data-action="icon"]' ).focus();
		editing = null;
	} );

	colorDialog.addEventListener( 'click', ( event ) => {
		const swatch = event.target.closest( '.egnitech-color-dialog__swatch' );
		if ( swatch ) {
			setColor( coloring, swatch.dataset.color );
			colorDialog.close();
		} else if ( event.target === colorDialog || event.target.closest( '[data-action="close"]' ) ) {
			colorDialog.close(); // The backdrop, or the close button.
		}
	} );

	colorDialog.addEventListener( 'close', () => {
		coloring?.querySelector( '[data-action="color"]' ).focus();
		coloring = null;
	} );

	// With at most as many badges as colours, a shuffled palette gives every badge its own.
	box.querySelector( '.egnitech-trust-badges__random' ).addEventListener( 'click', () => {
		let colors = [];
		rows().forEach( ( row ) => {
			colors = colors.length ? colors : shuffled();
			setColor( row, colors.pop() );
		} );
		announce( i18n.randomised );
	} );

	allColor.addEventListener( 'change', () => {
		if ( ! allColor.value ) {
			return;
		}
		rows().forEach( ( row ) => setColor( row, allColor.value ) );
		announce( fill( i18n.allColor, allColor.selectedOptions[ 0 ].textContent.trim() ) );
		allColor.value = '';
	} );

	search.addEventListener( 'input', filterIcons );

	// Enter would save the whole product; here it picks the first matching icon.
	search.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' !== event.key ) {
			return;
		}
		event.preventDefault();
		const first = icons.find( ( icon ) => ! icon.hidden );
		if ( first ) {
			chooseIcon( first );
		}
	} );

	refresh();
} )();
