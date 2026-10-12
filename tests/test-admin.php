<?php
/**
 * Tests for the meetings list table in wp-admin.
 *
 * @package Meeting_Calendar
 */

declare( strict_types = 1 );

/**
 * Class MeetingAdminTest
 */
class MeetingAdminTest extends WP_UnitTestCase {

	/**
	 * The meeting post type instance.
	 *
	 * @var Meeting_Post_Type
	 */
	protected Meeting_Post_Type $mpt;

	/**
	 * Set up the test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mpt = Meeting_Post_Type::getInstance();
		set_current_screen( 'edit-meeting' );

		foreach ( array( 'Core', 'core', 'Polyglots' ) as $team ) {
			self::factory()->post->create(
				array(
					'post_type'  => 'meeting',
					'meta_input' => array( 'team' => $team ),
				)
			);
		}
	}

	/**
	 * Tear down the test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['team'] );
		set_current_screen( 'front' );

		parent::tearDown();
	}

	/**
	 * Test that the dropdown lists each team once and preselects the current filter.
	 */
	public function test_team_filter_dropdown(): void {
		$_GET['team'] = 'polyglots';

		ob_start();
		$this->mpt->meeting_team_filter_dropdown( 'meeting' );
		$output = ob_get_clean();

		$this->assertSame( 1, substr_count( strtolower( $output ), 'value="core"' ) );
		$this->assertMatchesRegularExpression( '/value="Polyglots"\s+selected/', $output );
	}

	/**
	 * Test that the dropdown is not shown for other post types.
	 */
	public function test_team_filter_dropdown_other_post_type(): void {
		ob_start();
		$this->mpt->meeting_team_filter_dropdown( 'post' );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Test that selecting a team limits the main list table query to that team.
	 */
	public function test_filter_by_team(): void {
		$_GET['team'] = 'core';

		$query = $this->get_main_query();
		$this->mpt->meeting_filter_by_team( $query );

		$this->assertSame(
			array(
				array(
					'key'   => 'team',
					'value' => 'core',
				),
			),
			$query->get( 'meta_query' )
		);
	}

	/**
	 * Test that the query is left alone when no team is selected.
	 */
	public function test_filter_by_team_without_selection(): void {
		$query = $this->get_main_query();
		$this->mpt->meeting_filter_by_team( $query );

		$this->assertSame( '', $query->get( 'meta_query' ) );
	}

	/**
	 * Get an unexecuted meetings query that is flagged as the main query.
	 *
	 * @return WP_Query
	 */
	private function get_main_query(): WP_Query {
		global $wp_the_query;

		$query = new WP_Query();
		$query->parse_query( array( 'post_type' => 'meeting' ) );
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- is_main_query() compares against it.

		return $query;
	}
}
