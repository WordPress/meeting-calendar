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
	isCancelled,
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
						className={ `wporg-meeting-calendar__list-event ${
							isCancelled( event.status ) ? 'is-cancelled' : ''
						}` }
						key={ event.instance_id }
					>
						<div>
							{ format( 'g:i a ', event.datetime ) }
							<div className="wporg-meeting-calendar__list-event-tz">
								{ format( '(\\U\\T\\CP)', date ) }
							</div>
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
								>
									{ event.team }
								</a>
							) }

							<div>
								<h3 className="wporg-meeting-calendar__list-event-title">
									<a href={ event.link }>
										<span>{ event.title }</span>
										{ isCancelled( event.status ) && (
											<span>
												{ __(
													' Meeting is cancelled',
													'wporg'
												) }
											</span>
										) }
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
