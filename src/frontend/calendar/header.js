/**
 * WordPress dependencies
 */
import { __experimentalGetSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import Container from '../container';

function CalendarHeader() {
	const { l10n } = __experimentalGetSettings();
	return (
		<thead>
			<tr>
				{ l10n.weekdaysShort.map( ( day, i ) => {
					return (
						<Container
							as="th"
							display={ [ 'none', 'table-cell' ] }
							scope="col"
							key={ day }
						>
							<span className="screen-reader-text">
								{ l10n.weekdays[ i ] }
							</span>
							<span aria-hidden>{ day }</span>
						</Container>
					);
				} ) }
			</tr>
		</thead>
	);
}

export default CalendarHeader;
