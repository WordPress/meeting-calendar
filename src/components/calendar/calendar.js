import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

document.addEventListener( 'DOMContentLoaded', function() {
	const calendarEl = document.getElementById( 'wporg-meeting-calendar-js' );

	const meetingData = JSON.parse( calendarEl.getAttribute( 'data-meetings' ) );

	const events = meetingData.map( ( i ) => {
		return {
			...i,
			title: i.title.rendered,
			start: i.date,
		};
	} );

	//Empty calendar
	calendarEl.innerHTML = '';

	const calendar = new Calendar( calendarEl, {
		plugins: [ dayGridPlugin, timeGridPlugin, listPlugin ],
		events,
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth, timeGridWeek, timeGridDay',
		},
	} );

	calendar.render();
} );
