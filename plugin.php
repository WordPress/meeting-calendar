<?php
/**
 * Plugin Name: Meeting Calendar
 * Description: This provides a way of scheduling recurring meetings, and displaying a calendar or timetable..
 * Plugin URI: https://github.com/Automattic/meeting-calendar
 * Author: Automattic
 * Version: 1.1.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WordPressdotorg\Meeting_Calendar;

/**
 * Retrieves meetings
 *
 * @param integer $per_page Number of meetings per page.
 * @return string List of meetings in JSON format
 */
function get_meeting_data( $per_page ) {
	$date = date( 'Y-m-d', strtotime( 'first day of this month' ) );
	$request = new \WP_REST_Request( 'GET', '/wp/v2/meetings/from/' . $date );
	$request->set_query_params( [ 'per_page' => $per_page ] );
	$response = rest_do_request( $request );
	$server = rest_get_server();
	$data = $server->response_to_data( $response, false );
	return wp_json_encode( $data );
}

/**
 * Render the block content (html) on the frontend of the site.
 *
 * @param array  $attributes
 * @param string $content
 * @return string HTML output used by the calendar JS.
 */
function render_callback( $attributes, $content ) {
	$meetings = get_meeting_data( 12 );
	return sprintf(
		'<div class="alignwide wporg-block-meeting-calendar" id="%s" data-meetings="%s">Loading Calendar ...</div>',
		'wporg-meeting-calendar-js',
		htmlspecialchars( $meetings, ENT_QUOTES )
	);
}

/**
 * Register scripts, styles, and block.
 */
function register_assets() {
	$block_deps_path = __DIR__ . '/build/index.asset.php';
	$frontend_deps_path = __DIR__ . '/build/calendar.asset.php';
	if ( ! file_exists( $block_deps_path ) || ! file_exists( $frontend_deps_path ) ) {
		return;
	}

	$block_info = require $block_deps_path;
	$frontend_info = require $frontend_deps_path;

	// Register our block script with WordPress.
	wp_register_script(
		'wporg-calendar-block-script',
		plugins_url('build/index.js', __FILE__),
		$block_info['dependencies'],
		$block_info['version'],
		false
	);

	// Register our block's base CSS.
	wp_register_style(
		'wporg-calendar-block-style',
		plugins_url( 'style.css', __FILE__ ),
		[],
		$block_info['version']
	);

	// No frontend scripts in the editor
	if( ! is_admin() ) {
		wp_register_script(
			'wporg-calendar-script',
			plugin_dir_url( __FILE__ ) . 'build/calendar.js',
			$frontend_info['dependencies'],
			$frontend_info['version'],
			false
		);

		wp_register_style(
			'wporg-calendar-style',
			plugin_dir_url( __FILE__ ) . 'build/calendar.css',
			array( 'wp-components' ),
			$frontend_info['version']
		);
	}

	// Enqueue the script in the editor.
	register_block_type(
		'wporg-meeting-calendar/main',
		array(
			'editor_script' => 'wporg-calendar-block-script',
			'editor_style' => 'wporg-calendar-block-style',
			'script' => 'wporg-calendar-script',
			'style' => 'wporg-calendar-style',
			'render_callback' => __NAMESPACE__ . '\render_callback',
		)
	);
}
add_action('init', __NAMESPACE__ . '\register_assets');

/**
 * Set up the Meetings post type.
 */
function init() {
	require_once( __DIR__ . '/includes/wporg-meeting-posttype.php' );
	new \Meeting_Post_Type();
}
add_action('plugins_loaded', __NAMESPACE__ . '\init');

/**
 * Set up the ICS support.
 */
function ics_init() {
	require __DIR__ . '/includes/ical-functions.php';
	require __DIR__ . '/includes/ical-generator-functions.php';

}
add_action('plugins_loaded', __NAMESPACE__ . '\ics_init');

/**
 * First-Install activation hook.
 *
 * Creates some sample data, and sets up the Rewrite rules.
 */
function install() {
	// We need the CPT to be registered to install.
	init();
	$meeting_post_type = new \Meeting_Post_Type();
	$meeting_post_type->getInstance()->register_meeting_post_type();

	require_once( __DIR__ . '/includes/wporg-meeting-install.php' );
	wporg_meeting_install();

	ics_init();
	ICS\on_activate();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\install' );
