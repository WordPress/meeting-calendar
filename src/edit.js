/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

const EditView = () => (
	<Placeholder
		icon="calendar"
		label={ __( 'Go to "Meetings" to add or edit your meetings', 'wporg' ) }
	/>
);
export default EditView;
