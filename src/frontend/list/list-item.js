/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getTeamClass } from '../calendar/utils';

function ListItem( { date, events } ) {
	return (
		<li key={ `row-${ date }` }>
			<strong className="wporg-meeting-calendar__list-title">
				<span>{ format( 'l - F j, Y', date ) }</span>
			</strong>

			{ events.map( ( e ) => {
				return (
					<article
						className="wporg-meeting-calendar__list-event"
						key={ e.instance_id }
					>
						<div>{ format( 'g:i a ', e.datetime ) }</div>
						<div>
							<a
								className={ `wporg-meeting-calendar__list-event-team wporg-meeting-calendar__list-event-team-${ getTeamClass(
									e.team
								) }` }
								href={ `/${ e.team }` }
							>
								{ e.team }
							</a>
							<h3
								className="wporg-meeting-calendar__list-event-title"
								key={ e.id }
							>
								<a href={ e.link }>{ e.title }</a>
							</h3>
							<span className="wporg-meeting-calendar__list-event-subtitle">
								Location: { e.location }
							</span>
						</div>
					</article>
				);
			} ) }
		</li>
	);
}

export default ListItem;
