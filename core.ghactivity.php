<?php
/**
 * GHActivity calls to GitHub API
 *
 * https://developer.github.com/v3/
 *
 * @since 1.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * GitHub API Calls
 *
 * @since 1.0
 */
class GHActivity_Calls {

	function __construct() {
		add_action( 'ghactivity_publish', array( $this, 'publish_event' ) );
		if ( ! wp_next_scheduled( 'ghactivity_publish' ) ) {
			wp_schedule_event( time(), 'hourly', 'ghactivity_publish' );
		}
	}

	/**
	 * Get option saved in the plugin's settings screen.
	 *
	 * @since 1.0
	 *
	 * @param string $name Option name.
	 *
	 * @return string $str Specific option.
	 */
	private function get_option( $name ) {
		$options = get_option( 'ghactivity' );

		if ( isset( $options[ $name ] ) ) {
			return $options[ $name ];
		} else {
			return '';
		}
	}

	/**
	 * Remote call to get data from GitHub's API.
	 *
	 * @since 1.0
	 *
	 * @return null|array
	 */
	private function get_github_activity() {

		$response_body = array();

		/**
		 * Create an array of usernames.
		 * I try to account for single usernames, comma separated lists, space separated lists, and comma + space lists.
		 */
		$usernames = array_filter( preg_split( '/[,\s]+/', $this->get_option( 'username' ) ) );

		// Loop through that array and make a request to the GitHub API for each person.
		foreach ( $usernames as $username ) {
			$query_url = sprintf(
				'https://api.github.com/users/%1$s/events?access_token=%2$s',
				$username,
				$this->get_option( 'access_token' )
			);

			$data = wp_remote_get( esc_url_raw( $query_url ) );

			if (
				is_wp_error( $data )
				|| 200 != $data['response']['code']
				|| empty( $data['body'] )
			) {
				continue;
			}

			$single_response_body = json_decode( $data['body'] );

			$response_body = array_merge( $single_response_body, $response_body );
		}

		return $response_body;
	}

	/**
	 * Get an event type to use as a taxonomy, and in the post content.
	 *
	 * Starts from data collected with GitHub API, and displays a nice event type instead.
	 * @see https://developer.github.com/v3/activity/events/types/
	 *
	 * @since 1.0
	 *
	 * @param string $event_type Event type returned by GitHub API.
	 * @param string $action Action taken during event, as returned by GitHub API.
	 *
	 * @return string $ghactivity_event_type Event type displayed in ghactivity_event_type taxonomy.
	 */
	private function get_event_type( $event_type, $action ) {
		if ( 'IssuesEvent' == $event_type ) {
			if ( 'closed' == $action ) {
				$ghactivity_event_type = __( 'Issue Closed', 'ghactivity' );
			} elseif ( 'opened' == $action ) {
				$ghactivity_event_type = __( 'Issue Opened', 'ghactivity' );
			} else {
				$ghactivity_event_type = __( 'Issue touched', 'ghactivity' );
			}
		} elseif ( 'PullRequestEvent' == $event_type ) {
			if ( 'closed' == $action ) {
				$ghactivity_event_type = __( 'PR Closed', 'ghactivity' );
			} elseif ( 'opened' == $action ) {
				$ghactivity_event_type = __( 'PR Opened', 'ghactivity' );
			} else {
				$ghactivity_event_type = __( 'PR touched', 'ghactivity' );
			}
		} elseif ( 'IssueCommentEvent' == $event_type || 'CommitCommentEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Comment', 'ghactivity' );
		} elseif ( 'PullRequestReviewCommentEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Reviewed a PR', 'ghactivity' );
		} elseif ( 'PushEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Pushed a branch', 'ghactivity' );
		} elseif ( 'CreateEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Created a tag', 'ghactivity' );
		} elseif ( 'ReleaseEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Created a release', 'ghactivity' );
		} elseif ( 'DeleteEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Deleted a branch' );
		} elseif ( 'GollumEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Edited a Wiki page' );
		} elseif ( 'ForkEvent' == $event_type ) {
			$ghactivity_event_type = __( 'Forked a repo' );
		} else {
			$ghactivity_event_type = __( 'Did something', 'ghactivity' );
		}

		/**
		 * Filter Event Type creation.
		 *
		 * Allows you to define your own Event types.
		 *
		 * @since 1.3
		 *
		 * @param string $ghactivity_event_type Clean event type returned after function.
		 * @param string $event_type Event type returned by GitHub API.
		 * @param string $action Action taken during event, as returned by GitHub API.
		 */
		$ghactivity_event_type = apply_filters( 'ghactivity_event_type', $ghactivity_event_type, $event_type, $action );

		return $ghactivity_event_type;
	}

	/**
	 * Get HTML link matching the event.
	 *
	 * @since 1.5.0
	 *
	 * @param object $event Event information returned by GitHub API.
	 * @param string $action Action taken during event, as returned by GitHub API.
	 *
	 * @return string $link_html HTML link matching the action recorded by GitHub.
	 */
	private function get_event_link( $event, $action ) {
		if (
			empty( $event )
			|| empty( $event->type )
			|| empty( $action )
		) {
			return '';
		}

		if ( 'IssuesEvent' == $event->type ) {
			$link = $event->payload->issue->html_url;
		} elseif ( 'PullRequestEvent' == $event->type ) {
			$link = $event->payload->pull_request->html_url;
		} elseif (
			'IssueCommentEvent' == $event->type
			|| 'CommitCommentEvent' == $event->type
			|| 'PullRequestReviewCommentEvent' == $event->type
		) {
			$link = $event->payload->comment->html_url;
		} elseif ( 'PushEvent' == $event->type ) {
			$link = sprintf(
				'https://github.com/%1$s/commits/%2$s',
				esc_attr( $event->repo->name ),
				esc_attr( $event->payload->head )
			);
		} elseif ( 'CreateEvent' == $event->type ) {
			$link = sprintf(
				'https://github.com/%1$s/tree/%2$s',
				esc_attr( $event->repo->name ),
				esc_attr( $event->payload->ref )
			);
		} elseif ( 'ReleaseEvent' == $event->type ) {
			$link = $event->payload->release->html_url;
		} elseif ( 'ForkEvent' == $event->type ) {
			$link = $event->payload->forkee->html_url;
		} else {
			$link = '';
		}

		if ( ! empty( $link ) ) {
			$link_html = sprintf(
				'<a href="%2$s">%1$s</a>',
				esc_html( $this->get_event_type( $event->type, $action ) ),
				esc_url( $link )
			);
		} else {
			$link_html = esc_html( $this->get_event_type( $event->type, $action ) );
		}

		/**
		 * Filter Event HTML link.
		 *
		 * @since 1.5.0
		 *
		 * @param string $link_html HTML tag including the link to the GitHub event.
		 * @param object $event Event information returned by GitHub API.
		 * @param string $action Action taken during event, as returned by GitHub API.
		 */
		return apply_filters( 'ghactivity_event_link_html', $link_html, $event, $action );
	}

	/**
	 * Publish GitHub Event.
	 *
	 * @since 1.0
	 */
	public function publish_event() {

		$github_events = $this->get_github_activity();

		/**
		 * Only go through the event list if we have valid event array.
		 */
		if ( isset( $github_events ) && is_array( $github_events ) ) {

			foreach ( $github_events as $event ) {
				// Let's not keep private events if you don't want to save them.
				if (
					false == $event->public
					&& true != $this->get_option( 'display_private' )
				) {
					continue;
				}

				// If no post exists with that ID, let's go on and publish a post.
				if ( is_null( get_page_by_title( $event->id, OBJECT, 'ghactivity_event' ) ) ) {

					// Store the number of commits attached to the event in post meta.
					if ( 'PushEvent' == $event->type ) {
						$meta = array( '_github_commits' => absint( $event->payload->distinct_size ) );
					} else {
						$meta = false;
					}

					// Avoid errors when no action is attached to the event.
					if ( isset( $event->payload->action ) ) {
						$action = $event->payload->action;
					} else {
						$action = '';
					}

					// Create taxonomies.
					$taxonomies = array(
						'ghactivity_event_type' => esc_html( $this->get_event_type( $event->type, $action ) ),
						'ghactivity_repo'       => esc_html( $event->repo->name ),
						'ghactivity_actor'      => esc_html( $event->actor->display_login ),
					);

					// Build Post Content.
					$post_content = sprintf(
						/* translators: %1$s is an action taken, %2$s is a number of commits. */
						__( '%1$s, including %2$s commits.', 'ghactivity' ),
						$this->get_event_link( $event, $action ),
						( $meta ? $meta['_github_commits'] : 'no' )
					);

					$event_args = array(
						'post_title'   => $event->id,
						'post_type'    => 'ghactivity_event',
						'post_status'  => 'publish',
						'post_date'    => $event->created_at,
						'tax_input'    => $taxonomies,
						'meta_input'   => $meta,
						'post_content' => $post_content,
					);
					$post_id = wp_insert_post( $event_args );
					wp_set_object_terms(
						$post_id, $taxonomies['ghactivity_event_type'], 'ghactivity_event_type', true
					);
					wp_set_object_terms(
						$post_id, $taxonomies['ghactivity_repo'], 'ghactivity_repo', true
					);
					wp_set_object_terms(
						$post_id, $taxonomies['ghactivity_actor'], 'ghactivity_actor', true
					);
				}
			}
		}
	}

	/**
	 * Count Posts per event type.
	 *
	 * @since 1.1
	 *
	 * @param string $date_start Starting date range, using a strtotime compatible format.
	 * @param string $date_end   End date range, using a strtotime compatible format.
	 *
	 * @return array $count Array of count of registered Event types.
	 */
	public static function count_posts_per_event_type( $date_start, $date_end ) {
		$count = array();

		$args = array(
			'post_type'      => 'ghactivity_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,  // Show all posts.
			'date_query'     => array(
				'after' => $date_start,
				'before' => $date_end,
				'inclusive' => true,
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

			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( isset( $count[ $term->slug ] ) ) {
						$count[ $term->slug ]++;
					} else {
						$count[ $term->slug ] = 1;
					}
				}
			}

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

		}
		wp_reset_postdata();

		return (array) $count;
	}

	/**
	 * Count number of commits.
	 *
	 * @since 1.1
	 *
	 * @param string $date_start Starting date range, using a strtotime compatible format.
	 * @param string $date_end   End date range, using a strtotime compatible format.
	 *
	 * @return int $count Number of commits during that time period.
	 */
	public static function count_commits( $date_start, $date_end ) {
		$count = 0;

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
	 *
	 * @return int $count Number of repos during that time period.
	 */
	public static function count_repos( $date_start, $date_end ) {
		$repos = array();

		$args = array(
			'post_type'      => 'ghactivity_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,  // Show all posts.
			'date_query'     => array(
				'after' => $date_start,
				'before' => $date_end,
				'inclusive' => true,
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
}
new GHActivity_Calls();
