/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Get the list of events in day-buckets.
 *
 * @param {Object[]} events Meeting occurrences.
 * @return {Object<string, Object[]>} Events, keyed by date (Y-m-d).
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
