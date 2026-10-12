<?php
use WordPressdotorg\Meeting_Calendar;
use function WordPressdotorg\Meeting_Calendar\ICS\get_meeting_posts;
use function WordPressdotorg\Meeting_Calendar\ICS\{get_feed_status, is_known_team};
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

	public function setUp(): void {
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

	public function test_get_recurring_strings_last_weekday() {
		// Last Wednesday of the month.
		$freq = get_frequencies_by_day( array( -1 ), '2020-01-01' );
		$this->assertEquals( 'MONTHLY;BYDAY=-1WE', $freq );

		// 1st and last Wednesday of the month.
		$freq = get_frequencies_by_day( array( 1, -1 ), '2020-01-01' );
		$this->assertEquals( 'MONTHLY;BYDAY=1WE,-1WE', $freq );

		// Last Friday of the month.
		$freq = get_frequencies_by_day( array( -1 ), '2025-03-14' );
		$this->assertEquals( 'MONTHLY;BYDAY=-1FR', $freq );
	}

	public function test_get_ical() {
		$posts = get_meeting_posts();

		/*
		 * The query orders by post date, and the sample meetings can be created across a
		 * second boundary. Sort by ID so the order matches the fixture.
		 */
		usort(
			$posts,
			function ( $a, $b ) {
				return $a->ID <=> $b->ID;
			}
		);

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

	/**
	 * Only valid, upcoming cancellations should be listed in EXDATE, formatted as dates.
	 */
	public function test_get_ical_exdate_only_lists_valid_upcoming_cancellations() {
		$posts       = get_meeting_posts( 'Team-A' );
		$occurrences = Meeting_Post_Type::getInstance()->get_future_occurrences( get_post( $posts[0]->ID ), null, null );

		add_post_meta( $posts[0]->ID, 'meeting_cancelled', $occurrences[1] );
		add_post_meta( $posts[0]->ID, 'meeting_cancelled', '2020-01-08' );
		add_post_meta( $posts[0]->ID, 'meeting_cancelled', "not a date\r\nsecond line" );

		$lines = preg_split( '/\r\n|\r|\n/', generate( $posts, '' ) );

		$this->assertSame(
			array( 'EXDATE:' . str_replace( '-', '', $occurrences[1] ) . 'T140000Z' ),
			array_values( preg_grep( '/^EXDATE:/', $lines ) )
		);
		$this->assertNotContains( 'second line', $lines );
	}

	/**
	 * EXDATE should be omitted entirely when no valid, upcoming cancellation exists.
	 */
	public function test_get_ical_omits_exdate_without_valid_upcoming_cancellations() {
		$posts = get_meeting_posts( 'Team-A' );

		add_post_meta( $posts[0]->ID, 'meeting_cancelled', '2020-01-08' );
		add_post_meta( $posts[0]->ID, 'meeting_cancelled', 'not-a-date' );

		$lines = preg_split( '/\r\n|\r|\n/', generate( $posts, '' ) );

		$this->assertEmpty( preg_grep( '/^EXDATE:/', $lines ) );
	}

	/**
	 * Create a meeting for a team.
	 *
	 * @param string $team   Name of the team.
	 * @param string $status Post status.
	 * @param string $date   Start date.
	 * @return int
	 */
	protected function create_team_meeting( $team, $status = 'publish', $date = '2020-01-01' ) {
		return $this->factory->post->create(
			array(
				'post_type'   => 'meeting',
				'post_status' => $status,
				'meta_input'  => array(
					'team'       => $team,
					'start_date' => $date,
					'time'       => '12:00:00',
					'recurring'  => '',
				),
			)
		);
	}

	/**
	 * Test which teams count as known: any non-trashed meeting, past or unpublished, case-insensitive.
	 */
	public function test_is_known_team() {
		$this->create_team_meeting( 'Team-Past' );
		$this->create_team_meeting( 'Team-Draft', 'draft' );
		$this->create_team_meeting( 'Team-Trashed', 'trash' );

		$this->assertEmpty( get_meeting_posts( 'team-past' ) );
		$this->assertTrue( is_known_team( 'team-past' ) );
		$this->assertTrue( is_known_team( 'TEAM-PAST' ) );
		$this->assertTrue( is_known_team( 'team-draft' ) );
		$this->assertFalse( is_known_team( 'team-trashed' ) );
		$this->assertFalse( is_known_team( 'not-a-team' ) );
	}

	/**
	 * Test that only unknown teams get a 404, and empty feeds otherwise get a 200.
	 */
	public function test_get_feed_status() {
		$this->create_team_meeting( 'Team-Past' );
		$upcoming = get_post( $this->create_team_meeting( 'Team-Upcoming', 'publish', gmdate( 'Y-m-d', strtotime( '+1 week' ) ) ) );

		$this->assertSame( 200, get_feed_status( array( $upcoming ), 'team-upcoming' ) );
		$this->assertSame( 200, get_feed_status( array(), 'team-past' ) );
		$this->assertSame( 200, get_feed_status( array(), '' ) );
		$this->assertSame( 404, get_feed_status( array(), 'not-a-team' ) );
	}
}
