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
import Feed from '../feed';
import { useViews } from '../store/view-context';
import { list as ListIcon, calendar as CalendarIcon, arrow as ArrowIcon } from '../icons';

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
					aria-label={ __( 'Month navigation', 'wporg-meeting-calendar' ) }
				>
					<Button
						variant='secondary'
						onClick={ () =>
							void setDate( { month: month - 1, year } )
						}
						disabled={ month === currentMonth }
						aria-label={ __( 'Previous', 'wporg-meeting-calendar' ) }
					>
						<ArrowIcon fill={ month === currentMonth ? '#1E1E1E' : '#3858E9' } aria-hidden="true" focusable="false" />
					</Button>
					<Button
						variant='secondary'
						onClick={ () =>
							void setDate( { month: month + 1, year } )
						}
						disabled={ month > currentMonth }
						aria-label={ __( 'Next', 'wporg-meeting-calendar' ) }
					>
						<ArrowIcon fill={ month > currentMonth ? '#1E1E1E' : '#3858E9' } aria-hidden="true" focusable="false" />
					</Button>
				</nav>
				<div>
					<h2 aria-live="polite" aria-atomic>
						{ date( 'F Y', new Date( year, month, 2 ) ) }
					</h2>
				</div>
				<nav
					className="components-button-group"
					aria-label={ __( 'View options', 'wporg-meeting-calendar' ) }
				>
					<Button
						variant={ isListView() ? 'primary' : 'secondary' }
						onClick={ () => {
							if ( ! isListView() ) {
								speak( __( 'Switched to list view', 'wporg-meeting-calendar' ) );
							}
							setListView();
						} }
						disabled={ shouldForceListView }
						aria-label={ __( 'List view', 'wporg-meeting-calendar' ) }
					>
						<ListIcon 
							fill={ isListView() ? 'white' : '#1E1E1E' } 
							aria-hidden="true"
							focusable="false"
						/>
					</Button>
					<Button
						variant={ isCalendarView() ? 'primary' : 'secondary' }
						onClick={ () => {
							if ( ! isCalendarView() ) {
								speak(
									__( 'Switched to calendar view', 'wporg-meeting-calendar' )
								);
							}
							setCalendarView();
						} }
						disabled={ shouldForceListView }
						aria-label={ __( 'Calendar view', 'wporg-meeting-calendar' ) }
					>
						<CalendarIcon 
							fill={ isCalendarView() ? 'white' : '#1E1E1E' } 
							aria-hidden="true"
							focusable="false"
						/>
					</Button>
				</nav>
			</div>
			<Filter />
			{ isCalendarView() && (
				<CalendarGrid month={ month } year={ year } />
			) }
			{ isListView() && <List month={ month } year={ year } /> }
			<Feed />
		</Fragment>
	);
}

export default Calendar;
