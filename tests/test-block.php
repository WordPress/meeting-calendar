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
	 * Test that the block outputs the meeting data when the REST request succeeds.
	 */
	public function test_render_callback_outputs_meeting_data(): void {
		$output = Meeting_Calendar\render_callback( array(), '' );

		$this->assertStringContainsString( 'id="wporg-meeting-calendar-js"', $output );
		$this->assertStringContainsString( 'data-meetings="[', $output );
	}

	/**
	 * Test that a failed REST request returns an error instead of JSON encoding it.
	 */
	public function test_get_meeting_data_returns_error_on_failed_request(): void {
		$filter = $this->get_error_filter();
		add_filter( 'rest_pre_dispatch', $filter );

		$data = Meeting_Calendar\get_meeting_data( 12 );

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

		$output = Meeting_Calendar\render_callback( array(), '' );

		remove_filter( 'rest_pre_dispatch', $filter );

		$this->assertStringNotContainsString( 'data-meetings', $output );
		$this->assertStringContainsString( 'The meeting calendar could not be loaded.', $output );
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
