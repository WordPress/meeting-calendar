
/**
 * WordPress dependencies
 */
import { createContext, useContext, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { format } from '@wordpress/date';

const EventDataContext = createContext( [] );

export function useEventData() {
	const context = useContext( EventDataContext );
	if ( context === undefined ) {
		throw new Error( 'useEventData must be used within a EventDataProvider' );
	}
	return context;
}

export function EventDataProvider( { children, data } ) {
	const [ eventData, setEventData ] = useState( [] );
	// we bail out early if data is hydrated from the server.
	if ( data ) {
		return (
			<EventDataContext.Provider value={ data }>
				{ children }
			</EventDataContext.Provider>
		);
	}
	useEffect(() => {
		const currentDate = new Date();
		const fromDate = new Date( currentDate.getFullYear(), currentDate.getMonth(), 1 );

		apiFetch( {
			path: `/wp/v2/meetings/from/${ format( 'Y-m-d', fromDate ) }?per_page=12`,
		} ).then( events => {
				setEventData( events )
		} );
	}, [ setEventData ])
	return (
		<EventDataContext.Provider value={ eventData }>
			{ children }
		</EventDataContext.Provider>
	);
}
