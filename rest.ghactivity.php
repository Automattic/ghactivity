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
		 * Check Sync status for all Ghactivity issues in a repo.
		 *
		 * @since 2.0.0
		 */
		register_rest_route( 'ghactivity/v1', '/sync/(?P<repo>[a-zA-Z0-9-]+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'trigger_sync' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'repo' => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_string' ),
				),
			),
		) );

		/**
		 * Get a stats summary for a specific repo.
		 *
		 * @since 1.6.0
		 */
		register_rest_route( 'ghactivity/v1', '/stats/repo/(?P<repo>[0-9a-z\-_\/]+)', array(
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

		/**
		 * Get query records of specific repo/label.
		 *
		 * @since 2.1.0
		 */
		register_rest_route( 'ghactivity/v1', '/queries/average-label-time', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_average_label_time' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'id' => array(
					'required' => true,
				),
			),
		) );

		/**
		 * Rebuild all graphs on the site.
		 *
		 * @since 2.0.0
		 */
		register_rest_route( 'ghactivity/v1', '/build/graphs', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'redo_graphs' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );

		/**
		 * Get project stats for specific Org project.
		 *
		 * @since 2.1.0
		 */
		register_rest_route( 'ghactivity/v1', '/queries/project-stats/org/(?P<org>[0-9a-z\-_\/]+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_project_stats' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'org' => array(
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
		return current_user_can( 'manage_options' );
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
	 * Trigger a full synchronization of all issues in all watched repos.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response $response Response from the Sync function.
	 */
	public function trigger_sync( $request ) {
		// Get parameter from request.
		if ( isset( $request['repo'] ) ) {
			$repo = $request['repo'];
		} else {
			return new WP_Error(
				'not_found',
				esc_html__( 'You did not specify a repository.', 'ghactivity' ),
				array( 'status' => 404 )
			);
		}

		$options          = (array) get_option( 'ghactivity' );
		$repos_to_monitor = GHActivity_Queries::get_monitored_repos( 'all' );
		$repos_to_monitor = array_map(
			function( $term ) {
				return $term->slug;
			},
		$repos_to_monitor );

		// Gather info about our watched repos. Return early if we do not watch any repo yet.
		if ( empty( $repos_to_monitor ) ) {
			return new WP_REST_Response(
				esc_html__( 'You currently do not monitor activity on any repository. You cannot use this option yet.', 'ghactivity' ),
				200
			);
		}

		// Return an error if the repo asked for does not match an existing repo being watched.
		if ( ! in_array( $repo, $repos_to_monitor, true ) ) {
			return new WP_Error(
				'not_found',
				esc_html__( 'The specified repository is not currently being watched.', 'ghactivity' ),
				array( 'status' => 404 )
			);
		}

		// Return an error if Synchronization is already complete. No need to run it again.
		if (
			isset( $options[ $repo . '_full_sync' ], $options[ $repo . '_full_sync' ]['status'] )
			&& 'done' === $options[ $repo . '_full_sync' ]['status']
		) {
			return new WP_REST_Response(
				esc_html__( 'Synchronization is complete for this repository.', 'ghactivity' ),
				200
			);
		}

		// Return an error if Synchronization is currently in progress. Let's let it finish.
		if (
			isset( $options[ $repo . '_full_sync' ], $options[ $repo . '_full_sync' ]['status'] )
			&& 'in_progress' === $options[ $repo . '_full_sync' ]['status']
		) {
			return new WP_REST_Response(
				esc_html__( 'Synchronization for this repository is in progress. Give it some time!', 'ghactivity' ),
				200
			);
		}

		// No errors? Schedule a single event that will start in 2 seconds and trigger the full sync.
		if ( ! wp_next_scheduled( 'ghactivity_full_issue_sync' ) ) {
			wp_schedule_single_event( time(), 'ghactivity_full_issue_sync', array( $repo ) );
		}

		return new WP_REST_Response(
			sprintf(
				__( 'Synchronization has started. Give it a bit of time now. You can monitor progress <a href="%s">here</a>.', 'ghactivity' ),
				esc_url( get_admin_url( null, 'edit.php?post_type=ghactivity_issue' ) )
			),
			200
		);
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
		$summary_this_day = GHActivity_Queries::count_posts_per_event_type(
			$this_morning,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		$summary_this_week = GHActivity_Queries::count_posts_per_event_type(
			$first_of_week,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		$summary_this_month = GHActivity_Queries::count_posts_per_event_type(
			$first_of_month,
			$now,
			'',
			$is_recorded_repo['slug'],
			false
		);

		/**
		 * Build summaries per actor.
		 */
		$actors_this_day = GHActivity_Queries::count_posts_per_event_type(
			$this_morning,
			$now,
			'',
			$is_recorded_repo['slug'],
			true
		);

		$actors_this_week = GHActivity_Queries::count_posts_per_event_type(
			$first_of_week,
			$now,
			'',
			$is_recorded_repo['slug'],
			true
		);

		$actors_this_month = GHActivity_Queries::count_posts_per_event_type(
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

	/**
	 * Get an average label time for specified repo/label
	 *
	 * @since 2.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response $response Stats for a specific repo.
	 */
	public function get_average_label_time( $request ) {
		if ( ! empty( $request['id'] ) ) {
			$id = esc_html( $request['id'] );
		} else {
			return new WP_Error(
				'not_found',
				esc_html__( 'You did not specify a ID for the term you want to query.', 'ghactivity' ),
				array(
					'status' => 404,
				)
			);
		}

		// [average_time, date_of_record, recorded_issues]
		$records = GHActivity_Queries::fetch_average_label_time( $id, null );

		$response = array(
			'id'      => $id,
			'records' => $records,
		);
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Rebuild all graphs on the site.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response $response Response from the Build Graphs function.
	 */
	public function redo_graphs( $request ) {
		// Schedule a single event that will start in 2 seconds and build the graphs.
		wp_schedule_single_event( time(), 'gh_query_average_label_time' );

		return new WP_REST_Response(
			esc_html__( 'The graphs are now being rebuilt. Give it a bit of time.', 'ghactivity' ),
			200
		);
	}

	/**
	 * Get an project stats for specified org/project_name
	 *
	 * @since 2.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response $response Stats for a specific repo.
	 */
	public function get_project_stats( $request ) {
		if ( isset( $request['org'] ) && isset( $request->get_query_params()['project_name'] ) ) {
			$org  = esc_html( $request['org'] );
			$project_name = esc_html( $request->get_query_params()['project_name'] );
		} else {
			return new WP_Error(
				'not_found',
				esc_html__( 'You did not specify a valid GitHub org and/or project_name', 'ghactivity' ),
				array(
					'status' => 404,
				)
			);
		}

		// [average_time, date_of_record, recorded_issues]
		$records = GHActivity_Queries::fetch_project_stats( $org, $project_name );

		$response = array(
			'org'          => $org,
			'project_name' => $project_name,
			'records'      => $records,
		);
		return new WP_REST_Response( $response, 200 );
	}
} // End class.
new Ghactivity_Api();
