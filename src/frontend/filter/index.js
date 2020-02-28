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
	const { team, setTeam } = useEvents();

	if ( ! team.length ) {
		return null;
	}

	return (
		<div className="wporg-meeting-calendar__filter">
			<p>
				Showing meetings for{ ' ' }
				<span style={ { textTransform: 'capitalize' } }>
					{ team } team.
				</span>
			</p>
			<Button
				icon="no-alt"
				isLink
				isDestructive
				onClick={ () => void setTeam( '' ) }
			>
				{ __( 'Remove team filter', 'wporg' ) }
			</Button>
		</div>
	);
};

export default Filter;
