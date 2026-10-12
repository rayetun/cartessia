/**
 * Quick View — editor component.
 *
 * The modal itself is server-rendered, so the editor shows the trigger button
 * (exactly as it appears on the front end) plus placement and design controls.
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

const PRESETS = [
	{ label: __( 'Centered modal', 'cartessia' ), value: 'centered' },
	{ label: __( 'Side sheet', 'cartessia' ), value: 'side' },
	{ label: __( 'Compact card', 'cartessia' ), value: 'compact' },
];

const IMAGE_SIZES = [
	{ label: __( 'Small', 'cartessia' ), value: 'woocommerce_thumbnail' },
	{ label: __( 'Medium', 'cartessia' ), value: 'medium' },
	{ label: __( 'Large', 'cartessia' ), value: 'woocommerce_single' },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		productId,
		preset,
		buttonText,
		imageSize,
		showPrice,
		showDescription,
		showViewLink,
		accentColor,
		closeColor,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `cartessia-qv is-preset-${ preset }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Product ID', 'cartessia' ) }
						help={ __(
							'Leave 0 to use the current product (single product page or product loop). Set a product ID to preview a specific product anywhere.',
							'cartessia'
						) }
						value={ productId || 0 }
						onChange={ ( value ) =>
							setAttributes( { productId: parseInt( value, 10 ) || 0 } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Layout', 'cartessia' ) }>
					<SelectControl
						label={ __( 'Preset', 'cartessia' ) }
						value={ preset }
						options={ PRESETS }
						onChange={ ( value ) => setAttributes( { preset: value } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Image size', 'cartessia' ) }
						value={ imageSize }
						options={ IMAGE_SIZES }
						onChange={ ( value ) => setAttributes( { imageSize: value } ) }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Button text', 'cartessia' ) }
						value={ buttonText }
						placeholder={ __( 'Quick view', 'cartessia' ) }
						onChange={ ( value ) => setAttributes( { buttonText: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Content', 'cartessia' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Show price', 'cartessia' ) }
						checked={ showPrice }
						onChange={ ( value ) => setAttributes( { showPrice: value } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show short description', 'cartessia' ) }
						checked={ showDescription }
						onChange={ ( value ) =>
							setAttributes( { showDescription: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show “View full details” link', 'cartessia' ) }
						checked={ showViewLink }
						onChange={ ( value ) => setAttributes( { showViewLink: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Colour', 'cartessia' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: accentColor,
							onChange: ( value ) =>
								setAttributes( { accentColor: value || '' } ),
							label: __( 'Add to cart button', 'cartessia' ),
						},
						{
							value: closeColor,
							onChange: ( value ) =>
								setAttributes( { closeColor: value || '' } ),
							label: __( 'Close button', 'cartessia' ),
						},
					] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<button type="button" className="cartessia-qv__trigger" aria-haspopup="dialog">
					{ buttonText || __( 'Quick view', 'cartessia' ) }
				</button>
			</div>
		</>
	);
}
