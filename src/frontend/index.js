/**
 * WordPress dependencies
 */
import { createElement, render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Calendar from './calendar';
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

	render( createElement( Calendar, { events } ), calendarEl );
};

document.addEventListener( 'DOMContentLoaded', initCalendar );
