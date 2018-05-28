<?php
/**
 * Load a shortcode to display weekly reports for teams on the frontend.
 * Shortcode: ghactivity_team
 * One possible parameter: team, string.
 */

add_shortcode( 'ghactivity_team', 'ghactivity_team_shortcode' );

/**
 * Shortcode to display activity from your teams.
 *
 * @since 1.6.0
 */
function ghactivity_team_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'team' => '',
	), $atts, 'ghactivity_team' );

	// Get list of team members.
	$team_members_args = array(
		'taxonomy'   => 'ghactivity_actor',
		'hide_empty' => false,
		'fields'     => 'id=>slug',
		'meta_query' => array(
			array(
				'key'     => 'team',
				'value'   => esc_attr( $atts['team'] ),
				'compare' => '=',
			),
		),
	);
	$team_members = get_terms( $team_members_args );

	// Build list of teammates for that team.
	$team = array();
	foreach ( $team_members as $id => $teammate ) {
		$team[] = $teammate;
	}

	/**
	 * Build a basic report of what happened for this team in the past week.
	 */
	$date_end   = esc_attr( date( 'Y-m-d' ) );
	$date_start = esc_attr( date( 'Y-m-d', strtotime( '-1 week' ) ) );

	$action_count = GHActivity_Calls::get_summary_counts( $date_start, $date_end, $team, '', false );

	$report = sprintf(
		'<header class="page-header"><h2>%s</h2>',
		esc_html__( 'In the past 7 days, here is what the team did:', 'ghactivity' )
	);

	if ( ! empty( $action_count ) ) {
		$report .= '<ul>';
		foreach ( $action_count as $action => $count ) {
			$report .= sprintf(
				'<li>%1$s: %2$s</li>',
				esc_html( $action ),
				absint( $count )
			);
		}
		$report .= '</ul>';
	} else {
		$report .= '<p>' . esc_html__( 'Nothing to report', 'ghactivity' ) . '</p>';
	}

	$report .= '<h3>' . esc_html__( 'Check their individual stats here:', 'ghactivity' ) . '</h3>';
	$report .= '<ul>';
	foreach ( $team as $name ) {
		$report .= sprintf(
			'<li><a href="%1$s/ghactivity_actor/%2$s">%2$s</a></li>',
			get_home_url(),
			esc_attr( $name )
		);
	}
	$report .= '</ul>';

	return $report;
}
