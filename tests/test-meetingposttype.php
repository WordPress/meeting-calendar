<?php
/**
 * Tests for the meeting post type.
 *
 * @package Meeting_Calendar
 */

use WordPressdotorg\Meeting_Calendar;

/**
 * Tests for the meeting post type.
 */
class MeetingPostTypeTest extends WP_UnitTestCase {
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
	 * Test that a one-off meeting is listed exactly once.
	 */
	public function test_single_meeting() {
		// See https://github.com/WordPress/meeting-calendar/issues/34.

		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A single meeting', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A',
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

		$meetings = Meeting_Calendar\REST_API::get_instance()->get_occurrences_for_period( null );
		$found    = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id ) {
				++$found;
			}
		}

		$this->assertEquals( 1, $found, 'There should be exactly one instance of a single meeting.' );
	}

	/**
	 * Test that a non-standard time like "0100 UTC" is normalized in the API output.
	 */
	public function test_invalid_time() {
		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A meeting with an invalid time', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A',
					'start_date' => gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '0100 UTC', // Some production data is formatted like this.
					'recurring'  => '',
					'link'       => 'https://wordpress.org',
					'wptv_url'   => 'https://wordpress.tv',
					'location'   => '#meta',
				),
			)
		);

		$meetings = Meeting_Calendar\REST_API::get_instance()->get_occurrences_for_period( null );

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

	/**
	 * Test that special characters in meeting fields aren't double-encoded in the API output.
	 */
	public function test_encoding() {
		$meeting_id = $this->factory->post->create(
			array(
				'post_title'  => __( 'A & B meeting', 'wporg-meeting-calendar' ),
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-A&B',
					'start_date' => gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ),
					'end_date'   => '',
					'time'       => '01:00',
					'recurring'  => '',
					'link'       => 'https://wordpress.org/?a=1&b=2',
					'wptv_url'   => 'https://wordpress.tv/?a=1&b=2',
					'location'   => '&meta',
				),
			)
		);

		$meetings = Meeting_Calendar\REST_API::get_instance()->get_occurrences_for_period( null );

		$found = 0;
		foreach ( $meetings as $meeting ) {
			if ( $meeting['meeting_id'] === $meeting_id ) {
				++$found;
				$this->assertEquals( 'Team-A&B', $meeting['team'] );
				$this->assertEquals( 'https://wordpress.org/?a=1&b=2', $meeting['link'] );
				$this->assertEquals( 'https://wordpress.tv/?a=1&b=2', $meeting['wptv_url'] );
				$this->assertEquals( '&meta', $meeting['location'] );
				$this->assertEquals( 'A & B meeting', $meeting['title'] );
			}
		}
		$this->assertGreaterThan( 0, $found, 'Found no meeting to test' );
	}

	/**
	 * Test that meeting_set_next_meeting() doesn't interfere with sorting other post types.
	 *
	 * See https://github.com/WordPress/meeting-calendar/issues/98
	 */
	public function test_sort_filter_bug() {
		$category = $this->factory->category->create(
			array(
				'slug' => 'test_sort_filter_bug',
			)
		);

		$this->factory->post->create(
			array(
				'post_title'    => 'older post',
				'post_date'     => '2020-04-01 17:00:00',
				'post_category' => array( $category ),
				'meta_input'    => array(
					'time' => '17:00',
				),
			)
		);

		$this->factory->post->create(
			array(
				'post_title'    => 'newer post',
				'post_date'     => '2020-04-02 18:00:00',
				'post_category' => array( $category ),
				'meta_input'    => array(
					'time' => '18:00',
				),
			)
		);

		$posts = get_posts(
			array(
				'suppress_filters' => false,
				'category'         => $category,
			)
		);

		$this->assertTrue( is_array( $posts ) );
		$this->assertEquals( 2, count( $posts ) );

		// The newer post should come first.
		// The bug meanth that the filter sorted all post types that happened to have a `time` postmeta key, not just `meeting` CPTs.
		$this->assertEquals( '2020-04-02 18:00:00', $posts[0]->post_date );
		$this->assertEquals( 'newer post', $posts[0]->post_title );
		$this->assertEquals( '2020-04-01 17:00:00', $posts[1]->post_date );
		$this->assertEquals( 'older post', $posts[1]->post_title );
	}

	/**
	 * Test that saving the meta box stores quotes and backslashes unchanged.
	 */
	public function test_save_meta_boxes_keeps_slashes() {
		global $post;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post = get_post( self::factory()->post->create( array( 'post_type' => 'meeting' ) ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- save_meta_boxes() reads it.
		$team = 'Team "A" \\ O\'Reilly';

		// Request data arrives slashed, like core's wp_magic_quotes().
		$_POST = wp_slash(
			array(
				'meeting_nonce' => wp_create_nonce( 'save_meeting_meta_' . $post->ID ),
				'post_title'    => 'A meeting',
				'team'          => $team,
				'start_date'    => '2030-01-01',
				'time'          => '12:00',
			)
		);
		Meeting_Calendar\Admin::get_instance()->save_meta_boxes( $post->ID );
		$_POST = array();

		$this->assertSame( $team, get_post_meta( $post->ID, 'team', true ) );
	}
}
