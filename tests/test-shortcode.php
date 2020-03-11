<?php
use WordPressdotorg\Meeting_Calendar;
/**
 * Class MeetingShortcodeTest
 *
 * @package Meeting_Calendar
 */

/**
 * Sample test case.
 */
class MeetingShortcodeTest extends WP_UnitTestCase {
	protected $server;
	protected $meeting_ids;
	protected $mpt;

	function setUp() {
		parent::setUp();

		// Install test data
		$this->meeting_ids = Meeting_Calendar\wporg_meeting_install();

		$this->mpt = Meeting_Post_Type::getInstance();
	}

	function test_shortcode_simple() {

		// A one-off meeting tomorrow
		$meeting_1 = $this->factory->post->create( array(
			'post_title' => __( 'Meeting One', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-F',
				'start_date' => strftime( '%Y-%m-%d', strtotime( 'tomorrow' ) ),
				'end_date'   => '',
				'time'       => '01:00',
				'recurring'  => '',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		// A recurring weekly meeting, starting yesterday
		$meeting_1 = $this->factory->post->create( array(
			'post_title' => __( 'Meeting Two', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-F',
				'start_date' => strftime( '%Y-%m-%d', strtotime( 'yesterday' ) ),
				'end_date'   => '',
				'time'       => '02:00',
				'recurring'  => 'weekly',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		$actual = do_shortcode( '[meeting_time team="Team-F" before="" more=0 limit=-1 /]' );

		$this->assertGreaterThan( 0, strpos( $actual, strftime('<strong class="meeting-title">Meeting One</strong><br/><time class="date" date-time="%Y-%m-%dT01:00:00+00:00" title="%Y-%m-%dT01:00:00+00:00">%a %b %e 01:00:00 %Y UTC</time>', strtotime( 'tomorrow' ) )) );

		$this->assertGreaterThan( 0, strpos( $actual, strftime('<strong class="meeting-title">Meeting Two</strong><br/><time class="date" date-time="%Y-%m-%dT02:00:00+00:00" title="%Y-%m-%dT02:00:00+00:00">%a %b %e 02:00:00 %Y UTC</time>', strtotime( 'yesterday +7 days' ) )) );
	}

	function test_shortcode_cancelled() {

		// A recurring weekly meeting, starting yesterday
		$meeting_1 = $this->factory->post->create( array(
			'post_title' => __( 'Meeting One', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-F',
				'start_date' => strftime( '%Y-%m-%d', strtotime( 'yesterday' ) ),
				'end_date'   => '',
				'time'       => '01:00',
				'recurring'  => 'weekly',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		// Cancel the meeting that's in 6 days
		$response = $this->mpt->cancel_meeting( array( 'meeting_id' => $meeting_1, 'date' => strftime( '%Y-%m-%d', strtotime( 'yesterday +7 days' ) ) ) );
		$this->assertGreaterThan( 0, $response );

		$actual = do_shortcode( '[meeting_time team="Team-F" before="" more=0 limit=-1 /]' );


		// The shortcode should show the next meeting is in 7 days
		$this->assertGreaterThan( 0, strpos( $actual, strftime('<strong class="meeting-title">Meeting One</strong><br/><time class="date" date-time="%Y-%m-%dT01:00:00+00:00" title="%Y-%m-%dT01:00:00+00:00">%a %b %e 01:00:00 %Y UTC</time>', strtotime( 'yesterday +7 days' ) )) );

		// It should be listed as cancelled
		$this->assertGreaterThanOrEqual( 0, strpos( $actual, '<p class="wporg-meeting-shortcode meeting-cancelled"' ) );

		// It should give the next date
		$this->assertGreaterThan( 0, strpos( $actual, strftime( 'This event is cancelled. The next meeting is scheduled for %Y-%m-%d.', strtotime( 'yesterday +14 days' ) ) ) );
	}
}
