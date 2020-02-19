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

function a8c_meeting_calendar_register() {

	// Register our block script with WordPress
	wp_register_script(
		'a8c-meeting-calendar',
		plugins_url('build/index.js', __FILE__)
	);

	// Register our block's base CSS
	wp_register_style(
		'a8c-meeting-calendar-style',
		plugins_url( 'style.css', __FILE__ )
	);

	// Enqueue the script in the editor
	register_block_type('a8c-meeting-calendar/main', array(
		'editor_script' => 'a8c-meeting-calendar',
		'editor_style' => 'a8c-meeting-calendar-edit-style',
		'style' => 'a8c-meeting-calendar-style'
	));
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
add_action('init', 'a8c_meeting_calendar_register');
