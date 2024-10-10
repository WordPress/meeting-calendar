/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

const Feed = () => {
	const { teams, team } = useEvents();
	const selected = teams.find( ( option ) => team === option.value );

	const getCalendarUrl = () => {
		const baseUrl = window.location.origin;
		if ( ! selected ) {
			return `${ baseUrl }/meetings.ics`;
		}
		return `${ baseUrl }/meetings-${ selected.value }.ics`;
	};

	const getGoogleCalendarUrl = () => {
		const calendarUrl = getCalendarUrl().replace( 'https://', 'webcal://' );
		return addQueryArgs( 'https://www.google.com/calendar/render', {
			cid: calendarUrl,
		} );
	};

	return (
		<div className="wporg-meeting-calendar__feed">
			<p>
				{ __(
					'Events are shown in your local time zone',
					'wporg-meeting-calendar'
				) }
			</p>
			<p>
				{ __(
					'Subscribe to this calendar:',
					'wporg-meeting-calendar'
				) }{ ' ' }
				<a href={ getGoogleCalendarUrl() }>Google Calendar ↗</a> ·{ ' ' }
				<a href={ getCalendarUrl() }>ICS</a>
			</p>
		</div>
	);
};

export default Feed;
