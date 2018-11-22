<?php
/**
 * Schedule class which include all the scheduled tasks
 */
class GHActivity_Schedule {

	function __construct() {
		add_action( 'gh_query_average_label_time', array( $this, 'record_average_label_times' ) );
		if ( ! wp_next_scheduled( 'gh_query_average_label_time' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_query_average_label_time' );
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
}

new GHActivity_Schedule();
