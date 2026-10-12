/**
 * Delivery Date Picker — editor component.
 *
 * The calendar is built on the front end, so the editor shows a representative
 * field plus the date rules (lead time, range, unavailable weekdays).
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	CheckboxControl,
	SelectControl,
} from '@wordpress/components';

const PRESETS = [
	{ label: __( 'Popover (opens on click)', 'cartessia' ), value: 'popover' },
	{ label: __( 'Inline (always open)', 'cartessia' ), value: 'inline' },
	{ label: __( 'Compact inline', 'cartessia' ), value: 'compact' },
];

const WEEKDAYS = [
	{ index: 0, label: __( 'Sunday', 'cartessia' ) },
	{ index: 1, label: __( 'Monday', 'cartessia' ) },
	{ index: 2, label: __( 'Tuesday', 'cartessia' ) },
	{ index: 3, label: __( 'Wednesday', 'cartessia' ) },
	{ index: 4, label: __( 'Thursday', 'cartessia' ) },
	{ index: 5, label: __( 'Friday', 'cartessia' ) },
	{ index: 6, label: __( 'Saturday', 'cartessia' ) },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		productId,
		preset,
		fieldLabel,
		minLeadDays,
		maxDays,
		disabledWeekdays,
		buttonText,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `cartessia-dp is-preset-${ preset }`,
	} );

	const toggleWeekday = ( index, checked ) => {
		const set = new Set( disabledWeekdays );
		if ( checked ) {
			set.add( index );
		} else {
			set.delete( index );
		}
		setAttributes( { disabledWeekdays: Array.from( set ).sort() } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display', 'cartessia' ) }>
					<SelectControl
						label={ __( 'Preset', 'cartessia' ) }
						value={ preset }
						options={ PRESETS }
						onChange={ ( value ) => setAttributes( { preset: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Product', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Product ID', 'cartessia' ) }
						help={ __(
							'Leave 0 to use the current product. Works with simple (non-variable) products.',
							'cartessia'
						) }
						value={ productId || 0 }
						onChange={ ( value ) =>
							setAttributes( { productId: parseInt( value, 10 ) || 0 } )
						}
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Field label', 'cartessia' ) }
						value={ fieldLabel }
						onChange={ ( value ) => setAttributes( { fieldLabel: value } ) }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Add to cart button text', 'cartessia' ) }
						value={ buttonText }
						placeholder={ __( 'Add to cart', 'cartessia' ) }
						onChange={ ( value ) => setAttributes( { buttonText: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody title={ __( 'Date rules', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Minimum lead time (days)', 'cartessia' ) }
						help={ __(
							'Earliest selectable date = today plus this many days.',
							'cartessia'
						) }
						value={ minLeadDays }
						onChange={ ( value ) =>
							setAttributes( { minLeadDays: parseInt( value, 10 ) || 0 } )
						}
						__nextHasNoMarginBottom
					/>
					<TextControl
						type="number"
						label={ __( 'Booking window (days)', 'cartessia' ) }
						help={ __(
							'Latest selectable date = today plus this many days.',
							'cartessia'
						) }
						value={ maxDays }
						onChange={ ( value ) =>
							setAttributes( { maxDays: parseInt( value, 10 ) || 1 } )
						}
						__nextHasNoMarginBottom
					/>
					<p>{ __( 'Unavailable weekdays', 'cartessia' ) }</p>
					{ WEEKDAYS.map( ( day ) => (
						<CheckboxControl
							key={ day.index }
							label={ day.label }
							checked={ disabledWeekdays.includes( day.index ) }
							onChange={ ( checked ) => toggleWeekday( day.index, checked ) }
							__nextHasNoMarginBottom
						/>
					) ) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="cartessia-dp__field">
					<label className="cartessia-dp__label">
						{ fieldLabel || __( 'Delivery date', 'cartessia' ) }
					</label>
					<input type="date" className="cartessia-dp__native" disabled />
				</div>
				<p className="cartessia-dp__editor-note">
					{ __(
						'An accessible calendar with full keyboard support is shown here on the front end.',
						'cartessia'
					) }
				</p>
				<div className="cartessia-dp__actions">
					<button type="button" className="cartessia-dp__add" disabled>
						{ buttonText || __( 'Add to cart', 'cartessia' ) }
					</button>
				</div>
			</div>
		</>
	);
}
