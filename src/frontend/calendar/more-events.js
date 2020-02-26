/**
 * WordPress dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getFormattedEventDate } from './utils';

const MoreEvents = ( { count, events, onClick } ) => (
	<Dropdown
		className="my-container-class-name"
		contentClassName="my-popover-content-classname"
		position="bottom center"
		renderToggle={ ( { isOpen, onToggle } ) => (
			<Button isLink onClick={ onToggle } aria-expanded={ isOpen }>
				{ // translators: %d: Count of hidden events, ie: 4.
				sprintf( _n( '%d more', '%d more', count, 'wporg' ), count ) }
			</Button>
		) }
		renderContent={ () => (
			<MenuGroup>
				{ events.map( ( e ) => {
					const eventDate = getFormattedEventDate(
						e.meta.start_date,
						e.meta.time
					);
					return (
						<MenuItem key={ e.id } onClick={ onClick }>
							{ eventDate }
							{ e.title }
						</MenuItem>
					);
				} ) }
			</MenuGroup>
		) }
	/>
);

export default MoreEvents;
