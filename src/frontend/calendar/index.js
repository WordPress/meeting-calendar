/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { date } from '@wordpress/date';
import { Fragment, useState } from '@wordpress/element';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import CalendarGrid from './grid';
import List from '../list';
import Filter from '../filter';
import { useViews } from '../store/view-context';

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
		shouldForceListView,
	} = useViews();

	if ( shouldForceListView && ! isListView() ) {
		setListView();
	}

	return (
		<Fragment>
			<div className="wporg-meeting-calendar__header">
				<nav
					className="wporg-meeting-calendar__btn-group"
					aria-label={ __( 'Month navigation', 'wporg' ) }
				>
					<Button
						isSecondary
						onClick={ () =>
							void setDate( { month: month - 1, year } )
						}
						disabled={ month === currentMonth }
					>
						{ __( 'Previous', 'wporg' ) }
					</Button>
					<Button
						isSecondary
						onClick={ () =>
							void setDate( { month: month + 1, year } )
						}
						disabled={ month > currentMonth }
					>
						{ __( 'Next', 'wporg' ) }
					</Button>
				</nav>
				<div>
					<h2 aria-live="polite" aria-atomic>
						{ date( 'F Y', new Date( year, month, 1 ) ) }
					</h2>
				</div>
				<nav
					className="components-button-group"
					aria-label={ __( 'View options', 'wporg' ) }
				>
					<Button
						isSecondary={ ! isCalendarView() }
						isPrimary={ isCalendarView() }
						onClick={ () => {
							if ( ! isCalendarView() ) {
								speak(
									__( 'Switched to calendar view', 'wporg' )
								);
							}
							setCalendarView();
						} }
						disabled={ shouldForceListView }
					>
						{ __( 'Month', 'wporg' ) }
					</Button>
					<Button
						isSecondary={ ! isListView() }
						isPrimary={ isListView() }
						onClick={ () => {
							if ( ! isListView() ) {
								speak( __( 'Switched to list view', 'wporg' ) );
							}
							setListView();
						} }
						disabled={ shouldForceListView }
					>
						{ __( 'List', 'wporg' ) }
					</Button>
				</nav>
			</div>
			<Filter />
			{ isCalendarView() && (
				<CalendarGrid month={ month } year={ year } />
			) }
			{ isListView() && <List month={ month } year={ year } /> }
		</Fragment>
	);
}

export default Calendar;
