<?php

/**
 * Displays a Repo Label State for specified repo
 * Shortcode: ghactivity_repo_label_state
 * Parameters:
 *  string $repo    Repo slug, must match an existing term in the ghactivity_repo taxonomy.
 *
 * @package Ghactivity
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

add_shortcode( 'ghactivity_repo_label_state', 'output_repo_label_state' );

/**
 * Shortcode to display chart data of specific label
 *
 * @since 2.1.0
 *
 * @param array $atts Array of shortcode attributes.
 */
function output_repo_label_state( $atts ) {
	$atts = shortcode_atts( array(
		'repo'  => '',
		'label' => '',
	), $atts, 'ghactivity_repo_label_state' );

	/**
	 * Enqueue JavaScript.
	 */
	wp_register_script(
		'ghactivity-repo-label-state',
		plugins_url( '_build/shortcodes/repo-label-state.js', dirname( __FILE__ ) ),
		array(),
		GHACTIVITY__VERSION
	);
	$args = array(
		'api_url'   => esc_url_raw( rest_url() ),
		'site_url'  => esc_url_raw( home_url() ),
		'api_nonce' => wp_create_nonce( 'wp_rest' ),
		'repo'      => esc_attr( $atts['repo'] ),
	);
	wp_localize_script( 'ghactivity-repo-label-state', 'ghactivity_repo_label_state', $args );
	wp_enqueue_script( 'ghactivity-repo-label-state' );

	$class_name = preg_replace( '/\W/', '-', strtolower( html_entity_decode( $args['repo'] ) ) );
	return "<div id='repo-label-state' class='" . $class_name . "'></div>";
}
