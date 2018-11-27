<?php

/**
 * Creates a Line chart shortcode to display some Org project metrics such as number of opened issues
 * THis shortcode relies on data recorded via wpcron gh_query_project_stats job.
 * To make it work you want to:
 *  1. Add a org/project slug into "Query Project Slug" in format: "$org_name#$project_name" (/wp-admin/edit-tags.php?taxonomy=ghactivity_query_project_slug&post_type=gh_query_record)
 *  2. Wait or trigger gh_query_project_stats manually.
 *
 * Shortcode: ghactivity_project_stats
 * Parameters:
 *  string $org          Organisation name
 *  string $project_name Project name
 *  string $columns      Coma separated list of Project columns names needs to be displayed in graph.
 *
 * @package Ghactivity
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

add_shortcode( 'ghactivity_project_stats', 'output_project_stats' );

/**
 * Shortcode to display chart data of specific label
 *
 * @since 2.1.0
 *
 * @param array $atts Array of shortcode attributes.
 */
function output_project_stats( $atts ) {
	$atts = shortcode_atts(
		array( 'org' => '', 'project_name' => '', columns => '' ),
		$atts,
		'ghactivity_project_stats'
	);

	/**
	 * Enqueue JavaScript.
	 */
	wp_register_script(
		'ghactivity-project-stats',
		plugins_url( '_build/shortcodes/project-stats.js', dirname( __FILE__ ) ),
		array(),
		GHACTIVITY__VERSION
	);
	$args = array(
		'api_url'   => esc_url_raw( rest_url() ),
		'site_url'  => esc_url_raw( home_url() ),
		'api_nonce' => wp_create_nonce( 'wp_rest' ),
		'org'       => esc_attr( $atts['org'] ),
		'project_name' => esc_attr( $atts['project_name'] ),
		'columns'      => explode( ',', esc_attr( $atts['columns'] ) ),
	);
	wp_localize_script( 'ghactivity-project-stats', 'ghactivity_project_stats', $args );
	wp_enqueue_script( 'ghactivity-project-stats' );

	$class_name = preg_replace( '/\W/', '-', strtolower( html_entity_decode( $args['org'] ) . '#' . html_entity_decode( $args['project_name'] ) ) );
	return "<div id='project-stats' class='" . $class_name . "'></div>";
}
