/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRows } from '../calendar/utils';
import { useEvents } from '../store/event-context';
import ListItem from './list-item';

function List( { month, year } ) {
	const rows = getRows( year, month );
	const { events } = useEvents();
	const allDays = rows.flat().filter( ( i ) => ! i.blank );

	const days = allDays
		.map( ( row, i ) => {
			const date = new Date( row.year, row.month, row.day );
			const key = format( 'Y-m-d', date );
			const dayEvents = events[ key ] || [];

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

	if ( ! days.length ) {
		return <div>{ __( 'No Events Scheduled', 'wporg' ) }</div>;
	}

	return <ul className="wporg-meeting-calendar__list">{ days }</ul>;
}

export default List;
