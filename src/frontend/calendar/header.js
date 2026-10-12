/**
 * WordPress dependencies
 */
import { getSettings } from '@wordpress/date';

function CalendarHeader() {
	const { l10n } = getSettings();
	return (
		<thead>
			<tr>
				{ l10n.weekdaysShort.map( ( day, i ) => {
					return (
						<th scope="col" key={ day }>
							<span className="screen-reader-text">
								{ l10n.weekdays[ i ] }
							</span>
							<span aria-hidden>{ day }</span>
						</th>
					);
				} ) }
			</tr>
		</thead>
	);
}

export default CalendarHeader;
