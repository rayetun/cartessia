/**
 * Wishlist Button — editor component.
 *
 * The live toggle is server-rendered, so the editor shows a representative
 * button plus placement and design controls.
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';

const PRESETS = [
	{ label: __( 'Heart', 'cartessia' ), value: 'heart' },
	{ label: __( 'Outline heart', 'cartessia' ), value: 'outline' },
	{ label: __( 'Text button', 'cartessia' ), value: 'text' },
];

const HEART = (
	<svg viewBox="0 0 24 24" width="22" height="22" focusable="false" aria-hidden="true">
		<path d="M12 20.7 4.1 12.9a4.6 4.6 0 0 1 0-6.5 4.6 4.6 0 0 1 6.5 0l1.4 1.4 1.4-1.4a4.6 4.6 0 0 1 6.5 0 4.6 4.6 0 0 1 0 6.5Z" />
	</svg>
);

export default function Edit( { attributes, setAttributes } ) {
	const { productId, preset, addLabel, removeLabel } = attributes;

	const blockProps = useBlockProps( {
		className: `cartessia-wl is-preset-${ preset }`,
	} );

	const addText = addLabel || __( 'Add to wishlist', 'cartessia' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Product ID', 'cartessia' ) }
						help={ __(
							'Leave 0 to use the current product (single product page or product loop).',
							'cartessia'
						) }
						value={ productId || 0 }
						onChange={ ( value ) =>
							setAttributes( { productId: parseInt( value, 10 ) || 0 } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Design', 'cartessia' ) }>
					<SelectControl
						label={ __( 'Preset', 'cartessia' ) }
						value={ preset }
						options={ PRESETS }
						onChange={ ( value ) => setAttributes( { preset: value } ) }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Add label', 'cartessia' ) }
						value={ addLabel }
						placeholder={ __( 'Add to wishlist', 'cartessia' ) }
						onChange={ ( value ) => setAttributes( { addLabel: value } ) }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Remove label', 'cartessia' ) }
						value={ removeLabel }
						placeholder={ __( 'Remove from wishlist', 'cartessia' ) }
						onChange={ ( value ) => setAttributes( { removeLabel: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<button
				{ ...blockProps }
				type="button"
				aria-pressed="false"
				aria-label={ preset === 'text' ? undefined : addText }
			>
				<span className="cartessia-wl__icon" aria-hidden="true">
					{ HEART }
				</span>
				{ preset === 'text' && (
					<span className="cartessia-wl__text">{ addText }</span>
				) }
			</button>
		</>
	);
}
