<?php
/**
 * Tests for the meetings list table in wp-admin.
 *
 * @package Meeting_Calendar
 */

declare( strict_types = 1 );

use WordPressdotorg\Meeting_Calendar\Admin;

/**
 * Class MeetingAdminTest
 */
class MeetingAdminTest extends WP_UnitTestCase {

	/**
	 * The admin screens instance.
	 *
	 * @var Admin
	 */
	protected Admin $admin;

	/**
	 * Meeting IDs keyed by team name.
	 *
	 * @var int[]
	 */
	protected array $meeting_ids = array();

	/**
	 * Set up the test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin = Admin::get_instance();
		set_current_screen( 'edit-meeting' );

		foreach ( array( 'Core', 'core', 'Polyglots', 'Hosting' ) as $team ) {
			$this->meeting_ids[ $team ] = self::factory()->post->create(
				array(
					'post_type'  => 'meeting',
					'meta_input' => array( 'team' => $team ),
				)
			);
		}

		wp_trash_post( $this->meeting_ids['Hosting'] );
	}

	/**
	 * Tear down the test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['team'] );

		parent::tearDown();
	}

	/**
	 * Test that the dropdown lists each team once and preselects the current filter.
	 */
	public function test_team_filter_dropdown(): void {
		$_GET['team'] = 'polyglots';

		ob_start();
		$this->admin->meeting_team_filter_dropdown( 'meeting' );
		$output = ob_get_clean();

		$this->assertSame( 1, substr_count( strtolower( $output ), 'value="core"' ) );
		$this->assertMatchesRegularExpression( '/value="Polyglots"\s+selected/', $output );
	}

	/**
	 * Test that teams whose meetings are all trashed are not listed.
	 */
	public function test_team_filter_dropdown_skips_trashed_meetings(): void {
		ob_start();
		$this->admin->meeting_team_filter_dropdown( 'meeting' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'value="Hosting"', $output );
	}

	/**
	 * Test that the dropdown is not shown for other post types.
	 */
	public function test_team_filter_dropdown_other_post_type(): void {
		ob_start();
		$this->admin->meeting_team_filter_dropdown( 'post' );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Test that selecting a team limits the main list table query to that team, regardless of case.
	 */
	public function test_filter_by_team(): void {
		$_GET['team'] = 'core';

		$query = $this->get_main_query();
		$query->query(
			array(
				'post_type'      => 'meeting',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		$this->assertEqualsCanonicalizing(
			array( $this->meeting_ids['Core'], $this->meeting_ids['core'] ),
			$query->posts
		);
	}

	/**
	 * Test that an existing meta query is restricted to the team, not extended with it.
	 */
	public function test_filter_by_team_restricts_existing_meta_query(): void {
		$_GET['team'] = 'core';
		$existing     = array(
			'relation' => 'OR',
			array(
				'key'   => 'location',
				'value' => '#core',
			),
		);

		$query = $this->get_main_query();
		$query->set( 'meta_query', $existing );
		$this->admin->meeting_filter_by_team( $query );

		$this->assertSame(
			array(
				'relation' => 'AND',
				$existing,
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
		$this->admin->meeting_filter_by_team( $query );

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
