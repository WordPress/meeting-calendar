/**
 * External dependencies
 */
import { flatten } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { format } from '@wordpress/date';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { collapse, expand } from '../icons';
import { getRows } from '../calendar/utils';
import { useEvents } from '../store/event-context';
import ListItem from './list-item';

function List( { month, year } ) {
	const [ showPast, setShowPast ] = useState( false );
	const rows = getRows( year, month );
	const { events } = useEvents();
	const allDays = flatten( rows ).filter( ( i ) => ! i.blank );
	const cutoffDate = new Date();
	// Reset the time to 11:59pm the night before, so that we show all meetings today.
	cutoffDate.setHours( -1, 59, 0, 0 );
	// If today is later than the 1st of the month, there will be past meetings.
	const hasPast = new Date( year, month ) < cutoffDate;

	const days = allDays
		.map( ( row, i ) => {
			const date = new Date( row.year, row.month, row.day );
			const key = format( 'Y-m-d', date );
			const dayEvents = events[ key ] || [];

			// If we want to hide past events, skip over things before yesterday.
			if ( ! showPast && ! ( date > cutoffDate ) ) {
				return null;
			}

			if ( ! dayEvents.length ) {
				return null;
			}

			return (
				<ListItem
					key={ `row-${ i }` }
					date={ date }
					events={ dayEvents }
				/>
			);
		} )
		.filter( ( i ) => !! i );

	return (
		<>
			{ hasPast &&
				( ! showPast ? (
					<p className="wporg-meeting-calendar__list-expand">
						<Button
							icon={ expand }
							onClick={ () => setShowPast( true ) }
						>
							{ __( 'Show past meetings', 'wporg' ) }
						</Button>
					</p>
				) : (
					<p className="wporg-meeting-calendar__list-expand">
						<Button
							icon={ collapse }
							onClick={ () => setShowPast( false ) }
						>
							{ __( 'Hide past meetings', 'wporg' ) }
						</Button>
					</p>
				) ) }

			{ ! days.length ? (
				<p className="wporg-meeting-calendar__list-empty">
					{ __( 'No Events Scheduled', 'wporg' ) }
				</p>
			) : (
				<ul className="wporg-meeting-calendar__list">{ days }</ul>
			) }
		</>
	);
}

export default List;
