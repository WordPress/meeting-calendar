/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

const EditView = ( { className } ) => {
	return (
		<div className={ className }>
			<Icon icon="calendar" />
			<p>
				{ __(
					'Go to "Meetings" to add or edit your meetings',
					'wporg'
				) }
			</p>
		</div>
	);
};

export default EditView;
