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

		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A single meeting', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A',
					'start_date' => strftime( '%Y-%m-%d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '01:00',
					'recurring'  => '',
					'link'       => 'wordpress.org',
					'location'   => '#meta',
				),
			)
		);

		$meetings = $this->mpt->get_occurrences_for_period( null );
		$found    = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id ) {
				++ $found;
			}
		}

		$this->assertEquals( 1, $found, 'There should be exactly one instance of a single meeting.' );
	}

	function test_invalid_time() {
		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A meeting with an invalid time', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A',
					'start_date' => strftime( '%Y-%m-%d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '0100 UTC', // Some production data is formatted like this
					'recurring'  => '',
					'link'       => 'wordpress.org',
					'location'   => '#meta',
				),
			)
		);

		$meetings = $this->mpt->get_occurrences_for_period( null );

		$found = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id ) {
				++$found;
				$this->assertEquals( '01:00:00', $meeting['time'] );
				$this->assertEquals( "{$meeting['date']}T01:00:00+00:00", $meeting['datetime'] );
			}
		}
		$this->assertGreaterThan( 0, $found, 'Found no meeting to test' );
	}

	function test_encoding() {
		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A & B meeting', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A&B',
					'start_date' => strftime( '%Y-%m-%d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '01:00',
					'recurring'  => '',
					'link'       => '&wordpress.org',
					'location'   => '&meta',
				),
			)
		);

		$meetings = $this->mpt->get_occurrences_for_period( null );

		$found = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id ) {
				++$found;
				$this->assertEquals( 'Team-A&B', $meeting['team'] );
				$this->assertEquals( '&wordpress.org', $meeting['link'] );
				$this->assertEquals( '&meta', $meeting['location'] );
				$this->assertEquals( 'A & B meeting', $meeting['title'] );
			}
		}
		$this->assertGreaterThan( 0, $found, 'Found no meeting to test' );
	}

	/*
	 * There was a bug with the meeting_set_next_meeting() filter, where it was interfering with the sorting of other post types.
	 * See https://github.com/Automattic/meeting-calendar/issues/98
	 */
	function test_sort_filter_bug() {
		$category = $this->factory->category->create( array(
			'slug' => 'test_sort_filter_bug'
		) );

		$this->factory->post->create( array(
			'post_title' => 'older post',
			'post_date'  => '2020-04-01 17:00:00',
			'post_category' => array( $category ),
			'meta_input' => array(
				'time' => '17:00'
			)
		) );

		$this->factory->post->create( array(
			'post_title' => 'newer post',
			'post_date'  => '2020-04-02 18:00:00',
			'post_category' => array( $category ),
			'meta_input' => array(
				'time' => '18:00'
			)
		) );

		$posts = get_posts( array( 
			'suppress_filters' => false,
			'category' => $category,
		) );

		$this->assertTrue( is_array( $posts ) );
		$this->assertEquals( 2, count( $posts ) );

		// The newer post should come first.
		// The bug meanth that the filter sorted all post types that happened to have a `time` postmeta key, not just `meeting` CPTs.
		$this->assertEquals( '2020-04-02 18:00:00', $posts[0]->post_date );
		$this->assertEquals( 'newer post', $posts[0]->post_title );
		$this->assertEquals( '2020-04-01 17:00:00', $posts[1]->post_date );
		$this->assertEquals( 'older post', $posts[1]->post_title );
	}

}
