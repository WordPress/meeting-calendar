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

const formatMeeting = ( i ) => {
	return {
		...i,
		title: i.title.rendered,
		start: i.meta.start_date,
	};
};

const initCalendar = () => {
	const calendarEl = document.getElementById( 'wporg-meeting-calendar-js' );
	const events = getMeetings( calendarEl ).map( formatMeeting );

	render( createElement( App, { events } ), calendarEl );
};

document.addEventListener( 'DOMContentLoaded', initCalendar );
