<?php
use WordPressdotorg\Meeting_Calendar;
/**
 * Class MeetingAPI
 *
 * @package Meeting_Calendar
 */

/**
 * Sample test case.
 */
class MeetingAPITest extends WP_UnitTestCase {
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
		Meeting_Post_Type::getInstance()->register_rest_routes();
	}


	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}


	public function test_register_route() {
		$routes = $this->server->get_routes();
		// Standard route for the CPT
		$this->assertArrayHasKey( '/wp/v2/meeting', $routes );
		// Main endpoint for listing events
		$this->assertArrayHasKey( '/wp/v2/meetings/from/(?P<month>\d\d\d\d-\d\d-\d\d)', $routes );
		// Endpoint for cancelling
		$this->assertArrayHasKey( '/wp/v2/meetings/(?P<meeting_id>\d+):(?P<date>\d\d\d\d-\d\d-\d\d)', $routes );
	}

	public function test_get_meetings() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response->get_data() ) );
	}

	public function test_meeting_weekly() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[0] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-A', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '14:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'weekly', $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array(), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertGreaterThanOrEqual( 4, count( $meeting['future_occurrences'] ) );
		$this->assertLessThanOrEqual( 10, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date );
			// It should be in the future
			$this->assertGreaterThanOrEqual( new DateTime( 'yesterday' ), $dt );
			// It should be within 2 months (the default range)
			$this->assertLessThanOrEqual( new DateTime( '+2 months' ), $dt );
			// Day of week should be Wednesday, same as the original
			$this->assertEquals( 3, $dt->format( 'N' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be exactly 7 days after the prior date
				$this->assertEquals( '+7 days', $interval->format( '%R%a days' ) );
			}

			$last = $dt;
		}
	}

	public function test_meeting_monthly() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[1] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-B', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '15:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'monthly', $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array(), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertEquals( 2, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date );
			// It should be in the future
			$this->assertGreaterThanOrEqual( new DateTime(), $dt );
			// Day of week should be the first of the month, same as the original
			$this->assertEquals( 1, $dt->format( 'd' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be exactly 7 days after the prior date
				$this->assertEquals( '+1 month 0 days', $interval->format( '%R%m month %d days' ) );
			}

			$last = $dt;
		}
	}

	public function test_meeting_third_wednesday() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[2] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting', $meeting['type'] );
		$this->assertEquals( 'Team-C', $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01', $meeting['meta']['start_date'] );
		$this->assertEquals( '', $meeting['meta']['end_date'] );
		$this->assertEquals( '16:00:00', $meeting['meta']['time'] );
		$this->assertEquals( 'occurrence', $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array( 3 ), $meeting['meta']['occurrence'] );

		$this->assertTrue( is_array( $meeting['future_occurrences'] ) );
		$this->assertEquals( 2, count( $meeting['future_occurrences'] ) );
		// There should be no duplicates
		$this->assertEquals( $meeting['future_occurrences'], array_unique( $meeting['future_occurrences'] ) );
		$last = false;
		foreach ( $meeting['future_occurrences'] as $future_date ) {
			// Make sure it's in the expected date format
			$this->assertEquals( 1, preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $future_date, $matches ) );
			// And it's a valid date
			$this->assertTrue( checkdate( $matches[2], $matches[3], $matches[1] ) );

			$dt = new DateTime( $future_date . ' ' . $meeting['meta']['time'] );
			// It should be in the future
			$this->assertGreaterThanOrEqual( new DateTime(), $dt );

			// Day of week should be Wednesday, same as the original
			$this->assertEquals( 3, $dt->format( 'N' ) );

			if ( $last ) {
				$interval = $last->diff( $dt );
				// Should be between 28 and 36 days since the last meeting
				$this->assertGreaterThanOrEqual( 28, $interval->format( '%R%a' ) );
				$this->assertLessThanOrEqual( 36, $interval->format( '%R%a' ) );
			}

			$last = $dt;
		}
	}

	public function _january_meetings() {
		return array(
			0  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-01',
				'date'        => '2020-01-01',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-01T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			1  =>
			array(
				'meeting_id'  => $this->meeting_ids[1],
				'instance_id' => $this->meeting_ids[1] . ':2020-01-01',
				'date'        => '2020-01-01',
				'time'        => '15:00:00',
				'datetime'    => '2020-01-01T15:00:00+00:00',
				'team'        => 'Team-B',
				'link'        => 'wordpress.org',
				'title'       => 'A monthly meeting',
				'location'    => '#meta',
				'recurring'   => 'monthly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			2  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-08',
				'date'        => '2020-01-08',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-08T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			3  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-15',
				'date'        => '2020-01-15',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-15T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			4  =>
			array(
				'meeting_id'  => $this->meeting_ids[2],
				'instance_id' => $this->meeting_ids[2] . ':2020-01-15',
				'date'        => '2020-01-15',
				'time'        => '16:00:00',
				'datetime'    => '2020-01-15T16:00:00+00:00',
				'team'        => 'Team-C',
				'link'        => 'wordpress.org',
				'title'       => 'Third Wednesday of each month',
				'location'    => '#meta',
				'recurring'   => 'occurrence',
				'occurrence'  =>
				array(
					0 => 3,
				),
				'status'      => 'active',
			),
			5  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-22',
				'date'        => '2020-01-22',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-22T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			6  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-01-29',
				'date'        => '2020-01-29',
				'time'        => '14:00:00',
				'datetime'    => '2020-01-29T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			7  =>
			array(
				'meeting_id'  => $this->meeting_ids[1],
				'instance_id' => $this->meeting_ids[1] . ':2020-02-01',
				'date'        => '2020-02-01',
				'time'        => '15:00:00',
				'datetime'    => '2020-02-01T15:00:00+00:00',
				'team'        => 'Team-B',
				'link'        => 'wordpress.org',
				'title'       => 'A monthly meeting',
				'location'    => '#meta',
				'recurring'   => 'monthly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			8  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-05',
				'date'        => '2020-02-05',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-05T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			9  =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-12',
				'date'        => '2020-02-12',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-12T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			10 =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-19',
				'date'        => '2020-02-19',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-19T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
			11 =>
			array(
				'meeting_id'  => $this->meeting_ids[2],
				'instance_id' => $this->meeting_ids[2] . ':2020-02-19',
				'date'        => '2020-02-19',
				'time'        => '16:00:00',
				'datetime'    => '2020-02-19T16:00:00+00:00',
				'team'        => 'Team-C',
				'link'        => 'wordpress.org',
				'title'       => 'Third Wednesday of each month',
				'location'    => '#meta',
				'recurring'   => 'occurrence',
				'occurrence'  =>
				array(
					0 => 3,
				),
				'status'      => 'active',
			),
			12 =>
			array(
				'meeting_id'  => $this->meeting_ids[0],
				'instance_id' => $this->meeting_ids[0] . ':2020-02-26',
				'date'        => '2020-02-26',
				'time'        => '14:00:00',
				'datetime'    => '2020-02-26T14:00:00+00:00',
				'team'        => 'Team-A',
				'link'        => 'wordpress.org',
				'title'       => 'A weekly meeting',
				'location'    => '#meta',
				'recurring'   => 'weekly',
				'occurrence'  => '',
				'status'      => 'active',
			),
		);
	}

	public function test_meetings_january_2020() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$this->assertEquals( $this->_january_meetings(), $meetings );
	}

	public function test_meetings_february_2020() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meetings/from/2020-02-01' );
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

	public function test_cancel_meeting_permissions_noauth() {
		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		$request  = new WP_REST_Request( 'PUT', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_cancel_meeting_permissions_valid() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$request  = new WP_REST_Request( 'PUT', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_cancel_meeting_output() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Cancel the meeting on 2020-01-15
		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The feed should show that one meeting as cancelled
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$january_meetings              = $this->_january_meetings();
		$january_meetings[3]['status'] = 'cancelled';
		$this->assertEquals( $january_meetings, $meetings );

		// Now uncancel it
		$request  = new WP_REST_Request( 'PUT', '/wp/v2/meetings/' . $this->meeting_ids[0] . ':2020-01-15' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The feed should be as before
		$request  = new WP_REST_Request( 'GET', '/wp/v2/meetings/from/2020-01-01' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meetings = $response->get_data();

		$january_meetings[3]['status'] = 'active';
		$this->assertEquals( $january_meetings, $meetings );
	}


}
