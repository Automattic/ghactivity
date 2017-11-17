<?php
/**
 * REST API endpoints.
 *
 * @package Ghactivity
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Custom REST API endpoints.
 * We'll use them to return aggregated data.
 *
 * @since 1.6.0
 */
class Ghactivity_Api {

	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Register all endpoints.
	 *
	 * @since 1.6.0
	 */
	public function register_endpoints() {
		/**
		 * Get a stats summary for a specific repo.
		 *
		 * @since 1.6.0
		 */
		register_rest_route( 'ghactivity/v1', '/stats/repo/(?P<repo>[0-9a-z\-_]+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_repo_stats' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'repo'  => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_string' ),
				),
			),
		) );
	}

	/**
	 * Check permissions for each one of our requests.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool $permission Returns true if user is allowed to call the API.
	 */
	public function permissions_check( $request ) {
		// return current_user_can( 'manage_options' );
		return true;
	}

	/**
	 * Validate a string
	 *
	 * @since 1.6.0
	 *
	 * @param string          $param   Parameter that needs to be validated.
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $key     key argument.
	 *
	 * @return bool $validated Is the string in a valid format.
	 */
	public function validate_string( $param, $request, $key ) {
		return is_string( $param );
	}

	/**
	 * Get a stats summary about a specific repo.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response $response Stats for a specific repo.
	 */
	public function get_repo_stats( $request ) {
		// Get parameter from request.
		if ( isset( $request['repo'] ) ) {
			$repo = esc_html( $request['repo'] );
		} else {
			return new WP_Error(
				'not_found',
				esc_html__( 'You did not specify a valid GitHub repo.', 'ghactivity' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Check if the repo exists on our site, if there were even events registered for it.
		 */
		$is_recorded_repo = get_term_by( 'slug', $repo, 'ghactivity_repo', ARRAY_A );
		if (
			! is_array( $is_recorded_repo )
			|| empty( $is_recorded_repo )
		) {
			return new WP_Error(
				'not_found',
				esc_html__( 'There were no events recorded for this repo.', 'ghactivity' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Is the repo fully monitored?
		 */
		$full_reporting = ( '1' === get_term_meta( (int) $is_recorded_repo['term_id'], 'full_reporting', true ) ? true : false );

		// Set some dates.
		$now = date( 'c' );
		$this_morning = date( 'c', strtotime( 'Today' ) );
		$first_of_week = date( 'c', strtotime( 'Last monday' ) );
		$first_of_month = date( 'c', strtotime( 'First day of this month' ) );

		/**
		 * And now get stats.
		 */
		$summary_this_day = GHActivity_Calls::count_posts_per_event_type(
			$this_morning,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		$summary_this_week = GHActivity_Calls::count_posts_per_event_type(
			$first_of_week,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		$summary_this_month = GHActivity_Calls::count_posts_per_event_type(
			$first_of_month,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		/**
		 * Build summaries per actor.
		 */
		$actors_this_day = GHActivity_Calls::count_posts_per_event_type(
			$this_morning,
			$now,
			'',
			$is_recorded_repo['slug'],
			true
		);

		$actors_this_week = GHActivity_Calls::count_posts_per_event_type(
			$first_of_week,
			$now,
			'',
			$is_recorded_repo['slug'],
			true
		);

		$actors_this_month = GHActivity_Calls::count_posts_per_event_type(
			$first_of_month,
			$now,
			'',
			$is_recorded_repo['slug'],
			true
		);

		$response = array(
			'name'              => $is_recorded_repo['name'],
			'full_reporting'    => $full_reporting,
			'date'              => $now,
			'this_day'          => $summary_this_day,
			'this_week'         => $summary_this_week,
			'this_month'        => $summary_this_month,
			'actors_this_day'   => $actors_this_day,
			'actors_this_week'  => $actors_this_week,
			'actors_this_month' => $actors_this_month,
		);
		return new WP_REST_Response( $response, 200 );
	}
} // End class.
new Ghactivity_Api();
