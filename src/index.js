// eslint-disable-next-line no-undef
import { registerBlockType } from '@wordpress/blocks'

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';

registerBlockType( 'a8c-meeting-calendar/main', {
	title: 'Meeting Calendar',
	icon: 'calendar',
	category: 'widgets',
	attributes: {},
	edit,
	save,
} );
