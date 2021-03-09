/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { uniqBy } from 'lodash';

/**
 * Internal dependencies
 */
import { getSortedEvents } from './utils';
import { useEventData } from './data-context';

/**
 * Gets the team name if present in url.
 */
function getTeamOnLoad() {
	const { location } = window;
	const matches = location.href.match( /#(.+)/ );
	return matches ? matches[ 1 ] : '';
}

/**
 * Add the team to the current URL, pushing to history for browser navigation support.
 *
 * @param {string} team
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

export function EventsProvider( { children } ) {
	const events = useEventData();

	const [ team, setTeam ] = useState( getTeamOnLoad() );

	let eventsToDisplay = events;

	if ( team && team.trim().length ) {
		eventsToDisplay = events.filter(
			( e ) => e.team.toLowerCase() === team.toLowerCase()
		);
	}

	// Get a list of all teams available.
	const teams = uniqBy(
		events
			.map( ( e ) => ( {
				label: e.team,
				value: e.team.toLowerCase(),
			} ) )
			.filter( ( { value } ) => !! value ),
		'value'
	);

	const initialState = {
		events: getSortedEvents( eventsToDisplay ),
		team,
		teams,
		setTeam: ( newTeam ) => {
			newTeam = newTeam.toLowerCase();
			setTeam( newTeam );
			setTeamEffect( newTeam );
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
