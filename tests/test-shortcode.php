<?php
/**
 * Tests for the [meeting_time] shortcode.
 *
 * @package Meeting_Calendar
 */

use WordPressdotorg\Meeting_Calendar;

/**
 * Tests for the [meeting_time] shortcode.
 */
class MeetingShortcodeTest extends WP_UnitTestCase {
	/**
	 * IDs of the sample meetings.
	 *
	 * @var int[]
	 */
	protected $meeting_ids;

	/**
	 * The meeting post type instance.
	 *
	 * @var Meeting_Post_Type
	 */
	protected $mpt;

	/**
	 * Set up the test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Install test data.
		$this->meeting_ids = Meeting_Calendar\wporg_meeting_install();

		$this->mpt = Meeting_Post_Type::getInstance();
	}

	/**
	 * Test the [meeting_time] output for a team's upcoming meetings.
	 */
	public function test_shortcode_simple() {

		// A one-off meeting tomorrow.
		$this->factory->post->create(
			array(
				'post_title'  => __( 'Meeting One', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-F',
					'start_date' => gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '01:00',
					'recurring'  => '',
					'link'       => 'https://wordpress.org',
					'wptv_url'   => 'https://wordpress.tv',
					'location'   => '#meta',
				),
			)
		);

		// A recurring weekly meeting, starting yesterday.
		$this->factory->post->create(
			array(
				'post_title'  => __( 'Meeting Two', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-F',
					'start_date' => gmdate( 'Y-m-d', strtotime( 'yesterday' ) ),
					'end_date'   => '',
					'time'       => '02:00',
					'recurring'  => 'weekly',
					'link'       => 'https://wordpress.org',
					'wptv_url'   => 'https://wordpress.tv',
					'location'   => '#meta',
				),
			)
		);

		$actual = do_shortcode( '[meeting_time team="Team-F" before="" more=0 limit=-1 /]' );

		$date1    = gmdate( 'Y-m-d\T01:00:00+00:00', strtotime( 'tomorrow' ) );
		$display1 = gmdate( 'D M d 01:00:00 Y', strtotime( 'tomorrow' ) ) . ' UTC';
		$expected = '<strong class="meeting-title">Meeting One</strong><br/><time class="date" datetime="' . $date1 . '" title="' . $date1 . '">' . $display1 . '</time>';
		$substr   = substr( $actual, strpos( $actual, '<strong class="meeting-title">Meeting One</strong>' ), strlen( $expected ) );
		$this->assertEquals( $substr, $expected );

		$date2    = gmdate( 'Y-m-d\T02:00:00+00:00', strtotime( 'yesterday +7 days' ) );
		$display2 = gmdate( 'D M d 02:00:00 Y', strtotime( 'yesterday +7 days' ) ) . ' UTC';
		$expected = '<strong class="meeting-title">Meeting Two</strong><br/><time class="date" datetime="' . $date2 . '" title="' . $date2 . '">' . $display2 . '</time>';
		$substr   = substr( $actual, strpos( $actual, '<strong class="meeting-title">Meeting Two</strong>' ), strlen( $expected ) );
		$this->assertEquals( $substr, $expected );
	}

	/**
	 * Test the [meeting_time] output when the next occurrence is cancelled.
	 */
	public function test_shortcode_cancelled() {

		// A recurring weekly meeting, starting yesterday.
		$meeting_1 = $this->factory->post->create(
			array(
				'post_title'  => __( 'Meeting One', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-F',
					'start_date' => gmdate( 'Y-m-d', strtotime( 'yesterday' ) ),
					'end_date'   => '',
					'time'       => '01:00',
					'recurring'  => 'weekly',
					'link'       => 'https://wordpress.org',
					'wptv_url'   => 'https://wordpress.tv',
					'location'   => '#meta',
				),
			)
		);

		// Cancel the meeting that's in 6 days.
		$response = Meeting_Calendar\REST_API::get_instance()->cancel_meeting(
			array(
				'meeting_id' => $meeting_1,
				'date'       => gmdate(
					'Y-m-d',
					strtotime( 'yesterday +7 days' )
				),
			)
		);
		$this->assertGreaterThan( 0, $response );

		$actual = do_shortcode( '[meeting_time team="Team-F" before="" more=0 limit=-1 /]' );

		// The shortcode should show the next meeting is in 7 days.
		$date1    = gmdate( 'Y-m-d\T01:00:00+00:00', strtotime( 'yesterday +7 days' ) );
		$display1 = gmdate( 'D M d 01:00:00 Y', strtotime( 'yesterday +7 days' ) ) . ' UTC';
		$expected = '<strong class="meeting-title">Meeting One</strong><br/><time class="date" datetime="' . $date1 . '" title="' . $date1 . '">' . $display1 . '</time>';
		$substr   = substr( $actual, strpos( $actual, '<strong class="meeting-title">Meeting One</strong>' ), strlen( $expected ) );
		$this->assertEquals( $substr, $expected );

		// It should be listed as cancelled.
		$this->assertGreaterThanOrEqual( 0, strpos( $actual, '<p class="wporg-meeting-shortcode meeting-cancelled"' ) );

		// It should give the next date.
		$this->assertGreaterThan( 0, strpos( $actual, 'This event is cancelled. The next meeting is scheduled for ' . gmdate( 'Y-m-d', strtotime( 'yesterday +14 days' ) ) . '.' ) );
	}

	/**
	 * Test that [meeting_time] enqueues the time conversion script.
	 */
	public function test_shortcode_enqueues_time_conversion_script() {
		wp_dequeue_script( 'wporg-meeting-time-conversion' );

		do_shortcode( '[meeting_time team="Team-F" /]' );

		$this->assertTrue( wp_script_is( 'wporg-meeting-time-conversion' ) );
		$this->assertStringEndsWith( '/includes/time-conversion.js', wp_scripts()->registered['wporg-meeting-time-conversion']->src );
		$this->assertSame( 'defer', wp_scripts()->get_data( 'wporg-meeting-time-conversion', 'strategy' ) );
	}
}
