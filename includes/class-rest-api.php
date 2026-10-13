<?php
/**
 * REST API for meetings: occurrences in a period, and (un)cancelling occurrences.
 *
 * @package WordPressdotorg\Meeting_Calendar
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Meeting_Calendar;

use function WordPressdotorg\Meeting_Calendar\ICS\Generator\get_frequency;

/**
 * REST routes and fields for meetings.
 */
class REST_API {

	/**
	 * The single instance of the class.
	 *
	 * @var REST_API|null
	 */
	protected static ?REST_API $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return REST_API
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the REST hooks.
	 */
	public static function init(): void {
		$rest_api = self::get_instance();
		add_action( 'rest_api_init', array( $rest_api, 'register_rest_field' ) );
		add_action( 'rest_api_init', array( $rest_api, 'register_rest_routes' ) );
	}

	/**
	 * Add the upcoming occurrences to meetings in the REST API, as `future_occurrences`.
	 */
	public function register_rest_field(): void {
		register_rest_field(
			'meeting',
			'future_occurrences',
			array(
				'get_callback' => array( \Meeting_Post_Type::getInstance(), 'get_future_occurrences' ),
			)
		);
	}

	/**
	 * Register the REST routes for listing occurrences and (un)cancelling them.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'wporg/v1',
			'/meetings/from/(?P<month>\d\d\d\d-\d\d-\d\d)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_occurrences_for_period' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'month' => array(
						'validate_callback' => array( \Meeting_Post_Type::getInstance(), 'is_valid_date' ),
					),
				),
			)
		);
		register_rest_route(
			'wporg/v1',
			'/meetings/(?P<meeting_id>\d+):(?P<date>\d\d\d\d-\d\d-\d\d)',
			array(
				'args' => array(
					'date' => array(
						'validate_callback' => array( \Meeting_Post_Type::getInstance(), 'is_valid_date' ),
					),
				),
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

	/**
	 * Get all meeting occurrences in the period starting at the requested month.
	 *
	 * @param \WP_REST_Request|array|null $request Request, or an array of params.
	 * @return array[] Occurrences, sorted by date and time.
	 */
	public function get_occurrences_for_period( \WP_REST_Request|array|null $request ): array {
		$meeting_post_type = \Meeting_Post_Type::getInstance();
		$meetings          = get_posts(
			array(
				'post_type'   => 'meeting',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);
		$period            = array( 'month' => $this->get_route_param( $request, 'month' ) );
		$out               = array();
		foreach ( $meetings as $meeting ) {
			$occurrences = $meeting_post_type->get_future_occurrences( $meeting, null, $period );

			$frequency = '';
			if ( ! empty( $meeting->recurring ) && ! empty( $occurrences ) ) {
				$frequency = get_frequency( $meeting->recurring, $occurrences[0], $meeting->occurrence );
			}

			foreach ( $occurrences as $occurrence ) {
				// An unparseable time used to be coerced to 00:00:00; keep that.
				$meeting->time = gmdate( 'H:i:s', (int) strtotime( (string) $meeting->time ) );
				$out[]         = array(
					'meeting_id'  => $meeting->ID,
					'instance_id' => "{$meeting->ID}:{$occurrence}",
					'date'        => $occurrence,
					'time'        => $meeting->time,
					'datetime'    => "{$occurrence}T{$meeting->time}+00:00",
					'team'        => ucwords( (string) $meeting->team ),
					'link'        => $meeting->link,
					'title'       => wp_specialchars_decode( $meeting->post_title, ENT_QUOTES ),
					'location'    => $meeting->location,
					'wptv_url'    => $meeting->wptv_url,
					'recurring'   => $meeting->recurring,
					'occurrence'  => $meeting->occurrence,
					'status'      => ( $meeting_post_type->is_meeting_cancelled( $meeting->ID, $occurrence ) ? 'cancelled' : 'active' ),
					'rrule'       => $frequency ? "RRULE:FREQ={$frequency}" : '',
					'edit_url'    => current_user_can( 'edit_post', $meeting->ID ) ? get_edit_post_link( $meeting->ID, 'raw' ) : '',
				);
			}
		}

		usort(
			$out,
			static function ( $a, $b ) {
				return $a['datetime'] <=> $b['datetime'];
			}
		);
		return $out;
	}

	/**
	 * Cancel a meeting's occurrence.
	 *
	 * @param \WP_REST_Request|array $request Request, or an array of params.
	 * @return int|bool|\WP_Error Meta ID, true if it was already cancelled, false on failure, or an error if the meeting doesn't occur on the date.
	 */
	public function cancel_meeting( \WP_REST_Request|array $request ): int|bool|\WP_Error {
		$meeting_post_type = \Meeting_Post_Type::getInstance();
		$meeting_id        = (int) $this->get_route_param( $request, 'meeting_id' );
		$date              = (string) $this->get_route_param( $request, 'date' );

		if ( ! $meeting_post_type->is_valid_occurrence( $meeting_id, $date ) ) {
			return new \WP_Error( 'invalid_date', 'The meeting does not occur on the given date.', array( 'status' => 400 ) );
		}

		// Store each cancellation once.
		if ( $meeting_post_type->is_meeting_cancelled( $meeting_id, $date ) ) {
			return true;
		}

		return add_post_meta( $meeting_id, 'meeting_cancelled', $date, false );
	}

	/**
	 * Un-cancel a meeting's occurrence.
	 *
	 * @param \WP_REST_Request|array $request Request, or an array of params.
	 * @return bool Whether the cancellation was removed.
	 */
	public function uncancel_meeting( \WP_REST_Request|array $request ): bool {
		return delete_post_meta( $this->get_route_param( $request, 'meeting_id' ), 'meeting_cancelled', $this->get_route_param( $request, 'date' ) );
	}

	/**
	 * Whether the current user can (un)cancel the requested meeting.
	 *
	 * @param \WP_REST_Request|array $request Request, or an array of params.
	 * @return bool
	 */
	public function can_cancel_meeting( \WP_REST_Request|array $request ): bool {
		return current_user_can( 'edit_post', $this->get_route_param( $request, 'meeting_id' ) );
	}

	/**
	 * Get a parameter from the route's URL path.
	 *
	 * Query and body args take precedence over URL params in a WP_REST_Request, so
	 * `?date=…` could otherwise act on a different date than the one in the path.
	 *
	 * @param \WP_REST_Request|array|null $request Request, or an array of params.
	 * @param string                      $key     Parameter name.
	 * @return mixed
	 */
	private function get_route_param( \WP_REST_Request|array|null $request, string $key ): mixed {
		if ( $request instanceof \WP_REST_Request ) {
			$request = $request->get_url_params();
		}

		return $request[ $key ] ?? null;
	}
}
