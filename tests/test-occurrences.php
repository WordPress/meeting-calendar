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
	 * Test that [meeting_time] skips a meeting whose time can't be parsed, instead of showing it at midnight.
	 */
	public function test_shortcode_skips_meeting_without_time(): void {
		$this->create_meeting(
			array(
				'team'       => 'Team-T',
				'start_date' => gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ),
				'time'       => '',
			)
		);

		$this->assertSame( '', do_shortcode( '[meeting_time team="Team-T" /]' ) );
	}

	/**
	 * Test that the "more" link in [meeting_time] points at the site the meetings come from.
	 */
	public function test_shortcode_more_link_points_to_main_site(): void {
		$meta = array(
			'team'       => 'Team-M',
			'start_date' => gmdate( 'Y-m-d', strtotime( 'yesterday' ) ),
			'recurring'  => 'weekly',
		);
		$this->create_meeting( $meta );
		$this->create_meeting( $meta );

		$this->assertStringContainsString( 'href="' . home_url( '/meetings/#team-m' ) . '"', do_shortcode( '[meeting_time team="Team-M" limit=1 /]' ) );
	}

	/**
	 * Test that meetings are sorted by their next date only when the query doesn't ask for an order.
	 */
	public function test_sort_filter_keeps_requested_order(): void {
		$later  = $this->create_meeting(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '+10 days' ) ),
				'recurring'  => 'weekly',
			),
			'Alpha'
		);
		$sooner = $this->create_meeting(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
				'recurring'  => 'weekly',
			),
			'Beta'
		);
		$args   = array(
			'post_type' => 'meeting',
			'post__in'  => array( $later->ID, $sooner->ID ),
		);

		$by_next_date = ( new WP_Query( $args ) )->posts;
		$this->assertSame( array( 'Beta', 'Alpha' ), wp_list_pluck( $by_next_date, 'post_title' ) );

		$args['orderby'] = 'title';
		$args['order']   = 'ASC';

		$by_title = ( new WP_Query( $args ) )->posts;
		$this->assertSame( array( 'Alpha', 'Beta' ), wp_list_pluck( $by_title, 'post_title' ) );
	}

	/**
	 * Test that the occurrences include one on the end date, whatever the time of day.
	 */
	public function test_future_occurrences_include_end_date(): void {
		$meeting = $this->create_meeting(
			array(
				'start_date' => '2026-01-07',
				'end_date'   => '2026-01-14',
				'time'       => '23:59:00',
				'recurring'  => 'weekly',
			)
		);

		$occurrences = Meeting_Post_Type::getInstance()->get_future_occurrences( $meeting, null, array( 'month' => '2026-01-01' ) );

		$this->assertSame( array( '2026-01-07', '2026-01-14' ), $occurrences );
	}

	/**
	 * Test that the meta query keeps a recurring meeting through its end date, like get_future_occurrences().
	 */
	public function test_meta_query_includes_meeting_ending_today(): void {
		$today = gmdate( 'Y-m-d' );
		$this->create_meeting(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( $today . ' -7 days' ) ),
				'end_date'   => $today,
				'recurring'  => 'weekly',
			),
			'Ending today'
		);
		$this->create_meeting(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( $today . ' -8 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) ),
				'recurring'  => 'weekly',
			),
			'Ended yesterday'
		);

		$meetings = get_posts(
			array(
				'post_type'   => 'meeting',
				'numberposts' => -1,
				'meta_query'  => Meeting_Post_Type::getInstance()->meeting_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The query under test.
			)
		);

		$this->assertSame( array( 'Ending today' ), wp_list_pluck( $meetings, 'post_title' ) );
	}

	/**
	 * Test that an unparseable end date ends the meeting, as it does in the meta query.
	 */
	public function test_future_occurrences_with_unparseable_end_date(): void {
		$meeting = $this->create_meeting(
			array(
				'start_date' => '2026-01-07',
				'end_date'   => 'never',
				'recurring'  => 'weekly',
			)
		);

		$this->assertSame( array(), Meeting_Post_Type::getInstance()->get_future_occurrences( $meeting, null, array( 'month' => '2026-01-01' ) ) );
	}

	/**
	 * Test that a one-off meeting before the period isn't listed in it.
	 */
	public function test_future_occurrences_exclude_past_one_off_meeting(): void {
		$with_end_date    = $this->create_meeting(
			array(
				'start_date' => '2020-05-14',
				'end_date'   => '2020-05-14',
				'time'       => '19:00:00',
			)
		);
		$without_end_date = $this->create_meeting( array( 'start_date' => '2020-05-14' ) );

		$meeting_post_type = Meeting_Post_Type::getInstance();
		$this->assertSame( array(), $meeting_post_type->get_future_occurrences( $with_end_date, null, array( 'month' => '2026-10-01' ) ) );
		$this->assertSame( array(), $meeting_post_type->get_future_occurrences( $without_end_date, null, array( 'month' => '2026-10-01' ) ) );
	}

	/**
	 * Test that a one-off meeting in the period is listed, whatever its time of day.
	 */
	public function test_future_occurrences_include_one_off_meeting(): void {
		$meeting = $this->create_meeting(
			array(
				'start_date' => '2026-10-14',
				'end_date'   => '2026-10-14',
				'time'       => '23:00:00',
			)
		);

		$this->assertSame( array( '2026-10-14' ), Meeting_Post_Type::getInstance()->get_future_occurrences( $meeting, null, array( 'month' => '2026-10-01' ) ) );
	}

	/**
	 * Test that string and out-of-range week values don't break an "occurrence" meeting.
	 */
	public function test_next_occurrence_with_unexpected_week_values(): void {
		// The last Wednesday of each month, with a week that doesn't exist.
		$meeting = $this->create_occurrence_meeting( '2020-01-15', array( 7, '-1' ) );

		$next = Meeting_Post_Type::getInstance()->get_next_occurrence( $meeting, '2026-10-01 00:00:00 +00:00' );

		$this->assertSame( '2026-10-28', $next );
	}

	/**
	 * Test that a meeting with an unparseable time doesn't occur on any date, instead of throwing.
	 */
	public function test_is_valid_occurrence_with_unparseable_time(): void {
		$meeting = $this->create_meeting(
			array(
				'start_date' => '2026-01-07',
				'time'       => 'TBD',
				'recurring'  => 'weekly',
			)
		);

		$this->assertFalse( Meeting_Post_Type::getInstance()->is_valid_occurrence( $meeting->ID, '2026-01-14' ) );
	}

	/**
	 * Create a meeting that recurs on given weeks of the month, on the start date's weekday.
	 *
	 * @param string $start_date Start date, Y-m-d.
	 * @param array  $weeks      Weeks of the month, 1-4, or -1 for the last.
	 * @return WP_Post
	 */
	private function create_occurrence_meeting( string $start_date, array $weeks ): WP_Post {
		return $this->create_meeting(
			array(
				'start_date' => $start_date,
				'recurring'  => 'occurrence',
				'occurrence' => $weeks,
			)
		);
	}

	/**
	 * Create a published meeting.
	 *
	 * @param array  $meta  Meeting meta, merged into a 15:00 one-off meeting for Team-A.
	 * @param string $title Meeting title.
	 * @return WP_Post
	 */
	private function create_meeting( array $meta, string $title = 'Meeting' ): WP_Post {
		return get_post(
			self::factory()->post->create(
				array(
					'post_title'  => $title,
					'post_type'   => 'meeting',
					'post_status' => 'publish',
					'meta_input'  => array_merge(
						array(
							'team'       => 'Team-A',
							'start_date' => '',
							'end_date'   => '',
							'time'       => '15:00:00',
							'recurring'  => '',
						),
						$meta
					),
				)
			)
		);
	}
}
