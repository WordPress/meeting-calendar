/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

function ListItem( { row, events } ) {
	const date = new Date( row.year, row.month, row.day );
	const key = format( 'Y-m-d', date );
	const dayEvents = events[ key ] || [];

	return (
		<li key={ `row-${ key }` }>
			<strong className="wporg-meeting-calendar__list-title">
				<span>{ format( 'l - F j, Y', date ) }</span>
			</strong>

			{ dayEvents.map( ( e, i ) => {
				const eventDate = e.meta.start_date + 'T' + e.meta.time + 'Z';
				return (
					<article
						className="wporg-meeting-calendar__list-event"
						key={ `cell-${ i }-${ i }` }
					>
						<div>{ format( 'g:i a: ', eventDate ) }</div>
						<div>
							<a
								className="wporg-meeting-calendar__list-event-team"
								href={ `/${ e.meta.team }` }
							>
								{ e.meta.team }
							</a>
							<h3
								className="wporg-meeting-calendar__list-event-title"
								key={ e.id }
							>
								<a href={ e.link }>{ e.title }</a>
							</h3>
							<span className="wporg-meeting-calendar__list-event-subtitle">
								Location: { e.meta.location }
							</span>
						</div>
					</article>
				);
			} ) }
		</li>
	);
}

export default ListItem;
