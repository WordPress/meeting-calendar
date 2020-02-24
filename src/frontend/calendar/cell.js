/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import MoreEvents from './more-events';

function CalendarCell( {
	blank = false,
	year,
	month,
	day,
	events,
	onEventClick,
} ) {
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
			{ dayEvents.slice( 0, maxEventsToDisplay ).map( ( event ) => {
				return (
					<Button
						key={ event.instance_id }
						isLink
						onClick={ () => void onEventClick( event ) }
					>
						{ format( 'g:i a: ', event.datetime ) }
						{ event.title }
					</Button>
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
