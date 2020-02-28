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

	console.log( dayEvents );

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
					<h3
						key={ e.instance_id }
						className={ [
							'wporg-meeting-calendar__cellevent',
							`wporg-meeting-calendar__cellevent-${ e.team.toLowerCase() }`,
						].join( ' ' ) }
					>
						<span className="wporg-meeting-calendar__cellevent_time">
							{ format( 'g:i a: ', e.datetime ) }
						</span>
						<span className="wporg-meeting-calendar__cellevent_title">
							{ e.title }
						</span>
					</h3>
				);
			} ) }
		</td>
	);
}

export default CalendarCell;
