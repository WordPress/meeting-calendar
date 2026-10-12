/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { uniqBy } from 'lodash';

/**
 * Internal dependencies
 */
import { getSortedEvents } from './utils';

/**
 * Gets the team name if present in url.
 *
 * @return {string} Team name, or an empty string.
 */
function getTeamOnLoad() {
	const { location } = window;
	const matches = location.href.match( /#(.+)/ );
	if ( ! matches ) {
		return '';
	}
	const candidate = decodeURI( matches[ 1 ] );
	// Only allow alphanumeric characters, spaces, hyphens, and underscores.
	return /^[\w\s-]+$/.test( candidate ) ? candidate : '';
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

/**
 * Provide the events, filtered to the selected team, and the list of teams.
 *
 * @param {Object}                    props          Component props.
 * @param {import('react').ReactNode} props.children Child elements.
 * @param {Object[]}                  props.value    Meeting occurrences.
 * @return {Element} Context provider element.
 */
export function EventsProvider( { children, value } ) {
	const [ selectedTeam, setTeam ] = useState( getTeamOnLoad() );

	let eventsToDisplay = value;

	// Get a list of all teams available.
	const teams = uniqBy(
		value
			.map( ( e ) => ( {
				label: e.team,
				value: e.team.toLowerCase(),
			} ) )
			.filter( ( team ) => !! team.value ),
		'value'
	);

	// Ignore a team from the URL that has no meetings.
	const team = teams.some(
		( t ) => t.value.toLowerCase() === selectedTeam.toLowerCase()
	)
		? selectedTeam
		: '';

	// Filter the initial events list.
	if ( team && team.trim().length ) {
		eventsToDisplay = value.filter(
			( e ) => e.team.toLowerCase() === team.toLowerCase()
		);
	}

	const initialState = {
		events: getSortedEvents( eventsToDisplay ),
		team,
		teams,
		/**
		 * Select a team and add it to the URL.
		 *
		 * @param {string} newTeam Team name, or an empty string for all teams.
		 */
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

/**
 * Get the events state.
 *
 * @return {Object} Events, teams, the selected team, and its setter.
 */
export function useEvents() {
	const context = useContext( StateContext );
	if ( context === undefined ) {
		throw new Error( 'useEvents must be used within a Provider' );
	}
	return context;
}
