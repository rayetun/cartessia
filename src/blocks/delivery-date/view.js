/**
 * Delivery Date Picker — Interactivity API view module.
 *
 * Enhances the native <input type="date"> into a role="grid" calendar with full
 * keyboard support (arrows / Home / End / PageUp / PageDown, Enter/Space to
 * select). Unavailable dates are aria-disabled and announced; month changes and
 * the chosen date are announced through polite live regions. The native input
 * stays in the DOM as the value holder the add-to-cart request reads, so with no
 * JavaScript the native picker is the (fully accessible) fallback.
 */

import { store, getElement, getConfig } from '@wordpress/interactivity';

const pad = ( n ) => String( n ).padStart( 2, '0' );
const toISO = ( d ) => `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }`;
const parseISO = ( s ) => {
	const [ y, m, d ] = s.split( '-' ).map( Number );
	return new Date( y, m - 1, d );
};
const addDays = ( d, n ) => {
	const x = new Date( d );
	x.setDate( x.getDate() + n );
	return x;
};

/**
 * An accessible month calendar built into one date-picker block.
 */
class CartessiaCalendar {
	constructor( wrapper ) {
		this.wrapper = wrapper;
		this.native = wrapper.querySelector( '.cartessia-dp__native' );
		this.container = wrapper.querySelector( '.cartessia-dp__calendar' );
		this.chosenEl = wrapper.querySelector( '.cartessia-dp__chosen' );
		this.announceEl = wrapper.querySelector( '.cartessia-dp__announce' );
		this.min = wrapper.getAttribute( 'data-cartessia-min' );
		this.max = wrapper.getAttribute( 'data-cartessia-max' );
		try {
			this.disabledDays = JSON.parse(
				wrapper.getAttribute( 'data-cartessia-disabled' ) || '[]'
			);
		} catch ( e ) {
			this.disabledDays = [];
		}

		const cfg = getConfig( 'cartessia' );
		this.dates = cfg.dates;
		this.i18n = cfg.i18n;

		this.preset = wrapper.getAttribute( 'data-cartessia-preset' ) || 'inline';
		this.toggle = wrapper.querySelector( '.cartessia-dp__toggle' );
		this.toggleText = this.toggle
			? this.toggle.querySelector( '.cartessia-dp__toggle-text' )
			: null;
		this.open = false;
		this.onDocClick = null;

		this.selected = null;
		const minDate = parseISO( this.min );
		this.viewYear = minDate.getFullYear();
		this.viewMonth = minDate.getMonth();
		this.focusISO = this.firstSelectableISO();

		if ( this.native ) {
			this.native.setAttribute( 'hidden', '' );
		}
		this.render();
		this.setupPreset();
	}

	setupPreset() {
		// Popover: keep the calendar collapsed behind a toggle button. Any other
		// preset shows the calendar inline. With no JavaScript this never runs,
		// so the native date input remains the fallback.
		if ( 'popover' === this.preset && this.toggle ) {
			this.container.hidden = true;
			this.toggle.hidden = false;
			this.toggle.setAttribute( 'aria-expanded', 'false' );
			this.toggle.addEventListener( 'click', () => this.togglePopover() );
			this.toggle.addEventListener( 'keydown', ( e ) => {
				if ( 'ArrowDown' === e.key ) {
					e.preventDefault();
					this.openPopover();
				}
			} );
		} else {
			this.container.hidden = false;
		}
	}

	openPopover() {
		if ( ! this.toggle ) {
			return;
		}
		this.container.hidden = false;
		this.toggle.setAttribute( 'aria-expanded', 'true' );
		this.open = true;
		this.focusCurrent();
		this.onDocClick = ( e ) => {
			if ( ! this.wrapper.contains( e.target ) ) {
				this.closePopover( false );
			}
		};
		setTimeout( () => document.addEventListener( 'click', this.onDocClick ), 0 );
	}

	closePopover( focusToggle = true ) {
		if ( ! this.toggle ) {
			return;
		}
		this.container.hidden = true;
		this.toggle.setAttribute( 'aria-expanded', 'false' );
		this.open = false;
		if ( this.onDocClick ) {
			document.removeEventListener( 'click', this.onDocClick );
			this.onDocClick = null;
		}
		if ( focusToggle ) {
			this.toggle.focus();
		}
	}

	togglePopover() {
		if ( this.open ) {
			this.closePopover( true );
		} else {
			this.openPopover();
		}
	}

	isDisabled( iso ) {
		if ( iso < this.min || iso > this.max ) {
			return true;
		}
		return this.disabledDays.includes( parseISO( iso ).getDay() );
	}

	firstSelectableISO() {
		let d = parseISO( this.min );
		for ( let i = 0; i < 370; i++ ) {
			const iso = toISO( d );
			if ( ! this.isDisabled( iso ) ) {
				return iso;
			}
			d = addDays( d, 1 );
		}
		return this.min;
	}

	fullLabel( d ) {
		return (
			this.dates.weekdaysFull[ d.getDay() ] +
			', ' +
			d.getDate() +
			' ' +
			this.dates.months[ d.getMonth() ] +
			' ' +
			d.getFullYear()
		);
	}

	setAnnounce( msg ) {
		if ( this.announceEl ) {
			this.announceEl.textContent = msg;
		}
	}

	makeNavButton( label, symbol, onClick ) {
		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'cartessia-dp__nav';
		btn.setAttribute( 'aria-label', label );
		btn.textContent = symbol;
		btn.addEventListener( 'click', onClick );
		return btn;
	}

	render() {
		this.container.innerHTML = '';

		const header = document.createElement( 'div' );
		header.className = 'cartessia-dp__cal-header';
		const title = document.createElement( 'div' );
		title.className = 'cartessia-dp__cal-title';
		title.textContent = this.dates.months[ this.viewMonth ] + ' ' + this.viewYear;
		title.id = ( this.wrapper.id || 'cartessia-dp' ) + '-cal-title';
		header.append(
			this.makeNavButton( this.i18n.previousMonth, '‹', () => this.changeMonth( -1 ) ),
			title,
			this.makeNavButton( this.i18n.nextMonth, '›', () => this.changeMonth( 1 ) )
		);

		const table = document.createElement( 'table' );
		table.className = 'cartessia-dp__grid';
		table.setAttribute( 'role', 'grid' );
		table.setAttribute( 'aria-labelledby', title.id );

		const thead = document.createElement( 'thead' );
		const headRow = document.createElement( 'tr' );
		headRow.setAttribute( 'role', 'row' );
		for ( let i = 0; i < 7; i++ ) {
			const dayIdx = ( this.dates.firstDay + i ) % 7;
			const th = document.createElement( 'th' );
			th.setAttribute( 'scope', 'col' );
			th.setAttribute( 'role', 'columnheader' );
			const abbr = document.createElement( 'abbr' );
			abbr.title = this.dates.weekdaysFull[ dayIdx ];
			abbr.textContent = this.dates.weekdaysShort[ dayIdx ];
			th.append( abbr );
			headRow.append( th );
		}
		thead.append( headRow );
		table.append( thead );

		const tbody = document.createElement( 'tbody' );
		const firstOfMonth = new Date( this.viewYear, this.viewMonth, 1 );
		const startOffset = ( firstOfMonth.getDay() - this.dates.firstDay + 7 ) % 7;
		const daysInMonth = new Date( this.viewYear, this.viewMonth + 1, 0 ).getDate();

		let day = 1;
		for ( let week = 0; week < 6 && day <= daysInMonth; week++ ) {
			const row = document.createElement( 'tr' );
			row.setAttribute( 'role', 'row' );
			for ( let col = 0; col < 7; col++ ) {
				const td = document.createElement( 'td' );
				td.setAttribute( 'role', 'gridcell' );
				if ( ( 0 === week && col < startOffset ) || day > daysInMonth ) {
					td.className = 'cartessia-dp__empty';
					row.append( td );
					continue;
				}
				const dateObj = new Date( this.viewYear, this.viewMonth, day );
				const iso = toISO( dateObj );
				const disabled = this.isDisabled( iso );
				const full = this.fullLabel( dateObj );

				td.className = 'cartessia-dp__day';
				td.textContent = String( day );
				td.setAttribute( 'data-date', iso );
				td.setAttribute(
					'aria-label',
					disabled ? full + ' — ' + this.i18n.unavailableDate : full
				);
				td.setAttribute( 'aria-selected', this.selected === iso ? 'true' : 'false' );
				td.setAttribute( 'tabindex', iso === this.focusISO ? '0' : '-1' );
				if ( disabled ) {
					td.setAttribute( 'aria-disabled', 'true' );
					td.classList.add( 'is-disabled' );
				}
				if ( this.selected === iso ) {
					td.classList.add( 'is-selected' );
				}
				td.addEventListener( 'keydown', ( e ) => this.onKeydown( e, iso ) );
				td.addEventListener( 'click', () => this.onActivate( iso ) );
				row.append( td );
				day++;
			}
			tbody.append( row );
		}
		table.append( tbody );

		this.container.append( header, table );
	}

	focusCurrent() {
		const cell = this.container.querySelector(
			'td[data-date="' + this.focusISO + '"]'
		);
		if ( cell ) {
			cell.focus();
		}
	}

	moveFocus( iso ) {
		const d = parseISO( iso );
		if ( d.getFullYear() !== this.viewYear || d.getMonth() !== this.viewMonth ) {
			this.viewYear = d.getFullYear();
			this.viewMonth = d.getMonth();
			this.focusISO = iso;
			this.render();
			this.setAnnounce( this.dates.months[ this.viewMonth ] + ' ' + this.viewYear );
		} else {
			this.focusISO = iso;
			this.container.querySelectorAll( 'td[data-date]' ).forEach( ( td ) => {
				td.setAttribute(
					'tabindex',
					td.getAttribute( 'data-date' ) === iso ? '0' : '-1'
				);
			} );
		}
		this.focusCurrent();
	}

	changeMonth( delta ) {
		let m = this.viewMonth + delta;
		let y = this.viewYear;
		if ( m < 0 ) {
			m = 11;
			y--;
		} else if ( m > 11 ) {
			m = 0;
			y++;
		}
		this.viewYear = y;
		this.viewMonth = m;
		this.focusISO = toISO( new Date( y, m, 1 ) );
		this.render();
		this.setAnnounce( this.dates.months[ m ] + ' ' + y );
		this.focusCurrent();
	}

	shiftMonth( iso, delta ) {
		const d = parseISO( iso );
		const targetMonth = d.getMonth() + delta;
		const anchor = new Date( d.getFullYear(), targetMonth, 1 );
		const lastDay = new Date( anchor.getFullYear(), anchor.getMonth() + 1, 0 ).getDate();
		anchor.setDate( Math.min( d.getDate(), lastDay ) );
		return toISO( anchor );
	}

	onKeydown( event, iso ) {
		let target = null;
		const d = parseISO( iso );
		switch ( event.key ) {
			case 'ArrowLeft':
				target = toISO( addDays( d, -1 ) );
				break;
			case 'ArrowRight':
				target = toISO( addDays( d, 1 ) );
				break;
			case 'ArrowUp':
				target = toISO( addDays( d, -7 ) );
				break;
			case 'ArrowDown':
				target = toISO( addDays( d, 7 ) );
				break;
			case 'Home': {
				const off = ( d.getDay() - this.dates.firstDay + 7 ) % 7;
				target = toISO( addDays( d, -off ) );
				break;
			}
			case 'End': {
				const off = ( d.getDay() - this.dates.firstDay + 7 ) % 7;
				target = toISO( addDays( d, 6 - off ) );
				break;
			}
			case 'PageUp':
				target = this.shiftMonth( iso, -1 );
				break;
			case 'PageDown':
				target = this.shiftMonth( iso, 1 );
				break;
			case 'Enter':
			case ' ':
				event.preventDefault();
				this.onActivate( iso );
				return;
			case 'Escape':
				if ( 'popover' === this.preset && this.open ) {
					event.preventDefault();
					this.closePopover( true );
				}
				return;
			default:
				return;
		}
		if ( target ) {
			event.preventDefault();
			this.moveFocus( target );
		}
	}

	onActivate( iso ) {
		if ( this.isDisabled( iso ) ) {
			this.setAnnounce( this.i18n.unavailableDate );
			return;
		}
		this.selected = iso;
		if ( this.native ) {
			this.native.value = iso;
		}
		this.container.querySelectorAll( 'td[data-date]' ).forEach( ( td ) => {
			const chosen = td.getAttribute( 'data-date' ) === iso;
			td.setAttribute( 'aria-selected', chosen ? 'true' : 'false' );
			td.classList.toggle( 'is-selected', chosen );
		} );
		const text = this.fullLabel( parseISO( iso ) );
		if ( this.chosenEl ) {
			this.chosenEl.textContent = text;
		}
		this.setAnnounce( this.i18n.dateSelected.replace( '%s', text ) );

		if ( 'popover' === this.preset && this.toggle ) {
			if ( this.toggleText ) {
				this.toggleText.textContent = text;
			}
			this.closePopover( true );
		}
	}
}

store( 'cartessia', {
	callbacks: {
		initDatePicker() {
			const { ref } = getElement();
			// eslint-disable-next-line no-new
			new CartessiaCalendar( ref );
		},
	},
	actions: {
		onNativeDateChange() {
			const { ref } = getElement();
			const wrapper = ref.closest( '.cartessia-dp' );
			const chosen = wrapper && wrapper.querySelector( '.cartessia-dp__chosen' );
			if ( chosen && ref.value ) {
				chosen.textContent = ref.value;
			}
		},
		async deliveryAddToCart() {
			const { ref } = getElement();
			const wrapper = ref.closest( '.cartessia-dp' );
			if ( ! wrapper ) {
				return;
			}
			const cfg = getConfig( 'cartessia' );
			const native = wrapper.querySelector( '.cartessia-dp__native' );
			const announce = wrapper.querySelector( '.cartessia-dp__announce' );
			const date = native ? native.value : '';

			if ( ! date ) {
				if ( announce ) {
					announce.textContent = cfg.i18n.chooseDateFirst;
				}
				const firstCell = wrapper.querySelector( 'td[tabindex="0"]' );
				if ( firstCell ) {
					firstCell.focus();
				}
				return;
			}

			const body = new URLSearchParams();
			body.set( 'action', 'cartessia_add_delivery' );
			body.set( 'nonce', cfg.deliveryNonce );
			body.set( 'product_id', wrapper.getAttribute( 'data-cartessia-product' ) );
			body.set( 'rules', wrapper.getAttribute( 'data-cartessia-rules' ) );
			body.set( 'sig', wrapper.getAttribute( 'data-cartessia-sig' ) );
			body.set( 'date', date );

			try {
				const response = await fetch( cfg.ajaxUrl, { method: 'POST', body } );
				const data = await response.json();
				if ( data && data.success ) {
					if ( announce ) {
						announce.textContent = data.data.message;
					}
					if ( window.jQuery ) {
						window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
					}
					document.body.dispatchEvent(
						new CustomEvent( 'wc-blocks_added_to_cart' )
					);
				} else if ( announce ) {
					announce.textContent =
						( data && data.data && data.data.message ) ||
						cfg.i18n.addToCartError;
				}
			} catch ( e ) {
				if ( announce ) {
					announce.textContent = cfg.i18n.addToCartError;
				}
			}
		},
	},
} );
