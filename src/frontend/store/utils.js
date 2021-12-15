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
		const d = new Date( event.datetime );
		const key = format( 'Y-m-d', d );
		if ( sortedEvents.hasOwnProperty( key ) ) {
			sortedEvents[ key ].push( event );
		} else {
			sortedEvents[ key ] = [ event ];
		}
	} );
	return sortedEvents;
}
