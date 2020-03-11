/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';

const StateContext = createContext();

const CALENDAR_VIEW = 'calendar_view';
const LIST_VIEW = 'list_view';

export function ViewProvider( { children, isSmallViewport } ) {
	return (
		<StateContext.Provider
			value={ {
				defaultState: isSmallViewport ? LIST_VIEW : CALENDAR_VIEW,
				isSmallViewport,
			} }
		>
			{ children }
		</StateContext.Provider>
	);
}

export function useViews() {
	const context = useContext( StateContext );
	const isView = ( toMatch ) => currentView === toMatch;
	const [ currentView, setView ] = useState( context.defaultState );

	if ( context === undefined ) {
		throw new Error( 'useViews must be used within a Provider' );
	}

	return {
		isCalendarView: () => isView( CALENDAR_VIEW ),
		isListView: () => isView( LIST_VIEW ),
		setCalendarView: () => setView( CALENDAR_VIEW ),
		setListView: () => setView( LIST_VIEW ),
		shouldForceListView: context.isSmallViewport,
	};
}
