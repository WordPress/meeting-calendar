/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './app';
import './style.scss';

const getMeetings = ( calendarEl ) => {
	return JSON.parse( calendarEl.getAttribute( 'data-meetings' ) );
};

const initCalendar = () => {
	const calendarEl = document.getElementById( 'wporg-meeting-calendar-js' );
	if ( ! calendarEl ) {
		return;
	}
	const events = getMeetings( calendarEl );
	createRoot( calendarEl ).render( createElement( App, { events } ) );
};

document.addEventListener( 'DOMContentLoaded', initCalendar );
