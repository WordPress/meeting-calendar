/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';

registerBlockType( 'a8c-meeting-calendar/main', {
	title: 'Meeting Calendar',
	icon: 'calendar',
	category: 'widgets',
	attributes: {},
	edit,
	save: () => null,
} );
