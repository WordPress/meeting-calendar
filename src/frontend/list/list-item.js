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

function ListItem({ date, events }) {
	const { setTeam } = useEvents();

	return (
		<li key={`row-${date}`}>
			<h3 className="wporg-meeting-calendar__list-title">
				<span>{format('l - F j, Y', date)}</span>
			</h3>

			{events.map((event) => {
				const onTeamClick = (clickEvent) => {
					clickEvent.preventDefault();
					setTeam(event.team);
					speak(
						sprintf(
							// translators: %s is the team name
							__(
								'Showing meetings for %s',
								'wporg-meeting-calendar'
							),
							event.team
						)
					);
				};
				return (
					<article
						className={`wporg-meeting-calendar__list-event ${
							isCancelled(event.status) ? 'is-cancelled' : ''
						}`}
						key={event.instance_id}
					>
						{event.team && (
							<div className="wporg-meeting-calendar__list-event-team-wrapper">
								<a
									className={
										'wporg-meeting-calendar__list-event-team ' +
										getTeamClass(event.team)
									}
									aria-label={sprintf(
										// translators: %s is the team name
										__(
											'All %s meetings',
											'wporg-meeting-calendar'
										),
										event.team
									)}
									href={`#${event.team.toLowerCase()}`}
									onClick={onTeamClick}
								>
									{event.team}
								</a>
							</div>
						)}
						<div className="wporg-meeting-calendar__list-event-header">
							<h4 className="wporg-meeting-calendar__list-event-title">
								{!!event.link ? (
									<a href={event.link}>
										<EventTitle event={event} />
									</a>
								) : (
									<EventTitle event={event} />
								)}
							</h4>
							<div className="wporg-meeting-calendar__list-event-time">
								{format('g:i a ', event.datetime)}
								{format('(\\U\\T\\CP)', date)}
							</div>
						</div>
						<div className="wporg-meeting-calendar__list-event-details">
							<p className="wporg-meeting-calendar__list-event-copy">
								{__('Meets: ', 'wporg-meeting-calendar')}
								{getFrequencyLabel(event)}
							</p>
							<p className="wporg-meeting-calendar__list-event-copy">
								{__('Location: ', 'wporg-meeting-calendar')}
								{getSlackLink(event.location)}
							</p>
						</div>
						{!!event.wptv_url && (
							<div>
								<p className="wporg-meeting-calendar__list-event-copy">
									<a
										aria-label={__(
											'WordPress.tv URL for the meeting recording',
											'wporg-meeting-calendar'
										)}
										href={event.wptv_url}
									>
										{__(
											'View Recording',
											'wporg-meeting-calendar'
										)}
									</a>
								</p>
							</div>
						)}
					</article>
				);
			})}
		</li>
	);
}

function EventTitle({ event }) {
	return (
		<>
			<span>{event.title}</span>
			{isCancelled(event.status) && (
				<span>
					{__(' Meeting is cancelled', 'wporg-meeting-calendar')}
				</span>
			)}
		</>
	);
}

export default ListItem;
