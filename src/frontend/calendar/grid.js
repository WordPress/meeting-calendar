/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { format, gmdate } from '@wordpress/date';
import { Fragment, useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CalendarCell from './cell';
import CalendarHeader from './header';
import { getFrequencyLabel, getRows, getSlackLink } from './utils';
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
					className="wporg-meeting-calendar__modal"
					overlayClassName="wporg-meeting-calendar__modal-overlay"
					onRequestClose={ () => void setActiveEvent( null ) }
				>
					<p>
						{ 'cancelled' !== activeEvent.status ? (
							<abbr title={ gmdate( 'c', activeEvent.datetime ) }>
								{ format(
									'l, F j, Y, g:i a',
									activeEvent.datetime
								) }
							</abbr>
						) : (
							__( 'This meeting has been cancelled', 'wporg' )
						) }
					</p>
					{ !! activeEvent.location && (
						<p>
							Location: { getSlackLink( activeEvent.location ) }
						</p>
					) }
					<p>Meets: { getFrequencyLabel( activeEvent ) }</p>
					{ !! activeEvent.link && (
						<p>
							<a href={ activeEvent.link }>
								{ activeEvent.title }
							</a>
						</p>
					) }
				</Modal>
			) }
		</Fragment>
	);
}

export default CalendarGrid;
