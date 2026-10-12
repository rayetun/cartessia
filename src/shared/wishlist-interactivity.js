/**
 * Shared wishlist interactivity.
 *
 * Holds the wishlist (an array of product IDs) in the shared "cartessia" store
 * so every toggle for a product — in a grid, on the product page, anywhere —
 * reflects the same state live. Guests persist to localStorage; logged-in
 * customers persist to user meta over admin-ajax so the list follows them across
 * devices. Per-user state is loaded client-side, so nothing user-specific is
 * baked into cacheable HTML.
 */

import { store, getContext, getConfig } from '@wordpress/interactivity';

const STORAGE_KEY = 'cartessia_wishlist';

/**
 * Persist the wishlist IDs (server for logged-in users, browser for guests).
 *
 * @param {number[]} ids Product IDs.
 */
function persist( ids ) {
	const cfg = getConfig( 'cartessia' );
	if ( cfg.loggedIn ) {
		const body = new URLSearchParams();
		body.set( 'action', 'cartessia_wishlist_set' );
		body.set( 'nonce', cfg.wishlistNonce );
		ids.forEach( ( id ) => body.append( 'ids[]', id ) );
		fetch( cfg.ajaxUrl, { method: 'POST', body } ).catch( () => {} );
	} else {
		try {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( ids ) );
		} catch ( e ) {}
	}
}

/**
 * Remove a product from the wishlist and persist (for use from imperatively
 * created controls, e.g. the display block's remove buttons).
 *
 * @param {number} id Product ID.
 */
export function removeProduct( id ) {
	const { state } = store( 'cartessia' );
	state.wishlist = state.wishlist.filter( ( x ) => x !== id );
	persist( state.wishlist );
}

let registered = false;

/**
 * Register wishlist state and actions on the shared store, and load the saved
 * list once.
 */
export function registerWishlist() {
	if ( registered ) {
		return;
	}
	registered = true;

	const { state } = store( 'cartessia', {
		state: {
			wishlist: [],
			// Whether the current context's product is saved. Read by each
			// toggle's aria-pressed binding, so all toggles update together.
			get isInWishlist() {
				const ctx = getContext();
				return (
					!! ctx &&
					Array.isArray( state.wishlist ) &&
					state.wishlist.includes( ctx.productId )
				);
			},
			// Accessible name for the current toggle, reflecting its state.
			get wishlistLabel() {
				const ctx = getContext();
				return state.isInWishlist ? ctx.removeLabel : ctx.addLabel;
			},
		},
		actions: {
			toggleWishlist() {
				const ctx = getContext();
				const id = ctx.productId;
				if ( state.wishlist.includes( id ) ) {
					state.wishlist = state.wishlist.filter( ( x ) => x !== id );
				} else {
					state.wishlist = [ ...state.wishlist, id ];
				}
				persist( state.wishlist );
			},
			removeFromWishlist() {
				const ctx = getContext();
				const id = ctx.productId;
				state.wishlist = state.wishlist.filter( ( x ) => x !== id );
				persist( state.wishlist );
			},
		},
	} );

	// Load the saved wishlist once, client-side (cache-safe).
	const cfg = getConfig( 'cartessia' );
	if ( cfg.loggedIn ) {
		const body = new URLSearchParams();
		body.set( 'action', 'cartessia_wishlist_get' );
		body.set( 'nonce', cfg.wishlistNonce );
		fetch( cfg.ajaxUrl, { method: 'POST', body } )
			.then( ( r ) => r.json() )
			.then( ( d ) => {
				if ( d && d.success && Array.isArray( d.data.ids ) ) {
					state.wishlist = d.data.ids.map( Number );
				}
			} )
			.catch( () => {} );
	} else {
		try {
			const raw = localStorage.getItem( STORAGE_KEY );
			if ( raw ) {
				const ids = JSON.parse( raw );
				if ( Array.isArray( ids ) ) {
					state.wishlist = ids.map( Number );
				}
			}
		} catch ( e ) {}
	}

	return state;
}
