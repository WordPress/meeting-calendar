/**
 * WordPress dependencies
 */
import { createElement, render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './app';
import './styles.css';

const getMeetings = ( calendarEl ) => {
	return JSON.parse( calendarEl.getAttribute( 'data-meetings' ) );
};

const initCalendar = () => {
	const calendarEl = document.getElementById( 'wporg-meeting-calendar-js' );
	const events = getMeetings( calendarEl );

	render( createElement( App, { events } ), calendarEl );
};

document.addEventListener( 'DOMContentLoaded', initCalendar );
