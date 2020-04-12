/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Calendar from './frontend/app';

const EditView = () => (
	<Disabled>
		<Calendar />
	</Disabled>
);
export default EditView;
