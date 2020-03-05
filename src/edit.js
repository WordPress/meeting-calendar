/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

const EditView = ( { className } ) => {
	return (
		<div className={ className }>
			<Icon icon="calendar" />
			<h6>{ __( 'Meeting Calendar', 'wporg' ) }</h6>
		</div>
	);
};

export default EditView;
