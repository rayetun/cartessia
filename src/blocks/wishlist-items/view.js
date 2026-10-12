/**
 * Wishlist (display) — Interactivity API view module.
 *
 * Watches the shared wishlist state and renders the saved products (fetched from
 * the WooCommerce Store API) as accessible cards with a remove button each. It
 * re-renders whenever the wishlist changes, so adding/removing anywhere on the
 * page updates the list live.
 */

import { store, getElement, getConfig } from '@wordpress/interactivity';
import {
	registerWishlist,
	removeProduct,
} from '../../shared/wishlist-interactivity';

registerWishlist();
const { state } = store( 'cartessia' );

let requestSeq = 0;

/**
 * Build one product card element.
 *
 * @param {Object} product Store API product.
 * @param {Object} cfg     Shared config.
 * @return {HTMLElement} The <li> card.
 */
function buildCard( product, cfg ) {
	const li = document.createElement( 'li' );
	li.className = 'cartessia-wl-items__card';

	const media = document.createElement( 'a' );
	media.className = 'cartessia-wl-items__media';
	media.href = product.permalink || '#';
	const image = product.images && product.images[ 0 ];
	const im = document.createElement( 'img' );
	if ( image ) {
		im.src = image.thumbnail || image.src || '';
		im.alt = image.alt || '';
	} else {
		// Fall back to WooCommerce's placeholder so cards are never empty.
		im.src = cfg.placeholderImg || '';
		im.alt = '';
	}
	im.loading = 'lazy';
	if ( im.src ) {
		media.appendChild( im );
	}
	li.appendChild( media );

	const body = document.createElement( 'div' );
	body.className = 'cartessia-wl-items__body';

	const title = document.createElement( 'a' );
	title.className = 'cartessia-wl-items__title';
	title.href = product.permalink || '#';
	title.textContent = product.name || '';
	body.appendChild( title );

	const price = document.createElement( 'div' );
	price.className = 'cartessia-wl-items__price';
	// price_html is WooCommerce-generated, already sanitised markup.
	price.innerHTML = typeof product.price_html === 'string' ? product.price_html : '';
	body.appendChild( price );

	const remove = document.createElement( 'button' );
	remove.type = 'button';
	remove.className = 'cartessia-wl-items__remove';
	remove.textContent = cfg.i18n.remove;
	remove.setAttribute(
		'aria-label',
		cfg.i18n.removeNamed.replace( '%s', product.name || '' )
	);
	remove.addEventListener( 'click', () => removeProduct( product.id ) );
	body.appendChild( remove );

	li.appendChild( body );
	return li;
}

store( 'cartessia', {
	callbacks: {
		renderWishlistItems() {
			const { ref } = getElement();
			// Read the wishlist synchronously so this watch re-runs on change.
			const ids = state.wishlist.slice();
			const list = ref.querySelector( '.cartessia-wl-items__list' );
			const empty = ref.querySelector( '.cartessia-wl-items__empty' );
			if ( ! list ) {
				return;
			}

			if ( 0 === ids.length ) {
				list.innerHTML = '';
				if ( empty ) {
					empty.hidden = false;
				}
				return;
			}
			if ( empty ) {
				empty.hidden = true;
			}

			const cfg = getConfig( 'cartessia' );
			const seq = ++requestSeq;
			fetch(
				cfg.storeApiRoot + 'products?per_page=100&include=' + ids.join( ',' )
			)
				.then( ( r ) => r.json() )
				.then( ( products ) => {
					if ( seq !== requestSeq ) {
						return; // A newer request superseded this one.
					}
					const byId = {};
					( products || [] ).forEach( ( p ) => {
						byId[ p.id ] = p;
					} );
					list.innerHTML = '';
					ids.forEach( ( id ) => {
						const p = byId[ id ];
						if ( p ) {
							list.appendChild( buildCard( p, cfg ) );
						}
					} );
				} )
				.catch( () => {} );
		},
	},
} );
