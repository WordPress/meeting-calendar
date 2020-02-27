/**
 * Internal dependencies
 */
import CalendarCell from './cell';
import CalendarHeader from './header';
import { getRows, getSortedEvents } from './utils';
import { useEvents } from '../store/event-context';

function CalendarGrid( { month, year } ) {
	const rows = getRows( year, month );
	const { events: unsortedEvents } = useEvents();
	const events = getSortedEvents( unsortedEvents );

	return (
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
							/>
						) ) }
					</tr>
				) ) }
			</tbody>
		</table>
	);
}

export default CalendarGrid;
