/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Get the list of events in day-buckets.
 *
 * @param {Array} events
 */
export function getSortedEvents( events ) {
	const sortedEvents = {};
	events.forEach( ( event ) => {
		const key = format( 'Y-m-d', event.date );
		if ( sortedEvents.hasOwnProperty( key ) ) {
			sortedEvents[ key ].push( event );
		} else {
			sortedEvents[ key ] = [ event ];
		}
	} );
	return sortedEvents;
}
