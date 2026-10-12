<?php
namespace WordPressdotorg\Meeting_Calendar\ICS;

define( 'QUERY_KEY', 'meeting_ical' );
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

	status_header( get_feed_status( $posts, $team ) );

	// If the calendar has a 'method' property, the 'Content-Type' header must also specify it
	header( 'Content-Type: text/calendar; charset=utf-8; method=publish' );
	$filename = sanitize_file_name( 'meetings' . ( $team ? "-{$team}" : '' ) . '.ics' );
	header( 'Content-Disposition: inline; filename="' . $filename . '"' );

	// phpcs:ignore -- Direct output okay.
	echo Generator\generate( $posts, $team );

	exit;
}
add_action( 'parse_request', __NAMESPACE__ . '\parse_request' );

/**
 * Get the HTTP status for a feed.
 *
 * Only 404 for teams that never had meetings. A team without upcoming meetings
 * still gets an empty feed, so existing subscribers see its meetings go away.
 *
 * @param WP_Post[] $posts The upcoming meetings in the feed.
 * @param string    $team  Name of the team, or empty for all meetings.
 * @return int
 */
function get_feed_status( $posts, $team ) {
	if ( ! $posts && $team && ! is_known_team( $team ) ) {
		return 404;
	}

	return 200;
}

/**
 * Whether any meeting, including past and unpublished ones, belongs to a team.
 *
 * Matches the teams offered in the admin list table filter: trashed meetings don't count.
 *
 * @param string $team Name of the team.
 * @return bool
 */
function is_known_team( $team ) {
	$query = new \WP_Query(
		array(
			'post_type'      => 'meeting',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => 'team',
					'value' => $team,
				),
			),
		)
	);

	return (bool) $query->posts;
}

/**
 * Get all meetings for a team. If the 'team' parameter is empty, all meetings are returned.
 *
 * @param string $team Name of the team to fetch meetings for.
 * @return array
 */
function get_meeting_posts( $team = '' ) {
	$meta_query = \Meeting_Post_Type::getInstance()->meeting_meta_query();
	if ( $team ) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => 'team',
				'value'   => $team,
				'compare' => 'EQUALS',
			),
			$meta_query,
		);
	}

	$query = new \WP_Query(
		array(
			'post_type'        => 'meeting',
			'post_status'      => 'publish',
			'nopaging'         => true,
			'meta_query'       => $meta_query,
			// Avoid re-ordering the events.
			'suppress_filters' => true,
		)
	);

	$posts = $query->get_posts();
	// Update each post with next dates – needs to be called here because we `suppress_filters` above.
	\Meeting_Post_Type::getInstance()->meeting_set_next_meeting( $posts );

	return $posts;
}
