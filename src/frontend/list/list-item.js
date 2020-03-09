/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import {
	getTeamClass,
	getSlackLink,
	getFrequencyLabel,
} from '../calendar/utils';
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
							{ 'cancelled' === event.status && (
								<p>
									<em>
										{ __(
											'This meeting has been cancelled',
											'wporg'
										) }
									</em>
								</p>
							) }
							<h3 className="wporg-meeting-calendar__list-event-title">
								<a href={ event.link }>{ event.title }</a>
							</h3>
							<p className="wporg-meeting-calendar__list-event-copy">
								{ __( 'Meets: ', 'wporg' ) }
								{ getFrequencyLabel( event ) }
							</p>
							<p className="wporg-meeting-calendar__list-event-copy">
								{ __( 'Location: ', 'wporg' ) }
								{ getSlackLink( event.location ) }
							</p>
						</div>
					</article>
				);
			} ) }
		</li>
	);
}

export default ListItem;
