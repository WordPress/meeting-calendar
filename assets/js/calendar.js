// var calendarElement = document.querySelector( '#a8c-meeting-calendar-js' );

// var ulElement = document.createElement( 'ul' );

// for ( var i = 0; i < meetingData.length; i++ ) {
// 	var li = document.createElement( 'div' );
// 	console.log( meetingData[ i ] );
// 	li.textContent = `date: ${ meetingData[ i ].date } - Title: ${ meetingData[ i ].title.rendered } `;
// 	ulElement.appendChild( li );
// }

// calendarElement.innerHTML = '';
// calendarElement.appendChild( ulElement );

document.addEventListener( 'DOMContentLoaded', function() {
	var calendarEl = document.getElementById( 'a8c-meeting-calendar-js' );

	var meetingData = JSON.parse( calendarEl.getAttribute( 'data-meetings' ) );

	console.log( meetingData );
	var events = meetingData.map( ( i ) => {
		return {
			...i,
			title: i.title.rendered,
			start: i.date,
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
