/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './app';
import './style.scss';

/**
 * Get the meetings data the server printed into the calendar container.
 *
 * @param {HTMLElement} calendarEl Calendar container element.
 * @return {Object[]} Meeting occurrences.
 */
const getMeetings = ( calendarEl ) => {
	return JSON.parse( calendarEl.getAttribute( 'data-meetings' ) );
};

/**
 * Render the calendar app into its container, if the page has one.
 */
const initCalendar = () => {
	const calendarEl = document.getElementById( 'wporg-meeting-calendar-js' );
	if ( ! calendarEl ) {
		return;
	}
	const events = getMeetings( calendarEl );
	createRoot( calendarEl ).render( createElement( App, { events } ) );
};

document.addEventListener( 'DOMContentLoaded', initCalendar );
