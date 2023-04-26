<?php
/**
 * Create the meeting post type and assorted filters for https://make.wordpress.org/meetings
 */

// @todo: Fix these phpcs errors correctly.
// phpcs:disable Squiz.Commenting.FunctionComment.Missing, WordPress.Security.EscapeOutput.UnsafePrintingFunction, WordPress.Security.EscapeOutput.OutputNotEscaped

use function WordPressdotorg\Meeting_Calendar\ICS\Generator\get_frequency;

if ( ! class_exists( 'Meeting_Post_Type' ) ) :
	class Meeting_Post_Type {

		protected static $instance = null;

		// phpcs:ignore -- CamelCase name OK.
		public static function getInstance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public static function init() {
			$mpt = self::getInstance();
			add_action( 'init', array( $mpt, 'register_meeting_post_type' ) );
			add_action( 'init', array( $mpt, 'register_meta' ) );
			add_action( 'rest_api_init', array( $mpt, 'register_rest_field' ) );
			add_action( 'rest_api_init', array( $mpt, 'register_rest_routes' ) );
			add_action( 'save_post_meeting', array( $mpt, 'save_meta_boxes' ), 10, 2 );
			add_filter( 'pre_get_posts', array( $mpt, 'meeting_archive_page_query' ) );
			add_filter( 'the_posts', array( $mpt, 'meeting_set_next_meeting' ) );
			add_filter( 'the_posts', array( $mpt, 'meeting_sort_upcoming_meetings' ), 10, 2 );
			add_filter( 'manage_meeting_posts_columns', array( $mpt, 'meeting_add_custom_columns' ) );
			add_action( 'manage_meeting_posts_custom_column', array( $mpt, 'meeting_custom_columns' ), 11, 2 );
			add_action( 'admin_head', array( $mpt, 'meeting_column_width' ) );
			add_action( 'admin_bar_menu', array( $mpt, 'add_edit_meetings_item_to_admin_bar' ), 80 );
			add_action( 'wp_enqueue_scripts', array( $mpt, 'add_edit_meetings_icon_to_admin_bar' ) );
			add_shortcode( 'meeting_time', array( $mpt, 'meeting_time_shortcode' ) );

			// shortcodes aren't normally processed in widgets, add this to allow meeting_type in text widgets
			// TODO make this more specific and only add this shortcode to widget processing
			add_filter( 'widget_text', 'do_shortcode' );
		}

		public function meeting_column_width() { ?>
			<style type="text/css">
				.column-team { width: 10em !important; overflow: hidden; }
				#meeting-info .recurring label { padding-right: 10px; }
			</style>
			<?php
		}

		public function meeting_add_custom_columns( $columns ) {
			$columns = array_slice( $columns, 0, 1, true )
				+ array( 'team' => __( 'Team', 'wporg-meeting-calendar' ) )
				+ array_slice( $columns, 1, -1, true )
				+ array( 'wptv_url' => __( 'WPTV URL', 'wporg-meeting-calendar' ) )
				+ array_slice( $columns, -1, null, true );
			return $columns;
		}

		public function meeting_custom_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'team':
					$team = get_post_meta( $post_id, 'team', true );
					echo esc_html( $team );
					break;
			}
			switch ( $column ) {
				case 'wptv_url':
					$wptv_url = get_post_meta( $post_id, 'wptv_url', true );
					echo esc_html( $wptv_url ?: '—' );
					break;
			}
		}

		public function meeting_archive_page_query( $query ) {
			if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'meeting' ) ) {
				return;
			}
			// turn off paging on the archive page, to show all meetings in the table
			$query->set( 'nopaging', true );

			// meta query to eliminate expired meetings from query
			$query->set( 'meta_query', $this->meeting_meta_query() );
		}

		public function meeting_set_next_meeting( $posts ) {
			$is_meeting_list = array_reduce( $posts, function( $is_meeting, $post ) {
				return $is_meeting && 'meeting' === $post->post_type;
			}, true );
			if ( ! $is_meeting_list ) {
				return $posts;
			}

			// for each entry, set a fake meta value to show the next date for recurring meetings
			array_walk(
				$posts,
				function ( &$post ) {
					if ( 'meeting' !== $post->post_type ) {
						return false;
					}
					$next = $this->get_next_occurrence( $post );
					if ( $next ) {
						$post->next_date = $next;
					} else {
						// if the datetime is invalid, then set the post->next_date to the start date instead
						$post->next_date = $post->start_date;
					}
				}
			);

			return $posts;
		}

		public function meeting_sort_upcoming_meetings( $posts, $query ) {
			// Avoid reordering posts in the admin.
			if ( is_admin() || ! is_array( $posts ) ) {
				return $posts;
			}

			$is_meeting_list = array_reduce( $posts, function( $is_meeting, $post ) {
				return $is_meeting && 'meeting' === $post->post_type;
			}, true );
			if ( ! $is_meeting_list ) {
				return $posts;
			}

			// reorder the posts by next_date + time
			usort(
				$posts,
				function ( $a, $b ) {
					$adate = strtotime( $a->next_date . ' ' . $a->time );
					$bdate = strtotime( $b->next_date . ' ' . $b->time );
					if ( $adate == $bdate ) {
						return 0;
					}
					return ( $adate < $bdate ) ? -1 : 1;
				}
			);

			return $posts;
		}

		/**
		 * Returns the date of the next occurrence of the meeting.
		 *
		 * @param object $post A meeting post object.
		 * @param string $after_datetime Find the next occurrence after this date.
		 *
		 * @return string|bool A date string representing the date of the next occurrence; false if there is no next meeting.
		 */
		public function get_next_occurrence( $post, $after_datetime = '-30 minutes' ) {
			if ( ! is_object( $post ) || 'meeting' !== $post->post_type ) {
				return false;
			}

			$next_date = false;

			if ( 'weekly' === $post->recurring || '1' === $post->recurring ) {
				try {
					// from the start date, advance the week until it's past now
					$start = new DateTime( sprintf( '%s %s GMT', $post->start_date, $post->time ) );
					$next  = $start;
					// minus 30 minutes to account for currently ongoing meetings
					$now = new DateTime( $after_datetime );

					if ( $next <= $now ) {
						$interval = $start->diff( $now );
						// add one to days to account for events that happened earlier today
						$weekdiff = ceil( ( $interval->days + 1 ) / 7 );
						$next->modify( '+ ' . $weekdiff . ' weeks' );
					}

					$next_date = $next->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					$next_date = false;
				}
			} elseif ( 'biweekly' === $post->recurring ) {
				try {
					// advance the start date 2 weeks at a time until it's past now
					$start = new DateTime( sprintf( '%s %s GMT', $post->start_date, $post->time ) );
					$next  = $start;
					// minus 30 minutes to account for currently ongoing meetings
					$now = new DateTime( $after_datetime );

					while ( $next <= $now ) {
						$next->modify( '+2 weeks' );
					}

					$next_date = $next->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					$next_date = false;
				}
			} elseif ( 'occurrence' === $post->recurring ) {
				try {
					// advance the occurrence day in the current month until it's past now
					$start = new DateTime( sprintf( '%s %s GMT', $post->start_date, $post->time ) );
					$next  = $start;
					// minus 30 minutes to account for currently ongoing meetings
					$now = new DateTime( $after_datetime );

					$day_index = gmdate( 'w', strtotime( sprintf( '%s %s GMT', $post->start_date, $post->time ) ) );
					$day_name  = $GLOBALS['wp_locale']->get_weekday( $day_index );
					$numerals  = array( 'first', 'second', 'third', 'fourth' );
					$months    = array( 'last month', 'this month', 'next month', '+2 month' );

					$next = clone $now;

					$limit = 12;
					do {
						$month_year = $next->format( 'F Y' );
						foreach ( $post->occurrence as $index ) {
							$next = new DateTime( sprintf( '%s %s of %s %s GMT', $numerals[ $index - 1 ], $day_name, $month_year, $post->time ) );
							if ( $next > $now ) {
								break 2;
							}
						}
						$next->modify( '+1 month' );
					} while ( --$limit > 0 );
					$next_date = $next->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					$next_date = false;
				}
			} elseif ( 'monthly' === $post->recurring ) {
				try {
					// advance the start date 1 month at a time until it's past now
					$start = new DateTime( sprintf( '%s %s GMT', $post->start_date, $post->time ) );
					$next  = $start;
					// minus 30 minutes to account for currently ongoing meetings
					$now = new DateTime( $after_datetime );

					while ( $next <= $now ) {
						$next->modify( '+1 month' );
					}

					$next_date = $next->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					$next_date = false;
				}
			} else {
				$next_date = $post->start_date;
			}

			return $next_date;
		}

		public function register_meeting_post_type() {
			$labels = array(
				'name'               => _x( 'Meetings', 'Post Type General Name', 'wporg-meeting-calendar' ),
				'singular_name'      => _x( 'Meeting', 'Post Type Singular Name', 'wporg-meeting-calendar' ),
				'menu_name'          => __( 'Meetings', 'wporg-meeting-calendar' ),
				'name_admin_bar'     => __( 'Meeting', 'wporg-meeting-calendar' ),
				'parent_item_colon'  => __( 'Parent Meeting:', 'wporg-meeting-calendar' ),
				'all_items'          => __( 'All Meetings', 'wporg-meeting-calendar' ),
				'add_new_item'       => __( 'Add New Meeting', 'wporg-meeting-calendar' ),
				'add_new'            => __( 'Add New', 'wporg-meeting-calendar' ),
				'new_item'           => __( 'New Meeting', 'wporg-meeting-calendar' ),
				'edit_item'          => __( 'Edit Meeting', 'wporg-meeting-calendar' ),
				'update_item'        => __( 'Update Meeting', 'wporg-meeting-calendar' ),
				'view_item'          => __( 'View Meeting', 'wporg-meeting-calendar' ),
				'view_items'         => __( 'View Meetings', 'wporg-meeting-calendar' ),
				'search_items'       => __( 'Search Meeting', 'wporg-meeting-calendar' ),
				'not_found'          => __( 'Not found', 'wporg-meeting-calendar' ),
				'not_found_in_trash' => __( 'Not found in Trash', 'wporg-meeting-calendar' ),
			);
			$args   = array(
				'label'                => __( 'meeting', 'wporg-meeting-calendar' ),
				'description'          => __( 'Meeting', 'wporg-meeting-calendar' ),
				'labels'               => $labels,
				'supports'             => array( 'title', 'custom-fields' ),
				'hierarchical'         => false,
				'public'               => true,
				'show_ui'              => true,
				'show_in_menu'         => true,
				'menu_position'        => 20,
				'menu_icon'            => 'dashicons-calendar',
				'show_in_admin_bar'    => true,
				'show_in_nav_menus'    => false,
				'show_in_rest'         => true,
				'can_export'           => false,
				'has_archive'          => false,
				'exclude_from_search'  => true,
				'publicly_queryable'   => true,
				'capability_type'      => 'post',
				'register_meta_box_cb' => array( $this, 'add_meta_boxes' ),
				'rewrite'              => false,
			);
			register_post_type( 'meeting', $args );
		}

		public function register_meta() {
			// Most are string types
			$meta_keys = array(
				'team',
				'start_date',
				'end_date',
				'time',
				'recurring',
				'link',
				'location',
				'wptv_url',
			);
			foreach ( $meta_keys as $key ) {
				register_meta(
					'post',
					$key,
					array(
						'object_subtype' => 'meeting',
						'type'           => 'string',
						'single'         => true,
						'show_in_rest'   => true,
					)
				);
			}
			// 'occurrence' is an array of strings
			register_meta(
				'post',
				'occurrence',
				array(
					'object_subtype' => 'meeting',
					'type'           => 'array',
					'single'         => true,
					'show_in_rest'   => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
				)
			);
		}

		public function register_rest_field() {
			register_rest_field(
				'meeting',
				'future_occurrences',
				array(
					'get_callback' => array( $this, 'get_future_occurrences' ),
				)
			);
		}

		public function is_meeting_cancelled( $meeting_id, $date ) {
			// Note: this assumes the meeting does occur on $date
			$cancellations = get_post_meta( $meeting_id, 'meeting_cancelled', false );
			return in_array( $date, $cancellations, true );
		}

		public function get_occurrences_for_period( $request ) {
			$meetings = get_posts(
				array(
					'post_type'   => 'meeting',
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);
			$out      = array();
			foreach ( $meetings as $meeting ) {
				$occurrences = $this->get_future_occurrences( $meeting, null, $request );

				$frequency = '';
				if ( ! empty( $meeting->recurring ) && ! empty( $occurrences ) ) {
					$frequency = get_frequency( $meeting->recurring, $occurrences[0], $meeting->occurrence );
				}

				foreach ( $occurrences as $occurrence ) {
					$meeting->time = strftime( '%H:%M:%S', strtotime( $meeting->time ) );
					$out[]         = array(
						'meeting_id'  => $meeting->ID,
						'instance_id' => "{$meeting->ID}:{$occurrence}",
						'date'        => $occurrence,
						'time'        => $meeting->time,
						'datetime'    => "{$occurrence}T{$meeting->time}+00:00",
						'team'        => $meeting->team,
						'link'        => $meeting->link,
						'title'       => wp_specialchars_decode( $meeting->post_title, ENT_QUOTES ),
						'location'    => $meeting->location,
						'wptv_url'    => $meeting->wptv_url,
						'recurring'   => $meeting->recurring,
						'occurrence'  => $meeting->occurrence,
						'status'      => ( $this->is_meeting_cancelled( $meeting->ID, $occurrence ) ? 'cancelled' : 'active' ),
						'rrule'       => $frequency ? "RRULE:FREQ={$frequency}" : '',
					);
				}
			}

			usort(
				$out,
				function( $a, $b ) {
					return $a['datetime'] <=> $b['datetime'];
				}
			);
			return $out;
		}

		public function register_rest_routes() {
			register_rest_route(
				'wp/v2/meetings',
				'/from/(?P<month>\d\d\d\d-\d\d-\d\d)',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_occurrences_for_period' ),
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				'wp/v2/meetings',
				'/(?P<meeting_id>\d+):(?P<date>\d\d\d\d-\d\d-\d\d)',
				array(
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'cancel_meeting' ),
						'permission_callback' => array( $this, 'can_cancel_meeting' ),
					),
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'uncancel_meeting' ),
						'permission_callback' => array( $this, 'can_cancel_meeting' ),
					),
				)
			);
		}

		public function cancel_meeting( $request ) {
			// TODO: should validate that the meeting does occur on the given date
			return add_post_meta( $request['meeting_id'], 'meeting_cancelled', $request['date'], false );
		}

		public function uncancel_meeting( $request ) {
			return delete_post_meta( $request['meeting_id'], 'meeting_cancelled', $request['date'] );
		}

		public function can_cancel_meeting( $request ) {
			return current_user_can( 'edit_post', $request['meeting_id'] );
		}

		public function get_future_occurrences( $meeting, $attr, $request ) {
			if ( is_array( $meeting ) && ! empty( $meeting['id'] ) ) {
				// The register_rest_field callback passes a prepared array but we need the post object
				$meeting = get_post( $meeting['id'] );
			}

			// The month the occurrences should be within.
			// Passed to the API endpoint as /meetings?month=2020-09-01
			$now = time();
			if ( isset( $request['month'] ) ) {
				$now = strtotime( $request['month'] );
			}

			$from = DateTime::createFromFormat( 'U', strtotime( '-30 minutes', $now ) );
			$end  = DateTime::createFromFormat( 'U', strtotime( '+2 month', $now ) );
			if ( $meeting->end_date ) {
				$end = DateTime::createFromFormat( 'Y-m-d', $meeting->end_date );
			}
			$max         = 12;
			$occurrences = array();
			do {
				$next = $this->get_next_occurrence( $meeting, $from->format( 'Y-m-d H:i:s P' ) );
				if ( $next ) {
					$from = new DateTime( "{$next} {$meeting->time}" );
					if ( $from <= $end ) {
						$occurrences[] = $next;
					}
				}
			} while ( --$max > 0 && $next && $from && $from < $end && $meeting->recurring );

			return $occurrences;
		}

		public function add_meta_boxes() {
			add_meta_box(
				'meeting-info',
				'Meeting Info',
				array( $this, 'render_meta_boxes' ),
				'meeting',
				'normal',
				'high'
			);
			add_meta_box(
				'upcoming-meetings',
				'Upcoming Meetings',
				array( $this, 'render_meta_upcoming' ),
				'meeting',
				'normal',
				'high'
			);
		}

		public function render_meta_boxes( $post ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', true, '1.12.1' );

			$meta      = get_post_custom( $post->ID );
			$team      = isset( $meta['team'][0] ) ? $meta['team'][0] : '';
			$start     = isset( $meta['start_date'][0] ) ? $meta['start_date'][0] : '';
			$end       = isset( $meta['end_date'][0] ) ? $meta['end_date'][0] : '';
			$time      = isset( $meta['time'][0] ) ? $meta['time'][0] : '';
			$recurring = isset( $meta['recurring'][0] ) ? $meta['recurring'][0] : '';
			if ( '1' === $recurring ) {
				$recurring = 'weekly';
			}
			$occurrence = isset( $meta['occurrence'][0] ) ? unserialize( $meta['occurrence'][0] ) : array();
			$link       = isset( $meta['link'][0] ) ? $meta['link'][0] : '';
			$location   = isset( $meta['location'][0] ) ? $meta['location'][0] : '';
			$wptv_url   = isset( $meta['wptv_url'][0] ) ? $meta['wptv_url'][0] : '';
			wp_nonce_field( 'save_meeting_meta_' . $post->ID, 'meeting_nonce' );
			?>

		<p>
		<label for="team">
			<?php _e( 'Team: ', 'wporg-meeting-calendar' ); ?>
			<input type="text" id="team" name="team" class="regular-text wide" value="<?php echo esc_attr( $team ); ?>">
		</label>
		</p>
		<p>
		<label for="start_date">
			<?php _e( 'Start Date', 'wporg-meeting-calendar' ); ?>
			<input type="text" required="required" name="start_date" id="start_date" class="date" value="<?php echo esc_attr( $start ); ?>">
		</label>
		<label for="end_date">
			<?php _e( 'End Date', 'wporg-meeting-calendar' ); ?>
			<input type="text" name="end_date" id="end_date" class="date" value="<?php echo esc_attr( $end ); ?>">
		</label>
		</p>
		<p>
		<label for="time">
			<?php _e( 'Time (UTC)', 'wporg-meeting-calendar' ); ?>
			<input type="text" required="required" placeholder="13:00" name="time" id="time" class="time" value="<?php echo esc_attr( $time ); ?>">
		</label>
		</p>
		<p class="recurring">
			<?php _e( 'Recurring: ', 'wporg-meeting-calendar' ); ?><br />
		<label for="weekly">
			<input type="radio" name="recurring" value="weekly" id="weekly" class="regular-radio" <?php checked( $recurring, 'weekly' ); ?>>
			<?php _e( 'Weekly', 'wporg-meeting-calendar' ); ?>
		</label><br />

		<label for="biweekly">
			<input type="radio" name="recurring" value="biweekly" id="biweekly" class="regular-radio" <?php checked( $recurring, 'biweekly' ); ?>>
			<?php _e( 'Biweekly', 'wporg-meeting-calendar' ); ?>
		</label><br />

		<label for="occurrence">
			<input type="radio" name="recurring" value="occurrence" id="occurrence" class="regular-radio" <?php checked( $recurring, 'occurrence' ); ?>>
			<?php _e( 'Occurrence in a month:', 'wporg-meeting-calendar' ); ?>
		</label>
		<label for="week-1">
			<input type="checkbox" name="occurrence[]" value="1" id="week-1" <?php checked( in_array( 1, $occurrence ) ); ?>>
			<?php _e( '1st', 'wporg-meeting-calendar' ); ?>
		</label>
		<label for="week-2">
			<input type="checkbox" name="occurrence[]" value="2" id="week-2" <?php checked( in_array( 2, $occurrence ) ); ?>>
			<?php _e( '2nd', 'wporg-meeting-calendar' ); ?>
		</label>
		<label for="week-3">
			<input type="checkbox" name="occurrence[]" value="3" id="week-3" <?php checked( in_array( 3, $occurrence ) ); ?>>
			<?php _e( '3rd', 'wporg-meeting-calendar' ); ?>
		</label>
		<label for="week-4">
			<input type="checkbox" name="occurrence[]" value="4" id="week-4" <?php checked( in_array( 4, $occurrence ) ); ?>>
			<?php _e( '4th', 'wporg-meeting-calendar' ); ?>
		</label><br />

		<label for="monthly">
			<input type="radio" name="recurring" value="monthly" id="monthly" class="regular-radio" <?php checked( $recurring, 'monthly' ); ?>>
			<?php _e( 'Monthly', 'wporg-meeting-calendar' ); ?>
		</label>
		</p>
		<p>
		<label for="link"><?php _e( 'Link: ', 'wporg-meeting-calendar' ); ?>
			<input type="text" name="link" id="link" class="regular-text wide" value="<?php echo esc_url( $link ); ?>">
		</label>
		</p>
		<p>
		<label for="location"><?php _e( 'Location: ', 'wporg-meeting-calendar' ); ?>
			<input type="text" name="location" id="location" class="regular-text wide" value="<?php echo esc_attr( $location ); ?>">
		</label>
		</p>
		<p>
		<label for="wptv_url"><?php esc_html_e( 'WordPress.tv URL: ', 'wporg-meeting-calendar' ); ?></label>
			<input
				type="url"
				name="wptv_url"
				id="wptv_url"
				class="regular-text wide"
				value="<?php echo esc_url( $wptv_url ); ?>"
			/>
		</p>

		<script>
		jQuery(document).ready( function($) {
			$('.date').datepicker({
				dateFormat: 'yy-mm-dd'
			});

			$('input[name="recurring"]').change( function() {
				var disabled = ( 'occurrence' !== $(this).val() );
				$('#meeting-info').find('[name^="occurrence"]').prop('disabled', disabled);
			});

			if ( 'occurrence' !== $('input[name="recurring"]:checked').val() ) {
				$('#meeting-info').find('[name^="occurrence"]').prop('disabled', true);
			}
		});
		</script>
			<?php
		}

		public function render_meta_upcoming( $meeting ) {
			$occurrences = $this->get_future_occurrences( $meeting, null, null );
			if ( count( $occurrences ) ) {
				?>
				<ul>
				<?php

				foreach ( $occurrences as $occurrence ) {
					$is_cancelled = $this->is_meeting_cancelled( $meeting->ID, $occurrence );
					?>
					<li>
						<label>
							<?php printf( __( '%1$s at %2$s', 'wporg-meeting-calendar' ), $occurrence, $meeting->time ); ?>
							<input type="checkbox" class="cancel-meeting" value="<?php echo esc_attr( $meeting->ID ) . ':' . esc_attr( $occurrence ); ?>" <?php checked( ! $this->is_meeting_cancelled( $meeting->ID, $occurrence ) ); ?> />
							<span class="meeting-status">
								<?php
								if ( $this->is_meeting_cancelled( $meeting->ID, $occurrence ) ) {
									echo __( 'Cancelled', 'wporg-meeting-calendar' );
								}
								?>
							</span>
						</label>
					</li>
					<?php
				}
				?>
				</ul>
				<?php

			} else {
				?>
				<p><?php _e( 'No upcoming meetings.', 'wporg-meeting-calendar' ); ?></p>
				<?php
			}

			?>

		<script>
			jQuery(document).ready( function($) {

				var root = '<?php echo esc_url_raw( rest_url() ); ?>';
				var nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
				var cancelled = '<?php echo esc_html__( 'Cancelled', 'wporg-meeting-calendar' ); ?>';


				$('input.cancel-meeting').click( function() {

					var self = this;
					var meeting_id = this.value;
					var method = this.checked ? 'PUT' : 'DELETE';

					$.ajax( {
						url: root + 'wp/v2/meetings/' + meeting_id,
						method: method,
						dataType: 'json',
						beforeSend: function ( xhr ) {
							xhr.setRequestHeader( 'X-WP-Nonce', nonce );
						},
					} ).done( function ( response ) {
						if ( response ) {
							$(self).next('.meeting-status').text( 'DELETE' == method ? cancelled : '' );
						}
					} ).fail( function ( response ) {
						self.checked = !self.checked;
					} );
				} );

			} );
		</script>
			<?php
		}

		public function save_meta_boxes( $post_id ) {
			global $post;

			// Verify nonce
			if ( ! isset( $_POST['meeting_nonce'] ) || ! wp_verify_nonce( $_POST['meeting_nonce'], 'save_meeting_meta_' . $post_id ) ) {
				return $post_id;
			}

			// Check autosave
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
				return $post_id;
			}

			// Don't save for revisions
			if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
				return $post_id;
			}

			// Check permissions
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				return $post_id;
			}

			// Basic validation
			if ( empty( trim( $_POST['post_title'] ) ) ) {
				return false;
			}
			if ( empty( trim( $_POST['start_date'] ) ) ) {
				return false;
			}
			if ( false === strtotime( $_POST['start_date'] ) ) {
				return false;
			}
			if ( ! empty( trim( $_POST['end_date'] ) ) && false === strtotime( $_POST['end_date'] ) ) {
				return false;
			}
			if ( empty( trim( $_POST['time'] ) ) ) {
				return false;
			}
			if ( false === strtotime( $_POST['time'] ) ) {
				return false;
			}

			$meta['team']       = ( isset( $_POST['team'] ) ? esc_textarea( $_POST['team'] ) : '' );
			$meta['start_date'] = ( isset( $_POST['start_date'] ) ? esc_textarea( $_POST['start_date'] ) : '' );
			$meta['end_date']   = ( isset( $_POST['end_date'] ) ? esc_textarea( $_POST['end_date'] ) : '' );
			$meta['time']       = ( isset( $_POST['time'] ) ? esc_textarea( $_POST['time'] ) : '' );
			$meta['recurring']  = ( isset( $_POST['recurring'] )
								 && in_array( $_POST['recurring'], array( 'weekly', 'biweekly', 'occurrence', 'monthly' ) )
								 ? ( $_POST['recurring'] ) : '' );
			$meta['occurrence'] = ( isset( $_POST['occurrence'] ) && 'occurrence' === $meta['recurring']
								 && is_array( $_POST['occurrence'] )
								 ? array_map( 'intval', $_POST['occurrence'] ) : array() );
			$meta['link']       = ( isset( $_POST['link'] ) ? esc_url( $_POST['link'] ) : '' );
			$meta['location']   = ( isset( $_POST['location'] ) ? esc_textarea( $_POST['location'] ) : '' );
			$meta['wptv_url']   = ( isset( $_POST['wptv_url'] ) ? esc_url( $_POST['wptv_url'] ) : '' );

			foreach ( $meta as $key => $value ) {
				update_post_meta( $post->ID, $key, $value );
			}
		}

		/**
		 * Adds "Edit Meetings" item after "Add New" menu.
		 *
		 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
		 */
		public function add_edit_meetings_item_to_admin_bar( $wp_admin_bar ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			if ( is_admin() || ! is_post_type_archive( 'meeting' ) ) {
				return;
			}

			$wp_admin_bar->add_menu(
				array(
					'id'    => 'edit-meetings',
					'title' => '<span class="ab-icon"></span>' . __( 'Edit Meetings', 'wporg-meeting-calendar' ),
					'href'  => admin_url( 'edit.php?post_type=meeting' ),
				)
			);
		}

		/**
		 * Adds icon for the "Edit Meetings" item.
		 */
		public function add_edit_meetings_icon_to_admin_bar() {
			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			wp_add_inline_style(
				'admin-bar',
				'
			#wpadminbar #wp-admin-bar-edit-meetings .ab-icon:before {
				content: "\f145";
				top: 2px;
			}
		'
			);
		}

		/**
		 * Renders meeting information with the next meeting time based on user's local timezone. Used in Make homepage.
		 */
		public function meeting_time_shortcode( $attr, $content = '' ) {
			$attr = shortcode_atts(
				array(
					'team'     => null,
					'limit'    => 1,
					'before'   => __( 'Next meeting: ', 'wporg-meeting-calendar' ),
					'titletag' => 'strong',
					'more'     => true,
				),
				$attr
			);

			if ( empty( $attr['team'] ) ) {
				return '';
			}

			if ( 'Documentation' === $attr['team'] ) {
				$attr['team'] = 'Docs';
			}

			if ( ! has_action( 'wp_footer', array( $this, 'time_conversion_script' ) ) ) {
				add_action( 'wp_footer', array( $this, 'time_conversion_script' ), 999 );
			}

			// If we're on a network, assume the calendar exists on the main site
			if ( function_exists( 'switch_to_blog' ) ) {
				switch_to_blog( get_main_site_id() );
			}

			$query = new WP_Query(
				array(
					'post_type'  => 'meeting',
					'nopaging'   => true,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => 'team',
							'value'   => $attr['team'],
							'compare' => 'EQUALS',
						),
						$this->meeting_meta_query(),
					),
				)
			);

			$limit = $attr['limit'] > 0 ? $attr['limit'] : count( $query->posts );

			$out = '';
			foreach ( array_slice( $query->posts, 0, $limit ) as $post ) {
				$next_meeting_datestring = $post->next_date;
				$utc_time                = strftime( '%H:%M:%S', strtotime( $post->time ) );
				$next_meeting_iso        = $next_meeting_datestring . 'T' . $utc_time . '+00:00';
				$next_meeting_timestamp  = strtotime( $next_meeting_datestring . ' ' . $utc_time );
				$next_meeting_display    = strftime( '%c %Z', $next_meeting_timestamp );

				$slack_channel = null;
				if ( $post->location && preg_match( '/^#([-\w]+)$/', trim( $post->location ), $match ) ) {
					$slack_channel = sanitize_title( $match[1] );
				}

				$cancelled = $this->is_meeting_cancelled( $post->ID, $post->next_date );

				$out .= '<p class="wporg-meeting-shortcode' . ( $cancelled ? ' meeting-cancelled' : '' ) . '">';
				$out .= '<span class="wporg-meeting-detail">';

				$out         .= esc_html( $attr['before'] );
				$out         .= '<strong class="meeting-title">' . esc_html( $post->post_title ) . '</strong>';
				$display_more = $query->found_posts - intval( $limit );
				if ( $display_more > 0 ) {
					$out .= ' <a title="Click to view all meetings for this team" href="/meetings/#' . esc_attr( strtolower( $attr['team'] ) ) . '">' . sprintf( __( '(+%s more)', 'wporg-meeting-calendar' ), $display_more ) . '</a>';
				}
				$out .= '<br/>';
				$out .= '<time class="date" date-time="' . esc_attr( $next_meeting_iso ) . '" title="' . esc_attr( $next_meeting_iso ) . '">' . $next_meeting_display . '</time> ';
				$out .= sprintf( esc_html__( '(%s from now)', 'wporg-meeting-calendar' ), human_time_diff( $next_meeting_timestamp, current_time( 'timestamp' ) ) );
				if ( $post->location && $slack_channel ) {
					$out .= ' ' . sprintf( wp_kses( __( 'at <a href="%1$s">%2$s</a> on Slack', 'wporg-meeting-calendar' ), array( 'a' => array( 'href' => array() ) ) ), 'https://wordpress.slack.com/messages/' . $slack_channel, $post->location );
				}
				$out .= '</span>';

				if ( $cancelled ) {
					$out               .= '<br>';
					$future_occurrences = $this->get_future_occurrences( $post, null, array() );
					$next_meeting       = null;
					foreach ( $future_occurrences as $occurrence ) {
						if ( ! $this->is_meeting_cancelled( $post->ID, $occurrence )
							&& $occurrence > $post->next_date ) {
							$next_meeting = $occurrence;
							break;
						}
					}
					if ( $next_meeting ) {
						$out .= '<i>' . sprintf( esc_html__( 'This event is cancelled. The next meeting is scheduled for %s.', 'wporg-meeting-calendar' ), $next_meeting ) . '</i>';
					} else {
						$out .= '<i>' . esc_html__( 'This event is cancelled.', 'wporg-meeting-calendar' ) . '</i>';
					}
				}
				$out .= '</p>';
			}

			if ( function_exists( 'restore_current_blog' ) ) {
				restore_current_blog();
			}

			return $out;
		}

		public function meeting_meta_query() {
			return array(
				'relation' => 'OR',
				// not recurring  AND start_date >= CURDATE() = one-time meeting today or still in future
				array(
					'relation' => 'AND',
					array(
						'key'     => 'recurring',
						'value'   => array( 'weekly', 'biweekly', 'occurrence', 'monthly', '1' ),
						'compare' => 'NOT IN',
					),
					array(
						'key'     => 'start_date',
						'type'    => 'DATE',
						'compare' => '>=',
						'value'   => gmdate( 'Y-m-d' ),
					),
				),
				// recurring = 1 AND ( end_date = '' OR end_date > CURDATE() ) = recurring meeting that has no end or has not ended yet
				array(
					'relation' => 'AND',
					array(
						'key'     => 'recurring',
						'value'   => array( 'weekly', 'biweekly', 'occurrence', 'monthly', '1' ),
						'compare' => 'IN',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'end_date',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => 'end_date',
							'type'    => 'DATE',
							'compare' => '>',
							'value'   => gmdate( 'Y-m-d' ),
						),
					),
				),
			);
		}

		public function time_conversion_script() {
			echo <<<EOF
<script type="text/javascript">

	var parse_date = function (text) {
		var m = /^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})\+00:00$/.exec(text);
		var d = new Date();
		d.setUTCFullYear(+m[1]);
		d.setUTCDate(+m[3]);
		d.setUTCMonth(+m[2]-1);
		d.setUTCHours(+m[4]);
		d.setUTCMinutes(+m[5]);
		d.setUTCSeconds(+m[6]);
		return d;
	}
	var format_time = function (d) {
		return d.toLocaleTimeString(navigator.language, {weekday: 'long', hour: '2-digit', minute: '2-digit', timeZoneName: 'short'});
	}

	var nodes = document.getElementsByTagName('time');
	for (var i=0; i<nodes.length; ++i) {
		var node = nodes[i];
		if (node.className === 'date') {
			var d = parse_date(node.getAttribute('date-time'));
			if (d) {
				node.textContent = format_time(d);
			}
		}
	}
</script>
EOF;
		}
	}

	// fire it up
	Meeting_Post_Type::init();
endif;


