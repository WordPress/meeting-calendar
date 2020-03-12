/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { format, gmdate } from '@wordpress/date';
import { Fragment, useState } from '@wordpress/element';
import { Modal, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CalendarCell from './cell';
import CalendarHeader from './header';
import { getFrequencyLabel, getRows, getSlackLink, isCancelled } from './utils';
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
					{ ! isCancelled( activeEvent.status ) ? (
						<p>
							<abbr title={ gmdate( 'c', activeEvent.datetime ) }>
								{ format(
									'l, F j, Y, g:i a (\\U\\T\\CP)',
									activeEvent.datetime
								) }
							</abbr>
						</p>
					) : (
						<Notice
							className="wporg-meeting-calendar__modal-notice"
							status="warning"
							isDismissible={ false }
						>
							{ __( 'This meeting has been cancelled', 'wporg' ) }
						</Notice>
					) }

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
