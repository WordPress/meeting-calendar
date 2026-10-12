/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';

/**
 * Render the time zone note and the subscription links for the selected team.
 *
 * @return {Element} Feed element.
 */
const Feed = () => {
	const { teams, team } = useEvents();
	const selected = teams.find( ( option ) => team === option.value );

	/**
	 * Get the ICS feed URL for the selected team, or all meetings.
	 *
	 * @return {string} Feed URL.
	 */
	const getCalendarUrl = () => {
		const baseUrl = window.location.origin;
		if ( ! selected ) {
			return `${ baseUrl }/meetings.ics`;
		}
		const urlSafeTeam = encodeURIComponent( selected.value );
		return `${ baseUrl }/meetings-${ urlSafeTeam }.ics`;
	};

	/**
	 * Get the Google Calendar URL to subscribe to the ICS feed.
	 *
	 * @return {string} Google Calendar URL.
	 */
	const getGoogleCalendarUrl = () => {
		const calendarUrl = getCalendarUrl().replace( 'https://', 'webcal://' );
		return addQueryArgs( 'https://www.google.com/calendar/render', {
			cid: calendarUrl,
		} );
	};

	/**
	 * Get the RSS feed URL for meetings.
	 *
	 * @return {string} RSS feed URL.
	 */
	const getRSSUrl = () => {
		const baseUrl = window.location.origin;

		return `${ baseUrl }/feed/?post_type=meeting`;
	};

	return (
		<div className="wporg-meeting-calendar__feed">
			<p>
				{ __(
					'Events are shown in your local time zone.',
					'wporg-meeting-calendar'
				) }
			</p>
			<p>
				{ __(
					'Subscribe to this calendar:',
					'wporg-meeting-calendar'
				) }{ ' ' }
				<a href={ getGoogleCalendarUrl() }>
					{ __( 'Google Calendar ↗', 'wporg-meeting-calendar' ) }
				</a>{ ' ' }
				&middot;{ ' ' }
				<a href={ getCalendarUrl() }>
					{ __( 'ICS', 'wporg-meeting-calendar' ) }
				</a>{ ' ' }
				&middot;{ ' ' }
				<a href={ getRSSUrl() }>
					{ __( 'RSS', 'wporg-meeting-calendar' ) }
				</a>
			</p>
		</div>
	);
};

export default Feed;
