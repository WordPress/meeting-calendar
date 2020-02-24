/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup } from '@wordpress/components';
import { date } from '@wordpress/date';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CalendarGrid from './grid';
import { EventsProvider } from './event-context';

function Calendar( { events } ) {
	const today = new Date();
	const currentMonth = today.getMonth();
	const currentYear = today.getFullYear();
	const currentMonthYear = {
		month: currentMonth,
		year: currentYear,
	};
	const [ { month, year }, setDate ] = useState( currentMonthYear );

	return (
		<EventsProvider value={ events }>
			<div className="wporg-meeting-calendar__header">
				<div className="wporg-meeting-calendar__btn-group">
					<Button onClick={ () => void setDate( currentMonthYear ) }>
						{ __( 'Today', 'wporg' ) }
					</Button>
					<Button
						onClick={ () =>
							void setDate( { month: month - 1, year } )
						}
					>
						{ __( 'Previous', 'wporg' ) }
					</Button>
					<Button
						onClick={ () =>
							void setDate( { month: month + 1, year } )
						}
					>
						{ __( 'Next', 'wporg' ) }
					</Button>
				</div>
				<div>
					<h2 aria-live="polite" aria-atomic>
						{ date( 'F Y', new Date( year, month, 1 ) ) }
					</h2>
				</div>
				<ButtonGroup>
					<Button>{ __( 'Month', 'wporg' ) }</Button>
					<Button>{ __( 'List', 'wporg' ) }</Button>
				</ButtonGroup>
			</div>
			<CalendarGrid month={ month } year={ year } />
		</EventsProvider>
	);
}

export default Calendar;
