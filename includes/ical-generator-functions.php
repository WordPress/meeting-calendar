<?php
namespace WordPressdotorg\Meeting_Calendar\ICS\Generator;

define( 'NEWLINE', "\r\n" );

/**
 * Generate an iCalendar for the given set of meetings.
 *
 * @param WP_Post[] $posts
 * @return string
 */
function generate( $posts ) {
	$ical  = 'BEGIN:VCALENDAR' . NEWLINE;
	$ical .= 'VERSION:2.0' . NEWLINE;
	$ical .= 'PRODID:-//Make WordPress//Meeting Events Calendar//EN' . NEWLINE;
	$ical .= 'METHOD:PUBLISH' . NEWLINE;
	$ical .= 'CALSCALE:GREGORIAN' . NEWLINE;

	foreach ( $posts as $post ) {
		$ical .= generate_event( $post );
	}

	$ical .= 'END:VCALENDAR';
	return $ical;
}

/**
 * Generate an event for a meeting.
 *
 * @param WP_Post $post
 * @return string
 */
function generate_event( $post ) {
	$id        = $post->ID;
	$title     = $post->post_title;
	$location  = $post->location;
	$link      = $post->link;
	$team      = $post->team;
	$recurring = $post->recurring;
	$sequence  = empty( $post->sequence ) ? 0 : intval( $post->sequence );

	$start_date      = strftime( '%Y%m%d', strtotime( $post->start_date ) );
	$start_time      = strftime( '%H%M%S', strtotime( $post->time ) );
	$start_date_time = "{$start_date}T{$start_time}Z";

	$end_date      = $start_date;
	$end_time      = strftime( '%H%M%S', strtotime( "{$post->time} +1 hour" ) );
	$end_date_time = "{$end_date}T{$end_time}Z";

	$description   = '';
	$slack_channel = null;

	if ( $location && preg_match( '/^#([-\w]+)$/', trim( $location ), $match ) ) {
		$slack_channel = '#' . sanitize_title( $match[1] );
		$location      = "{$slack_channel} channel on Slack";
	}

	if ( $link ) {
		if ( $slack_channel ) {
			$description .= "Slack channel link: https://wordpress.slack.com/messages/{$slack_channel}\\n";
		}

		$description .= "For more information visit {$link}";
	}

	$frequency = get_frequency( $recurring, $post->next_date, $post->occurrence );

	$event  = 'BEGIN:VEVENT' . NEWLINE;
	$event .= "UID:{$id}" . NEWLINE;

	$event .= "DTSTAMP:{$start_date_time}" . NEWLINE;
	$event .= "DTSTART;VALUE=DATE:{$start_date_time}" . NEWLINE;
	$event .= "DTEND;VALUE=DATE:{$end_date_time}" . NEWLINE;
	$event .= 'CATEGORIES:WordPress' . NEWLINE;
	// Some calendars require the organizer's name and email address
	$event .= "ORGANIZER;CN=WordPress {$team} Team:mailto:mail@example.com" . NEWLINE;
	$event .= "SUMMARY:{$team}: {$title}" . NEWLINE;
	// Incrementing the sequence number updates the specified event
	$event .= "SEQUENCE:{$sequence}" . NEWLINE;
	$event .= 'STATUS:CONFIRMED' . NEWLINE;
	$event .= 'TRANSP:OPAQUE' . NEWLINE;

	if ( ! empty( $location ) ) {
		$event .= "LOCATION:{$location}" . NEWLINE;
	}

	if ( ! empty( $description ) ) {
		$event .= "DESCRIPTION:{$description}" . NEWLINE;
	}

	if ( ! is_null( $frequency ) ) {
		$event .= "RRULE:FREQ={$frequency}" . NEWLINE;

		if ( $cancelled = get_post_meta( $post->ID, 'meeting_cancelled', false ) ) {
			foreach ( $cancelled as $i => $cancelled_date ) {
				$exdate = strtotime( $cancelled_date );
				// Only list cancelled dates that are valid and in the future or recent past
				if ( $exdate >= strtotime( 'yesterday' ) ) {
					$cancelled[ $i ] = strftime( '%Y%m%d', $exdate );
				}
			}
			$event .= "EXDATE:" . implode( ',', $cancelled ) . NEWLINE;
		}
	}

	$event .= 'END:VEVENT' . NEWLINE;

	return $event;
}

function get_frequency( $recurrence, $date, $occurrences ) {
	switch ( $recurrence ) {
		case 'weekly':
			$frequency = 'WEEKLY';
			break;
		case 'biweekly':
			$frequency = 'WEEKLY;INTERVAL=2';
			break;
		case 'monthly':
			$frequency = 'MONTHLY';
			break;
		case 'occurrence':
			$frequency = get_frequencies_by_day( $occurrences, $date );
			break;
		default:
			$frequency = null;
	}

	return $frequency;
}

/**
 * Returns a comma separated list of days in which the event should repeat for the month.
 *
 * For example, given:
 *   $occurrences = array( 1, 3 ) // 1st and 3rd week in the month
 *   $date = '2019-09-15' // the day is Sunday
 * it will return: 'MONTHLY;BYDAY=1SU,3SU'
 *
 * @param array $occurrences
 * @param string $date
 * @return string
 */
function get_frequencies_by_day( $occurrences, $date ) {
	// Get the first two letters of the day of the start date in uppercase letters
	$day = strtoupper(
		substr( strftime( '%a', strtotime( $date ) ), 0, 2 )
	);

	$by_days = array_reduce(
		array_keys( $occurrences ),
		function ( $carry, $key ) use ( $day, $occurrences ) {
			$carry .= $occurrences[ $key ] . $day;

			if ( $key < count( $occurrences ) - 1 ) {
				$carry .= ',';
			}

			return $carry;
		}
	);

	return "MONTHLY;BYDAY={$by_days}";
}