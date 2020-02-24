/**
 * External Dependencies
 */
import { createContext, useContext } from '@wordpress/element';

const StateContext = createContext();

export function EventsProvider( { children, value } ) {
	return (
		<StateContext.Provider value={ value }>
			{ children }
		</StateContext.Provider>
	);
}

export function useEvents() {
	const context = useContext( StateContext );
	if ( context === undefined ) {
		throw new Error( 'useEvents must be used within a Provider' );
	}
	return context;
}
