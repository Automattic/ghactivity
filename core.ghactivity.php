<?php
/**
 * GHActivity calls to GitHub API
 *
 * https://developer.github.com/v3/
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

	function __construct() {
	}

	/**
	 * Get option saved in the plugin's settings screen.
	 *
	 * @since 1.0
	 *
	 * @param string $name Option name.
	 *
	 * @return Specific option
	 */
	private function get_option( $name ) {
		$options = get_option( 'ghactivity' );

		return $options[ $name ];
	}

	/**
	 * Remote call to get data from GitHub's API.
	 *
	 * @since 1.0
	 */
	private function get_github_activity( $endpoint ) {
		$query_url = sprintf(
			'https://api.github.com/users/%1$s/events?client_id=%2$s&client_secret=%3$s',
			$this->get_option( 'username' ),
			$this->get_option( 'client_id' ),
			$this->get_option( 'client_secret' )
		);
		$data = wp_remote_get( esc_url_raw( $query_url ) );

		if (
			is_wp_error( $data )
			|| 200 != $data['response']['code']
			|| empty( $data['body'] )
		) {
			return;
		}

		$response_body = json_decode( $data['body'] );

		return $response_body;
	}
}
new GHActivity_Calls();
