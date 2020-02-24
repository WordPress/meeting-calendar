/**
 * WordPress dependencies
 */
import { __experimentalGetSettings } from '@wordpress/date';

function CalendarHeader() {
	const { l10n } = __experimentalGetSettings();
	return (
		<thead>
			<tr>
				{ l10n.weekdaysShort.map( ( day, i ) => {
					return (
						<th scope="col" key={ day }>
							<span class="screen-reader-text">
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
