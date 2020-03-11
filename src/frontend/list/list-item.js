/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { format } from '@wordpress/date';
import { speak } from '@wordpress/a11y';

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
				const onTeamClick = ( clickEvent ) => {
					clickEvent.preventDefault();
					setTeam( event.team );
					speak(
						sprintf(
							__( 'Showing meetings for %s', 'wporg' ),
							event.team
						)
					);
				};
				return (
					<article
						className="wporg-meeting-calendar__list-event"
						key={ event.instance_id }
						itemScope
						itemType="http://schema.org/Event"
					>
						<div itemProp="startDate" content={ event.datetime }>
							{ format( 'g:i a ', event.datetime ) }
						</div>
						<div className="wporg-meeting-calendar__list-event-details">
							{ event.team && (
								<a
									className={
										'wporg-meeting-calendar__list-event-team ' +
										getTeamClass( event.team )
									}
									aria-label={ sprintf(
										__( 'All %s meetings', 'wporg' ),
										event.team
									) }
									href={ `#${ event.team.toLowerCase() }` }
									onClick={ onTeamClick }
									itemType="http://schema.org/Organization"
									itemScope
								>
									<span itemProp="name">{ event.team }</span>
								</a>
							) }
							<div>
								<h3
									className="wporg-meeting-calendar__list-event-title"
									itemProp="name"
								>
									<a itemProp="url" href={ event.link }>
										{ event.title }
									</a>
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
						</div>
					</article>
				);
			} ) }
		</li>
	);
}

export default ListItem;
