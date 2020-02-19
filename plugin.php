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


/**
 * Retrieves meetings
 *
 * @param integer $perPage Number of meetings per page.
 * @return string List of meetings in JSON format
 */
function getMeetingData( $perPage ) {
	$request = new WP_REST_Request( 'GET', '/wp/v2/meeting' );
	$request->set_query_params( [ 'per_page' => $perPage ] );
	$response = rest_do_request( $request );
	$server = rest_get_server();
	$data = $server->response_to_data( $response, false );
	return wp_json_encode( $data );
}

function wporg_meeting_calendar_callback( $attributes, $content ) {
	$meetings = getMeetingData( 12 );
    return sprintf(
        '<div id="%s" data-meetings="%s">Loading Calendar ...</div>',
        'wporg-meeting-calendar-js',
        htmlspecialchars( $meetings, ENT_QUOTES ) );
}

function wporg_meeting_calendar_register() {

	// Register our block script with WordPress
	wp_register_script(
		'wporg-meeting-calendar',
		plugins_url('build/index.js', __FILE__)
	);

	// Register our block's base CSS
	wp_register_style(
		'wporg-meeting-calendar-style',
		plugins_url( 'style.css', __FILE__ )
	);

	// Enqueue the script in the editor
	register_block_type( 'wporg-meeting-calendar/main', array(
		'editor_script' => 'wporg-meeting-calendar',
		'style' => 'wporg-meeting-calendar-style',
		'render_callback' => 'wporg_meeting_calendar_callback'
	) );
}

function wporg_meeting_calendar_init_back_end() {
	require_once( __DIR__ . '/includes/wporg-meeting-posttype.php' );
	new Meeting_Post_Type();
}

// Create some sample data on first install
function wporg_meeting_calendar_install() {

	// We need the CPT to be registered to install
	wporg_meeting_calendar_init_back_end();
	$meeting_post_type = new Meeting_Post_Type();
	$meeting_post_type->getInstance()->register_meeting_post_type();

	require_once( __DIR__ . '/includes/wporg-meeting-install.php' );
	wporg_meeting_install();
}

register_activation_hook( __FILE__, 'wporg_meeting_calendar_install' );
add_action('plugins_loaded', 'wporg_meeting_calendar_init_back_end');
add_action('init', 'wporg_meeting_calendar_register');

// TODO - We probably don't always want to load this if they don't have a calendar block on the page
function enqueue_calendar_frontend() {
	// TODO - Assets should probably not be CDN hosted
	wp_enqueue_style( 'wporg-meeting-fullcalendar-core-css', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.css' );
	wp_enqueue_style( 'wporg-meeting-fullcalendar-daygrid-css', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.css' );
	wp_enqueue_style( 'wporg-meeting-fullcalendar-list-css', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/list/main.min.css' );
	wp_enqueue_style( 'wporg-meeting-fullcalendar-timegrid-css', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/timegrid/main.min.css' );

	wp_enqueue_script( 'wporg-meeting-fullcalendar-core-script', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.js' );
	wp_enqueue_script( 'wporg-meeting-fullcalendar-daygrid-script', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.js' );
	wp_enqueue_script( 'wporg-meeting-fullcalendar-list-script', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/list/main.min.js' );
	wp_enqueue_script( 'wporg-meeting-fullcalendar-timegrid-script', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/timegrid/main.min.js' );
	wp_enqueue_script( 'wporg-meeting-fullcalendar-moment-script', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/moment/main.min.js' );

	wp_enqueue_script( 'wporg-meeting-calendar-script', plugin_dir_url( __FILE__ ) . 'assets/js/calendar.js' );
}

add_action('wp_footer', 'enqueue_calendar_frontend');
