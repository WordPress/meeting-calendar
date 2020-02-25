/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getRows, getSortedEvents } from '../calendar/utils';
import { useEvents } from '../calendar/event-context';
import ListItem from './listItem';

function List( { month, year, week } ) {
	const rows = getRows( year, month );
	const events = getSortedEvents( useEvents() );
	const allDays = rows.flat().filter( ( i ) => ! i.blank );

	const days = allDays.map( ( row, i ) => {
		const date = new Date( row.year, row.month, row.day );
		const key = format( 'Y-m-d', date );
		const dayEvents = events[ key ] || [];

		if ( ! dayEvents.length ) {
			return;
		}

		return (
			<ListItem
				key={ `row-${ i }` }
				key={ key }
				date={ date }
				events={ dayEvents }
			/>
		);
	} ).filter( i => !! i );

	if ( ! days.length ) {
		return <div>No Events Scheduled</div>;
	}

	return <ul className="wporg-meeting-calendar__list">{ days }</ul>;
}

export default List;
