<?php
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
		$this->server = $wp_rest_server = new \WP_REST_Server;
		do_action( 'rest_api_init' );

		// Install test data
		$this->meeting_ids = wporg_meeting_install();

		// Make sure the meta keys are registered - setUp/tearDown nukes these
		Meeting_Post_Type::getInstance()->register_meta();

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
		$this->assertArrayHasKey( '/wp/v2/meeting', $routes );
	}

	public function test_get_meetings() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/meeting' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response->get_data() ) );
	}

	public function test_meeting_weekly() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[0] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting',       $meeting['type'] );
		$this->assertEquals( 'Team-A',        $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01',    $meeting['meta']['start_date'] );
		$this->assertEquals( '',              $meeting['meta']['end_date'] );
		$this->assertEquals( '14:00:00',      $meeting['meta']['time'] );
		$this->assertEquals( 'weekly',        $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array(),         $meeting['meta']['occurrence'] );
	}

	public function test_meeting_monthly() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[1] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting',       $meeting['type'] );
		$this->assertEquals( 'Team-B',        $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01',    $meeting['meta']['start_date'] );
		$this->assertEquals( '',              $meeting['meta']['end_date'] );
		$this->assertEquals( '15:00:00',      $meeting['meta']['time'] );
		$this->assertEquals( 'monthly',       $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array(),         $meeting['meta']['occurrence'] );
	}

	public function test_meeting_third_wednesday() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/meeting/' . $this->meeting_ids[2] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$meeting = $response->get_data();

		// Make sure the postmeta is all present
		$this->assertEquals( 'meeting',       $meeting['type'] );
		$this->assertEquals( 'Team-C',        $meeting['meta']['team'] );
		$this->assertEquals( '2020-01-01',    $meeting['meta']['start_date'] );
		$this->assertEquals( '',              $meeting['meta']['end_date'] );
		$this->assertEquals( '16:00:00',      $meeting['meta']['time'] );
		$this->assertEquals( 'occurrence',    $meeting['meta']['recurring'] );
		$this->assertEquals( 'wordpress.org', $meeting['meta']['link'] );
		$this->assertEquals( array(3),        $meeting['meta']['occurrence'] );
	}


}
