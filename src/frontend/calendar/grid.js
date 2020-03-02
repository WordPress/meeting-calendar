/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';
import { Fragment, useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CalendarCell from './cell';
import CalendarHeader from './header';
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
				<Modal
					title={ activeEvent.title }
					overlayClassName="wporg-meeting-calendar__modal-overlay"
					onRequestClose={ () => void setActiveEvent( null ) }
				>
					Time: { format( 'g:i a', activeEvent.datetime ) }
				</Modal>
			) }
		</Fragment>
	);
}

export default CalendarGrid;
