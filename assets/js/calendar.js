// var calendarElement = document.querySelector( '#a8c-meeting-calendar-js' );
// var meetingData = JSON.parse( calendarElement.getAttribute( 'data-meetings' ) );
// var ulElement = document.createElement( 'ul' );

// for ( var i = 0; i < meetingData.length; i++ ) {
// 	var li = document.createElement( 'div' );
// 	console.log( meetingData[ i ] );
// 	li.textContent = `date: ${ meetingData[ i ].date } - Title: ${ meetingData[ i ].title.rendered } `;
// 	ulElement.appendChild( li );
// }

// calendarElement.innerHTML = '';
// calendarElement.appendChild( ulElement );


document.addEventListener('DOMContentLoaded', function() {
	var calendarEl = document.getElementById('a8c-meeting-calendar-js');

	//Empty calendar
	calendarEl.innerHTML = '';

	var calendar = new FullCalendar.Calendar(calendarEl, {
	  plugins: [ 'dayGrid', 'timeGrid', 'list' ],
	  header: {
		left: 'prev,next today',
		center: 'title',
		right: 'dayGridMonth, timeGridWeek, timeGridDay'
	  }
	});

	calendar.render();
  });
