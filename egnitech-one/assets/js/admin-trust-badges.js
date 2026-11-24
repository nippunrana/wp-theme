/**
 * Trust badge icon picker (product edit screen).
 *
 * The radio buttons submit with the product form on their own. This file only shows the
 * chosen icon on the picker's button and closes the grid. Only one grid opens at a time
 * because the pickers share a <details name>.
 */
( () => {
	const pickers = document.querySelectorAll( '.egnitech-icon-picker' );

	pickers.forEach( ( picker ) => {
		const current = picker.querySelector( '.egnitech-icon-picker__current' );
		const summary = picker.querySelector( 'summary' );

		picker.addEventListener( 'change', ( event ) => {
			const svg = event.target.closest( '.egnitech-icon-picker__option' )?.querySelector( 'svg' );
			if ( svg ) {
				current.replaceChildren( svg.cloneNode( true ) );
			}
		} );

		// A mouse or touch choice closes the grid. Keyboard clicks (detail 0) do not,
		// so the arrow keys can move through the icons.
		picker.addEventListener( 'click', ( event ) => {
			if ( event.detail > 0 && event.target.closest( '.egnitech-icon-picker__option' ) ) {
				picker.open = false;
			}
		} );

		picker.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && picker.open ) {
				event.preventDefault();
				picker.open = false;
				summary.focus();
			}
		} );
	} );

	// A click anywhere else closes an open grid.
	document.addEventListener( 'click', ( event ) => {
		pickers.forEach( ( picker ) => {
			if ( picker.open && ! picker.contains( event.target ) ) {
				picker.open = false;
			}
		} );
	} );
} )();
