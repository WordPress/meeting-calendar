<?php
use WordPressdotorg\Meeting_Calendar;
/**
 * Class MeetingPostTypeTest
 *
 * @package Meeting_Calendar
 */

/**
 * Sample test case.
 */
class MeetingPostTypeTest extends WP_UnitTestCase {
	protected $server;
	protected $meeting_ids;
	protected $mpt;

	function setUp() {
		parent::setUp();

		// Install test data
		$this->meeting_ids = Meeting_Calendar\wporg_meeting_install();

		$this->mpt = Meeting_Post_Type::getInstance();
	}

	function test_single_meeting() {
		// See https://github.com/Automattic/meeting-calendar/issues/34

		$meeting_id = $this->factory->post->create( array(
			'post_title' => __( 'A single meeting', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-A',
				'start_date' => strftime( '%Y-%m-%d', strtotime( 'tomorrow' ) ),
				'end_date'   => '',
				'time'       => '01:00',
				'recurring'  => '',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );


		$meetings = $this->mpt->get_occurrences_for_period(null);
		$found = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id )
				++ $found;
		}

		$this->assertEquals( 1, $found, 'There should be exactly one instance of a single meeting.' );
	}

}