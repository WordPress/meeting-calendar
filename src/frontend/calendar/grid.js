/**
 * WordPress dependencies
 */
import { Fragment, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CalendarCell from './cell';
import CalendarHeader from './header';
import EventModal from './modal';
import { getRows } from './utils';
import { useEvents } from '../store/event-context';

function CalendarGrid( { month, year } ) {
	const [ activeEvent, setActiveEvent ] = useState( null );
	const rows = getRows( year, month );
	const { events } = useEvents();

	return (
		<Fragment>
			<table>
				<CalendarHeader />
				<tbody>
					{ rows.map( ( row, i ) => (
						<tr key={ `row-${ i }` }>
							{ row.map( ( day, index ) => (
								<CalendarCell
									key={ `cell-${ i }-${ index }` }
									{ ...day }
									events={ events }
									onEventClick={ setActiveEvent }
								/>
							) ) }
						</tr>
					) ) }
				</tbody>
			</table>
			{ activeEvent && (
				<EventModal
					event={ activeEvent }
					onRequestClose={ () => void setActiveEvent( null ) }
				/>
			) }
		</Fragment>
	);
}

export default CalendarGrid;
