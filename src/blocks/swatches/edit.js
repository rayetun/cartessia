/**
 * Variation Swatches — editor component.
 *
 * The live markup is server-rendered (render.php), so the editor shows a
 * representative, accessible preview and the design controls. The preview uses
 * the same class names and preset modifiers as the front end, so choosing a
 * preset looks the same in the editor as on the store.
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';

const PRESETS = [
	{ label: __( 'Rounded', 'cartessia' ), value: 'rounded' },
	{ label: __( 'Square', 'cartessia' ), value: 'square' },
	{ label: __( 'Circle / Pill', 'cartessia' ), value: 'circle' },
	{ label: __( 'Bordered minimal', 'cartessia' ), value: 'bordered' },
];

const SIZES = [
	{ label: __( 'Small', 'cartessia' ), value: 'small' },
	{ label: __( 'Medium', 'cartessia' ), value: 'medium' },
	{ label: __( 'Large', 'cartessia' ), value: 'large' },
];

const SAMPLE = [
	{ label: __( 'Black', 'cartessia' ), color: '#1e1e1e' },
	{ label: __( 'Sky', 'cartessia' ), color: '#2f6fed' },
	{ label: __( 'Sand', 'cartessia' ), color: '#d8c3a5' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { productId, preset, size, showLabels } = attributes;

	const blockProps = useBlockProps( {
		className: `cartessia-swatches is-preset-${ preset } is-size-${ size }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Product ID', 'cartessia' ) }
						help={ __(
							'Leave 0 to use the current product (single product page or product loop). Set a variable product ID to show its swatches on any page.',
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
					<SelectControl
						label={ __( 'Size', 'cartessia' ) }
						value={ size }
						options={ SIZES }
						onChange={ ( value ) => setAttributes( { size: value } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show text label beside each colour', 'cartessia' ) }
						help={ __(
							'Recommended. A visible text label keeps colour swatches usable for everyone, not colour alone.',
							'cartessia'
						) }
						checked={ showLabels }
						onChange={ ( value ) => setAttributes( { showLabels: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="cartessia-swatch-group">
					<span className="cartessia-swatch-group__label">
						{ __( 'Color', 'cartessia' ) }
					</span>
					<div className="cartessia-swatch-group__options" role="radiogroup" aria-label={ __( 'Color', 'cartessia' ) }>
						{ SAMPLE.map( ( term, index ) => (
							<button
								key={ term.label }
								type="button"
								className="cartessia-swatch cartessia-swatch--visual"
								role="radio"
								aria-checked={ index === 0 ? 'true' : 'false' }
								tabIndex={ index === 0 ? 0 : -1 }
								style={ { '--cartessia-swatch-color': term.color } }
							>
								<span className="cartessia-swatch__chip" aria-hidden="true" />
								{ showLabels && (
									<span className="cartessia-swatch__label">{ term.label }</span>
								) }
							</button>
						) ) }
					</div>
				</div>
			</div>
		</>
	);
}
