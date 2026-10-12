/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { format } from '@wordpress/date';

// Default value for days not in this month.
const emptyDate = {
	blank: true,
};

/**
 * Get days in the given month in a 2-dimensional array of [week][day].
 *
 * @param {number} year
 * @param {number} month
 */
export function getRows( year, month ) {
	const daysInWeek = 7;
	const firstOffset = new Date( year, month, 1 ).getDay(); // Get day of the week.
	const monthLength = new Date( year, month + 1, 0 ).getDate(); // 0 gets the last day of the next month.
	const days = [];

	for ( let i = 0; i < firstOffset; i++ ) {
		days.push( emptyDate );
	}
	for ( let i = 1; i <= monthLength; i++ ) {
		days.push( {
			month,
			year,
			day: i,
		} );
	}

	const rows = [];
	for ( let i = 0; i < Math.ceil( days.length / daysInWeek ); i++ ) {
		const start = i * daysInWeek;
		let row = days.slice( start, start + daysInWeek );
		if ( row.length !== daysInWeek ) {
			row = [
				...row,
				...Array( daysInWeek - row.length ).fill( emptyDate ),
			];
		}
		rows.push( row );
	}

	return rows;
}

/**
 * Get human-friendly reccurance string.
 *
 * @param {Object} event
 */
export function getFrequencyLabel( event ) {
	const occurrences = {
		1: __( '1st', 'wporg-meeting-calendar' ),
		2: __( '2nd', 'wporg-meeting-calendar' ),
		3: __( '3rd', 'wporg-meeting-calendar' ),
		4: __( '4th', 'wporg-meeting-calendar' ),
	};
	const dayOfWeek = format( 'l', event.datetime );

	switch ( event.recurring ) {
		case 'weekly':
			return sprintf(
				// translators: %s: Day of the week, e.g. Monday.
				__( 'Every week on %s', 'wporg-meeting-calendar' ),
				dayOfWeek
			);

		case 'biweekly':
			return sprintf(
				// translators: %s: Day of the week, e.g. Monday.
				__( 'Every other week on %s', 'wporg-meeting-calendar' ),
				dayOfWeek
			);

		case 'monthly':
			return __( 'Every month', 'wporg-meeting-calendar' );

		case 'occurrence':
			if ( event.occurrence.length ) {
				return sprintf(
					// translators: 1: Week(s) of the month, e.g. "1st, 3rd". 2: Day of the week, e.g. Monday.
					__(
						'Every month on the %1$s %2$s',
						'wporg-meeting-calendar'
					),
					event.occurrence
						.map( ( o ) => occurrences[ o ] )
						.join( ', ' ),
					dayOfWeek
				);
			}
			return '';

		default:
			return __( 'Does not repeat', 'wporg-meeting-calendar' );
	}
}

/**
 * Get link to the slack channel.
 *
 * @param {string} location
 */
export function getSlackLink( location ) {
	if ( location[ 0 ] === '#' ) {
		location = location.slice( 1 );
	}

	return (
		<a
			href={ `https://wordpress.slack.com/app_redirect?channel=${ location }` }
		>
			#{ location }
		</a>
	);
}

/**
 * Get a team's slug, as used in its classname.
 *
 * @param {string} team
 * @return {string} Slug.
 */
function getTeamSlug( team ) {
	return team
		.trim()
		.replace( /[\s.]+/g, '-' )
		.toLowerCase();
}

/**
 * Get the classnames for a team: its own, for the hardcoded team colors, and the fallback color.
 *
 * @param {string} team
 * @return {string} Classnames.
 */
export function getTeamClass( team ) {
	return (
		'wporg-meeting-calendar__team-color wporg-meeting-calendar__team-' +
		getTeamSlug( team )
	);
}

/**
 * Get the inline style for a team's fallback color, a hue derived from its name.
 *
 * Teams with a hardcoded color in style.scss override it.
 *
 * @param {string} team
 * @return {Object} Style object.
 */
export function getTeamStyle( team ) {
	// 32-bit FNV-1a, which spreads similar names further apart than a simple rolling hash.
	let hash = 0x811c9dc5;
	for ( const char of getTeamSlug( team ) ) {
		// eslint-disable-next-line no-bitwise -- FNV-1a is defined in terms of XOR and unsigned 32-bit math.
		hash = Math.imul( hash ^ char.charCodeAt( 0 ), 0x01000193 ) >>> 0;
	}

	return { '--wporg-meeting-calendar-team-hue': hash % 360 };
}

/**
 * Checks whether a date is today
 *
 * @param {Object} date
 */
export function isToday( date ) {
	const today = new Date();
	return (
		date.getDate() === today.getDate() &&
		date.getMonth() === today.getMonth() &&
		date.getFullYear() === today.getFullYear()
	);
}

/**
 * Returns whether the event is cancelled
 *
 * @param {string} status Status of the event Ie: active, cancelled
 */
export function isCancelled( status ) {
	return 'cancelled' === status;
}

/**
 * Checks whether a date is upcoming
 *
 * @param {string} eventDatetime The date and time of the event in UTC
 */
export function isUpcoming( eventDatetime ) {
	return new Date( eventDatetime ) > new Date();
}
