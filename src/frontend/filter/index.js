/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

const Filter = () => {
	const { teams, team, setTeam } = useEvents();
	const dropdownId = 'wporg-meeting-calendar__filter-dropdown';
	const selected = teams.find( ( option ) => team === option.value );

	return (
		<div className="wporg-meeting-calendar__filter">
			<label
				className="wporg-meeting-calendar__filter-label"
				htmlFor={ dropdownId }
			>
				{ __( 'Filter by team: ', 'wporg' ) }
			</label>
			<SelectControl
				id={ dropdownId }
				value={ team }
				className="wporg-meeting-calendar__filter-dropdown"
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
					<p className="wporg-meeting-calendar__filter-applied">
						{ sprintf(
							__( 'Showing meetings for %s', 'wporg' ),
							selected.label
						) }
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
