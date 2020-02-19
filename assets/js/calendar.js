( function( $ ) {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		var calendarEl = document.getElementById( 'a8c-meeting-calendar-js' );

		var meetingData = JSON.parse(
			calendarEl.getAttribute( 'data-meetings' )
		);

		var events = meetingData.map( ( i ) => {
			return {
				...i,
				title: i.title.rendered,
				start: i.date,gi
			};
		} );

		//Empty calendar
		calendarEl.innerHTML = '';

		var calendar = new FullCalendar.Calendar( calendarEl, {
			plugins: [ 'dayGrid', 'timeGrid', 'list' ],
			events,
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth, timeGridWeek, timeGridDay',
			},
		} );

		calendar.render();
	} );
} )( jQuery );
