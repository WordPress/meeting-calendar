/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { date } from '@wordpress/date';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CalendarGrid from './grid';
import { EventsProvider } from './event-context';

function Calendar( { events } ) {
	const today = new Date();
	const [ { month, year }, setDate ] = useState( {
		month: today.getMonth(),
		year: today.getFullYear(),
	} );

	return (
		<EventsProvider value={ events }>
			<div className="wporg-meeting-calendar__header">
				<Button
					onClick={ () => void setDate( { month: month - 1, year } ) }
				>
					{ __( 'Previous', 'wordcamporg' ) }
				</Button>
				<h2>{ date( 'F Y', new Date( year, month, 1 ) ) }</h2>
				<Button
					onClick={ () => void setDate( { month: month + 1, year } ) }
				>
					{ __( 'Next', 'wordcamporg' ) }
				</Button>
			</div>
			<CalendarGrid month={ month } year={ year } />
		</EventsProvider>
	);
}

export default Calendar;
