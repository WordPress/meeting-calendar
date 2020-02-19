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
function a8c_meeting_calendar_callback( $attributes, $content ) {
	$temporaryData = '[{"id":2673,"date":"2020-01-20T22:37:52","date_gmt":"2020-01-20T22:37:52","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2673"},"modified":"2020-01-20T22:37:52","modified_gmt":"2020-01-20T22:37:52","slug":"5-4-bug-scrub-week-1","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-1\/","title":{"rendered":"5.4 Bug Scrub Week 1"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2673"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2673"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2674,"date":"2020-01-20T22:38:43","date_gmt":"2020-01-20T22:38:43","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2674"},"modified":"2020-01-20T22:38:43","modified_gmt":"2020-01-20T22:38:43","slug":"5-4-bug-scrub-week-2","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-2\/","title":{"rendered":"5.4 Bug Scrub Week 2"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2674"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2674"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2675,"date":"2020-01-20T22:39:25","date_gmt":"2020-01-20T22:39:25","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2675"},"modified":"2020-01-20T22:39:25","modified_gmt":"2020-01-20T22:39:25","slug":"5-4-bug-scrub-week-3","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-3\/","title":{"rendered":"5.4 Bug Scrub Week 3"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2675"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2675"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2676,"date":"2020-01-20T22:40:05","date_gmt":"2020-01-20T22:40:05","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2676"},"modified":"2020-01-20T22:40:05","modified_gmt":"2020-01-20T22:40:05","slug":"5-4-bug-scrub-week-4","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-4\/","title":{"rendered":"5.4 Bug Scrub Week 4"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2676"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2676"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2677,"date":"2020-01-20T22:40:44","date_gmt":"2020-01-20T22:40:44","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2677"},"modified":"2020-01-20T22:40:44","modified_gmt":"2020-01-20T22:40:44","slug":"5-4-bug-scrub-week-5","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-5\/","title":{"rendered":"5.4 Bug Scrub Week 5"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2677"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2677"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2690,"date":"2020-01-28T18:15:05","date_gmt":"2020-01-28T18:15:05","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2690"},"modified":"2020-01-28T18:16:36","modified_gmt":"2020-01-28T18:16:36","slug":"block-based-themes-chat","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/block-based-themes-chat\/","title":{"rendered":"Block Based Themes Chat"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2690"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2690"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2689,"date":"2020-01-28T18:13:16","date_gmt":"2020-01-28T18:13:16","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2689"},"modified":"2020-01-28T18:17:23","modified_gmt":"2020-01-28T18:17:23","slug":"themes-team-triage","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/themes-team-triage\/","title":{"rendered":"Themes Team Triage"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2689"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2689"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2678,"date":"2020-01-20T22:41:26","date_gmt":"2020-01-20T22:41:26","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2678"},"modified":"2020-01-20T22:41:26","modified_gmt":"2020-01-20T22:41:26","slug":"5-4-bug-scrub-week-6","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-6\/","title":{"rendered":"5.4 Bug Scrub Week 6"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2678"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2678"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2679,"date":"2020-01-20T22:42:08","date_gmt":"2020-01-20T22:42:08","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2679"},"modified":"2020-01-20T22:42:08","modified_gmt":"2020-01-20T22:42:08","slug":"5-4-bug-scrub-week-7","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/5-4-bug-scrub-week-7\/","title":{"rendered":"5.4 Bug Scrub Week 7"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2679"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2679"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}},{"id":2612,"date":"2019-10-17T14:43:02","date_gmt":"2019-10-17T14:43:02","guid":{"rendered":"https:\/\/make.wordpress.org\/?post_type=meeting&#038;p=2612"},"modified":"2020-01-14T11:53:45","modified_gmt":"2020-01-14T11:53:45","slug":"polyglots-team-monthly-chat-americas","status":"publish","type":"meeting","link":"https:\/\/make.wordpress.org\/meetings\/polyglots-team-monthly-chat-americas\/","title":{"rendered":"Polyglots Team Monthly Chat (Americas)"},"template":"","_links":{"self":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting\/2612"}],"collection":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/meeting"}],"about":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/types\/meeting"}],"wp:attachment":[{"href":"https:\/\/make.wordpress.org\/wp-json\/wp\/v2\/media?parent=2612"}],"curies":[{"name":"wp","href":"https:\/\/api.w.org\/{rel}","templated":true}]}}]';

    $scripts = [
        '<script src="' . plugins_url( '/assets/calendar.js', dirname( __FILE__ ) ) . '"></script>',
        '<script>window.meetingData = ' . $temporaryData . '</script>',
	];

	add_action('wp_head', 'testFunction' );


    return sprintf(
        '<div id="%s" data-meetings="%s">Loading Calendar ...</div>',
        'a8c-meeting-calendar-js',
        htmlspecialchars( $temporaryData, ENT_QUOTES ) );
}

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
		'style' => 'a8c-meeting-calendar-style',
		'render_callback' => 'a8c_meeting_calendar_callback'
	));
}

add_action('init', 'a8c_meeting_calendar_register');

// TODO - We probably don't always want to load this if they don't have a calendar block on the page
function enqueue_calendar_frontend() {
	wp_enqueue_script( 'a8c-meeting-calendar-js', plugin_dir_url( __FILE__ ) . 'assets/calendar.js' );
  }

  add_action('wp_footer', 'enqueue_calendar_frontend');
