/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getSortedEvents } from '../calendar/utils';

/**
 * Gets the team name if present in url.
 */
function getTeamOnLoad() {
	const { location } = window;
	const matches = location.href.match( /(?<=#).+/ );
	return matches ? matches[ 0 ] : '';
}

/**
 * Add the team to the current URL, pushing to history for browser navigation support.
 */
function setTeamEffect( team = '' ) {
	if ( '' === team ) {
		window.history.pushState(
			team,
			document.title,
			window.location.pathname
		);
	} else {
		window.history.pushState( team, document.title, '#' + team );
	}
}

const StateContext = createContext();

export function EventsProvider( { children, value } ) {
	const [ team, setTeam ] = useState( getTeamOnLoad() );

	let eventsToDisplay = value;

	if ( team && team.trim().length ) {
		eventsToDisplay = value.filter(
			( e ) => e.team.toLowerCase() === team.toLowerCase()
		);
	}

	const initialState = {
		events: getSortedEvents( eventsToDisplay ),
		team,
		setTeam: ( team ) => {
			team = team.toLowerCase();
			setTeam( team );
			setTeamEffect( team );
		},
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
