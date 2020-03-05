/**
 * WordPress dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getTeamClass } from './utils';

function CalendarCell( {
	blank = false,
	year,
	month,
	day,
	events,
	onEventClick,
} ) {
	const MAX_EVENTS = 3;
	if ( blank ) {
		return <td aria-hidden />;
	}

	const date = new Date( year, month, day );
	const key = format( 'Y-m-d', date );
	const dayEvents = events[ key ] || [];
	const restOfEvents = dayEvents.slice( MAX_EVENTS );

	return (
		<td className="wporg-meeting-calendar__cell">
			<strong>
				<span className="screen-reader-text">
					{ format( 'F j', date ) }
				</span>
				<span aria-hidden>{ day }</span>
			</strong>
			{ dayEvents.slice( 0, MAX_EVENTS ).map( ( event ) => {
				return (
					<Button
						key={ event.instance_id }
						isLink
						onClick={ () => void onEventClick( event ) }
						className={
							'wporg-meeting-calendar__cell-event ' +
							getTeamClass( event.team )
						}
					>
						<div className="wporg-meeting-calendar__cell-event-time">
							{ format( 'g:i a: ', event.datetime ) }
						</div>
						<div className="wporg-meeting-calendar__cell-event-title">
							{ event.title }
						</div>
					</Button>
				);
			} ) }

			{ !! restOfEvents.length && (
				<Dropdown
					className="wporg-meeting-calendar__dropdown"
					position="bottom center"
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							isLink
							onClick={ onToggle }
							aria-expanded={ isOpen }
						>
							{ // translators: %d: Count of hidden events, ie: 4.
							sprintf(
								_n(
									'%d more',
									'%d more',
									restOfEvents.length,
									'wporg'
								),
								restOfEvents.length
							) }
						</Button>
					) }
					renderContent={ () => (
						<MenuGroup>
							{ restOfEvents.map( ( event ) => {
								return (
									<MenuItem
										key={ event.instance_id }
										isLink
										onClick={ () =>
											void onEventClick( event )
										}
										className={
											'wporg-meeting-calendar__cell-event ' +
											getTeamClass( event.team )
										}
									>
										{ format( 'g:i a: ', event.datetime ) }
										{ event.title }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					) }
				/>
			) }
		</td>
	);
}

export default CalendarCell;
