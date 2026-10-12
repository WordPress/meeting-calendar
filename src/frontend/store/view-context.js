/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';

const StateContext = createContext();

const CALENDAR_VIEW = 'calendar_view';
const LIST_VIEW = 'list_view';

/**
 * Provide the default view, based on the viewport size.
 *
 * @param {Object}                    props                 Component props.
 * @param {import('react').ReactNode} props.children        Child elements.
 * @param {boolean}                   props.isSmallViewport Whether the viewport is small.
 * @return {Element} Context provider element.
 */
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

/**
 * Get the current view and functions to switch it.
 *
 * @return {Object} View state and setters.
 */
export function useViews() {
	const context = useContext( StateContext );

	/**
	 * Check whether a view is the current one.
	 *
	 * @param {string} toMatch View to check.
	 * @return {boolean} Whether it's the current view.
	 */
	const isView = ( toMatch ) => currentView === toMatch;

	if ( context === undefined ) {
		throw new Error( 'useViews must be used within a Provider' );
	}

	const [ currentView, setView ] = useState( context.defaultState );

	return {
		/**
		 * Check whether the calendar view is shown.
		 *
		 * @return {boolean} Whether the calendar view is shown.
		 */
		isCalendarView: () => isView( CALENDAR_VIEW ),

		/**
		 * Check whether the list view is shown.
		 *
		 * @return {boolean} Whether the list view is shown.
		 */
		isListView: () => isView( LIST_VIEW ),

		/**
		 * Switch to the calendar view.
		 */
		setCalendarView: () => setView( CALENDAR_VIEW ),

		/**
		 * Switch to the list view.
		 */
		setListView: () => setView( LIST_VIEW ),
		shouldForceListView: context.isSmallViewport,
	};
}
