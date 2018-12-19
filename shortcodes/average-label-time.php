<?php

/**
 * Creates a Line chart shortcode to display average label time for specific repo/label
 * Shortcode: ghactivity_average_label_time
 * Parameters:
 *  string $repo    Repo slug, must match an existing term in the ghactivity_repo taxonomy.
 *  string $label   Issue label.
 *
 * @package Ghactivity
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

add_shortcode( 'ghactivity_average_label_time', 'output_average_label_time' );

/**
 * Shortcode to display chart data of specific label
 *
 * @since 2.1.0
 *
 * @param array $atts Array of shortcode attributes.
 */
function output_average_label_time( $atts ) {
	$atts = shortcode_atts( array(
		'repo'  => '',
		'label' => '',
		'id'    => '',
	), $atts, 'ghactivity_average_label_time' );

	/**
	 * Enqueue JavaScript.
	 */
	wp_register_script(
		'ghactivity-average-label-time',
		plugins_url( '_build/shortcodes/average-label-time.js', dirname( __FILE__ ) ),
		array(),
		GHACTIVITY__VERSION
	);
	$args = array(
		'api_url'   => esc_url_raw( rest_url() ),
		'site_url'  => esc_url_raw( home_url() ),
		'api_nonce' => wp_create_nonce( 'wp_rest' ),
		'repo'      => esc_attr( $atts['repo'] ),
		'label'     => esc_attr( $atts['label'] ),
		'id'        => esc_attr( $atts['id'] ),
	);
	wp_localize_script( 'ghactivity-average-label-time', 'ghactivity_avg_label_time', $args );
	wp_enqueue_script( 'ghactivity-average-label-time' );

	$class_name = preg_replace( '/\W/', '-', strtolower( html_entity_decode( $args['repo'] ) . '#' . html_entity_decode( $args['label'] ) . html_entity_decode( $args['id'] ) ) );
	return "<div id='avg-label-time' class='" . $class_name . "'></div>";
}
