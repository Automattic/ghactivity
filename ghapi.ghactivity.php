<?php
/**
 * Wrapper functions for GitHub API: https://developer.github.com/v3/
 *
 * @since 2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * GitHub API Calls
 *
 * @since 2.0
 */
class API_Calls {
	/**
	 * Get option saved in the plugin's settings screen.
	 *
	 * @since 1.0
	 *
	 * @param string $name Option name.
	 *
	 * @return string $str Specific option.
	 */
	private function get_option( $name ) {
		$options = get_option( 'ghactivity' );

		if ( isset( $options[ $name ] ) ) {
			return $options[ $name ];
		} else {
			return '';
		}
	}

	/**
	 * Get an array of repos we want to follow a bit more closely.
	 * For those repos we will log activity from everyone,
	 * not just from the usernames set in the plugin options.
	 *
	 * We will select all repos from the ghactivity_repo taxonomy,
	 * and monitor all those that have the `full_reporting` term meta set to true.
	 *
	 * @since 2.0.0
	 *
	 * @param string $fields Type of info to return. Accept full or names. Default to full.
	 *
	 * @return WP_Error|array $repos_to_monitor Array of repos to monitor.
	 */
	public function get_monitored_repos( $fields = 'full' ) {
		$repos_query_args = array(
			'taxonomy'   => 'ghactivity_repo',
			'hide_empty' => false,
			'number'     => 10, // Just to make sure we don't get rate-limited by GH.
			'fields'     => 'id=>name',
			'meta_query' => array(
				array(
					'key'     => 'full_reporting',
					'value'   => true,
					'compare' => '=',
				),
			),
		);
		$repos_to_monitor = get_terms( $repos_query_args );

		if ( 'full' === $fields ) {
			return $repos_to_monitor;
		} else {
			$repo_names = array();
			if (
				! is_wp_error( $repos_to_monitor )
				&& is_array( $repos_to_monitor )
				&& ! empty( $repos_to_monitor )
			) {
				foreach ( $repos_to_monitor as $id => $name ) {
					$repo_names[] = $name;
				}
			}
			return $repo_names;
		}
	}

	/**
	 * Remote call to get data from GitHub's API.
	 *
	 * @since 1.0
	 *
	 * @return null|array
	 */
	public function get_github_activity() {
		$response_body = array();

		/**
		 * Create an array of usernames.
		 * I try to account for single usernames, comma separated lists, space separated lists, and comma + space lists.
		 */
		$usernames = array_filter( preg_split( '/[,\s]+/', $this->get_option( 'username' ) ) );

		// Loop through that array and make a request to the GitHub API for each person.
		foreach ( $usernames as $username ) {
			$query_url = sprintf(
				'https://api.github.com/users/%1$s/events?access_token=%2$s',
				$username,
				$this->get_option( 'access_token' )
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
			$response_body        = array_merge( $single_response_body, $response_body );
		}

		// If we have repos to watch, let's get data for them.
		$repos_to_monitor = $this->get_monitored_repos( 'names' );
		if ( ! empty( $repos_to_monitor ) ) {
			foreach ( $repos_to_monitor as $repo ) {
				$repo_activity = self::get_repo_activity( $repo );
				// If we got data from those repos, add it to the existing list of events.
				if ( isset( $repo_activity ) && is_array( $repo_activity ) ) {
					$response_body = array_merge( $repo_activity, $response_body );
				}
			}
		}

		// Finally return the response.
		return $response_body;
	}

	/**
	 * Remote call to get data for a specific repo
	 *
	 * @since 1.6.0
	 *
	 * @param string $repo_name Name of the repo we want data from.
	 *
	 * @return null|array
	 */
	public function get_repo_activity( $repo_name ) {

		$response_body = array();

		if ( empty( $repo_name ) ) {
			return $response_body;
		}

		$query_url = sprintf(
			'https://api.github.com/repos/%1$s/events?access_token=%2$s',
			esc_html( $repo_name ),
			$this->get_option( 'access_token' )
		);

		$data = wp_remote_get( esc_url_raw( $query_url ) );

		if (
			is_wp_error( $data )
			|| 200 !== $data['response']['code']
			|| empty( $data['body'] )
		) {
			return $response_body;
		}

		$response_body = json_decode( $data['body'] );
		return $response_body;
	}

	/**
	 * Remote call to get information about a specific GitHub user.
	 *
	 * @since 1.6.0
	 *
	 * @param string $gh_username GitHub username.
	 *
	 * @return array $gh_user_details Details about a GitHub user.
	 */
	public function get_person_details( $gh_username = '' ) {
		$gh_user_details = array();

		if ( empty( $gh_username ) ) {
			return $gh_user_details;
		}

		// Let's get some info from GitHub.
		$query_url = sprintf(
			'https://api.github.com/users/%1$s?access_token=%2$s',
			$gh_username,
			$this->get_option( 'access_token' )
		);

		$data = wp_remote_get( esc_url_raw( $query_url ) );

		if (
			is_wp_error( $data )
			|| 200 !== $data['response']['code']
			|| empty( $data['body'] )
		) {
			return $gh_user_details;
		}

		$person_info_body = json_decode( $data['body'] );

		/**
		 * Let's build a name based on the name field.
		 * If it is not defined, fall back to username.
		 */
		if ( ! empty( $person_info_body->name ) ) {
			$nicename = $person_info_body->name;
		} else {
			$nicename = $person_info_body->login;
		}

		// Build the array of data we will save.
		$gh_user_details = array(
			'name'       => esc_html( $nicename ),
			'avatar_url' => esc_url( $person_info_body->avatar_url ),
			'bio'        => esc_html( $person_info_body->bio ),
		);

		return $gh_user_details;
	}

	/**
	 * Remote call to get label events for every monitored repo
	 *
	 * @since 2.1.0
	 *
	 * @return null|array
	 */
	public function get_github_issue_events() {
		$response_body = array();

		$repos_to_monitor = $this->get_monitored_repos( 'names' );
		if ( empty( $repos_to_monitor ) ) {
			return $response_body;
		}

		foreach ( $repos_to_monitor as $repo_name ) {
			$query_url = sprintf(
				'https://api.github.com/repos/%1$s/issues/events?access_token=%2$s&per_page=100',
				esc_html( $repo_name ),
				$this->get_option( 'access_token' )
			);

			$data = wp_remote_get( esc_url_raw( $query_url ) );

			if (
				is_wp_error( $data )
				|| 200 !== $data['response']['code']
				|| empty( $data['body'] )
			) {
				return $response_body;
			}

			$single_response_body = json_decode( $data['body'] );
			$response_body        = array_merge( $single_response_body, $response_body );
		}

		return $response_body;
	}
}
