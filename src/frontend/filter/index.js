/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

const Filter = () => {
	const { team, clearTeam } = useEvents();

	if( ! team.length ) {
		return null;
	}

	return (
		<div className="wporg-meeting-calendar__filter">
			<Button isLink onClick={ () => void clearTeam() }>
				{ __( 'Show meetings for other teams', 'wporg' ) }
			</Button>
		</div>
	);
};

export default Filter;
