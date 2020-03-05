/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getTeamClass } from '../calendar/utils';
import { useEvents } from '../store/event-context';

function ListItem( { date, events } ) {
	const { setTeam } = useEvents();

	return (
		<li key={ `row-${ date }` }>
			<strong className="wporg-meeting-calendar__list-title">
				<span>{ format( 'l - F j, Y', date ) }</span>
			</strong>

			{ events.map( ( event ) => {
				return (
					<article
						className="wporg-meeting-calendar__list-event"
						key={ event.instance_id }
					>
						<div>{ format( 'g:i a: ', event.datetime ) }</div>
						<div>
							<a
								className={
									'wporg-meeting-calendar__list-event-team ' +
									getTeamClass( event.team )
								}
								href={ `#${ event.team.toLowerCase() }` }
								onClick={ ( clickEvent ) => {
									clickEvent.preventDefault();
									setTeam( event.team );
								} }
							>
								{ event.team }
							</a>
							<h3 className="wporg-meeting-calendar__list-event-title">
								<a href={ event.link }>{ event.title }</a>
							</h3>
							<span className="wporg-meeting-calendar__list-event-subtitle">
								Location: { event.location }
							</span>
						</div>
					</article>
				);
			} ) }
		</li>
	);
}

export default ListItem;
