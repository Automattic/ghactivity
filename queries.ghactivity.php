<?php
/**
 * Bunch of static functions which are fetching some data from DB.
 *
 * @since 2.0.0
 */
class GHActivity_Queries {

	/**
	 * Count Posts per event type.
	 *
	 * @since 1.1
	 *
	 * @param string       $date_start      Starting date range, using a strtotime compatible format.
	 * @param string       $date_end        End date range, using a strtotime compatible format.
	 * @param string       $person          Get stats for a specific GitHub username.
	 * @param string|array $repo            Get stats for a specific GitHub repo, or a list of repos.
	 * @param bool         $split_per_actor Split counts per actor.
	 *
	 * @return array       $count           Array of count of registered Event types.
	 */
	public static function count_posts_per_event_type( $date_start, $date_end, $person = '', $repo = '', $split_per_actor = false ) {
		$count = array();

		if ( empty( $person ) ) {
			$person = get_terms( array(
				'taxonomy'   => 'ghactivity_actor',
				'hide_empty' => false,
			) );

			$person = wp_list_pluck( $person, 'name' );
		} elseif ( is_string( $person ) ) {
			$person = esc_html( $person );
		} elseif ( is_array( $person ) ) {
			$person = $person;
		}

		if ( empty( $repo ) ) {
			$repo = get_terms( array(
				'taxonomy'   => 'ghactivity_repo',
				'hide_empty' => true,
				'fields'     => 'id=>slug',
			) );

			$repo = array_values( $repo );
		} elseif ( is_string( $repo ) ) {
			$repo = esc_html( $repo );
		} elseif ( is_array( $repo ) ) {
			$repo = $repo;
		}

		$args = array(
			'post_type'      => 'ghactivity_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,  // Show all posts.
			'date_query'     => array(
				'after' => $date_start,
				'before' => $date_end,
				'inclusive' => true,
			),
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'ghactivity_actor',
					'field'    => 'name',
					'terms'    => $person,
				),
				array(
					'taxonomy' => 'ghactivity_repo',
					'field'    => 'slug',
					'terms'    => $repo,
				),
			),
		);
		/**
		 * Filter WP Query arguments used to count Posts per event type.
		 *
		 * @since 1.2
		 *
		 * @param array $args Array of WP Query arguments.
		 */
		$args = apply_filters( 'ghactivity_count_posts_event_type_query_args', $args );

		// Start a Query.
		$query = new WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			$terms = get_the_terms( $query->post->ID, 'ghactivity_event_type' );

			/**
			 * If we want to split the counts per actor,
			 * we need to create an multidimensional array,
			 * with counts for each person.
			 */
			if ( true === $split_per_actor ) {
				$actor = get_the_terms( $query->post->ID, 'ghactivity_actor' );
				if (
					$terms
					&& ! is_wp_error( $terms )
					&& $actor
					&& ! is_wp_error( $actor )
				) {
					// Get the person's name.
					foreach ( $actor as $a ) {
						$actor_name = esc_html( $a->name );
					}

					if ( ! isset( $count[ $actor_name ] ) ) {
						$count[ $actor_name ] = array();
					}
					foreach ( $terms as $term ) {
						if ( isset( $count[ $actor_name ][ $term->slug ] ) ) {
							$count[ $actor_name ][ $term->slug ]++;
						} else {
							$count[ $actor_name ][ $term->slug ] = 1;
						}

						if ( isset( $count[ $actor_name ]['total'] ) ) {
							$count[ $actor_name ]['total']++;
						} else {
							$count[ $actor_name ]['total'] = 1;
						}
					}
				}
			} else {
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( isset( $count[ $term->slug ] ) ) {
							$count[ $term->slug ]++;
						} else {
							$count[ $term->slug ] = 1;
						}
					}
				}
			} // End if().

			/**
			 * Filter the final array of event types and matching counts after calculation.
			 *
			 * Allows one to add their own a action, matching a specific term or Query element.
			 *
			 * @since 1.3
			 *
			 * @param array $count Array of count of registered Event types.
			 */
			$count = apply_filters( 'ghactivity_count_posts_event_type_counts', $count, $query );

		} // End while().
		wp_reset_postdata();

		// Sort the actors by total descending.
		if ( true === $split_per_actor ) {
			uasort( $count, array( 'GHActivity_Queries', 'sort_totals' ) );
		}

		return (array) $count;
	}

	/**
	 * Custom function to sort our counts.
	 *
	 * @since 1.6.0
	 *
	 * @param int $a Total number of contributions.
	 * @param int $b Total number of contributions.
	 */
	private static function sort_totals( $a, $b ) {
		return $a['total'] < $b['total'];
	}

	/**
	 * Count number of commits.
	 *
	 * @since 1.1
	 *
	 * @param string $date_start Starting date range, using a strtotime compatible format.
	 * @param string $date_end   End date range, using a strtotime compatible format.
	 * @param string $person     Get stats for a specific GitHub username.
	 *
	 * @return int $count Number of commits during that time period.
	 */
	public static function count_commits( $date_start, $date_end, $person = '' ) {
		$count = 0;

		if ( empty( $person ) ) {
			$person = get_terms( array(
				'taxonomy'   => 'ghactivity_actor',
				'hide_empty' => false,
			) );

			$person = wp_list_pluck( $person, 'name' );
		} elseif ( is_array( $person ) ) {
			$person = $person;
		} else {
			$person = esc_html( $person );
		}

		$args = array(
			'post_type'      => 'ghactivity_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,  // Show all posts.
			'meta_key'       => '_github_commits',
			'date_query'     => array(
				'after' => $date_start,
				'before' => $date_end,
				'inclusive' => true,
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_actor',
					'field'    => 'name',
					'terms'    => $person,
				),
			),
		);
		/**
		 * Filter WP Query arguments used to count the number of commits in a specific date range.
		 *
		 * @since 1.2
		 *
		 * @param array $args Array of WP Query arguments.
		 */
		$args = apply_filters( 'ghactivity_count_commits_query_args', $args );

		// Start a Query.
		$query = new WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			$count = $count + get_post_meta( $query->post->ID, '_github_commits', true );

		}
		wp_reset_postdata();

		return (int) $count;
	}

	/**
	 * Count the number of repos where you were involved in a specific time period.
	 *
	 * @since 1.4
	 *
	 * @param string $date_start Starting date range, using a strtotime compatible format.
	 * @param string $date_end   End date range, using a strtotime compatible format.
	 * @param string $person     Get stats for a specific GitHub username.
	 *
	 * @return int $count Number of repos during that time period.
	 */
	public static function count_repos( $date_start, $date_end, $person = '' ) {
		$repos = array();

		if ( empty( $person ) ) {
			$person = get_terms( array(
				'taxonomy'   => 'ghactivity_actor',
				'hide_empty' => false,
			) );

			$person = wp_list_pluck( $person, 'name' );
		} elseif ( is_array( $person ) ) {
			$person = $person;
		} else {
			$person = esc_html( $person );
		}

		$args = array(
			'post_type'      => 'ghactivity_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,  // Show all posts.
			'date_query'     => array(
				'after'     => $date_start,
				'before'    => $date_end,
				'inclusive' => true,
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_actor',
					'field'    => 'name',
					'terms'    => $person,
				),
			),
		);
		/**
		 * Filter WP Query arguments used to count the number of repos in a specific date range.
		 *
		 * @since 1.4
		 *
		 * @param array $args Array of WP Query arguments.
		 */
		$args = apply_filters( 'ghactivity_count_repos_query_args', $args );

		// Start a Query.
		$query = new WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			$terms = get_the_terms( $query->post->ID, 'ghactivity_repo' );

			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( isset( $repos[ $term->slug ] ) ) {
						$repos[ $term->slug ]++;
					} else {
						$repos[ $term->slug ] = 1;
					}
				}
			}
		}
		wp_reset_postdata();

		return (int) count( $repos );
	}

	/**
	 * Usage: current_average_label_time('Automattic/jetpack', '[Status] Needs Review').
	 *
	 * @param string $repo_name name of the repo.
	 * @param string|Array $labels string or array of labels.
	 */
	public static function current_average_label_time( $repo_name, $labels ) {
		$dates = array();
		$slugs = array();
		$query = array(
			'taxonomy' => 'ghactivity_issues_labels',
			'name'     => $labels,
		);

		$terms            = get_terms( $query );
		$first_term       = array_shift( $terms );
		$intersected_meta = get_term_meta( $first_term->term_id );

		// Find all the records which appears in every term meta.
		// e.g. find issues which marked with the combination of all $labels.
		foreach ( $terms as $term ) {
			$meta  = get_term_meta( $term->term_id );
			$intersected_meta = array_intersect_key( $intersected_meta, $meta );
		}

		/**
		 * Iterate over all the records and capture only opened & currently labeled issues
		 * Also fills the $dates & $slugs arrays with slugs and labeled dates
		 */
		foreach ( $intersected_meta as $repo_slug => $serialized ) {
			// count only issues from specific repo.
			if ( strpos( strtolower( $repo_slug ), strtolower( $repo_name ) ) === 0 ) {
				$issue_number = explode( '#', $repo_slug )[1];
				$post_id      = self::find_open_gh_issue( $repo_name, $issue_number );
				$label_ary    = unserialize( $serialized[0] );

				// We want to capture only opened, labeled issues.
				if ( $post_id && 'labeled' === $label_ary['status'] ) {
					$time                = time() - strtotime( $label_ary['labeled'] );
					$dates[]             = $time;
					$slugs[ $repo_slug ] = $time;
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

	public static function fetch_average_label_time( $id, $range = null ) {
		$args = array(
			'post_type'      => 'gh_query_record',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_query_label_slug',
					'field'    => 'id',
					'terms'    => $id,
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

	/**
	 * Get an array of repos we want to follow a bit more closely.
	 * For those repos we will log activity from everyone,
	 * not just from the usernames set in the plugin options.
	 *
	 * We will select all repos from the ghactivity_repo taxonomy,
	 * and monitor all those that have the `full_reporting` term meta set to true.
	 *
	 * @since 2.0.0
	 *
	 * @param string $fields Type of info to return. Accept id=>name, all, or names. Default to id=>name.
	 *
	 * @return WP_Error|array $repos_to_monitor Array of repos to monitor.
	 */
	public static function get_monitored_repos( $fields = 'id=>name' ) {
		$repos_query_args = array(
			'taxonomy'   => 'ghactivity_repo',
			'hide_empty' => false,
			'number'     => 10, // Just to make sure we don't get rate-limited by GH.
			'fields'     => $fields,
			'meta_query' => array(
				array(
					'key'     => 'full_reporting',
					'value'   => true,
					'compare' => '=',
				),
			),
		);
		$repos_to_monitor = get_terms( $repos_query_args );

		return $repos_to_monitor;
	}

	public static function fetch_project_stats( $org_name, $project_name, $range = null ) {
		$slug = $org_name . '#' . $project_name;
		$args = array(
			'post_type'      => 'gh_query_record',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_query_project_slug',
					'field'    => 'name',
					'terms'    => $slug,
				),
			),
		);

		// FIXME: Add caching
		$posts = get_posts( $args );

		function get_post_content( $post ) {
			return array(
				'post_content' => esc_html( $post->post_content ),
				'post_date'    => esc_html( strtotime( $post->post_date ) ),
				'columns'      => json_decode( get_post_meta( $post->ID, 'recorded_columns', true ) ),
				'project_url'  => esc_url( get_post_meta( $post->ID, 'project_url', true ) ),
			);
		}
		return array_map( 'get_post_content', $posts );
	}

	public static function current_project_stats( $org_name, $project_name ) {
		$columns          = array();
		$minified_columns = array();
		$options          = get_option( 'ghactivity' );

		$api = new GHActivity_GHApi( $options['access_token'] );

		// Find matching project first.
		$project_array = $api->get_projects( $org_name );
		foreach ( $project_array as $proj ) {
			if ( $project_name === $proj->name ) {
				$project = $proj;
				break;
			}
		}

		// Collect column cards from GH api endpoint.
		$columns_array = $api->get_project_columns( $project->id );
		foreach ( $columns_array as $column ) {
			$cards_array              = $api->get_project_column_cards( $column->id );
			$columns[ $column->name ] = $cards_array;
		}

		// Collect only relevant card data (to save array size).
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

		return array( $minified_columns, $project->html_url );
	}

	/**
	 * Returns array of post_ids of open issues for specified repo
	 */
	public static function get_all_open_gh_issues( $repo_name ) {
		$is_open_args = array(
			'post_type'      => 'ghactivity_issue',
			'post_status'    => 'publish',
			'fields'          => 'ids', // Only get post IDs
			'posts_per_page' => -1,
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
		);
		$posts_ids = wp_cache_get( 'all_open_gh_issues_' . $repo_name );
		if ( false === $posts_ids ) {
			$posts_ids = get_posts( $is_open_args );
			wp_reset_postdata();
			wp_cache_set( 'all_open_gh_issues_' . $repo_name, $posts_ids, '', 30 * 60 /** 30 min */ );
		}
		return $posts_ids;
	}
}
