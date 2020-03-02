<?php
/*
* Install hook with sample post data.
*/

function wporg_meeting_install() {

	if ( wp_count_posts('meeting')->publish + wp_count_posts('meeting')->draft <= 0 ) {
		// No posts of any status exist, so insert a few sample meetings.

		$meeting_ids = array();

		$meeting_ids[] = wp_insert_post( array(
			'post_title' => __( 'A weekly meeting', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-A',
				'start_date' => '2020-01-01',
				'end_date'   => '',
				'time'       => '14:00:00',
				'recurring'  => 'weekly',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		$meeting_ids[] = wp_insert_post( array(
			'post_title' => __( 'A monthly meeting', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-B',
				'start_date' => '2020-01-01',
				'end_date'   => '',
				'time'       => '15:00:00',
				'recurring'  => 'monthly',
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		$meeting_ids[] = wp_insert_post( array(
			'post_title' => __( 'Third Wednesday of each month', 'wporg-meeting-calendar' ),
			'post_type'  => 'meeting',
			'post_status' => 'publish',
			'meta_input' => array(
				'team'       => 'Team-C',
				'start_date' => '2020-01-01',
				'end_date'   => '',
				'time'       => '16:00:00',
				'recurring'  => 'occurrence',
				'occurrence' => array( 3 ),
				'link'       => 'wordpress.org',
				'location'   => '#meta',
				),
		) );

		flush_rewrite_rules();

		return $meeting_ids;
	}
	
}
