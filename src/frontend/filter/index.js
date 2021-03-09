/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';
import { speak } from '@wordpress/a11y';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

const Filter = () => {
	const { teams, team, setTeam } = useEvents();
	if ( teams.length < 2 ) {
		return null;
	}

	const filterLabel = useRef( null );
	const dropdownId = 'wporg-meeting-calendar__filter-dropdown';
	const selected = teams.find( ( option ) => team === option.value );

	const getCalendarUrl = () => {
		if ( ! selected ) {
			return '/meetings.ics';
		}

		return `/meetings-${ selected.value }.ics`;
	};

	return (
		<div className="wporg-meeting-calendar__filter">
			<label
				className="wporg-meeting-calendar__filter-label"
				htmlFor={ dropdownId }
				ref={ filterLabel }
			>
				{ __( 'Filter by team: ', 'wporg-meeting-calendar' ) }
			</label>
			<SelectControl
				id={ dropdownId }
				className="wporg-meeting-calendar__filter-dropdown"
				value={ team }
				options={ [
					{ label: __( 'All teams', 'wporg-meeting-calendar' ), value: '' },
					...teams,
				] }
				onChange={ ( value ) => {
					setTeam( value );
					const newSelected = teams.find(
						( option ) => value === option.value
					);
					speak(
						sprintf(
							__( 'Showing meetings for %s', 'wporg-meeting-calendar' ),
							newSelected.label
						),
						'assertive'
					);
				} }
			/>
			{ '' !== team && (
				<>
					<p className="wporg-meeting-calendar__filter-applied">
						{ sprintf(
							__( 'Showing meetings for %s', 'wporg-meeting-calendar' ),
							selected.label
						) }
					</p>
					<Button
						icon="no-alt"
						isLink
						isDestructive
						onClick={ () => {
							setTeam( '' );
							speak(
								__( 'Showing all meetings.', 'wporg-meeting-calendar' ),
								'assertive'
							);
							filterLabel.current.focus();
						} }
					>
						{ __( 'Remove team filter', 'wporg-meeting-calendar' ) }
					</Button>
				</>
			) }
			<div className="wporg-meeting-calendar__filter-feed">
				<Button
					icon="calendar-alt"
					href={ getCalendarUrl() }
					isSecondary
					style={ {
						marginLeft: 'auto',
					} }
					download
				>
					{ __( 'iCal', 'wporg-meeting-calendar' ) }
					{ '' !== team && ` - ${ selected.label }` }
				</Button>
			</div>
		</div>
	);
};

export default Filter;
