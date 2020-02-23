/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';

registerBlockType( 'wporg-meeting-calendar/main', {
	title: 'Meeting Calendar',
	icon: 'calendar',
	category: 'widgets',
	attributes: {},
	edit,
	save: () => null,
} );
