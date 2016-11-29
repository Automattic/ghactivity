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
}
new GHActivity_Reports();
