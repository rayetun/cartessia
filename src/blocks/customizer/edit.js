/**
 * Live Product Customizer — editor component.
 *
 * The customizer is server-rendered, so the editor shows a representative
 * preview plus the field configuration (text field, priced options).
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Button,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const {
		productId,
		textEnabled,
		textLabel,
		textPlaceholder,
		textPrice,
		textMaxLength,
		optionsEnabled,
		optionsLabel,
		options,
		buttonText,
	} = attributes;

	const blockProps = useBlockProps( { className: 'cartessia-cz' } );

	const updateOption = ( index, key, value ) => {
		const next = options.map( ( opt, i ) =>
			i === index ? { ...opt, [ key ]: value } : opt
		);
		setAttributes( { options: next } );
	};

	const addOption = () => {
		setAttributes( { options: [ ...options, { label: '', price: 0 } ] } );
	};

	const removeOption = ( index ) => {
		setAttributes( {
			options: options.filter( ( _, i ) => i !== index ),
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product', 'cartessia' ) }>
					<TextControl
						type="number"
						label={ __( 'Product ID', 'cartessia' ) }
						help={ __(
							'Leave 0 to use the current product. The customizer works with simple (non-variable) products.',
							'cartessia'
						) }
						value={ productId || 0 }
						onChange={ ( value ) =>
							setAttributes( { productId: parseInt( value, 10 ) || 0 } )
						}
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

				<PanelBody title={ __( 'Custom text', 'cartessia' ) }>
					<ToggleControl
						label={ __( 'Enable a custom text field', 'cartessia' ) }
						checked={ textEnabled }
						onChange={ ( value ) => setAttributes( { textEnabled: value } ) }
						__nextHasNoMarginBottom
					/>
					{ textEnabled && (
						<>
							<TextControl
								label={ __( 'Field label', 'cartessia' ) }
								value={ textLabel }
								onChange={ ( value ) =>
									setAttributes( { textLabel: value } )
								}
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __( 'Placeholder', 'cartessia' ) }
								value={ textPlaceholder }
								onChange={ ( value ) =>
									setAttributes( { textPlaceholder: value } )
								}
								__nextHasNoMarginBottom
							/>
							<TextControl
								type="number"
								label={ __( 'Add-on price when filled in', 'cartessia' ) }
								value={ textPrice }
								onChange={ ( value ) =>
									setAttributes( {
										textPrice: parseFloat( value ) || 0,
									} )
								}
								__nextHasNoMarginBottom
							/>
							<TextControl
								type="number"
								label={ __( 'Maximum length', 'cartessia' ) }
								value={ textMaxLength }
								onChange={ ( value ) =>
									setAttributes( {
										textMaxLength: parseInt( value, 10 ) || 0,
									} )
								}
								__nextHasNoMarginBottom
							/>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Priced options', 'cartessia' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Enable a priced options group', 'cartessia' ) }
						checked={ optionsEnabled }
						onChange={ ( value ) =>
							setAttributes( { optionsEnabled: value } )
						}
						__nextHasNoMarginBottom
					/>
					{ optionsEnabled && (
						<>
							<TextControl
								label={ __( 'Group label', 'cartessia' ) }
								value={ optionsLabel }
								onChange={ ( value ) =>
									setAttributes( { optionsLabel: value } )
								}
								__nextHasNoMarginBottom
							/>
							{ options.map( ( opt, index ) => (
								<div key={ index } className="cartessia-cz-editor__option-row">
									<TextControl
										label={ __( 'Label', 'cartessia' ) }
										value={ opt.label }
										onChange={ ( value ) =>
											updateOption( index, 'label', value )
										}
										__nextHasNoMarginBottom
									/>
									<div className="cartessia-cz-editor__option-row-bottom">
										<TextControl
											type="number"
											label={ __( 'Price', 'cartessia' ) }
											value={ opt.price }
											onChange={ ( value ) =>
												updateOption(
													index,
													'price',
													parseFloat( value ) || 0
												)
											}
											__nextHasNoMarginBottom
										/>
										<Button
											isDestructive
											variant="tertiary"
											onClick={ () => removeOption( index ) }
										>
											{ __( 'Remove', 'cartessia' ) }
										</Button>
									</div>
								</div>
							) ) }
							<Button variant="secondary" onClick={ addOption }>
								{ __( 'Add option', 'cartessia' ) }
							</Button>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ textEnabled && (
					<div className="cartessia-cz__field">
						<label className="cartessia-cz__label">
							{ textLabel || __( 'Personalization', 'cartessia' ) }
						</label>
						<input
							type="text"
							className="cartessia-cz__text"
							placeholder={ textPlaceholder }
							disabled
						/>
					</div>
				) }
				{ optionsEnabled && options.length > 0 && (
					<fieldset className="cartessia-cz__options">
						<legend className="cartessia-cz__label">
							{ optionsLabel || __( 'Options', 'cartessia' ) }
						</legend>
						<div className="cartessia-cz__options-list">
							<span className="cartessia-cz__option">
								{ __( 'None', 'cartessia' ) }
							</span>
							{ options.map( ( opt, index ) => (
								<span key={ index } className="cartessia-cz__option">
									{ opt.label || __( 'Option', 'cartessia' ) }
								</span>
							) ) }
						</div>
					</fieldset>
				) }
				<div className="cartessia-cz__actions">
					<button type="button" className="cartessia-cz__add" disabled>
						{ buttonText || __( 'Add to cart', 'cartessia' ) }
					</button>
				</div>
			</div>
		</>
	);
}
