<?php
use WordPressdotorg\Meeting_Calendar;
use function WordPressdotorg\Meeting_Calendar\ICS\get_meeting_posts;
use function WordPressdotorg\Meeting_Calendar\ICS\Generator\{generate, get_frequencies_by_day};

/**
 * Class MeetingiCalTest
 *
 * @package Meeting_Calendar
 */

/**
 * Sample test case.
 */
class MeetingiCalTest extends WP_UnitTestCase {
	protected $server;
	protected $meeting_ids;

	function setUp() {
		parent::setUp();

		// Initialize a REST server
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init' );

		// Install test data
		$this->meeting_ids = Meeting_Calendar\wporg_meeting_install();

		// Make sure the meta keys are registered - setUp/tearDown nukes these
		Meeting_Post_Type::getInstance()->register_meta();
	}

	public function test_get_recurring_strings() {
		// 2020-01-01 is a Sunday.
		$freq = get_frequencies_by_day( array( 1, 3 ), '2019-09-15' );
		$this->assertEquals( 'MONTHLY;BYDAY=1SU,3SU', $freq );

		// 2020-01-01 is a Wednesday.
		$freq = get_frequencies_by_day( array( 3 ), '2020-01-01' );
		$this->assertEquals( 'MONTHLY;BYDAY=3WE', $freq );

		// 2025-03-14 is a Friday.
		$freq = get_frequencies_by_day( array( 4 ), '2025-03-14' );
		$this->assertEquals( 'MONTHLY;BYDAY=4FR', $freq );
	}

	public function test_get_ical() {
		$posts      = get_meeting_posts();
		$ical_feed  = generate( $posts, '' );
		$events_ics = file_get_contents( __DIR__ . '/fixtures/events.ics' );
		$events_ics = str_replace( '%ID1%', str_replace( '-', '', $posts[0]->ID ), $events_ics );
		$events_ics = str_replace( '%ID2%', str_replace( '-', '', $posts[1]->ID ), $events_ics );
		$events_ics = str_replace( '%ID3%', str_replace( '-', '', $posts[2]->ID ), $events_ics );

		$this->assertEquals(
			preg_split( '/\r\n|\r|\n/', $events_ics ),
			preg_split( '/\r\n|\r|\n/', $ical_feed )
		);
	}

	public function test_get_ical_with_cancellation() {
		$posts = get_meeting_posts( 'Team-A' );
		// Cancel the second occurrence of the weekly meeting
		$occurrences = Meeting_Post_Type::getInstance()->get_future_occurrences( get_post( $posts[0]->ID ), null, null );
		$this->assertGreaterThan(
			0,
			Meeting_Post_Type::getInstance()->cancel_meeting(
				array(
					'meeting_id' => $posts[0]->ID,
					'date'       => $occurrences[1],
				)
			)
		);

		$ical_feed  = generate( $posts, '' );
		$events_ics = file_get_contents( __DIR__ . '/fixtures/events-with-cancel.ics' );
		$events_ics = str_replace( '%ID%', str_replace( '-', '', $posts[0]->ID ), $events_ics );
		$events_ics = str_replace( '%EXDATE%', str_replace( '-', '', $occurrences[1] ), $events_ics );

		$this->assertEquals(
			preg_split( '/\r\n|\r|\n/', $events_ics ),
			preg_split( '/\r\n|\r|\n/', $ical_feed )
		);
	}
}
