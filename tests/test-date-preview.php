<?php
/**
 * Tests for the date preview in the meeting edit screen.
 *
 * @package Meeting_Calendar
 */

declare( strict_types = 1 );

/**
 * Class MeetingDatePreviewTest
 */
class MeetingDatePreviewTest extends WP_Ajax_UnitTestCase {

	/**
	 * Set up an editor and the nonce the edit screen sends.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$_POST['nonce'] = wp_create_nonce( 'meeting_date_preview' );
	}

	/**
	 * Test that a weekly meeting previews its next four dates, a week apart.
	 */
	public function test_weekly_preview(): void {
		$start               = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$_POST['start_date'] = $start;
		$_POST['time']       = '12:00';
		$_POST['recurring']  = 'weekly';

		$response = $this->get_response();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 4, $response['data'] );
		foreach ( $response['data'] as $i => $date ) {
			$expected = gmdate( 'l, Y-m-d', strtotime( "{$start} +{$i} weeks" ) );
			$this->assertSame( $expected, $date );
		}
	}

	/**
	 * Test that the preview stops at the end date.
	 */
	public function test_preview_respects_end_date(): void {
		$_POST['start_date'] = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$_POST['end_date']   = gmdate( 'Y-m-d', strtotime( '+10 days' ) );
		$_POST['time']       = '12:00';
		$_POST['recurring']  = 'weekly';

		$response = $this->get_response();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 2, $response['data'] );
	}

	/**
	 * Test that an unparseable start date is rejected.
	 */
	public function test_invalid_start_date(): void {
		$_POST['start_date'] = 'not a date';
		$_POST['time']       = '12:00';
		$_POST['recurring']  = 'weekly';

		$response = $this->get_response();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * Run the Ajax action and decode its JSON response.
	 *
	 * @return array
	 */
	private function get_response(): array {
		try {
			$this->_handleAjax( 'meeting_date_preview' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}
}
