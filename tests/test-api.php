<?php
/**
 * Tests for the meetings REST API.
 *
 * @package Meeting_Calendar
 */

use WordPressdotorg\Meeting_Calendar;

/**
 * Tests for the meetings REST API.
 */
class MeetingAPITest extends WP_UnitTestCase {
	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * IDs of the sample meetings.
	 *
	 * @var int[]
	 */
	protected $meeting_ids;

	/**
	 * Set up the test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize a REST server.
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- rest_get_server() reads it.
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		// Install test data.
		$this->meeting_ids = Meeting_Calendar\wporg_meeting_install();

		// Make sure the meta keys are registered - setUp/tearDown nukes these.
		Meeting_Post_Type::getInstance()->register_meta();
	}


	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}


	/**
	 * Test that the meeting routes are registered.
	 */
	public function test_register_route() {
		$routes = $this->server->get_routes();
		// Standard route for the CPT.
		$this->assertArrayHasKey( '/wp/v2/meeting', $routes );
		// Main endpoint for listing events.
		$this->assertArrayHasKey( '/wporg/v1/meetings/from/(?P<month>\d\d\d\d-\d\d-\d\d)', $routes );
		// Endpoint for cancelling.
		$this->assertArrayHasKey( '/wporg/v1/meetings/(?P<meeting_id>\d+):(?P<date>\d\d\d\d-\d\d-\d\d)', $routes );
	}

	/**
	 * Test that the core meeting endpoint lists the sample meetings.
	 */
	public function test_get_meetings() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response->get_data() ) );
	}

	/**
	 * Test the occurrences of a weekly meeting.
	 */
	public function test_meeting_weekly() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[0] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present.
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-A', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '14:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'weekly', $meeting['meta']['recurring'] );
		$this->assertEquals( 'https://wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( 'https://wordpress.tv', $meeting['meta']['wptv_url'] );
		$this->assertEquals( array(), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertGreaterThanOrEqual( 4, count( $meeting['future_occurrences'] ) );
		$this->assertLessThanOrEqual( 10, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates.
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format.
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date.
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date );
			// It should be in the future.
			$this->assertGreaterThanOrEqual( new DateTime( 'yesterday' ), $dt );
			// It should be within 2 months (the default range).
			$this->assertLessThanOrEqual( new DateTime( '+2 months' ), $dt );
			// Day of week should be Wednesday, same as the original.
			$this->assertEquals( 3, $dt->format( 'N' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be exactly 7 days after the prior date.
				$this->assertEquals( '+7 days', $interval->format( '%R%a days' ) );
			}

			$last = $dt;
		}
	}

	/**
	 * Test the occurrences of a monthly meeting.
	 */
	public function test_meeting_monthly() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[1] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present.
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-B', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '15:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'monthly', $meeting['meta']['recurring'] );
		$this->assertEquals( 'https://wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( 'https://wordpress.tv', $meeting['meta']['wptv_url'] );
		$this->assertEquals( array(), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertEquals( 2, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates.
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format.
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date.
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date );
			// It should be in the future.
			$this->assertGreaterThanOrEqual( new DateTime(), $dt );
			// Day of week should be the first of the month, same as the original.
			$this->assertEquals( 1, $dt->format( 'd' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be exactly 7 days after the prior date.
				$this->assertEquals( '+1 month 0 days', $interval->format( '%R%m month %d days' ) );
			}

			$last = $dt;
		}
	}

	/**
	 * Test the occurrences of a meeting on the third Wednesday of the month.
	 */
	public function test_meeting_third_wednesday() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[2] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present.
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-C', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '16:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'occurrence', $meeting['meta']['recurring'] );
		$this->assertEquals( 'https://wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( 'https://wordpress.tv', $meeting['meta']['wptv_url'] );
		$this->assertEquals( array( 3 ), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertGreaterThanOrEqual( 1, count( $meeting['future_occurrences'] ) );
		$this->assertLessThanOrEqual( 2, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates.
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format.
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date.
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date . ' ' . $meeting['meta']['time'] );
			// It should be in the future.
			$this->assertGreaterThanOrEqual( new DateTime(), $dt );

			// Day of week should be Wednesday, same as the original.
			$this->assertEquals( 3, $dt->format( 'N' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be between 28 and 36 days since the last meeting.
				$this->assertGreaterThanOrEqual( 28, $interval->format( '%R%a' ) );
				$this->assertLessThanOrEqual( 36, $interval->format( '%R%a' ) );
			}

			$last = $dt;
		}
	}

	/**
	 * Get the expected sample meeting occurrences from January 2020.
	 *
	 * @return array[]
	 */
	public function get_january_meetings() {
		return array(
			0  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-01',
				'date'        => '2020-01-01',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-01T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			1  =>
			array(
				'meeting_id'  => $this->meeting_ids[1],
				'instance_id' => $this->meeting_ids[1] . ':2020-01-01',
				'date'        => '2020-01-01',
				'time'        => '15:00:00',
				'datetime'    => '2020-01-01T15:00:00+00:00',
				'team'        => 'Team-B',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A monthly meeting',
				'location'    => '#meta',
				'recurring'   => 'monthly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=MONTHLY',
				'edit_url'    => '',
			),
			2  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-08',
				'date'        => '2020-01-08',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-08T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			3  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-15',
				'date'        => '2020-01-15',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-15T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			4  =>
			array(
				'meeting_id'  => $this->meeting_ids[2],
				'instance_id' => $this->meeting_ids[2] . ':2020-01-15',
				'date'        => '2020-01-15',
				'time'        => '16:00:00',
				'datetime'    => '2020-01-15T16:00:00+00:00',
				'team'        => 'Team-C',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'Third Wednesday of each month',
				'location'    => '#meta',
				'recurring'   => 'occurrence',
				'occurrence'  =>
				array(
					0 => 3,
				),
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=MONTHLY;BYDAY=3WE',
				'edit_url'    => '',
			),
			5  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-22',
				'date'        => '2020-01-22',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-22T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			6  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-29',
				'date'        => '2020-01-29',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-29T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			7  =>
			array(
				'meeting_id'  => $this->meeting_ids[1],
				'instance_id' => $this->meeting_ids[1] . ':2020-02-01',
				'date'        => '2020-02-01',
				'time'        => '15:00:00',
				'datetime'    => '2020-02-01T15:00:00+00:00',
				'team'        => 'Team-B',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A monthly meeting',
				'location'    => '#meta',
				'recurring'   => 'monthly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=MONTHLY',
				'edit_url'    => '',
			),
			8  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-05',
				'date'        => '2020-02-05',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-05T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			9  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-12',
				'date'        => '2020-02-12',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-12T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			10 =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-19',
				'date'        => '2020-02-19',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-19T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
			11 =>
			array(
				'meeting_id'  => $this->meeting_ids[2],
				'instance_id' => $this->meeting_ids[2] . ':2020-02-19',
				'date'        => '2020-02-19',
				'time'        => '16:00:00',
				'datetime'    => '2020-02-19T16:00:00+00:00',
				'team'        => 'Team-C',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'Third Wednesday of each month',
				'location'    => '#meta',
				'recurring'   => 'occurrence',
				'occurrence'  =>
				array(
					0 => 3,
				),
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=MONTHLY;BYDAY=3WE',
				'edit_url'    => '',
			),
			12 =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-26',
				'date'        => '2020-02-26',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-26T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'https://wordpress.org',
				'wptv_url'    => 'https://wordpress.tv',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
				'rrule'       => 'RRULE:FREQ=WEEKLY',
				'edit_url'    => '',
			),
		);
	}

	/**
	 * Test the occurrences listed from January 2020.
	 */
	public function test_meetings_january_2020() {
		$request  = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$this->assertEquals( $this->get_january_meetings(), $meetings );
	}

	/**
	 * Test the occurrences listed from February 2020.
	 */
	public function test_meetings_february_2020() {
		$request  = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-02-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$expected_datetimes = array(
			0  => '2020-02-01T15:00:00+00:00',
			1  => '2020-02-05T14:00:00+00:00',
			2  => '2020-02-12T14:00:00+00:00',
			3  => '2020-02-19T14:00:00+00:00',
			4  => '2020-02-19T16:00:00+00:00',
			5  => '2020-02-26T14:00:00+00:00',
			6  => '2020-03-01T15:00:00+00:00',
			7  => '2020-03-04T14:00:00+00:00',
			8  => '2020-03-11T14:00:00+00:00',
			9  => '2020-03-18T14:00:00+00:00',
			10 => '2020-03-18T16:00:00+00:00',
			11 => '2020-03-25T14:00:00+00:00',
		);
		$this->assertEquals( $expected_datetimes, wp_list_pluck( $meetings, 'datetime' ) );

		$expected_teams = array(
			0  => 'Team-B',
			1  => 'Team-A',
			2  => 'Team-A',
			3  => 'Team-A',
			4  => 'Team-C',
			5  => 'Team-A',
			6  => 'Team-B',
			7  => 'Team-A',
			8  => 'Team-A',
			9  => 'Team-A',
			10 => 'Team-C',
			11 => 'Team-A',
		);
		$this->assertEquals( $expected_teams, wp_list_pluck( $meetings, 'team' ) );
	}

	/**
	 * Test that logged-out users can't cancel or uncancel meetings.
	 */
	public function test_cancel_meeting_permissions_noauth() {
		$request  = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		$request  = new WP_REST_Request( 'PUT', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that editors can cancel and uncancel meetings.
	 */
	public function test_cancel_meeting_permissions_valid() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$request  = new WP_REST_Request( 'PUT', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test that cancelling and uncancelling an occurrence updates its status in the listing.
	 */
	public function test_cancel_meeting_output() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Cancel the meeting on 2020-01-15.
		$request  = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The feed should show that one meeting as cancelled.
		$request  = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$january_meetings              = $this->get_january_meetings();
		$january_meetings[3]['status'] = 'cancelled';
		// Set expected edit URLs since we are logged in as an editor.
		foreach ( $january_meetings as &$meeting ) {
			$meeting['edit_url'] = get_edit_post_link( $meeting['meeting_id'], 'raw' );
		}
		$this->assertEquals( $january_meetings, $meetings );

		// Now uncancel it.
		$request  = new WP_REST_Request( 'PUT', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The feed should be as before.
		$request  = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$january_meetings[3]['status'] = 'active';
		$this->assertEquals( $january_meetings, $meetings );
	}

	/**
	 * Test that the period route rejects dates that don't exist.
	 */
	public function test_meetings_from_impossible_date() {
		$request  = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-13-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->as_error()->get_error_code() );
	}

	/**
	 * Test that cancelling and uncancelling reject dates that don't exist.
	 */
	public function test_cancel_meeting_impossible_date() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		foreach ( array( 'DELETE', 'PUT' ) as $method ) {
			$request  = new WP_REST_Request( $method, '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-02-30' );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 400, $response->get_status(), $method );
			$this->assertEquals( 'rest_invalid_param', $response->as_error()->get_error_code(), $method );
		}
	}

	/**
	 * Test that a non-string date, e.g. from `?date[]=`, is rejected instead of erroring.
	 */
	public function test_meeting_date_array_param() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' );
		$request->set_query_params( array( 'month' => array( 'x' ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		$request = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$request->set_query_params( array( 'date' => array( 'x' ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that a `?date=` query arg can't change which occurrence gets cancelled.
	 */
	public function test_cancel_meeting_uses_path_date() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'editor' ) ) );

		$request = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$request->set_query_params( array( 'date' => '2020-01-22' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( array( '2020-01-15' ), get_post_meta( $this->meeting_ids[0], 'meeting_cancelled', false ) );
	}

	/**
	 * Test that a `?meeting_id=` query arg can't move the permission check to another meeting.
	 */
	public function test_cancel_meeting_permission_uses_path_meeting() {
		$author_id   = $this->factory->user->create( array( 'role' => 'author' ) );
		$own_meeting = $this->factory->post->create(
			array(
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);
		wp_set_current_user( $author_id );

		$request = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$request->set_query_params( array( 'meeting_id' => $own_meeting ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEmpty( get_post_meta( $this->meeting_ids[0], 'meeting_cancelled', false ) );
	}

	/**
	 * Test that a `?month=` query arg can't change the listed period.
	 */
	public function test_meetings_from_uses_path_month() {
		$expected = $this->server->dispatch( new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' ) );

		$request = new WP_REST_Request( 'GET', '/wporg/v1/meetings/from/2020-01-01' );
		$request->set_query_params( array( 'month' => '2020-06-01' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( $expected->get_data(), $response->get_data() );
	}

	/**
	 * Test that cancelling the same occurrence twice stores it once.
	 */
	public function test_cancel_meeting_twice() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'editor' ) ) );

		foreach ( array( 1, 2 ) as $attempt ) {
			$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-15' ) );
			$this->assertEquals( 200, $response->get_status(), "Attempt {$attempt}" );
		}

		$this->assertSame( array( '2020-01-15' ), get_post_meta( $this->meeting_ids[0], 'meeting_cancelled', false ) );
	}

	/**
	 * Test that an invalid `?month=` on the core meeting endpoint is ignored, not read as 1970.
	 */
	public function test_future_occurrences_invalid_month() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/meeting' );
		$request->set_query_params( array( 'month' => '2020-99-99' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response->get_data() as $meeting ) {
			$this->assertNotEmpty( $meeting['future_occurrences'] );
			foreach ( $meeting['future_occurrences'] as $date ) {
				$this->assertGreaterThanOrEqual( gmdate( 'Y-m-d', strtotime( '-1 day' ) ), $date );
			}
		}
	}

	/**
	 * Test that cancelling a date the meeting doesn't occur on is rejected.
	 */
	public function test_cancel_meeting_invalid_date() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// 2020-01-16 is a Thursday, not a valid occurrence for the weekly Wednesday meeting.
		$request  = new WP_REST_Request( 'DELETE', '/wporg/v1/meetings/' . $this->meeting_ids[0] . ':2020-01-16' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Verify no cancellation was stored.
		$cancellations = get_post_meta( $this->meeting_ids[0], 'meeting_cancelled', false );
		$this->assertNotContains( '2020-01-16', $cancellations );
	}

	/**
	 * Test the occurrences of a meeting on the last Wednesday of the month.
	 */
	public function test_meeting_last_wednesday() {
		// Create a meeting on the last Wednesday of each month.
		// 2020-01-01 is a Wednesday, so the day of week is Wednesday.
		$meeting_id = wp_insert_post(
			array(
				'post_title'  => 'Last Wednesday of each month',
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-D',
					'start_date' => '2020-01-01',
					'end_date'   => '',
					'time'       => '17:00:00',
					'recurring'  => 'occurrence',
					'occurrence' => array( -1 ),
					'link'       => 'https://wordpress.org',
					'location'   => '#meta',
					'wptv_url'   => '',
				),
			)
		);

		$mpt  = Meeting_Post_Type::getInstance();
		$post = get_post( $meeting_id );

		// Check from January 2020.
		$next = $mpt->get_next_occurrence( $post, '2020-01-01 00:00:00 +00:00' );
		// Last Wednesday of January 2020 is the 29th.
		$this->assertEquals( '2020-01-29', $next );

		// Check from February 2020.
		$next = $mpt->get_next_occurrence( $post, '2020-01-30 00:00:00 +00:00' );
		// Last Wednesday of February 2020 is the 26th.
		$this->assertEquals( '2020-02-26', $next );

		// Check from March 2020.
		$next = $mpt->get_next_occurrence( $post, '2020-02-27 00:00:00 +00:00' );
		// Last Wednesday of March 2020 is the 25th.
		$this->assertEquals( '2020-03-25', $next );
	}

	/**
	 * Test the occurrences of a meeting on the first and last Wednesday of the month.
	 */
	public function test_meeting_first_and_last_wednesday() {
		// Create a meeting on the 1st and last Wednesday of each month.
		$meeting_id = wp_insert_post(
			array(
				'post_title'  => 'First and last Wednesday',
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-E',
					'start_date' => '2020-01-01',
					'end_date'   => '',
					'time'       => '18:00:00',
					'recurring'  => 'occurrence',
					'occurrence' => array( 1, -1 ),
					'link'       => 'https://wordpress.org',
					'location'   => '#meta',
					'wptv_url'   => '',
				),
			)
		);

		$mpt  = Meeting_Post_Type::getInstance();
		$post = get_post( $meeting_id );

		// First Wednesday of January 2020 is the 1st.
		$next = $mpt->get_next_occurrence( $post, '2020-01-01 00:00:00 +00:00' );
		$this->assertEquals( '2020-01-01', $next );

		// After the 1st, the next should be last Wednesday = 29th.
		$next = $mpt->get_next_occurrence( $post, '2020-01-02 00:00:00 +00:00' );
		$this->assertEquals( '2020-01-29', $next );

		// After the 29th, next should be first Wednesday of Feb = 5th.
		$next = $mpt->get_next_occurrence( $post, '2020-01-30 00:00:00 +00:00' );
		$this->assertEquals( '2020-02-05', $next );
	}
}
