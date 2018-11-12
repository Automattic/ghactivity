<?php
/**
 * Schedule class which include all the scheduled tasks
 */
class GHActivity_Schedule {

	function __construct() {
		// record_average_label_times.
		add_action( 'gh_query_average_label_time', array( $this, 'record_average_label_times' ) );
		if ( ! wp_next_scheduled( 'gh_query_average_label_time' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_query_average_label_time' );
		}

		// record_projects_stats.
		add_action( 'gh_query_project_stats', array( $this, 'record_projects_stats' ) );
		if ( ! wp_next_scheduled( 'gh_query_project_stats' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_query_project_stats' );
		}

		add_action( 'gh_query_update_repo_meta', array( $this, 'record_repo_meta' ) );
		if ( ! wp_next_scheduled( 'gh_query_update_repo_meta' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_query_update_repo_meta' );
		}

		add_action( 'gh_query_repo_labels_state', array( $this, 'record_current_repo_labels_state' ) );
		if ( ! wp_next_scheduled( 'gh_query_repo_labels_state' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_query_repo_labels_state' );
		}
	}

	public function record_average_label_times() {
		$label_slugs = get_terms(
			array(
				'taxonomy'   => 'ghactivity_query_label_slug',
				'hide_empty' => 0,
			)
		);
		foreach ( $label_slugs as $term ) {
			$term_name = explode( '#', $term->name );
			$labels = explode( ',', $term_name[1] );
			$this->record_average_label_time( $term_name[0], $labels );
		}
	}

	public function record_average_label_time( $repo_name, $labels ) {
		$record          = GHActivity_Queries::current_average_label_time( $repo_name, $labels );
		$record_avg_time = $record[0];
		$record_slugs    = $record[1];

		$taxonomies = array(
			'ghactivity_query_record_type' => 'average_label_time',
			'ghactivity_repo'              => $repo_name,
			'ghactivity_query_label_slug'  => $repo_name . '#' . implode( ',', $labels ),
		);

		$event_args = array(
			'post_title'   => $repo_name . ' | ' . implode( ',', $labels ) . ' | ' . date( DATE_RSS ),
			'post_type'    => 'gh_query_record',
			'post_status'  => 'publish',
			'tax_input'    => $taxonomies,
			'meta_input'   => array( 'record_slugs' => $record_slugs ),
			'post_content' => $record_avg_time,
		);
		$post_id = wp_insert_post( $event_args );

		foreach ( $taxonomies as $taxonomy => $value ) {
			$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, true );
			// we can set Repo and label as term meta
			// update_term_meta( $term_taxonomy_id[0], 'issue_slugs', $record_slugs );
		}
	}

	public function record_projects_stats() {
		$label_slugs = get_terms(
			array(
				'taxonomy'   => 'ghactivity_query_project_slug',
				'hide_empty' => 0,
			)
		);
		foreach ( $label_slugs as $term ) {
			$term_slug = explode( '#', $term->name );

			$this->record_project_stats( $term_slug[0], $term_slug[1] );
		}
	}

	public function record_project_stats( $org_name, $project_name ) {
		$record      = GHActivity_Queries::current_project_stats( $org_name, $project_name );
		$columns     = $record[0];
		$project_url = $record[1];

		$taxonomies = array(
			'ghactivity_query_record_type'  => 'project_stats',
			'ghactivity_query_project_slug' => $org_name . '#' . $project_name,
		);

		$event_args = array(
			'post_title'  => $org_name . ' | ' . $project_name . ' | ' . date( DATE_RSS ),
			'post_type'   => 'gh_query_record',
			'post_status' => 'publish',
			'tax_input'   => $taxonomies,
			'meta_input'  => array(
				'recorded_columns' => wp_json_encode( $columns ),
				'project_url'      => $project_url,
			),
		);

		$post_id = wp_insert_post( $event_args );
		foreach ( $taxonomies as $taxonomy => $value ) {
			$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, true );
		}
	}
	/**
	 * Task for updating list of repo labels for all the monitored(full_reporting) repos.
	 *
	 * @since 2.0.0
	 */
	public function record_repo_meta() {
		$api   = new GHActivity_GHApi();
		$repos = GHActivity_Queries::get_monitored_repos();

		foreach ( $repos as $term_id => $name ) {
			$labels = $api->get_repo_label_names( $name );
			update_term_meta( $term_id, 'repo_labels', $labels );

			$open_issues_count = $api->get_repo_open_issues_count( $name );
			update_term_meta( $term_id, 'open_issues_count', $open_issues_count );		}
	}

	/**
	 * Records current Repo Label State in format $label => $labeled_issue_count
	 * Splits all the labels in categories mentioned between `[` & `]`.
	 * Example categories for Jetpack repo: Pri, Status, Type, none
	 *
	 * @since 2.0.0
	 */
	public function record_current_repo_labels_state() {
		$repos = GHActivity_Queries::get_monitored_repos();

		foreach ( $repos as $term_id => $repo_name ) {
			$repo_labels_state      = GHActivity_Queries::current_repo_labels_state( $repo_name );
			$repo_label_issues      = $repo_labels_state[0];
			$repo_open_issues_count = $repo_labels_state[1];
			$final_state            = array();
			// We are interest in number of labeled issues rather then the issue numbers.
			// Also we would like to split this list into label categories.
			// TODO: maybe move categorising code into shortcode/frontend code since it seems more logical to have it there.
			foreach ( $repo_label_issues as $label => $issue_slugs ) {
				if ( strpos( $label, ']' ) !== false ) {
					$label_type = explode( ' ', $label )[0];
					$label_type = str_replace( '[', '', $label_type );
					$label_type = str_replace( ']', '', $label_type );
				} else {
					$label_type = 'none';
				}

				// Init 2d array for the first time.
				if ( ! array_key_exists( $label_type, $final_state ) ) {
					$final_state[ $label_type ] = array();
				}

				// Skip labels without any labeled issues.
				if ( count( $issue_slugs ) <= 0 ) {
					continue;
				}

				$current_label_counts = array( $label => count( $issue_slugs ) );
				$final_state[ $label_type ] = array_merge( $final_state[ $label_type ], $current_label_counts );
			}

			$taxonomies = array(
				'ghactivity_query_record_type' => 'repo_label_state',
				'ghactivity_repo'              => $repo_name,
			);

			$event_args = array(
				'post_title'  => 'Current Repo State' . ' | ' . $repo_name . ' | ' . date( DATE_RSS ),
				'post_type'   => 'gh_query_record',
				'post_status' => 'publish',
				'tax_input'   => $taxonomies,
				'meta_input'  => array(
					'final_state'       => $final_state,
					'open_issues_count' => $repo_open_issues_count,
				),
			);
			$post_id = wp_insert_post( $event_args );

			// Add taxonomies to the new record.
			foreach ( $taxonomies as $taxonomy => $value ) {
				wp_set_object_terms( $post_id, $value, $taxonomy, true );
			}
		}
	}
}

new GHActivity_Schedule();
