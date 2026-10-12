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
	 * Test that an "occurrence" meeting without any weeks has no next occurrence, rather than one a year out.
	 */
	public function test_next_occurrence_without_weeks(): void {
		$meeting = $this->create_occurrence_meeting( '2020-01-15', array() );

		$this->assertFalse( Meeting_Post_Type::getInstance()->get_next_occurrence( $meeting, '2026-10-01 00:00:00 +00:00' ) );
	}

	/**
	 * Test that an "occurrence" meeting starting in the future has no occurrences before its start date.
	 */
	public function test_next_occurrence_not_before_start_date(): void {
		// The second Wednesday of each month, starting with December 9, 2026.
		$meeting = $this->create_occurrence_meeting( '2026-12-09', array( 2 ) );

		$next = Meeting_Post_Type::getInstance()->get_next_occurrence( $meeting, '2026-10-01 00:00:00 +00:00' );

		$this->assertSame( '2026-12-09', $next );
	}

	/**
	 * Test that [meeting_time] skips a meeting whose date can't be parsed, instead of throwing.
	 */
	public function test_shortcode_skips_unparseable_start_date(): void {
		self::factory()->post->create(
			array(
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'meta_input'  => array(
					'team'       => 'Team-Z',
					'start_date' => 'not a date',
					'end_date'   => '',
					'time'       => '15:00:00',
					'recurring'  => 'weekly',
				),
			)
		);

		$this->assertSame( '', do_shortcode( '[meeting_time team="Team-Z" /]' ) );
	}

	/**
	 * Test that [meeting_time] skips a meeting without a next date, instead of showing today.
	 */
	public function test_shortcode_skips_meeting_without_next_date(): void {
		// No weeks and no start date, so the next date falls back to the empty start date.
		$meeting = $this->create_occurrence_meeting( '', array() );
		update_post_meta( $meeting->ID, 'team', 'Team-Y' );

		$this->assertSame( '', do_shortcode( '[meeting_time team="Team-Y" /]' ) );
	}

	/**
	 * Test that a non-numeric [meeting_time] limit lists all meetings, instead of throwing.
	 */
	public function test_shortcode_non_numeric_limit(): void {
		$meeting = $this->create_occurrence_meeting( '2020-01-15', array( 3 ) );
		update_post_meta( $meeting->ID, 'team', 'Team-X' );

		$this->assertStringContainsString( '<time class="date"', do_shortcode( '[meeting_time team="Team-X" limit="all" /]' ) );
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
