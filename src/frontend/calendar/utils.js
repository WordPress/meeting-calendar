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
		1: __( '1st', 'wporg' ),
		2: __( '2nd', 'wporg' ),
		3: __( '3rd', 'wporg' ),
		4: __( '4th', 'wporg' ),
	};
	const dayOfWeek = format( 'l', event.datetime );

	switch ( event.recurring ) {
		case 'weekly':
			return sprintf( __( 'Every week on %s', 'wporg' ), dayOfWeek );

		case 'biweekly':
			return sprintf(
				__( 'Every other week on %s', 'wporg' ),
				dayOfWeek
			);

		case 'monthly':
			return __( 'Every month', 'wporg' );

		case 'occurrence':
			if ( event.occurrence.length ) {
				return sprintf(
					__( 'Every month on the %s %s', 'wporg' ),
					event.occurrence
						.map( ( o ) => occurrences[ o ] )
						.join( ', ' ),
					dayOfWeek
				);
			}
			return '';

		default:
			return __( 'Does not repeat', 'wporg' );
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
