<?php
namespace WordPressdotorg\Meeting_Calendar;

class ICal {

	const QUERY_KEY      = 'meeting_ical';
	const QUERY_TEAM_KEY = 'meeting_team';

	/**
	 * @var Plugin The singleton instance.
	 */
	private static $instance;

	/**
	 * Returns always the same instance of this plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::$instance = new Plugin();
		}
		return self::$instance;
	}

	/**
	 * Instantiates a new Plugin object.
	 */
	private function __construct() {
		register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivate' ) );

		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );

		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	public function on_activate() {
		$this->add_rewrite_rules();
		flush_rewrite_rules();
	}

	public function on_deactivate() {
		flush_rewrite_rules(); // remove custom rewrite rule
	}

	/**
	 * Add Rewrite rules to allow for ICS access.
	 *
	 * This adds rules such as /meetings.ics and /meetings-$team.ics
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^meetings(-[a-zA-Z\d\s_-]+)?\.ics$',
			array(
				self::QUERY_KEY      => 1,
				self::QUERY_TEAM_KEY => '$matches[1]',
			),
			'top'
		);
	}

	public function parse_request( $request ) {
		if ( ! isset( $request->query_vars[ self::QUERY_KEY ] ) ) {
			return;
		}

		$team = strtolower( $request->query_vars[ self::QUERY_TEAM_KEY ] );

		// Generate a calendar if such a team exists
		$ical = $this->get_ical_contents( $team );

		if ( null !== $ical ) {
			/**
			 * If the calendar has a 'method' property, the 'Content-Type' header must also specify it
			 */
			header( 'Content-Type: text/calendar; charset=utf-8; method=publish' );
			header( 'Content-Disposition: inline; filename=calendar.ics' );
			echo $ical;
			exit;
		}

		return;
	}

	public function query_vars( $query_vars ) {
		$query_vars[] = self::QUERY_KEY;
		$query_vars[] = self::QUERY_TEAM_KEY;
		return $query_vars;
	}

	private function get_ical_contents( $team ) {
		$posts = $this->get_meeting_posts( $team );

		// Don't generate a calendar if there are no meetings for that team
		if ( empty( $posts ) ) {
			return null;
		}

		$ical_generator = new ICAL_Generator();
		return $ical_generator->generate( $posts );
	}

	/**
	 * Get all meetings for a team. If the 'team' parameter is empty, all meetings are returned.
	 *
	 * @param string $team Name of the team to fetch meetings for.
	 * @return array
	 */
	private function get_meeting_posts( $team = '' ) {
		$query = new Meeting_Query( $team );

		return $query->get_posts();
	}
}

