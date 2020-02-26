<?php
namespace WordPressdotorg\Meeting_Calendar;

class ICAL_Generator {

	const NEWLINE = "\r\n";

	/**
	 * Generate an iCalendar for the given set of meetings.
	 *
	 * @param WP_Post[] $posts
	 * @return string
	 */
	public function generate( $posts ) {
		$ical  = 'BEGIN:VCALENDAR' . self::NEWLINE;
		$ical .= 'VERSION:2.0' . self::NEWLINE;
		$ical .= 'PRODID:-//WPORG Make//Meeting Events Calendar//EN' . self::NEWLINE;
		$ical .= 'METHOD:PUBLISH' . self::NEWLINE;
		$ical .= 'CALSCALE:GREGORIAN' . self::NEWLINE;

		foreach ( $posts as $post ) {
			$ical .= $this->generate_event( $post );
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
	private function generate_event( $post ) {
		$id        = $post->ID;
		$title     = $post->post_title;
		$location  = $post->location;
		$link      = $post->link;
		$team      = $post->team;
		$recurring = $post->recurring;
		$sequence  = empty( $post->sequence ) ? 0 : intval( $post->sequence );

		$start_date      = strftime( '%Y%m%d', strtotime( $post->next_date ) );
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

		$frequency = $this->get_frequency( $recurring, $post->next_date, $post->occurrence );

		$event  = 'BEGIN:VEVENT' . self::NEWLINE;
		$event .= "UID:{$id}" . self::NEWLINE;

		$event .= "DTSTAMP:{$start_date_time}" . self::NEWLINE;
		$event .= "DTSTART;VALUE=DATE:{$start_date_time}" . self::NEWLINE;
		$event .= "DTEND;VALUE=DATE:{$end_date_time}" . self::NEWLINE;
		$event .= 'CATEGORIES:WordPress' . self::NEWLINE;
		// Some calendars require the organizer's name and email address
		$event .= "ORGANIZER;CN=WordPress {$team} Team:mailto:mail@example.com" . self::NEWLINE;
		$event .= "SUMMARY:{$team}: {$title}" . self::NEWLINE;
		// Incrementing the sequence number updates the specified event
		$event .= "SEQUENCE:{$sequence}" . self::NEWLINE;
		$event .= 'STATUS:CONFIRMED' . self::NEWLINE;
		$event .= 'TRANSP:OPAQUE' . self::NEWLINE;

		if ( ! empty( $location ) ) {
			$event .= "LOCATION:{$location}" . self::NEWLINE;
		}

		if ( ! empty( $description ) ) {
			$event .= "DESCRIPTION:{$description}" . self::NEWLINE;
		}

		if ( ! is_null( $frequency ) ) {
			$event .= "RRULE:FREQ={$frequency}" . self::NEWLINE;
		}

		$event .= 'END:VEVENT' . self::NEWLINE;

		return $event;
	}

	private function get_frequency( $recurrence, $date, $occurrences ) {
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
				$frequency = $this->get_frequencies_by_day( $occurrences, $date );
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
	private function get_frequencies_by_day( $occurrences, $date ) {
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
}
