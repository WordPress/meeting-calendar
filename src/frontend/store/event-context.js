/**
 * External Dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';

const StateContext = createContext();

/**
 * Gets the team name if present in url.
 */
function getTeamOnLoad() {
	const { location } = window;
	const matches = location.href.match( /(?<=#).+/ );
	return matches ? matches[ 0 ] : '';
}

export function EventsProvider( { children, value } ) {
	const [ team, setTeam ] = useState( getTeamOnLoad() );

	let eventsToDisplay = value;

	if ( team && team.trim().length ) {
		eventsToDisplay = value.filter(
			( e ) => e.team.toLowerCase() === team.toLowerCase()
		);
	}

	const initialState = {
		events: eventsToDisplay,
		team,
		setTeam,
		clearTeam: () => setTeam( '' ),
	};

	return (
		<StateContext.Provider value={ initialState }>
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
