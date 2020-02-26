/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup } from '@wordpress/components';
import { date } from '@wordpress/date';
import { useState, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CalendarGrid from './grid';
import List from '../list';
import { useViews } from '../app/view-context';

function Calendar() {
	const today = new Date();
	const currentMonth = today.getMonth();
	const currentYear = today.getFullYear();
	const currentMonthYear = {
		month: currentMonth,
		year: currentYear,
	};
	const [ { month, year }, setDate ] = useState( currentMonthYear );
	const {
		isCalendarView,
		isListView,
		setCalendarView,
		setListView,
	} = useViews();

	return (
		<Fragment>
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
						isSecondary={ ! isCalendarView() }
						isPrimary={ isCalendarView() }
						onClick={ () => void setCalendarView() }
					>
						{ __( 'Month', 'wporg' ) }
					</Button>
					<Button
						isSecondary={ ! isListView() }
						isPrimary={ isListView() }
						onClick={ () => void setListView() }
					>
						{ __( 'List', 'wporg' ) }
					</Button>
				</ButtonGroup>
			</div>
			{ isCalendarView() && (
				<CalendarGrid month={ month } year={ year } />
			) }
			{ isListView() && <List month={ month } year={ year } /> }
		</Fragment>
	);
}

export default Calendar;
