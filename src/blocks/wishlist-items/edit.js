/**
 * Wishlist (display) — editor component.
 *
 * The saved items render on the front end (client-side, per user), so the editor
 * shows a placeholder plus the column control.
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { columns } = attributes;
	const blockProps = useBlockProps( { className: 'cartessia-wl-items-editor' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'cartessia' ) }>
					<RangeControl
						label={ __( 'Columns', 'cartessia' ) }
						value={ columns }
						min={ 1 }
						max={ 6 }
						onChange={ ( value ) => setAttributes( { columns: value || 3 } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<p>
					{ __(
						'Wishlist — the products a shopper has saved appear here on the front end.',
						'cartessia'
					) }
				</p>
			</div>
		</>
	);
}
