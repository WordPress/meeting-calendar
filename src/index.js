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
	/**
	 * Save nothing; the block is rendered on the server.
	 *
	 * @return {null} No markup.
	 */
	save: () => null,
} );
