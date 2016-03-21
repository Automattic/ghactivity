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

		$query_url = sprintf(
			'https://api.github.com/users/%1$s/events?access_token=%2$s',
			$this->get_option( 'username' ),
			$this->get_option( 'access_token' )
		);
		$data = wp_remote_get( esc_url_raw( $query_url ) );

		if (
			is_wp_error( $data )
			|| 200 != $data['response']['code']
			|| empty( $data['body'] )
		) {
			return;
		}

		$response_body = json_decode( $data['body'] );

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
		} elseif ( 'IssueCommentEvent' == $event_type ) {
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
			$ghactivity_event_type = __( 'Deleted a branch');
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
		 * @param string $event_type Event type returned by GitHub API.
		 * @param string $action Action taken during event, as returned by GitHub API.
		 */
		$ghactivity_event_type = apply_filters( 'ghactivity_event_type', $event_type, $action );

		return $ghactivity_event_type;
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

			foreach( $github_events as $event ) {
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

					// Create taxonomies
					$taxonomies = array(
						'ghactivity_event_type' => esc_html( $this->get_event_type( $event->type, $action ) ),
						'ghactivity_repo' => esc_html( $event->repo->name ),
					);

					// Build Post Content.
					$post_content = sprintf(
						/* translators: %1$s is an action taken, %2$s is a number of commits. */
						__( '%1$s, including %2$s commits.', 'ghactivity' ),
						esc_html( $this->get_event_type( $event->type, $action ) ),
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

		// Start a Query
		$query = new WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			if ( has_term( 'comment', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['comment']++;
			}
			if ( has_term( 'issue-opened', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['issue-opened']++;
			}
			if ( has_term( 'issue-closed', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['issue-closed']++;
			}
			if ( has_term( 'issue-touched', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['issue-touched']++;
			}
			if ( has_term( 'reviewed-a-pr', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['reviewed-a-pr']++;
			}
			if ( has_term( 'deleted-a-branch', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['deleted-a-branch']++;
			}
			if ( has_term( 'pr-opened', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['pr-opened']++;
			}
			if ( has_term( 'pr-closed', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['pr-closed']++;
			}
			if ( has_term( 'pr-touched', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['pr-touched']++;
			}
			if ( has_term( 'did-something', 'ghactivity_event_type', $query->post->ID ) ) {
				$count['did-something']++;
			}
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

		// Start a Query
		$query = new WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			$count = $count + get_post_meta( $query->post->ID, '_github_commits', true );

		}
		wp_reset_postdata();

		return (int) $count;
	}
}
new GHActivity_Calls();
