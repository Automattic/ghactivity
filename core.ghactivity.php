<?php
/**
 * GHActivity calls to GitHub API
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
	public $api;

	function __construct() {
		add_action( 'ghactivity_publish', array( $this, 'publish_event' ) );
		if ( ! wp_next_scheduled( 'ghactivity_publish' ) ) {
			wp_schedule_event( time(), 'hourly', 'ghactivity_publish' );
		}

		// Trigger a single event to launch the full sync loop.
		add_action( 'ghactivity_full_issue_sync', array( $this, 'full_issue_sync' ), 10, 1 );

		$this->api = new GHActivity_GHApi( $this->get_option( 'access_token' ) );
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
	 * Save option in our array of 'ghactivity' options.
	 *
	 * @since 2.0.0
	 *
	 * @param string       $name  Option name.
	 * @param string|array $value Option value.
	 */
	private function update_option( $name, $value ) {
		$options = get_option( 'ghactivity' );
		if ( isset( $value ) && ! empty( $value ) ) {
			$options[ $name ] = $value;
			update_option( 'ghactivity', $options );
		}
	}

	/**
	 * Generate array of labels from multidimensional array.
	 * Utility function.
	 *
	 * @since 2.0.0
	 *
	 * @param array $labels Array of labels and their details as provided by GitHub.
	 *
	 * @return array $label_names Array of label names.
	 */
	private function get_label_names( $labels = array() ) {
		$label_names = array();

		if ( ! empty( $labels ) ) {
			foreach ( $labels as $label ) {
				$label_names[] = esc_html( $label->name );
			}
		}

		return $label_names;
	}

	/**
	 * Get an event type to use as a taxonomy, and in the post content.
	 *
	 * Starts from data collected with GitHub API, and displays a nice event type instead.
	 *
	 * @see https://developer.github.com/v3/activity/events/types/
	 *
	 * @since 1.0
	 *
	 * @param string $event_type Event type returned by GitHub API.
	 * @param string $action Action taken during event, as returned by GitHub API.
	 *
	 * @return string $ghactivity_event_type Event type displayed in ghactivity_event_type taxonomy.
	 */
	private function get_event_type( $event_type, $action = '' ) {
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
	private function get_event_link( $event, $action = '' ) {
		if (
			empty( $event )
			|| empty( $event->type )
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
		// Avoid timeouts during the data import process.
		set_time_limit( 0 );

		$github_events = $this->api->get_github_activity( $this->get_option( 'username' ) );

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
					$post_content = $this->get_event_link( $event, $action );

					// Mention the number of commits if there are any.
					if ( $meta ) {
						$post_content .= sprintf(
							__( ', including %1$s commits.', 'ghactivity' ),
							$meta['_github_commits']
						);
					}

					/**
					 * Small interlude: let's record info in the ghactivity_issue CPT
					 * if the event is about an issue or PR.
					 */
					if (
						(
							'PullRequestEvent' === $event->type
							|| 'IssuesEvent' === $event->type
							|| 'IssueCommentEvent' === $event->type
							|| 'PullRequestReviewCommentEvent' === $event->type
						)
						&& (
							! empty( $event->payload->issue )
							|| ! empty( $event->payload->pull_request )
						)
						&& (
							in_array(
								$event->repo->name,
								/**
								 * Allow site owners to only log issues for specific repos.
								 *
								 * @since 2.0.0
								 *
								 * @param array $repos Array of repos for which we want to monitor events.
								 */
								apply_filters( 'ghactivity_issues_repo_to_monitor', GHActivity_Queries::get_monitored_repos( 'names' ) )
							)
						)
					) {
						$this->log_issue( $event );
					}

					// Finally, publish our event.
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

					/**
					 * Establish the relationship between terms and taxonomies.
					 */
					foreach ( $taxonomies as $taxonomy => $value ) {
						$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, true );

						/**
						 * Since wp_set_object_terms returned an array of term_taxonomy_ids after running,
						 * we can use it to add more info to each term.
						 * From Term taxonomy IDs, we'll get term IDs.
						 * Then from there, we'l update the term and add a description and additional information if needed.
						 */
						if ( is_array( $term_taxonomy_ids ) && ! empty( $term_taxonomy_ids ) ) {
							foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
								/**
								 * Let's search for people without info attached to their profile.
								 * We'll try to get that info from GitHub.
								 */
								$term_id_object = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, 'ghactivity_actor', ARRAY_A );
								$term_id        = (int) $term_id_object['term_id'];
								if (
									is_array( $term_id_object )
									&& 'ghactivity_actor' === $term_id_object['taxonomy']
									&& empty( get_term_meta( $term_id, 'github_info', true ) )
								) {
									$gh_user_details = $this->api->get_person_details( $term_id_object['slug'] );
									if ( ! empty( $gh_user_details ) ) {
										// Add a bio and change the nice name.
										$person_args = array(
											'name'        => esc_html( $gh_user_details['name'] ),
											'description' => esc_html( $gh_user_details['bio'] ),
										);
										wp_update_term( $term_id, 'ghactivity_actor', $person_args );

										// Save all the info as term meta.
										update_term_meta( $term_id, 'github_info', $gh_user_details );
									}
								}
							}
						}
					} // End foreach().
				}
			}

			$this->update_issue_records();
		}
	}

	/**
	 * Get info about a specific issue/PR and record it in our ghactivity_issue CPT.
	 *
	 * @since 2.0.0
	 *
	 * @param Object $event GitHub event data.
	 */
	private function log_issue( $event ) {
		// Are we backfilling issues and PRs? In this case we hit a different endpoint, with different data structure.
		if ( ! isset( $event->type ) ) {
			$issue_type = ( isset( $event->pull_request ) ? 'pull_request' : 'issue' );
			$created_at = $event->created_at;
			$state      = $event->state;
			$title      = esc_html( $event->title );
			$labels     = $this->get_label_names( $event->labels );
			$number     = $event->number;
			$creator    = $event->user->login;
			$repo_name  = ( preg_match( '/([^\/]+)\/([^\/]+)$/', $event->repository_url, $matches ) ) ? $matches[0] : '';
			$comments   = $event->comments;
		} else {
			// Is it an issue or a PR event?
			if ( ! empty( $event->payload->pull_request ) ) {
				$issue_type = 'pull_request';
				$created_at = $event->payload->pull_request->created_at;
				$state      = $event->payload->pull_request->state;
				$title      = esc_html( $event->payload->pull_request->title );
				$labels     = ( isset( $event->payload->pull_request->labels ) ? $this->get_label_names( $event->payload->pull_request->labels ) : array() );
				$number     = $event->payload->pull_request->number;
				$repo_name  = $event->repo->name;
			} else {
				$issue_type = 'issue';
				$created_at = $event->payload->issue->created_at;
				$state      = $event->payload->issue->state;
				$title      = esc_html( $event->payload->issue->title );
				$labels     = ( isset( $event->payload->issue->labels ) ? $this->get_label_names( $event->payload->issue->labels ) : array() );
				$number     = $event->payload->issue->number;
				$repo_name  = $event->repo->name;
			}

			/**
			 * Specify a creator when an issue or PR is opened.
			 * Favorize display_login when possible.
			 */
			if ( 'opened' === $event->payload->action ) {
				$creator = esc_html( $event->actor->display_login );
			} elseif ( ! empty( $event->payload->pull_request ) ) {
				$creator = esc_html( $event->payload->pull_request->user->login );
			} elseif ( ! empty( $event->payload->issue ) ) {
				$creator = esc_html( $event->payload->issue->user->login );
			} else {
				$creator = '';
			}

			// Get the number of comments.
			$comments = ( isset( $event->payload->comments ) ? $event->payload->comments : 0 );
		}

		// Record event.
		$issue_details = array(
			'type'       => $issue_type,
			'created_at' => $created_at,
			'number'     => ( ! empty( $number ) ? absint( $number ) : 0 ),
			'repo_name'  => esc_html( $repo_name ),
			'state'      => ( isset( $state ) ? esc_html( $state ) : 'open' ),
			'title'      => esc_html( $repo_name ) . '#' . ( ! empty( $number ) ? absint( $number ) : 0 ),
			'issue_name' => $title,
			'comments'   => $comments,
			'creator'    => $creator,
			'labels'     => $labels,
		);
		$this->record_issue_details( $issue_details );
	}

	/**
	 * Record data about each one of our issues in the ghactivity_issue CPT.
	 *
	 * @since 2.0.0
	 *
	 * @param array $issue_details {
	 * 	Array of information about the issue.
	 * 		@type string $type       issue or pull_request.
	 * 		@type string created_at  When did this happen.
	 * 		@type int    $number     Issue Number.
	 * 		@type string $repo_name  Repo name.
	 * 		@type string $state      Issue state (open or closed).
	 * 		@type string $title      Repo name and issue number concatenated to build a post title.
	 * 		@type string $issue_name Issue title.
	 * 		@type int    $comments   Number of comments on the issue.
	 * 		@type string $creator    Issue creator.
	 * 		@type array  $labels     Array of labels for that issue.
	 * }
	 */
	private function record_issue_details( $issue_details ) {
		/**
		 * Create a new post if that issue does not exist yet.
		 * Update the post if not.
		 * We make a WP_Query and set $is_new to help us figure this out.
		 */
		$is_new_args = array(
			'post_type'      => 'ghactivity_issue',
			'posts_per_page' => 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'ghactivity_repo',
					'field'    => 'name',
					'terms'    => $issue_details['repo_name'],
				),
			),
			'meta_query' => array(
				array(
					'key'     => 'number',
					'value'   => $issue_details['number'],
					'compare' => '=',
				),
			),
		);
		$query = new WP_Query( $is_new_args );
		if ( $query->have_posts() ) {
			$query->the_post();

			$is_new = false;
			$post_id = $query->post->ID;
		} else {
			$is_new = true;
		}
		wp_reset_postdata();

		if ( $is_new ) {
			// Create taxonomies.
			$taxonomies = array(
				'ghactivity_repo'          => $issue_details['repo_name'],
				'ghactivity_actor'         => $issue_details['creator'],
				'ghactivity_issues_state'  => $issue_details['state'],
				'ghactivity_issues_labels' => $issue_details['labels'],
				'ghactivity_issues_type'   => $issue_details['type'],
			);

			$meta = array(
				'number'   => absint( $issue_details['number'] ),
				'comments' => absint( $issue_details['comments'] ),
			);

			$post_content = sprintf(
				'<h3 class="issue-title"><a href="https://github.com/%2$s/issues/%3$s">%1$s</a></h3>
				<ul>
					<li>%4$s %5$s</li>
					<li>Comments: %6$s</li>
				</ul>',
				esc_html( $issue_details['issue_name'] ),
				esc_attr( $issue_details['repo_name'] ),
				absint( $issue_details['number'] ),
				esc_html__( 'Labels:', 'ghactivity' ),
				implode( ', ', $issue_details['labels'] ),
				absint( $issue_details['comments'] )
			);
			$issue_args = array(
				'post_title'   => $issue_details['title'],
				'post_type'    => 'ghactivity_issue',
				'post_status'  => 'publish',
				'post_date'    => $issue_details['created_at'],
				'tax_input'    => $taxonomies,
				'meta_input'   => $meta,
				'post_content' => $post_content,
			);
			$post_id = wp_insert_post( $issue_args );

			/**
			 * Establish the relationship between terms and taxonomies.
			 */
			foreach ( $taxonomies as $taxonomy => $value ) {
				$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, true );
			}
		} else {
			$taxonomies = array(
				'ghactivity_issues_state'  => $issue_details['state'],
				'ghactivity_issues_labels' => $issue_details['labels'],
			);
			$meta = array(
				'number'   => absint( $issue_details['number'] ),
				'comments' => absint( $issue_details['comments'] ),
			);
			$post_content = sprintf(
				'<h3 class="issue-title"><a href="https://github.com/%2$s/issues/%3$s">%1$s</a></h3>
				<ul>
					<li>%4$s %5$s</li>
					<li>Comments: %6$s</li>
				</ul>',
				esc_html( $issue_details['issue_name'] ),
				esc_attr( $issue_details['repo_name'] ),
				absint( $issue_details['number'] ),
				esc_html__( 'Labels:', 'ghactivity' ),
				implode( ', ', $issue_details['labels'] ),
				absint( $issue_details['comments'] )
			);

			$issue_args = array(
				'ID'           => $post_id,
				'post_title'   => $issue_details['title'],
				'meta_input'   => $meta,
				'tax_input'    => $taxonomies,
				'post_content' => $post_content,
			);
			wp_update_post( $issue_args );

			/**
			 * Establish the relationship between terms and taxonomies.
			 */
			foreach ( $taxonomies as $taxonomy => $value ) {
				$term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $taxonomy, false );
			}
		} // End if() $is_new.
	}

	/**
	 * Sort events by its creation date in ascending order
	 *
	 * @param Object $a Event object as it returned from Github API.
	 * @param Object $b Event object as it returned from Github API.
	 */
	private function sort_by_date( $a, $b ) {
		return ( strtotime( $a->created_at ) < strtotime( $b->created_at ) ) ? -1 : 1;
	}

	/**
	 * Record any label updates into taxonomy meta of issue post.
	 * It designed to work with repository issues events & with specific issue events.
	 * To make it work with latter - $options array should be passed with post_id, repo_name, issue_number values
	 *
	 * @since 2.1
	 *
	 * @param array $event_list Event object as it returned from Github API.
	 * @param array $options List of options which is used when passing list issue-specific events.
	 */
	public function update_issue_records( $event_list = null, $options = null ) {
		if ( ! is_array( $event_list ) && ! is_array( $options ) ) {
			$event_list = $this->api->get_github_issue_events();
		}

		if ( ! isset( $event_list ) || ! is_array( $event_list ) ) {
			return;
		}

		// Sorts all the events by created date from older to newer.
		usort( $event_list, array( 'GHActivity_Calls', 'sort_by_date' ) );

		foreach ( $event_list as $event ) {
			// process only specific event types.
			if ( 'labeled' !== $event->event
			&& 'unlabeled' !== $event->event
			&& 'closed' !== $event->event
			&& 'reopened' !== $event->event ) {
				continue;
			}

			if ( is_array( $options ) && $options['issue_number'] && $options['repo_name'] ) {
				$issue_number = $options['issue_number'];
				$repo_name    = $options['repo_name'];
				$post_id      = $options['post_id'];
			} else {
				preg_match( '/(?<=repos\/)(.*?)(?=\/issues)/', $event->url, $match );
				$issue_number = $event->issue->number;
				$repo_name    = $match[0];
				$post_id      = $this->find_post( $repo_name, $issue_number );
			}

			$slug = $repo_name . '#' . $issue_number;
			if ( ! $post_id ) {
				continue;
			}

			// If issue is closed/reopened - update ghactivity_issues_state accordingly, and continue to next event.
			if ( 'closed' === $event->event ) {
				wp_set_post_terms( $post_id, 'closed', 'ghactivity_issues_state', false );
				continue;
			} elseif ( 'reopened' === $event->event ) {
				wp_set_post_terms( $post_id, 'open', 'ghactivity_issues_state', false );
				continue;
			}

			// Update label list according to event data.
			if ( 'labeled' === $event->event ) { // Add missing labels if needed.
				wp_set_post_terms( $post_id, $event->label->name, 'ghactivity_issues_labels', true );
			} elseif ( 'unlabeled' === $event->event ) {
				wp_remove_object_terms( $post_id, $event->label->name, 'ghactivity_issues_labels' );
			}

			$query = array(
				'taxonomy' => 'ghactivity_issues_labels',
				'name'     => $event->label->name,
			);
			$term  = get_terms( $query );
			if ( ! is_array( $term ) || empty( $term ) ) {
				continue;
			}
			$term = $term[0];
			/**
			 * If this is labeled/unlabeled event - update label meta to include event data.
			 * Since ghactivity_issues_labels terms are shared between all the issues
			 * we need to store term metadata (label status, labeled/unlabeled date) as an array
			 * Expected key/value pair:
			 *  automattic/jetpack#5432 => [
			 *    'status'    => labeled,
			 *    'labeled'   => 2018-07-10T21:52:02Z",
			 *    'unlabeled' => null,
			 *  ]
			 */
			$record = array(
				'status'    => null,
				'labeled'   => null,
				'unlabeled' => null,
			);
			if ( metadata_exists( 'term', $term->term_id, $slug ) ) {
				$record = get_term_meta( $term->term_id, $slug, true );
			}
			$record['status']        = $event->event;
			$record[ $event->event ] = $event->created_at;
			update_term_meta( $term->term_id, $slug, $record );
		}
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
	public function find_post( $repo_name, $issue_number ) {
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

	/**
	 * Get all issues open for a watched repo, and record them on our end.
	 *
	 * @since 2.0.0
	 *
	 * @param string $repo_slug Repo slug.
	 *
	 * @return bool $done Returns true when done.
	 */
	public function full_issue_sync( $repo_slug ) {
		/**
		 * First, let's get info about the sync.
		 *
		 * The 'full_sync' option can be one of 2 things:
		 * 1. Empty string -> Option doesn't exist, Sync was never run before. Sync will start and an option will be set.
		 * 2. Array $args {
		 * 		string status Sync Status. Can be 'in_progress' or 'done'.
		 *		int    pages  Number of pages left to sync.
		 * }
		 */
		$status = $this->get_option( $repo_slug . '_full_sync' );

		// If sync already ran successfully, we can stop here.
		if ( ! empty( $status ) && isset( $status['status'] ) && 'done' === $status['status'] ) {
			return true;
		}

		// Get the full name of the repo.
		$repo = get_term_by( 'slug', $repo_slug, 'ghactivity_repo' );

		/**
		 * If the option doesn't exist, that means we never ran sync before.
		 * Let's get started by changing the status to 'in_progress', and get some data.
		 */
		if ( empty( $status ) ) {
			$status = array(
				'status' => 'in_progress',
				// dividing by 100 here because we are getting 100 issues per page.
				'pages'  => ( floor( $this->api->get_repo_issues_count( $repo->name ) / 100 ) + 1 ),
			);
			// Update our option.
			$this->update_option( $repo_slug . '_full_sync', $status );
		}

		// Set WP_IMPORTING to avoid triggering things like subscription emails.
		defined( 'WP_IMPORTING' ) || define( 'WP_IMPORTING', true );

		// let's start looping.
		do {
			$issues_body = $this->api->get_github_issues( $repo->name, $status['pages'] );

			/**
			 * Only go through the event list if we have valid event array.
			 */
			if ( isset( $issues_body ) && is_array( $issues_body ) ) {
				foreach ( $issues_body as $issue ) {
					$this->log_issue( $issue );
				}
			}

			// One page less to go.
			$status['pages']--;
		} while ( 'in_progress' === $status['status'] && 0 !== $status['pages'] );

		// We're done. Save options.
		$status = array(
			'status' => 'done',
			'pages'  => 0,
		);
		$this->update_option( $repo_slug . '_full_sync', $status );

		return true;
	}
}
new GHActivity_Calls();
