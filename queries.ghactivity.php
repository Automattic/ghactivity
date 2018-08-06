<?php
/**
 * Bunch of static functions which are fetching some data from DB.
 *
 * @since 2.1
 */
class GHActivity_Queries {

	/**
	 * Usage: current_average_label_time('Automattic/jetpack', '[Status] Needs Review').
	 *
	 * @param string $repo_name name of the repo.
	 * @param string $label issue label.
	 */
	public static function current_average_label_time( $repo_name, $label ) {
		$dates = array();
		$slugs = array();
		$query = array(
			'taxonomy' => 'ghactivity_issues_labels',
			'name'     => $label,
		);
		$term  = get_terms( $query )[0];
		$meta  = get_term_meta( $term->term_id );
		foreach ( $meta as $repo_slug => $serialized ) {
			// count only issues from specific repo.
			if ( strpos( strtolower( $repo_slug ), strtolower( $repo_name ) ) === 0 ) {
				$issue_number = explode( '#', $repo_slug )[1];
				$post_id      = self::find_open_gh_issue( $repo_name, $issue_number );
				$label_ary    = unserialize( $serialized[0] );

				// We want to capture only opened, labeled issues.
				if ( $post_id && 'labeled' === $label_ary['status'] ) {
					$dates[] = time() - strtotime( $label_ary['labeled'] );
					$slugs[] = $repo_slug;
				}
			}
		}
		return array( (int) array_sum( $dates ) / count( $dates ), $slugs );
	}

	public static function find_open_gh_issue( $repo_name, $issue_number ) {
		$post_id      = null;
		$is_open_args = array(
			'post_type'      => 'ghactivity_issue',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'ghactivity_issues_state',
					'field'    => 'name',
					'terms'    => 'open',
				),
				array(
					'taxonomy' => 'ghactivity_repo',
					'field'    => 'name',
					'terms'    => $repo_name,
				),
			),
			'meta_query' => array(
				array(
					'key'     => 'number',
					'value'   => $issue_number,
					'compare' => '=',
				),
			),
		);
		$query = new WP_Query( $is_open_args );
		if ( $query->have_posts() ) {
			$query->the_post();
			$post_id = $query->post->ID;
		}
		wp_reset_postdata();

		return $post_id;
	}

	/**
	 * Search for a exisiting `ghactivity_issue` post
	 * Return post_id if found, and null if not.
	 *
	 * @param string $repo_name name of the repo.
	 * @param int    $issue_number issue number.
	 *
	 * @return int $post_id ID of the post. Null if not found.
	 */
	public static function find_gh_issue( $repo_name, $issue_number ) {
		$post_id     = null;
		$is_new_args = array(
			'post_type'      => 'ghactivity_issue',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_repo',
					'field'    => 'name',
					'terms'    => $repo_name,
				),
			),
			'meta_query' => array(
				array(
					'key'     => 'number',
					'value'   => $issue_number,
					'compare' => '=',
				),
			),
		);
		$query = new WP_Query( $is_new_args );
		if ( $query->have_posts() ) {
			$query->the_post();
			$post_id = $query->post->ID;
		}
		wp_reset_postdata();

		return $post_id;
	}

	public static function fetch_average_label_time( $repo_name, $label, $range = null ) {
		$slug = $repo_name . '#' . $label;
		$args = array(
			'post_type'      => 'gh_query_record',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_query_label_slug',
					'field'    => 'name',
					'terms'    => $slug,
				),
			),
		);

		if ( isset( $range ) ) {
			$args['date_query'] = array(
				'after'     => $range[0],
				'before'    => $range[1],
				'inclusive' => true,
			);
		}

		// FIXME: Add caching
		$posts = get_posts( $args );

		function get_post_content( $post ) {
			return array(
				(int) $post->post_content,
				strtotime( $post->post_date ),
				get_post_meta( $post->ID, 'record_slugs', true ),
			);
		}
		return array_map( 'get_post_content', $posts );
	}
}
