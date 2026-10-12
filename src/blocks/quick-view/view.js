/**
 * Quick View — Interactivity API view module.
 *
 * Opens/closes a native <dialog> (platform focus trap, Escape, focus return),
 * and adds the selected product/variation to the cart through the WooCommerce
 * Store API, announcing the result politely. Reuses the shared swatch actions so
 * swatches inside the dialog behave exactly as they do on a product page.
 */

import { store, getElement, getConfig } from '@wordpress/interactivity';
import { registerSwatchActions } from '../../shared/swatches-interactivity';

registerSwatchActions();

/**
 * Read the chosen variation (and selected attributes) from an open dialog.
 *
 * @param {HTMLElement} dialog The dialog element.
 * @return {{ variation: Object|null, selected: Object, complete: boolean }}
 */
function readSelection( dialog ) {
	let variations = [];
	try {
		variations = JSON.parse(
			dialog.getAttribute( 'data-cartessia-variations' ) || '[]'
		);
	} catch ( e ) {
		variations = [];
	}

	const groups = dialog.querySelectorAll( '[role="radiogroup"]' );
	const selected = {};
	let complete = true;

	groups.forEach( ( group ) => {
		const attr = group.getAttribute( 'data-cartessia-attr' );
		const chosen = group.querySelector(
			'[role="radio"][aria-checked="true"]'
		);
		if ( attr && chosen ) {
			selected[ attr ] = chosen.getAttribute( 'data-cartessia-value' );
		} else {
			complete = false;
		}
	} );

	if ( ! complete ) {
		return { variation: null, selected, complete: false };
	}

	const variation =
		variations.find( ( candidate ) =>
			Object.keys( selected ).every( ( attr ) => {
				const value = candidate.attributes[ attr ];
				return value === '' || value === selected[ attr ];
			} )
		) || null;

	return { variation, selected, complete: true };
}

const { state } = store( 'cartessia', {
	state: {
		qvAnnouncement: '',
	},
	actions: {
		openQuickView() {
			const { ref } = getElement();
			const wrapper = ref.closest( '.cartessia-qv' );
			const dialog = wrapper && wrapper.querySelector( 'dialog' );
			if ( dialog && typeof dialog.showModal === 'function' ) {
				dialog.showModal();
				const cfg = getConfig( 'cartessia' );
				state.qvAnnouncement = cfg.i18n.productLoaded;
			}
		},
		closeQuickView() {
			const { ref } = getElement();
			const dialog = ref.closest( 'dialog' );
			if ( dialog ) {
				dialog.close();
			}
		},
		onQuickViewClose() {
			state.qvAnnouncement = '';
		},
		async quickViewAddToCart() {
			const { ref } = getElement();
			const dialog = ref.closest( 'dialog' );
			if ( ! dialog ) {
				return;
			}

			const cfg = getConfig( 'cartessia' );
			const type = dialog.getAttribute( 'data-cartessia-type' );
			const productId = parseInt(
				dialog.getAttribute( 'data-cartessia-product' ),
				10
			);

			let body;

			if ( 'variable' === type ) {
				const { variation, selected, complete } = readSelection( dialog );
				if ( ! complete || ! variation ) {
					state.qvAnnouncement = cfg.i18n.selectOptions;
					const firstRadio = dialog.querySelector( '[role="radio"]' );
					if ( firstRadio ) {
						firstRadio.focus();
					}
					return;
				}
				body = {
					id: variation.id,
					quantity: 1,
					variation: Object.keys( selected ).map( ( attr ) => ( {
						attribute: attr,
						value: selected[ attr ],
					} ) ),
				};
			} else {
				body = { id: productId, quantity: 1 };
			}

			try {
				const response = await fetch(
					cfg.storeApiRoot + 'cart/add-item',
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							Nonce: cfg.nonce,
						},
						body: JSON.stringify( body ),
					}
				);

				if ( response.ok ) {
					state.qvAnnouncement = cfg.i18n.addedToCart;
					// Let WooCommerce cart/mini-cart blocks refresh if present.
					document.body.dispatchEvent(
						new CustomEvent( 'wc-blocks_added_to_cart' )
					);
				} else {
					state.qvAnnouncement = cfg.i18n.addToCartError;
				}
			} catch ( e ) {
				state.qvAnnouncement = cfg.i18n.addToCartError;
			}
		},
	},
} );
