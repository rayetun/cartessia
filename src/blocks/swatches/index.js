/**
 * Variation Swatches — block registration (editor).
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	// Dynamic block — markup comes from render.php.
	save: () => null,
} );
