/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import MoreEvents from './more-events';

function CalendarCell( { blank = false, year, month, day, events } ) {
	if ( blank ) {
		return <td aria-hidden />;
	}

	const date = new Date( year, month, day );
	const key = format( 'Y-m-d', date );
	const dayEvents = events[ key ] || [];
	const maxEventsToDisplay = 3;

	return (
		<td className="wporg-meeting-calendar__cell">
			<strong>
				<span className="screen-reader-text">
					{ format( 'F j', date ) }
				</span>
				<span aria-hidden>{ day }</span>
			</strong>
			{ dayEvents.map( ( e ) => {
				return (
					<h3 key={ e.instance_id }>
						{ format( 'g:i a: ', e.datetime ) }
						{ e.title }
					</h3>
				);
			} ) }

			{ dayEvents.length > maxEventsToDisplay && (
				<MoreEvents
					events={ dayEvents.slice( maxEventsToDisplay ) }
					// @todo Add an onClick that displays the event
				/>
			) }
		</td>
	);
}

export default CalendarCell;
