/**
 * Shared swatch interactivity.
 *
 * Registers the swatch radio-group behaviour into the shared "cartessia" store.
 * Imported by both the Swatches block and the Quick View block, so swatches
 * behave identically on a product page and inside a Quick View dialog.
 */

import { store, getElement } from '@wordpress/interactivity';

/**
 * Update selection state within a group without touching the native <select>.
 *
 * @param {HTMLElement} radio The chosen role="radio" button.
 * @return {HTMLElement|null} The group element, or null.
 */
function markChecked( radio ) {
	const group = radio.closest( '[role="radiogroup"]' );
	if ( ! group ) {
		return null;
	}

	const radios = Array.from( group.querySelectorAll( '[role="radio"]' ) );
	radios.forEach( ( node ) => {
		const isChosen = node === radio;
		node.setAttribute( 'aria-checked', isChosen ? 'true' : 'false' );
		node.setAttribute( 'tabindex', isChosen ? '0' : '-1' );
	} );

	return group;
}

/**
 * Drive WooCommerce's native variation <select> from the swatch selection.
 *
 * @param {HTMLElement} group The radiogroup (carries data-cartessia-attr).
 * @param {HTMLElement} radio The chosen radio (carries data-cartessia-value).
 */
function mirrorToNativeSelect( group, radio ) {
	const attrName = group.getAttribute( 'data-cartessia-attr' );
	const value = radio.getAttribute( 'data-cartessia-value' );
	if ( ! attrName ) {
		return;
	}

	const select = document.querySelector( 'select[name="' + attrName + '"]' );
	if ( ! select ) {
		return;
	}

	select.value = value;
	select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
}

/**
 * Select a swatch from user action: update state and drive the native select.
 *
 * @param {HTMLElement} radio The chosen role="radio" button.
 */
function selectRadio( radio ) {
	const group = markChecked( radio );
	if ( group ) {
		mirrorToNativeSelect( group, radio );
	}
}

/**
 * Find the radio at a given offset within its group, wrapping around.
 *
 * @param {HTMLElement} current The current radio.
 * @param {number}      delta   +1 (next) or -1 (previous).
 * @return {HTMLElement|null} The target radio.
 */
function siblingRadio( current, delta ) {
	const group = current.closest( '[role="radiogroup"]' );
	if ( ! group ) {
		return null;
	}
	const radios = Array.from( group.querySelectorAll( '[role="radio"]' ) );
	const index = radios.indexOf( current );
	if ( index === -1 ) {
		return null;
	}
	const next = ( index + delta + radios.length ) % radios.length;
	return radios[ next ];
}

let registered = false;

/**
 * Register swatch state, callbacks, and actions on the shared store (once).
 */
export function registerSwatchActions() {
	if ( registered ) {
		return;
	}
	registered = true;

	const { state } = store( 'cartessia', {
		state: {
			announcement: '',
		},
		callbacks: {
			/**
			 * On hydration: hide the native variation dropdowns the swatches now
			 * drive, and reflect any value WooCommerce has preselected. With no
			 * JavaScript this never runs, so the dropdowns stay usable.
			 */
			initSwatches() {
				const { ref } = getElement();
				const groups = ref.querySelectorAll( '[role="radiogroup"]' );

				groups.forEach( ( group ) => {
					const attr = group.getAttribute( 'data-cartessia-attr' );
					if ( ! attr ) {
						return;
					}
					const select = document.querySelector(
						'select[name="' + attr + '"]'
					);
					if ( ! select ) {
						return;
					}

					const row = select.closest( 'tr' );
					if ( row ) {
						row.hidden = true;
					} else {
						select.style.display = 'none';
					}

					if ( select.value ) {
						const chosen = group.querySelector(
							'[data-cartessia-value="' +
								CSS.escape( select.value ) +
								'"]'
						);
						if ( chosen ) {
							markChecked( chosen );
						}
					}
				} );
			},
		},
		actions: {
			selectSwatch() {
				const { ref } = getElement();
				selectRadio( ref );
				const label = ref.textContent.trim();
				if ( label ) {
					state.announcement = label + ', selected';
				}
			},
			onKeydown( event ) {
				const { ref } = getElement();
				let target = null;

				switch ( event.key ) {
					case 'ArrowRight':
					case 'ArrowDown':
						target = siblingRadio( ref, 1 );
						break;
					case 'ArrowLeft':
					case 'ArrowUp':
						target = siblingRadio( ref, -1 );
						break;
					case ' ':
					case 'Enter':
						event.preventDefault();
						selectRadio( ref );
						state.announcement =
							ref.textContent.trim() + ', selected';
						return;
					default:
						return;
				}

				if ( target ) {
					event.preventDefault();
					selectRadio( target );
					target.focus();
					state.announcement =
						target.textContent.trim() + ', selected';
				}
			},
		},
	} );
}
