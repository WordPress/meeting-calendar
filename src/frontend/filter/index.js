/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

const Filter = () => {
	const { teams, team, setTeam } = useEvents();
	const ddId = 'dropDownID';

	return (
		<div className="wporg-meeting-calendar__filter">
			<label
				className="wporg-meeting-calendar__filter_label"
				htmlFor={ ddId }
			>
				{ __( 'Filter by team: ', 'wporg' ) }
			</label>
			<SelectControl
				id={ ddId }
				value={ team }
				className="wporg-meeting-calendar__filter_dropdown"
				options={ [
					{ label: __( 'All teams', 'wporg' ), value: '' },
					...teams,
				] }
				onChange={ ( value ) => {
					setTeam( value );
				} }
			/>
			{ '' !== team && (
				<>
					<p className="wporg-meeting-calendar__filter_applied">
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
				</>
			) }
		</div>
	);
};

export default Filter;
