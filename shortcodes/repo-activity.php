<?php
/**
 * Load a shortcode to display activity from a specific repo.
 * Shortcode: ghactivity_repo_stats
 * Parameters:
 *   string $repo            Repo slug, must match an existing term in the ghactivity_repo taxonomy.
 *   bool   $split_per_actor Should we display stats per actor or overall stats for the repo? Default to true.
 *   string $period          When do we want the data from? 4 options: `today`, `week`, `month`, `all`. Default to `all`.
 *
 * @package Ghactivity
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

add_shortcode( 'ghactivity_repo_stats', 'jeherve_ghactivity_repo_report' );

/**
 * Return our report table.
 *
 * @since 1.6.0
 *
 * @param array $atts Array of shortcode attributes.
 */
function jeherve_ghactivity_repo_report( $atts ) {
	$atts = shortcode_atts( array(
		'repo'            => '',
		'split_per_actor' => true,
		'period'          => 'all',
	), $atts, 'ghactivity_repo_stats' );

	/**
	 * Enqueue JavaScript.
	 */
	wp_register_script(
		'ghactivity-repo-activity',
		plugins_url( '_build/shortcodes/repo-activity.js', dirname( __FILE__ ) ),
		array(),
		GHACTIVITY__VERSION
	);
	$traktivity_dash_args = array(
		'api_url'         => esc_url_raw( rest_url() ),
		'site_url'        => esc_url_raw( home_url() ),
		'api_nonce'       => wp_create_nonce( 'wp_rest' ),
		'repo'            => esc_attr( $atts['repo'] ),
		'split_per_actor' => (bool) $atts['split_per_actor'],
		'period'          => esc_attr( $atts['period'] ),
	);
	wp_localize_script( 'ghactivity-repo-activity', 'ghactivity_repo_activity', $traktivity_dash_args );
	wp_enqueue_script( 'ghactivity-repo-activity' );

	return '<div id="repo-activity"></div>';
}
