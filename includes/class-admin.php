<?php
/**
 * Admin screens for meetings: list table columns and filter, and meta boxes.
 *
 * @package WordPressdotorg\Meeting_Calendar
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Meeting_Calendar;

/**
 * Admin screens for editing meetings.
 */
class Admin {

	/**
	 * The single instance of the class.
	 *
	 * @var Admin|null
	 */
	protected static ?Admin $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return Admin
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the admin hooks.
	 */
	public static function init(): void {
		$admin = self::get_instance();
		add_action( 'admin_head', array( $admin, 'meeting_column_width' ) );
		add_filter( 'manage_meeting_posts_columns', array( $admin, 'meeting_add_custom_columns' ) );
		add_action( 'manage_meeting_posts_custom_column', array( $admin, 'meeting_custom_columns' ), 11, 2 );
		add_action( 'restrict_manage_posts', array( $admin, 'meeting_team_filter_dropdown' ) );
		add_action( 'pre_get_posts', array( $admin, 'meeting_filter_by_team' ) );
		add_action( 'add_meta_boxes_meeting', array( $admin, 'add_meta_boxes' ) );
		add_action( 'save_post_meeting', array( $admin, 'save_meta_boxes' ), 10, 2 );
		add_action( 'wp_ajax_meeting_date_preview', array( $admin, 'ajax_date_preview' ) );
	}

	/**
	 * Print the styles for the meetings list table columns and meta box.
	 */
	public function meeting_column_width(): void {
		?>
		<style type="text/css">
			.column-team { width: 10em !important; overflow: hidden; }
			#meeting-info .recurring label { padding-right: 10px; }
		</style>
		<?php
	}

	/**
	 * Add the Team and WPTV URL columns to the meetings list table.
	 *
	 * @param array $columns List table columns.
	 * @return array
	 */
	public function meeting_add_custom_columns( array $columns ): array {
		$columns = array_slice( $columns, 0, 1, true )
			+ array( 'team' => __( 'Team', 'wporg-meeting-calendar' ) )
			+ array_slice( $columns, 1, -1, true )
			+ array( 'wptv_url' => __( 'WPTV URL', 'wporg-meeting-calendar' ) )
			+ array_slice( $columns, -1, null, true );
		return $columns;
	}

	/**
	 * Print the content of the custom list table columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Meeting ID.
	 */
	public function meeting_custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'team':
				$team = (string) get_post_meta( $post_id, 'team', true );
				echo esc_html( ucwords( $team ) );
				break;
		}
		switch ( $column ) {
			case 'wptv_url':
				$wptv_url = get_post_meta( $post_id, 'wptv_url', true );
				echo esc_html( $wptv_url ? $wptv_url : '—' );
				break;
		}
	}

	/**
	 * Outputs a team dropdown above the meetings list table.
	 *
	 * @param string $post_type The post type of the current list table.
	 */
	public function meeting_team_filter_dropdown( string $post_type ): void {
		global $wpdb;

		if ( 'meeting' !== $post_type ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Distinct meta values can't be fetched through the API, and this only runs on the list table.
		$teams = $wpdb->get_col(
			"SELECT DISTINCT pm.meta_value
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = 'team' AND pm.meta_value != '' AND p.post_type = 'meeting'
				AND p.post_status NOT IN ( 'trash', 'auto-draft' )
			ORDER BY pm.meta_value"
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter.
		$selected = isset( $_GET['team'] ) ? sanitize_text_field( wp_unslash( $_GET['team'] ) ) : '';
		?>
		<label for="filter-by-team" class="screen-reader-text"><?php esc_html_e( 'Filter by team', 'wporg-meeting-calendar' ); ?></label>
		<select name="team" id="filter-by-team">
			<option value=""><?php esc_html_e( 'All teams', 'wporg-meeting-calendar' ); ?></option>
			<?php foreach ( $teams as $team ) : ?>
				<option value="<?php echo esc_attr( $team ); ?>" <?php selected( strtolower( $selected ), strtolower( $team ) ); ?>><?php echo esc_html( ucwords( $team ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Limits the meetings list table to the team selected in the dropdown.
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function meeting_filter_by_team( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'meeting' !== $query->get( 'post_type' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter.
		$team = isset( $_GET['team'] ) ? sanitize_text_field( wp_unslash( $_GET['team'] ) ) : '';
		if ( ! $team ) {
			return;
		}

		$team_query = array(
			'key'   => 'team',
			'value' => $team,
		);

		// Restrict any existing meta query instead of adding the team as an alternative to it.
		$meta_query = $query->get( 'meta_query' );
		if ( $meta_query ) {
			$team_query = array(
				'relation' => 'AND',
				$meta_query,
				$team_query,
			);
		} else {
			$team_query = array( $team_query );
		}
		$query->set( 'meta_query', $team_query );
	}

	/**
	 * Add the meeting info and upcoming meetings meta boxes.
	 *
	 * @param \WP_Post $post Meeting.
	 */
	public function add_meta_boxes( \WP_Post $post ): void {
		add_meta_box(
			'meeting-info',
			'Meeting Info',
			array( $this, 'render_meta_boxes' ),
			'meeting',
			'normal',
			'high'
		);
		if ( 'auto-draft' !== $post->post_status ) {
			add_meta_box(
				'upcoming-meetings',
				'Upcoming Meetings',
				array( $this, 'render_meta_upcoming' ),
				'meeting',
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meeting info meta box.
	 *
	 * @param \WP_Post $post Meeting.
	 */
	public function render_meta_boxes( \WP_Post $post ): void {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', true, '1.12.1' );

		$team      = (string) get_post_meta( $post->ID, 'team', true );
		$start     = (string) get_post_meta( $post->ID, 'start_date', true );
		$end       = (string) get_post_meta( $post->ID, 'end_date', true );
		$time      = (string) get_post_meta( $post->ID, 'time', true );
		$recurring = (string) get_post_meta( $post->ID, 'recurring', true );
		if ( '1' === $recurring ) {
			$recurring = 'weekly';
		}
		$occurrence = get_post_meta( $post->ID, 'occurrence', true );
		$occurrence = $occurrence ? array_map( 'intval', (array) $occurrence ) : array();
		$link       = (string) get_post_meta( $post->ID, 'link', true );
		$location   = (string) get_post_meta( $post->ID, 'location', true );
		$wptv_url   = (string) get_post_meta( $post->ID, 'wptv_url', true );
		wp_nonce_field( 'save_meeting_meta_' . $post->ID, 'meeting_nonce' );
		?>

	<p>
	<label for="team">
		<?php esc_html_e( 'Team: ', 'wporg-meeting-calendar' ); ?>
		<input type="text" id="team" name="team" class="regular-text wide" value="<?php echo esc_attr( $team ); ?>">
	</label>
	</p>
	<p>
	<label for="start_date">
		<?php esc_html_e( 'Start Date', 'wporg-meeting-calendar' ); ?>
		<input type="text" required="required" name="start_date" id="start_date" class="date" value="<?php echo esc_attr( $start ); ?>">
	</label>
	</p>
	<p>
	<label for="time">
		<?php esc_html_e( 'Time (UTC)', 'wporg-meeting-calendar' ); ?>
		<input type="text" required="required" placeholder="13:00" name="time" id="time" class="time" value="<?php echo esc_attr( $time ); ?>">
	</label>
	</p>
	<p class="recurring">
		<?php esc_html_e( 'Recurring: ', 'wporg-meeting-calendar' ); ?><br />
	<label for="once-off">
		<input type="radio" name="recurring" value="" id="once-off" class="regular-radio" <?php checked( $recurring, '' ); ?>>
		<?php esc_html_e( 'Once-off', 'wporg-meeting-calendar' ); ?>
	</label><br />
	<label for="weekly">
		<input type="radio" name="recurring" value="weekly" id="weekly" class="regular-radio" <?php checked( $recurring, 'weekly' ); ?>>
		<?php esc_html_e( 'Weekly', 'wporg-meeting-calendar' ); ?>
	</label><br />

	<label for="biweekly">
		<input type="radio" name="recurring" value="biweekly" id="biweekly" class="regular-radio" <?php checked( $recurring, 'biweekly' ); ?>>
		<?php esc_html_e( 'Biweekly', 'wporg-meeting-calendar' ); ?>
	</label><br />

	<label for="occurrence">
		<input type="radio" name="recurring" value="occurrence" id="occurrence" class="regular-radio" <?php checked( $recurring, 'occurrence' ); ?>>
		<?php esc_html_e( 'Occurrence in a month:', 'wporg-meeting-calendar' ); ?>
	</label>
	<label for="week-1">
		<input type="checkbox" name="occurrence[]" value="1" id="week-1" <?php checked( in_array( 1, $occurrence, true ) ); ?>>
		<?php esc_html_e( '1st', 'wporg-meeting-calendar' ); ?>
	</label>
	<label for="week-2">
		<input type="checkbox" name="occurrence[]" value="2" id="week-2" <?php checked( in_array( 2, $occurrence, true ) ); ?>>
		<?php esc_html_e( '2nd', 'wporg-meeting-calendar' ); ?>
	</label>
	<label for="week-3">
		<input type="checkbox" name="occurrence[]" value="3" id="week-3" <?php checked( in_array( 3, $occurrence, true ) ); ?>>
		<?php esc_html_e( '3rd', 'wporg-meeting-calendar' ); ?>
	</label>
	<label for="week-4">
		<input type="checkbox" name="occurrence[]" value="4" id="week-4" <?php checked( in_array( 4, $occurrence, true ) ); ?>>
		<?php esc_html_e( '4th', 'wporg-meeting-calendar' ); ?>
	</label>
	<label for="week-last">
		<input type="checkbox" name="occurrence[]" value="-1" id="week-last" <?php checked( in_array( -1, $occurrence, true ) ); ?>>
		<?php esc_html_e( 'Last', 'wporg-meeting-calendar' ); ?>
	</label><br />

	<label for="monthly">
		<input type="radio" name="recurring" value="monthly" id="monthly" class="regular-radio" <?php checked( $recurring, 'monthly' ); ?>>
		<?php esc_html_e( 'Monthly', 'wporg-meeting-calendar' ); ?>
	</label>
	</p>

	<div id="meeting-date-preview" style="margin: 10px 0; padding: 8px 12px; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;">
		<strong><?php esc_html_e( 'Upcoming dates:', 'wporg-meeting-calendar' ); ?></strong>
		<span class="spinner" id="meeting-date-spinner" style="float: none; margin: 0 0 0 4px;"></span>
		<ul id="meeting-date-preview-list" style="margin: 4px 0 0 16px;"></ul>
	</div>

	<p>
	<label for="end_date">
		<?php esc_html_e( 'End Date', 'wporg-meeting-calendar' ); ?>
		<input type="text" name="end_date" id="end_date" class="date" value="<?php echo esc_attr( $end ); ?>">
	</label>
	</p>
	<p>
	<label for="link"><?php esc_html_e( 'Link: ', 'wporg-meeting-calendar' ); ?>
		<input type="text" name="link" id="link" class="regular-text wide" value="<?php echo esc_url( $link ); ?>">
	</label>
	</p>
	<p>
	<label for="location"><?php esc_html_e( 'Location: ', 'wporg-meeting-calendar' ); ?>
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

			$('#end_date').prop( 'disabled', ! $(this).val() );
			updateDatePreview();
		});

		if ( 'occurrence' !== $('input[name="recurring"]:checked').val() ) {
			$('#meeting-info').find('[name^="occurrence"]').prop('disabled', true);
		}

		$('#end_date').prop( 'disabled', ! $('input[name="recurring"]:checked').val() );

		var previewTimer;
		function updateDatePreview() {
			clearTimeout( previewTimer );
			previewTimer = setTimeout( fetchDatePreview, 300 );
		}

		function fetchDatePreview() {
			var recurring = $('input[name="recurring"]:checked').val();
			var startDate = $('#start_date').val();
			var time = $('#time').val();

			if ( ! recurring || ! startDate || ! time ) {
				$('#meeting-date-preview').hide();
				return;
			}

			var occurrence = [];
			if ( 'occurrence' === recurring ) {
				$('input[name="occurrence[]"]:checked:not(:disabled)').each( function() {
					occurrence.push( $(this).val() );
				});
				if ( ! occurrence.length ) {
					$('#meeting-date-preview').hide();
					return;
				}
			}

			// Show the preview box with spinner immediately.
			$('#meeting-date-preview-list').empty();
			$('#meeting-date-spinner').addClass('is-active');
			$('#meeting-date-preview').show();

			$.post( ajaxurl, {
				action: 'meeting_date_preview',
				nonce: '<?php echo esc_js( wp_create_nonce( 'meeting_date_preview' ) ); ?>',
				start_date: startDate,
				end_date: $('#end_date').val() || '',
				time: time,
				recurring: recurring,
				occurrence: occurrence
			}, function( response ) {
				$('#meeting-date-spinner').removeClass('is-active');
				var list = $('#meeting-date-preview-list').empty();
				if ( response.success && response.data.length ) {
					$.each( response.data, function( i, date ) {
						list.append( '<li>' + $('<span>').text( date ).html() + '</li>' );
					});
				} else {
					list.append( '<li><?php echo esc_js( __( 'No upcoming dates.', 'wporg-meeting-calendar' ) ); ?></li>' );
				}
			}).fail( function() {
				$('#meeting-date-spinner').removeClass('is-active');
				$('#meeting-date-preview').hide();
			});
		}

		$('#start_date, #time, #end_date').on( 'change', updateDatePreview );
		$('input[name="occurrence[]"]').on( 'change', updateDatePreview );

		// Initial preview if editing an existing recurring meeting.
		if ( $('input[name="recurring"]:checked').val() ) {
			fetchDatePreview();
		}
	});
	</script>
		<?php
	}

	/**
	 * Render the upcoming meetings meta box, with checkboxes to (un)cancel each occurrence.
	 *
	 * @param \WP_Post $meeting Meeting.
	 */
	public function render_meta_upcoming( \WP_Post $meeting ): void {
		$meeting_post_type = \Meeting_Post_Type::getInstance();
		$occurrences       = $meeting_post_type->get_future_occurrences( $meeting, null, null );
		if ( count( $occurrences ) ) {
			?>
			<ul>
			<?php

			foreach ( $occurrences as $occurrence ) {
				$is_cancelled = $meeting_post_type->is_meeting_cancelled( $meeting->ID, $occurrence );
				?>
				<li>
					<label>
						<?php
						/* translators: 1: Date of the meeting. 2: Time of the meeting. */
						printf( esc_html__( '%1$s at %2$s', 'wporg-meeting-calendar' ), esc_html( $occurrence ), esc_html( $meeting->time ) );
						?>
						<input type="checkbox" class="cancel-meeting" value="<?php echo esc_attr( $meeting->ID ) . ':' . esc_attr( $occurrence ); ?>" <?php checked( ! $is_cancelled ); ?> />
						<span class="meeting-status">
							<?php
							if ( $is_cancelled ) {
								esc_html_e( 'Cancelled', 'wporg-meeting-calendar' );
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
			<p><?php esc_html_e( 'No upcoming meetings.', 'wporg-meeting-calendar' ); ?></p>
			<?php
		}

		?>

	<script>
		jQuery(document).ready( function($) {

			var root = '<?php echo esc_url_raw( rest_url() ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';
			var cancelled = '<?php echo esc_html__( 'Cancelled', 'wporg-meeting-calendar' ); ?>';


			$('input.cancel-meeting').click( function() {

				var self = this;
				var meeting_id = this.value;
				var method = this.checked ? 'PUT' : 'DELETE';

				$.ajax( {
					url: root + 'wporg/v1/meetings/' + meeting_id,
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

	/**
	 * Save the meeting info meta box fields.
	 *
	 * @param int $post_id Meeting ID.
	 */
	public function save_meta_boxes( int $post_id ): void {
		global $post;

		// Verify nonce.
		if ( ! isset( $_POST['meeting_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meeting_nonce'] ) ), 'save_meeting_meta_' . $post_id ) ) {
			return;
		}

		// Check autosave.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		// Don't save for revisions.
		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		$meta['team']       = sanitize_text_field( wp_unslash( $_POST['team'] ?? '' ) );
		$meta['start_date'] = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		$meta['end_date']   = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
		$meta['time']       = sanitize_text_field( wp_unslash( $_POST['time'] ?? '' ) );
		$meta['link']       = esc_url_raw( wp_unslash( $_POST['link'] ?? '' ) );
		$meta['location']   = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );
		$meta['wptv_url']   = esc_url_raw( wp_unslash( $_POST['wptv_url'] ?? '' ) );

		// Basic validation.
		if ( '' === sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) ) ) {
			return;
		}
		if ( '' === $meta['start_date'] || false === strtotime( $meta['start_date'] ) ) {
			return;
		}
		if ( '' !== $meta['end_date'] && false === strtotime( $meta['end_date'] ) ) {
			return;
		}
		if ( '' === $meta['time'] || false === strtotime( $meta['time'] ) ) {
			return;
		}

		$meta['recurring'] = sanitize_text_field( wp_unslash( $_POST['recurring'] ?? '' ) );
		if ( ! in_array( $meta['recurring'], array( 'weekly', 'biweekly', 'occurrence', 'monthly' ), true ) ) {
			$meta['recurring'] = '';
		}

		$meta['occurrence'] = array();
		if ( 'occurrence' === $meta['recurring'] && is_array( $_POST['occurrence'] ?? null ) ) {
			$meta['occurrence'] = array_map( 'intval', $_POST['occurrence'] );
		}

		// Non-recurring events should not have a end_date set.
		if ( ! $meta['recurring'] && $meta['end_date'] ) {
			$meta['end_date'] = '';
		}

		foreach ( $meta as $key => $value ) {
			// update_post_meta() expects slashed data.
			update_post_meta( $post->ID, $key, wp_slash( $value ) );
		}
	}

	/**
	 * Send a preview of the next dates for the meeting settings in the edit screen, via Ajax.
	 */
	public function ajax_date_preview(): void {
		check_ajax_referer( 'meeting_date_preview', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		$start_date = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		$time       = sanitize_text_field( wp_unslash( $_POST['time'] ?? '' ) );
		$recurring  = sanitize_text_field( wp_unslash( $_POST['recurring'] ?? '' ) );
		$occurrence = array_map( 'intval', (array) ( $_POST['occurrence'] ?? array() ) );

		if ( ! $start_date || ! $time || ! $recurring || false === strtotime( $start_date ) || false === strtotime( $time ) ) {
			wp_send_json_error();
		}

		$end_date = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
		if ( ! \Meeting_Post_Type::getInstance()->is_valid_date( $end_date ) ) {
			$end_date = '';
		}

		// Build a temporary post object to pass to get_future_occurrences().
		$post             = new \stdClass();
		$post->ID         = 0;
		$post->post_type  = 'meeting';
		$post->start_date = $start_date;
		$post->end_date   = $end_date;
		$post->time       = $time;
		$post->recurring  = $recurring;
		$post->occurrence = $occurrence;

		// Use a longer window than the default 2 months to ensure we get enough dates.
		$from = \DateTime::createFromFormat( 'U', (string) strtotime( '-30 minutes' ) );
		$end  = new \DateTime( '+6 months' );
		if ( $post->end_date ) {
			// The meeting still occurs on its end date.
			$end = \DateTime::createFromFormat( '!Y-m-d', $post->end_date )->setTime( 23, 59, 59 );
		}

		$max   = 12;
		$dates = array();
		// Collect up to 4 upcoming dates; `isset( $dates[3] )` means there are 4.
		do {
			$next = \Meeting_Post_Type::getInstance()->get_next_occurrence( $post, $from->format( 'Y-m-d H:i:s P' ) );
			if ( $next ) {
				$from = new \DateTime( "{$next} {$post->time}" );
				if ( $from <= $end ) {
					$dates[] = $next;
				}
			}
		} while ( --$max > 0 && $next && $from && $from < $end && ! isset( $dates[3] ) );

		// Format dates with day names for display (e.g. "Wednesday, 2026-03-25").
		$formatted = array();
		foreach ( $dates as $date ) {
			$dt          = new \DateTime( $date );
			$formatted[] = $dt->format( 'l, Y-m-d' );
		}

		wp_send_json_success( $formatted );
	}
}
