/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { format, gmdate } from '@wordpress/date';
import { Modal, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getFrequencyLabel, getSlackLink, isCancelled } from './utils';

function EventModal( { event, onRequestClose } ) {
	const start = gmdate( 'Ymd\\THis\\Z', event.datetime );
	const endTimestamp = Number( gmdate( 'U', event.datetime ) ) + 3600;
	const end = gmdate( 'Ymd\\THis\\Z', endTimestamp * 1000 );

	const channel = event.location.replace( '#', '' );
	let googleCalLink = addQueryArgs(
		'https://calendar.google.com/calendar/render',
		{
			action: 'TEMPLATE',
			text: event.title,
			dates: `${ start }/${ end }`,
			details: `Location: #${ channel } on Slack - https://wordpress.slack.com/app_redirect?channel=${ channel } `,
		}
	);
	if ( event.rrule ) {
		googleCalLink = addQueryArgs( googleCalLink, { recur: event.rrule } );
	}

	return (
		<Modal
			title={ event.title }
			className="wporg-meeting-calendar__modal"
			overlayClassName="wporg-meeting-calendar__modal-overlay"
			onRequestClose={ onRequestClose }
		>
			{ ! isCancelled( event.status ) ? (
				<p>
					<abbr title={ gmdate( 'c', event.datetime ) }>
						{ format(
							'l, F j, Y, g:i a (\\U\\T\\CP)',
							event.datetime
						) }
					</abbr>
				</p>
			) : (
				<Notice
					className="wporg-meeting-calendar__modal-notice"
					status="warning"
					isDismissible={ false }
				>
					{ __(
						'This meeting has been cancelled',
						'wporg-meeting-calendar'
					) }
				</Notice>
			) }

			{ !! event.location && (
				<p>Location: { getSlackLink( event.location ) }</p>
			) }
			<p>Meets: { getFrequencyLabel( event ) }</p>
			{ !! event.link && (
				<p>
					<a href={ event.link }>{ event.title }</a>
				</p>
			) }
			<p className="wporg-meeting-calendar__modal-export-links">
				<a href={ googleCalLink }>
					{ __( 'Add to Google Calendar', 'wporg-meeting-calendar' ) }
				</a>
			</p>
		</Modal>
	);
}

export default EventModal;
