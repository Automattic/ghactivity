<?php
/**
 * Load a shortcode to display the whole thing on the frontend.
 */

add_shortcode( 'jeherve_ghactivity', 'jeherve_ghactivity_short_markup' );

/**
 * Get data for a custom report.
 */
function jeherve_ghactivity_cust_report() {
	// Grab original options.
	$options = (array) get_option( 'ghactivity' );

	/**
	 * Let's change our saved options.
	 * End date will be today.
	 * Start date will be 2 weeks ago.
	 */
	$options['date_end'] = esc_attr( date( 'Y-m-d' ) );
	$options['date_start'] = esc_attr( date( 'Y-m-d', strtotime( '-2 weeks' ) ) );

	// Let's get some data for these custom dates.
	$custom_report_data = GHActivity_Reports::get_main_report_data( $options );

	if ( ! empty( $custom_report_data ) ) {
		return $custom_report_data;
	} else {
		return array();
	}
}
add_filter( 'ghactivity_chart_data', 'jeherve_ghactivity_cust_report' );

/**
 * Build shortcode
 */
function jeherve_ghactivity_short_markup() {

	$markup = sprintf(
		'
		<p>From %1$s until %2$s:</p>
		<div id="canvas-holder">
			<canvas id="chart-area-ghactivity_widget-1"/>
		</div>
		<ul id="ghactivity_admin_report"></ul>
		',
		date_i18n( get_option( 'date_format' ), strtotime( date( 'Y-m-d', strtotime( '-2 weeks' ) ) ) ),
		date_i18n( get_option( 'date_format' ), strtotime( date( 'Y-m-d' ) ) )
	);

	// Get a list of Top Issues.
	$top_issues = get_transient( 'ghactivity_top_issues' );

	if ( false != $top_issues ) {
		$markup .= $top_issues;
	}

	/**
	 * Filter the content of the GitHub activity shortcode output.
	 *
	 * @since 1.4.0
	 *
	 * @param string $markup Shortcode HTML markup.
	 */
	return apply_filters( 'ghactivity_shortcode_output', $markup );
}
