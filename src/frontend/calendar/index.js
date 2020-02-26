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
import List from '../list';
import { EventsProvider } from './event-context';

const CALENDAR_VIEW = 'calendar_view';
const LIST_VIEW = 'list_view';

function Calendar( { events } ) {
	const today = new Date();
	const currentMonth = today.getMonth();
	const currentYear = today.getFullYear();
	const currentMonthYear = {
		month: currentMonth,
		year: currentYear,
	};
	const [ { month, year }, setDate ] = useState( currentMonthYear );
	const [ currentView, setView ] = useState( CALENDAR_VIEW );
	const isView = ( toMatch ) => currentView === toMatch;

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
					<Button
						isSecondary={ ! isView( CALENDAR_VIEW ) }
						isPrimary={ isView( CALENDAR_VIEW ) }
						onClick={ () => void setView( CALENDAR_VIEW ) }
					>
						{ __( 'Month', 'wporg' ) }
					</Button>
					<Button
						isSecondary={ ! isView( LIST_VIEW ) }
						isPrimary={ isView( LIST_VIEW ) }
						onClick={ () => void setView( LIST_VIEW ) }
					>
						{ __( 'List', 'wporg' ) }
					</Button>
				</ButtonGroup>
			</div>
			{ isView( CALENDAR_VIEW ) && (
				<CalendarGrid month={ month } year={ year } />
			) }
			{ isView( LIST_VIEW ) && <List month={ month } year={ year } /> }
		</EventsProvider>
	);
}

export default Calendar;
