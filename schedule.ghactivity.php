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
	}

	public function record_average_label_times() {
		$label_slugs = get_terms(
			array(
				'taxonomy'   => 'ghactivity_query_label_slug',
				'hide_empty' => 0,
			)
		);
		foreach ( $label_slugs as $term ) {
			$term_slug = explode( '#', $term->name );

			$this->record_average_label_time( $term_slug[0], $term_slug[1] );
		}
	}

	public function record_average_label_time( $repo_name, $label ) {
		$record          = GHActivity_Queries::current_average_label_time( $repo_name, $label );
		$record_avg_time = $record[0];
		$record_slugs    = $record[1];

		$taxonomies = array(
			'ghactivity_query_record_type' => 'average_label_time',
			'ghactivity_repo'              => $repo_name,
			'ghactivity_query_label_slug'  => $repo_name . '#' . $label,
		);

		$event_args = array(
			'post_title'   => $repo_name . ' | ' . $label . ' | ' . date( DATE_RSS ),
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

	public function record_project_stats( $org_name, $project_name, $allowed_columns = null ) {
		$options = get_option( 'ghactivity' );
		$api = new GHActivity_GHApi( $options['access_token'] );

		// Find project ID first.
		$project_array = $api->get_projects( $org_name );
		foreach ( $project_array as $proj ) {
			if ( $project_name === $proj->name ) {
				$project_id = $proj->id;
				break;
			}
		}

		$columns_array = $api->get_project_columns( $project_id );

		$columns = array();
		foreach ( $columns_array as $column ) {
			if ( is_null( $allowed_columns ) || in_array( $column->name, $allowed_columns ) ) {
				$cards_array = $api->get_project_column_cards( $column->id );
				$columns[ $column->name ] = $cards_array;
			}
		}

		$minified_columns = array();
		foreach ( $columns as $column_name => $cards ) {
			$minified_columns[ $column_name ] = array_map(
				function( $card ) {
					$result = array(
						'creator'    => $card->creator->login,
						'created_at' => $card->created_at,
						'updated_at' => $card->updated_at,
					);
					if ( property_exists( $card, 'content_url' ) ) {
						$html_url = str_replace( 'api.', '', str_replace( 'repos/', '', $card->content_url ) );
						$result['content_url'] = $card->content_url;
						$result['html_url']    = $html_url;
					}
					return $result;
				},
			$cards );
		}

		// To get last updated time - it is needed to fetch some more endpoints

		$taxonomies = array(
			'ghactivity_query_record_type'   => 'project_stats',
			'ghactivity_query_project_slug'  => $org_name . '#' . $project_name,
		);

		$event_args = array(
			'post_title'   => $org_name . ' | ' . $project_name . ' | ' . date( DATE_RSS ),
			'post_type'    => 'gh_query_record',
			'post_status'  => 'publish',
			'tax_input'    => $taxonomies,
			'meta_input'   => array( 'recorded_columns' => wp_json_encode( $minified_columns ) ),
			'post_content' => 'Allowed columns: ' . wp_json_encode( $allowed_columns ),
		);

		$post_id = wp_insert_post( $event_args );
		foreach ( $taxonomies as $taxonomy => $value ) {
			$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, true );
		}
	}
}

new GHActivity_Schedule();
