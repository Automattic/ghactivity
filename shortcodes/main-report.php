<?php
/**
 * Load a shortcode to display the main report on the frontend.
 * Shortcode: ghactivity
 * One possible parameter: top_issues, boolean
 *
 * @package Ghactivity
 */

add_shortcode( 'ghactivity', 'jeherve_ghactivity_short_markup' );

/**
 * Create a color linked to an event type.
 *
 * @since 1.2
 *
 * @param string $event_type Name of an event type.
 *
 * @return string $color RGB Color.
 */
function jeherve_ghactivity_get_event_color( $event_type ) {
	$type_hash = md5( $event_type );

	$r = hexdec( substr( $type_hash, 0, 2 ) );
	$g = hexdec( substr( $type_hash, 2, 2 ) );
	$b = hexdec( substr( $type_hash, 4, 2 ) );

	$color = $r . ',' . $g . ',' . $b;

	return $color;
}

/**
 * Get data for a custom report.
 */
function jeherve_ghactivity_cust_report() {
	// Check if we are on a page with a shortcode. If not, bail now.
	global $post;

	if (
		empty( $post )
		|| ! has_shortcode( $post->post_content, 'ghactivity' )
	) {
		return $chart_data;
	}

	/**
	 * Filter the list of people included in the reports.
	 *
	 * @since 1.6.0
	 *
	 * @param string|array $people Person or list of people included in the report.
	 */
	$people = apply_filters( 'ghactivity_cust_report_people', '' );

	/**
	 * End date will be today.
	 * Start date will be 2 weeks ago.
	 */
	$dates = array(
		'date_start' => esc_attr( date( 'Y-m-d', strtotime( '-2 weeks' ) ) ),
		'date_end'   => esc_attr( date( 'Y-m-d' ) ),
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
	$action_count = GHActivity_Queries::count_posts_per_event_type( $dates['date_start'], $dates['date_end'], $people, '', false );

	// Remove all actions with a count of 0. We won't need to display them.
	$action_count = array_filter( $action_count );

	/**
	 * Let's loop through our array of actions taken,
	 * and replace the taxonomy slugs used when counting posts by the taxonomy names, better for display.
	 */
	foreach ( $action_count as $type => $count ) {
		// Get the pretty name for each taxonomy
		$tax_info  = get_term_by( 'slug', $type, 'ghactivity_event_type' );
		$type_name = $tax_info->name;

		// Add the new pretty names to the array, matching them to their value.
		$action_count[ $type_name ] = $count;

		// Remove the old array key.
		unset( $action_count[ $type ] );
	}

	/**
	 * Add number of commits to the report.
	 */
	$commit_count = GHActivity_Queries::count_commits( $dates['date_start'], $dates['date_end'], '' );

	$commits_key                  = __( 'Committed', 'ghactivity' );
	$action_count[ $commits_key ] = (int) $commit_count;

	/**
	 * Add number of repos to the report.
	 */
	$repos_count = GHActivity_Queries::count_repos( $dates['date_start'], $dates['date_end'], '' );

	$repos_key                  = __( 'Projects', 'ghactivity' );
	$action_count[ $repos_key ] = (int) $repos_count;

	$chart_data = array();

	foreach ( $action_count as $type => $count ) {
		// Get a set of colors.
		$rgb       = jeherve_ghactivity_get_event_color( $type );
		$color     = 'rgb(' . $rgb . ')';
		$highlight = 'rgba(' . $rgb . ',0.6)';

		$chart_data[] = (object) array(
			'value'     => absint( $count ),
			'color'     => esc_attr( $color ),
			'highlight' => esc_attr( $highlight ),
			'label'     => esc_attr( $type ),
		);
	}

	if ( ! empty( $chart_data ) ) {
		return $chart_data;
	} else {
		return array();
	}
}

/**
 * Build shortcode.
 *
 * @param array $atts Array of shortcode attributes.
 */
function jeherve_ghactivity_short_markup( $atts ) {
	$atts = shortcode_atts( array(
		'top_issues' => false,
	), $atts, 'ghactivity' );

	// General Chart.js minified source.
	wp_register_script( 'ghactivity-chartjs', plugins_url( 'js/chartjs.js', __FILE__ ), array( 'jquery' ), GHACTIVITY__VERSION );

	/**
	 * Filter the data returned for each chart.
	 *
	 * @since 1.2
	 *
	 * @param array $chart_data Array of event objects to be used in a chart.
	 */
	$chart_data = (array) apply_filters( 'ghactivity_chart_data', jeherve_ghactivity_cust_report() );

	/**
	 * Filter the chart dimensions.
	 *
	 * @since 1.2
	 *
	 * @param array $dims Array of width and height values for a chart.
	 */
	$dims = (array) apply_filters( 'ghactivity_chart_dimensions', array( '300', '300' ) );

	wp_register_script( 'ghactivity-chartdata', plugins_url( 'js/chart-data.js', __FILE__ ), array( 'jquery', 'ghactivity-chartjs' ), GHACTIVITY__VERSION );
	$chart_options = array(
		'doughtnut_data' => $chart_data,
		'width'          => absint( $dims[0] ),
		'height'         => absint( $dims[1] ),
		'doughnut_id'    => 'admin',
	);
	wp_localize_script( 'ghactivity-chartdata', 'chart_options', $chart_options );

	wp_register_style( 'ghactivity-reports-charts', plugins_url( 'css/charts.css', __FILE__ ), array(), GHACTIVITY__VERSION );

	if ( ! empty( $chart_data ) ) {
		wp_enqueue_script( 'ghactivity-chartjs' );
		wp_enqueue_script( 'ghactivity-chartdata' );
		wp_enqueue_style( 'ghactivity-reports-charts' );
	}

	$markup = sprintf(
		'
		<p>From %1$s until %2$s:</p>
		<div id="canvas-holder">
			<canvas id="chart-area-admin"/>
		</div>
		<ul id="ghactivity_admin_report"></ul>
		',
		date_i18n( get_option( 'date_format' ), strtotime( date( 'Y-m-d', strtotime( '-2 weeks' ) ) ) ),
		date_i18n( get_option( 'date_format' ), strtotime( date( 'Y-m-d' ) ) )
	);

	if ( 'true' === $atts['top_issues'] ) {
		// Start from an empty list of issues.
		$issues_markup = '';

		// Get a list of Top Issues.
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
					|| 200 !== $data['response']['code']
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

			foreach ( $response_body as $issue ) {
				$repo_name = explode( '/', $issue->repository_url );

				$issues[] = array(
					'url'        => $issue->html_url,
					'repo'       => $repo_name[5],
					'title'      => $issue->title,
					'comments'   => $issue->comments,
					'created_at' => date_i18n( get_option( 'date_format' ), strtotime( $issue->created_at ) ),
					'milestone'  => ( $issue->milestone->title ? $issue->milestone->title : '' ),
				);
			}

			/**
			 * Let's now keep the top X issues from our array, and save them in a transient.
			 */
			// Sort the issues.
			uasort( $issues, function( $a, $b ) {
				if ( $a['comments'] === $b['comments'] ) {
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
	}

	// Add the top issues (or an empty string) to the markup.
	$markup .= $issues_markup;

	/**
	 * Filter the content of the GitHub activity shortcode output.
	 *
	 * @since 1.4.0
	 *
	 * @param string $markup Shortcode HTML markup.
	 */
	return apply_filters( 'ghactivity_shortcode_output', $markup );
}
