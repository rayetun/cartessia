/**
 * Live Product Customizer — Interactivity API view module.
 *
 * Computes the running total as the shopper types custom text or picks a priced
 * option, updates it visually, and announces it through a polite live region —
 * the dynamic-price announcement most add-on plugins miss. Adds to the cart with
 * the custom data through a signed admin-ajax request (prices are recomputed
 * server-side from the signed config).
 */

import { store, getElement, getContext, getConfig } from '@wordpress/interactivity';

/**
 * Format a number as a WooCommerce-style price string.
 *
 * @param {number} amount   The amount.
 * @param {Object} currency Currency format from the shared config.
 * @return {string} Formatted price.
 */
function formatPrice( amount, currency ) {
	const negative = amount < 0;
	const abs = Math.abs( amount );
	const fixed = abs.toFixed( currency.decimals );
	const parts = fixed.split( '.' );
	parts[ 0 ] = parts[ 0 ].replace(
		/\B(?=(\d{3})+(?!\d))/g,
		currency.thousandSep
	);
	const num =
		parts.length > 1 ? parts[ 0 ] + currency.decimalSep + parts[ 1 ] : parts[ 0 ];
	const sym = currency.symbol;
	let out;
	switch ( currency.position ) {
		case 'right':
			out = num + sym;
			break;
		case 'right_space':
			out = num + ' ' + sym;
			break;
		case 'left_space':
			out = sym + ' ' + num;
			break;
		default:
			out = sym + num;
	}
	return ( negative ? '-' : '' ) + out;
}

/**
 * Recompute the running total into the block context and announce it.
 *
 * @param {Object} context The block context.
 */
function recompute( context ) {
	const cfg = getConfig( 'cartessia' );
	const total =
		context.base +
		( context.textActive ? context.textPrice : 0 ) +
		context.optionPrice;
	context.totalText = formatPrice( total, cfg.currency );
	context.announce = cfg.i18n.totalIs.replace( '%s', context.totalText );
}

/**
 * Update option radio selection within its group.
 *
 * @param {HTMLElement} radio Chosen option button.
 * @return {HTMLElement|null} The group.
 */
function markOption( radio ) {
	const group = radio.closest( '[role="radiogroup"]' );
	if ( ! group ) {
		return null;
	}
	group.querySelectorAll( '[role="radio"]' ).forEach( ( node ) => {
		const chosen = node === radio;
		node.setAttribute( 'aria-checked', chosen ? 'true' : 'false' );
		node.setAttribute( 'tabindex', chosen ? '0' : '-1' );
	} );
	return group;
}

/**
 * Find a sibling option at an offset, wrapping around.
 *
 * @param {HTMLElement} current Current option.
 * @param {number}      delta   +1 or -1.
 * @return {HTMLElement|null}
 */
function siblingOption( current, delta ) {
	const group = current.closest( '[role="radiogroup"]' );
	if ( ! group ) {
		return null;
	}
	const radios = Array.from( group.querySelectorAll( '[role="radio"]' ) );
	const index = radios.indexOf( current );
	if ( index === -1 ) {
		return null;
	}
	return radios[ ( index + delta + radios.length ) % radios.length ];
}

/**
 * Apply an option button's price/index to context and recompute.
 *
 * @param {HTMLElement} radio   The option.
 * @param {Object}      context Block context.
 */
function applyOption( radio, context ) {
	markOption( radio );
	context.optionPrice = parseFloat( radio.getAttribute( 'data-cartessia-price' ) ) || 0;
	context.optionIndex = parseInt( radio.getAttribute( 'data-cartessia-index' ), 10 );
	recompute( context );
}

store( 'cartessia', {
	actions: {
		onCustomizerText( event ) {
			const context = getContext();
			context.textActive = event.target.value.trim().length > 0;
			recompute( context );
		},
		selectCustomizerOption() {
			const { ref } = getElement();
			applyOption( ref, getContext() );
		},
		customizerOptionKeydown( event ) {
			const { ref } = getElement();
			const context = getContext();
			let target = null;

			switch ( event.key ) {
				case 'ArrowRight':
				case 'ArrowDown':
					target = siblingOption( ref, 1 );
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					target = siblingOption( ref, -1 );
					break;
				case ' ':
				case 'Enter':
					event.preventDefault();
					applyOption( ref, context );
					return;
				default:
					return;
			}

			if ( target ) {
				event.preventDefault();
				applyOption( target, context );
				target.focus();
			}
		},
		async customizerAddToCart() {
			const { ref } = getElement();
			const context = getContext();
			const cfg = getConfig( 'cartessia' );
			const wrapper = ref.closest( '.cartessia-cz' );
			if ( ! wrapper ) {
				return;
			}

			const textField = wrapper.querySelector( '.cartessia-cz__text' );
			const body = new URLSearchParams();
			body.set( 'action', 'cartessia_add_custom' );
			body.set( 'nonce', cfg.customizerNonce );
			body.set( 'product_id', wrapper.getAttribute( 'data-cartessia-product' ) );
			body.set( 'config', wrapper.getAttribute( 'data-cartessia-config' ) );
			body.set( 'sig', wrapper.getAttribute( 'data-cartessia-sig' ) );
			body.set( 'text', textField ? textField.value : '' );
			body.set( 'option', String( context.optionIndex ) );

			try {
				const response = await fetch( cfg.ajaxUrl, {
					method: 'POST',
					body,
				} );
				const data = await response.json();
				if ( data && data.success ) {
					context.announce = data.data.message;
					if ( window.jQuery ) {
						window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
					}
					document.body.dispatchEvent(
						new CustomEvent( 'wc-blocks_added_to_cart' )
					);
				} else {
					context.announce =
						( data && data.data && data.data.message ) ||
						cfg.i18n.addToCartError;
				}
			} catch ( e ) {
				context.announce = cfg.i18n.addToCartError;
			}
		},
	},
} );
