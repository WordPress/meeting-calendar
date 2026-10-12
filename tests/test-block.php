<?php
/**
 * Tests for the meeting calendar block render callback.
 *
 * @package Meeting_Calendar
 */

declare( strict_types = 1 );

use WordPressdotorg\Meeting_Calendar;

/**
 * Class MeetingBlockTest
 */
class MeetingBlockTest extends WP_UnitTestCase {

	/**
	 * Set up a fresh REST server and the sample meetings.
	 */
	public function setUp(): void {
		global $wp_rest_server;

		parent::setUp();

		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- rest_get_server() reads it.
		do_action( 'rest_api_init', $wp_rest_server );

		Meeting_Calendar\wporg_meeting_install();
		Meeting_Post_Type::getInstance()->register_meta();
	}

	/**
	 * Reset the REST server.
	 */
	public function tearDown(): void {
		global $wp_rest_server;

		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset for other tests.

		parent::tearDown();
	}

	/**
	 * Test that the block outputs the meeting data when the REST request succeeds.
	 */
	public function test_render_callback_outputs_meeting_data(): void {
		$output = Meeting_Calendar\render_callback();

		$this->assertStringContainsString( 'id="wporg-meeting-calendar-js"', $output );
		$this->assertSame( 1, preg_match( '/data-meetings="([^"]*)"/', $output, $matches ) );

		$meetings = json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true );
		$this->assertNotEmpty( $meetings );
		$this->assertContains( 'A weekly meeting', wp_list_pluck( $meetings, 'title' ) );
	}

	/**
	 * Test that a failed REST request returns an error instead of JSON encoding it.
	 */
	public function test_get_meeting_data_returns_error_on_failed_request(): void {
		$filter = $this->get_error_filter();
		add_filter( 'rest_pre_dispatch', $filter );

		$data = Meeting_Calendar\get_meeting_data();

		remove_filter( 'rest_pre_dispatch', $filter );

		$this->assertWPError( $data );
		$this->assertSame( 'meetings_unavailable', $data->get_error_code() );
	}

	/**
	 * Test that the block shows a message instead of the calendar when the REST request fails.
	 */
	public function test_render_callback_shows_message_on_failed_request(): void {
		$filter = $this->get_error_filter();
		add_filter( 'rest_pre_dispatch', $filter );

		$output = Meeting_Calendar\render_callback();

		remove_filter( 'rest_pre_dispatch', $filter );

		$this->assertStringNotContainsString( 'data-meetings', $output );
		$this->assertStringContainsString( 'The meeting calendar could not be loaded.', $output );
		$this->assertStringNotContainsString( 'wporg-meeting-calendar__admin-links', $output );
	}

	/**
	 * Test that editors still get the admin links when the REST request fails.
	 */
	public function test_render_callback_keeps_admin_links_on_failed_request(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$filter = $this->get_error_filter();
		add_filter( 'rest_pre_dispatch', $filter );

		$output = Meeting_Calendar\render_callback();

		remove_filter( 'rest_pre_dispatch', $filter );

		$this->assertStringContainsString( 'The meeting calendar could not be loaded.', $output );
		$this->assertStringContainsString( 'wporg-meeting-calendar__admin-links', $output );
	}

	/**
	 * Get a `rest_pre_dispatch` callback that short-circuits the request with an error.
	 *
	 * @return Closure
	 */
	private function get_error_filter(): Closure {
		return static function (): WP_Error {
			return new WP_Error( 'meetings_unavailable', 'Meetings are unavailable.', array( 'status' => 500 ) );
		};
	}
}
