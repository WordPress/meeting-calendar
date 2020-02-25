/**
 * Internal dependencies
 */
import { getRows, getSortedEvents } from '../calendar/utils';
import { useEvents } from '../calendar/event-context';
import ListItem from './listItem';

function List( { month, year, week } ) {
	const rows = getRows( year, month );
	const events = getSortedEvents( useEvents() );
	const allDays = rows.flat().filter( ( i ) => ! i.blank );

	return (
		<ul className="wporg-meeting-calendar__list">
			{ allDays.map( ( row, i ) => {
				return (
					<ListItem
						key={ `row-${ i }` }
						row={ row }
						events={ events }
					/>
				);
			} ) }
		</ul>
	);
}

export default List;
