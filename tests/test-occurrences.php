<?php
/**
 * Tests for next occurrence edge cases.
 *
 * @package Meeting_Calendar
 */

declare( strict_types = 1 );

/**
 * Class MeetingOccurrencesTest
 */
class MeetingOccurrencesTest extends WP_UnitTestCase {

	/**
	 * Test that "Nth weekday" meetings work when weekday names are translated.
	 */
	public function test_next_occurrence_with_translated_weekdays(): void {
		global $wp_locale;

		// The third Wednesday of each month.
		$meeting = $this->create_occurrence_meeting( '2020-01-15', array( 3 ) );

		$original_locale = $wp_locale;
		$translate       = static function ( string $translation, string $text ): string {
			return 'Wednesday' === $text ? 'Mittwoch' : $translation;
		};
		add_filter( 'gettext', $translate, 10, 2 );
		$wp_locale = new WP_Locale(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Rebuild the weekday names with the translation.

		$next = Meeting_Post_Type::getInstance()->get_next_occurrence( $meeting, '2026-10-01 00:00:00 +00:00' );

		remove_filter( 'gettext', $translate, 10 );
		$wp_locale = $original_locale; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the original locale.

		$this->assertSame( '2026-10-21', $next );
	}

	/**
	 * Test that "last weekday" meetings don't skip a shorter following month.
	 */
	public function test_next_last_weekday_after_month_end(): void {
		// The last Friday of each month. January 30, 2026 is one.
		$meeting = $this->create_occurrence_meeting( '2026-01-30', array( -1 ) );

		$next = Meeting_Post_Type::getInstance()->get_next_occurrence( $meeting, '2026-01-30 16:00:00 +00:00' );

		$this->assertSame( '2026-02-27', $next );
	}

	/**
	 * Test that a "last weekday" meeting's occurrences in the two-month window include both months.
	 */
	public function test_future_last_weekday_occurrences(): void {
		// The last Saturday of each month, starting with January 31, 2026.
		$meeting = $this->create_occurrence_meeting( '2026-01-31', array( -1 ) );

		$occurrences = Meeting_Post_Type::getInstance()->get_future_occurrences( $meeting, null, array( 'month' => '2026-01-01' ) );

		$this->assertSame( array( '2026-01-31', '2026-02-28' ), $occurrences );
	}

	/**
	 * Create a meeting that recurs on given weeks of the month, on the start date's weekday.
	 *
	 * @param string $start_date Start date, Y-m-d.
	 * @param int[]  $weeks      Weeks of the month, 1-4, or -1 for the last.
	 * @return WP_Post
	 */
	private function create_occurrence_meeting( string $start_date, array $weeks ): WP_Post {
		return get_post(
			self::factory()->post->create(
				array(
					'post_type'   => 'meeting',
					'post_status' => 'publish',
					'meta_input'  => array(
						'team'       => 'Team-A',
						'start_date' => $start_date,
						'end_date'   => '',
						'time'       => '15:00:00',
						'recurring'  => 'occurrence',
						'occurrence' => $weeks,
					),
				)
			)
		);
	}
}
