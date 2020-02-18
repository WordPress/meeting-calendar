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
		plugins_url('build/index.js', __FILE__),
		array('wp-blocks', 'wp-element', 'wp-editor')
	);

	// Register our block's base CSS
	wp_register_style(
		'a8c-meeting-calendar-style',
		plugins_url( 'style.css', __FILE__ )
	);

	// Register our block's editor-specific CSS
	wp_register_style(
		'a8c-meeting-calendar-edit-style',
		plugins_url('style.css', __FILE__),
		array( 'wp-edit-blocks' )
	);

	// Enqueue the script in the editor
	register_block_type('a8c-meeting-calendar/main', array(
		'editor_script' => 'a8c-meeting-calendar',
		'editor_style' => 'a8c-meeting-calendar-edit-style',
		'style' => 'a8c-meeting-calendar-style'
	));
}

add_action('init', 'a8c_meeting_calendar_register');
