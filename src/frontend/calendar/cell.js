/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

function CalendarCell( { blank = false, year, month, day, events } ) {
	if ( blank ) {
		return <td aria-hidden />;
	}

	const date = new Date( year, month, day );
	const key = format( 'Y-m-d', date );
	const dayEvents = events[ key ] || [];

	return (
		<td className="wporg-meeting-calendar__cell">
			<strong>
				<span className="screen-reader-text">
					{ format( 'F j', date ) }
				</span>
				<span aria-hidden>{ day }</span>
			</strong>
			{ dayEvents.map( ( e ) => {
				// Get the event date + time from the event using RFC3339 for `format`.
				// @todo Get the recurring event time, not just first instance.
				const eventDate = e.meta.start_date + 'T' + e.meta.time + 'Z';
				return (
					<h3 key={ e.id }>
						{ format( 'g:i a: ', eventDate ) }
						{ e.title }
					</h3>
				);
			} ) }
		</td>
	);
}

export default CalendarCell;
