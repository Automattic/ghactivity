<?php
/**
 * GHActivity Reports
 *
 * @since 1.2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * GitHub Activity Settings Reports
 *
 * @since 1.2
 */
class GHActivity_Reports {

	function __construct() {
		// Add doughnut chart to widget.
		add_action( 'ghactivity_widget_output', array( 'GHActivity_Charts', 'print_doughnut' ) );

		// Add Doughtnut chart to Settings page.
		add_action( 'ghactivity_after_settings', array( $this, 'admin_report_markup' ) );
		add_action( 'ghactivity_after_settings', array( 'GHActivity_Charts', 'print_doughnut' ) );
		add_filter( 'ghactivity_chart_data', array( $this, 'get_main_report_data' ) );

		// Add Popular issues to Settings page.
		add_action( 'ghactivity_after_settings', array( $this, 'popular_issues_markup' ) );
	}

	/**
	 * Get a set of data based on the report dates saved in the plugin options.
	 *
	 * @since 1.2
	 *
	 * @param array $options Array of custom Chart options.
	 *
	 * @return null|array $chart_data
	 */
	public static function get_main_report_data( $options ) {

		// Get options.
		if ( empty( $options ) || ! isset( $options ) ) {
			$options = (array) get_option( 'ghactivity' );
		}

		// If no dates were set, give up now.
		if ( ! isset( $options['date_start'], $options['date_end'] ) ) {
			return;
		}

		$dates = array(
			'date_start' => $options['date_start'],
			'date_end'   => $options['date_end'],
		);

		/**
		 * Filter the dates used to generated the main report data.
		 *
		 * @since 1.3.1
		 *
		 * @param array $dates Array of the report data ranges.
		 */
		$dates = apply_filters( 'ghactivity_main_report_dates', $dates );

		// Action count during that period.
		$action_count = GHActivity_Calls::count_posts_per_event_type( $dates['date_start'], $dates['date_end'] );

		// Remove all actions with a count of 0. We won't need to display them.
		$action_count = array_filter( $action_count );

		/**
		 * Let's loop through our array of actions taken,
		 * and replace the taxonomy slugs used when counting posts by the taxonomy names, better for display.
		 */
		foreach( $action_count as $type => $count ) {
			// Get the pretty name for each taxonomy
			$tax_info = get_term_by( 'slug', $type, 'ghactivity_event_type' );
			$type_name = $tax_info->name;

			// Add the new pretty names to the array, matching them to their value.
			$action_count[ $type_name ] = $count;

			// Remove the old array key.
			unset( $action_count[ $type ] );
		}

		/**
		 * Add number of commits to the report.
		 */
		$commit_count = GHActivity_Calls::count_commits( $dates['date_start'], $dates['date_end'] );

		$commits_key = __( 'Committed', 'ghactivity' );
		$action_count[ $commits_key ] = (int) $commit_count;

		/**
		 * Add number of repos to the report.
		 */
		$repos_count = GHActivity_Calls::count_repos( $dates['date_start'], $dates['date_end'] );

		$repos_key = __( 'Projects', 'ghactivity' );
		$action_count[ $repos_key ] = (int) $repos_count;

		$chart_data = GHActivity_Charts::get_action_chart_data( $action_count );

		return $chart_data;
	}

	/**
	 * Build HTML markup for the Admin Settings report.
	 *
	 * @since 1.2
	 *
	 * @echo string $report_markup HTML markup for report.
	 */
	public function admin_report_markup() {
		$options = (array) get_option( 'ghactivity' );
		if ( isset( $options['date_start'], $options['date_end'] ) ) {

			printf(
				'<h2>%1$s</h2>',
				__( 'Last Report', 'ghactivity' )
			);

			printf(
				__( '<p>From %1$s until %2$s:</p>', 'ghactivity' ),
				date_i18n( get_option( 'date_format' ), strtotime( $options['date_start'] ) ),
				date_i18n( get_option( 'date_format' ), strtotime( $options['date_end'] ) )
			);

			echo '<ul id="ghactivity_admin_report"></ul>';
		}
	}

	/**
	 * Build a list of our most popular issues.
	 *
	 * @since 1.4.0
	 *
	 * @echo null|string $pop_report HTML markup for our report. Ordered list.
	 */
	public function popular_issues_markup( $number ) {
		$options = (array) get_option( 'ghactivity' );

		// Check if we have info about a repo or more.
		if ( ! isset( $options['repos'] ) ) {
			return;
		}

		// Look for data in our transient. If nothing, let's get a new list of issues.
		$data_from_cache = get_transient( 'ghactivity_top_issues' );
		if ( false === $data_from_cache ) {
			/**
			 * Create an array of repos.
			 * I try to account for single repos, comma separated lists, space separated lists, and comma + space lists.
			 */
			$repos = array_filter( preg_split( '/[,\s]+/', $options['repos'] ) );

			/**
			 * Start with an empty array
			 * we'll fill it with top issues from all the repos specified in the plugin options page.
			 */
			$response_body = array();

			// Loop through each repo.
			foreach ( $repos as $repo ) {
				$query_url = sprintf(
					'https://api.github.com/repos/%s/issues?sort=comments',
					$repo
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

				// Limit our list to a small number of issues.
				/**
				 * How many issues do we want to track?
				 *
				 * @since 1.4.0
				 *
				 * @param int $number Default number of popular issues to display.
				 */
				$number = apply_filters( 'ghactivity_popular_issues_number', 5 );

				$single_response_body = array_slice( $single_response_body, 0, $number );

				$response_body = array_merge( $single_response_body, $response_body );
			}

			// Let's build a multidimensional array with only what we need from those issues.
			$issues = array();

			foreach ( $response_body as $issue  ) {
				$repo_name = explode( '/', $issue->repository_url );

				$issues[] = array(
					'url'        => $issue->html_url,
					'repo'       => $repo_name[5],
					'title'      => $issue->title,
					'comments'   => $issue->comments,
					'created_at' => date_i18n( get_option( 'date_format' ), strtotime( $issue->created_at ) ),
					'milestone'  => $issue->milestone->title,
				);
			}

			/**
			 * Let's now keep the top X issues from our array, and save them in a transient.
			 */
			// Sort the issues.
			uasort( $issues, function( $a, $b ) {
				if ( $a['comments'] == $b['comments'] ) {
					return 0;
				}
				return ( $a['comments'] > $b['comments'] ) ? -1 : 1;
			} );

			/** This filter is already documented above. */
			$number = apply_filters( 'ghactivity_popular_issues_number', 5 );

			// Only keep X issues (5 by default).
			$issues = array_slice( $issues, 0, $number );

			// Build a string with our markup.
			$issues_markup = sprintf(
				'<div class="ghactivity_popular_issues"><h2>%s</h2><ol>',
				__( 'Most popular issues', 'ghactivity' )
			);

			foreach ( $issues as $issue ) {
				$issues_markup .= sprintf(
					'<li><a href="%2$s">%3$s</a> (<span class="count">%1$s comments</span>) | Created on %4$s | %5$s | Product: %6$s</li>',
					absint( $issue['comments'] ),
					esc_url( $issue['url'] ),
					esc_html( $issue['title'] ),
					esc_html( $issue['created_at'] ),
					esc_html( $issue['milestone'] ),
					esc_html( ucwords( $issue['repo'] ) )
				);
			}

			$issues_markup .= '</ol></div>';

			// Save our issues in a transient.
			set_transient( 'ghactivity_top_issues', $issues_markup, 12 * HOUR_IN_SECONDS );

		} else {
			$issues_markup = $data_from_cache;
		}

		echo $issues_markup;
	}
}
new GHActivity_Reports();
