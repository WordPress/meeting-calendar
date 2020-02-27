<?php
namespace WordPressdotorg\Meeting_Calendar\ICS;

define( 'QUERY_KEY',      'meeting_ical' );
define( 'QUERY_TEAM_KEY', 'meeting_team' );

/**
 * Activation hook for ICS Support.
 */
function on_activate() {
	add_rewrite_rules();
	flush_rewrite_rules();
}

/**
 * Add Rewrite rules to allow for ICS access.
 *
 * This adds rules such as /meetings.ics and /meetings-$team.ics
 */
function add_rewrite_rules() {
	add_rewrite_rule(
		'^meetings(?:-([a-zA-Z\d\s_-]+))?\.ics$',
		array(
			QUERY_KEY      => 1,
			QUERY_TEAM_KEY => '$matches[1]',
		),
		'top'
	);
}
add_action( 'init', __NAMESPACE__ . '\add_rewrite_rules' );

/**
 * Add the Query Var for the Rewrite Rules.
 */
function query_vars( $query_vars ) {
	$query_vars[] = QUERY_KEY;
	$query_vars[] = QUERY_TEAM_KEY;
	return $query_vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\query_vars' );

/**
 * Main handler for ICS output for matching requests.
 */
function parse_request( $request ) {
	if ( ! isset( $request->query_vars[ QUERY_KEY ] ) ) {
		return;
	}

	$team = strtolower( $request->query_vars[ QUERY_TEAM_KEY ] );

	// Grab the meetings, optionally 
	$posts = get_meeting_posts( $team );

	// Output a 404 if there's no meetings, but still generate a ICS feed.
	if ( ! $posts ) {
		header(
			( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 404 No Meetings Found',
			true,
			404
		);
	}

	/**
	 * If the calendar has a 'method' property, the 'Content-Type' header must also specify it
	 */
	header( 'Content-Type: text/calendar; charset=utf-8; method=publish' );
	header( 'Content-Disposition: inline; filename=calendar.ics' );
	echo Generator\generate( $posts );

	exit;
}
add_action( 'parse_request', __NAMESPACE__ . '\parse_request' );

/**
 * Get all meetings for a team. If the 'team' parameter is empty, all meetings are returned.
 *
 * @param string $team Name of the team to fetch meetings for.
 * @return array
 */
function get_meeting_posts( $team = '' ) {

	$meta_query = \Meeting_Post_Type::getInstance()->meeting_meta_query();
	if ( $team ) {
		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => 'team',
				'value'   => $team,
				'compare' => 'EQUALS',
			],
			$meta_query
		];
	}

	$query = new \WP_Query( [
		'post_type'   => 'meeting',
		'post_status' => 'publish',
		'nopaging'    => true,
		'meta_query'  => $meta_query,
	] );

	return $query->get_posts();
}
