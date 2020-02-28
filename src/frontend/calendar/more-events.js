/**
 * WordPress dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { format } from '@wordpress/date';

const MoreEvents = ( { events, onClick } ) => (
	<Dropdown
		className="my-container-class-name"
		contentClassName="my-popover-content-classname"
		position="bottom center"
		renderToggle={ ( { isOpen, onToggle } ) => (
			<Button isLink onClick={ onToggle } aria-expanded={ isOpen }>
				{ // translators: %d: Count of hidden events, ie: 4.
				sprintf(
					_n( '%d more', '%d more', events.length, 'wporg' ),
					events.length
				) }
			</Button>
		) }
		renderContent={ () => (
			<MenuGroup>
				{ events.map( ( e ) => {
					return (
						<MenuItem key={ e.instance_id } onClick={ onClick }>
							{ format( 'g:i a: ', e.datetime ) }
							{ e.title }
						</MenuItem>
					);
				} ) }
			</MenuGroup>
		) }
	/>
);

export default MoreEvents;
