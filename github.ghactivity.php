<?php
/**
 * GitHub API
 *
 * https://developer.github.com/v3/
 *
 * @since 2.0.0
 */
class GHActivity_GHApi {
	private $token;

	function __construct( $token ) {
		$this->token = $token;
	}

	/**
	 * Remote call to get data from GitHub's API.
	 *
	 * @since 1.0
	 *
	 * @param string $usernames_raw String of usernames which comes from wp-admin page.
	 *
	 * @return null|array
	 */
	public function get_github_activity( $usernames_raw ) {
		$response_body = array();

		/**
		 * Create an array of usernames.
		 * I try to account for single usernames, comma separated lists, space separated lists, and comma + space lists.
		 */
		$usernames = array_filter( preg_split( '/[,\s]+/', $usernames_raw ) );

		// Loop through that array and make a request to the GitHub API for each person.
		foreach ( $usernames as $username ) {
			$query_url = sprintf(
				'https://api.github.com/users/%1$s/events?access_token=%2$s',
				$username,
				$this->token
			);
			$single_response_body = $this->get_github_data( $query_url );

			$response_body = array_merge( $single_response_body, $response_body );
		}

		// If we have repos to watch, let's get data for them.
		$repos_to_monitor = GHActivity_Queries::get_monitored_repos( 'names' );
		if ( ! empty( $repos_to_monitor ) ) {
			foreach ( $repos_to_monitor as $repo ) {
				$repo_activity = $this->get_repo_activity( $repo );
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
	 * Remote call to get information about a specific GitHub user.
	 *
	 * @since 1.6.0
	 *
	 * @param string $gh_username GitHub username.
	 *
	 * @return array $gh_user_details Details about a GitHub user.
	 */
	public function get_person_details( $gh_username = '' ) {
		if ( empty( $gh_username ) ) {
			return array();
		}

		// Let's get some info from GitHub.
		$query_url = sprintf(
			'https://api.github.com/users/%1$s?access_token=%2$s',
			$gh_username,
			$this->token
		);
		$person_info_body = $this->get_github_data( $query_url );

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
			'name'        => esc_html( $nicename ),
			'avatar_url'  => esc_url( $person_info_body->avatar_url ),
			'bio'         => esc_html( $person_info_body->bio ),
			'is_employee' => (bool) $this->is_company_member( $person_info_body->login ),
		);

		return $gh_user_details;
	}


	/**
	 * Does a GitHub user belong to a specific organization?
	 *
	 * @since 2.0.0
	 *
	 * @param string $gh_username GitHub username.
	 *
	 * @return bool false Does the person belongs to a specific organization? Default to false.
	 */
	private function is_company_member( $gh_username ) {
		if ( empty( $gh_username ) ) {
			return false;
		}

		// Let's get some info from GitHub.
		$query_url = sprintf(
			'https://api.github.com/users/%1$s/orgs?access_token=%2$s',
			$gh_username,
			$this->token
		);
		$person_orgs_body = $this->get_github_data( $query_url );

		/**
		 * Define your own organization name here.
		 * It will allow you to filter people that belong to your organization.
		 *
		 * @since 2.0.0
		 *
		 * @param null|string $org_name Name of your organization, as it appears in the organization you've set up on GitHub.
		 */
		$org_name = apply_filters( 'ghactivity_organization_slug', null );

		/**
		 * Does the list of organizations include the one you've defined in the filter?
		 * If so, return true.
		 */
		if (
			! empty( $person_orgs_body )
			&& ! empty( $org_name )
		) {
			foreach ( $person_orgs_body as $org => $org_detail ) {
				if ( $org_detail->login === $org_name ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Get the number of open issues/PR for a repo.
	 *
	 * @since 2.0.0
	 *
	 * @param string $repo_name Name of the repo we are interested in.
	 *
	 * @return int $issues_number Number of open issues.
	 */
	public function get_repo_issues_count( $repo_name ) {
		if ( empty( $repo_name ) ) {
			// Fallback.
			return 0;
		}

		// Let's get some info from GitHub.
		$query_url = sprintf(
			'https://api.github.com/repos/%1$s?access_token=%2$s',
			$repo_name,
			$this->token
		);
		$repo_info_body = $this->get_github_data( $query_url );

		if ( empty( $repo_info_body->open_issues ) ) {
			return 0;
		}

		return $repo_info_body->open_issues;
	}

	/**
	 * Remote call to get all label events for every monitored repo
	 *
	 * @since 2.1.0
	 *
	 * @param string $repo         name of the repo.
	 * @param int    $issue_number issue number.
	 *
	 * @return array
	 */
	public function get_github_issue_events( $repo = null, $issue_number = null ) {
		$response_body = array();

		if ( empty( $repo ) ) {
			$repos_to_query = GHActivity_Queries::get_monitored_repos( 'names' );
			if ( empty( $repos_to_query ) ) {
				return $response_body;
			}
		} else {
			$repos_to_query = array( $repo );
		}

		foreach ( $repos_to_query as $repo_name ) {
			$query_url = sprintf(
				'https://api.github.com/repos/%1$s/issues%2$s/events?access_token=%3$s&per_page=100',
				esc_html( $repo_name ),
				esc_html( $issue_number ? '/' . $issue_number : '' ),
				$this->token
			);
			$single_response_body = $this->get_github_data( $query_url );
			$response_body        = array_merge( $single_response_body, $response_body );
		}
		return $response_body;
	}

	/**
	 * Remote call to get all label events for every monitored repo
	 *
	 * @since 2.1.0
	 *
	 * @param string $repo_name   name of the repo.
	 * @param int    $page_number page number of paginated response.
	 *
	 * @return array
	 */
	public function get_github_issues( $repo_name, $page_number = 1 ) {
		$query_url = sprintf(
			'https://api.github.com/repos/%1$s/issues?access_token=%2$s&page=%3$s&per_page=100',
			esc_html( $repo_name ),
			$this->token,
			$page_number
		);
		return $this->get_github_data( $query_url );
	}

	/**
	 * Remote call to get all org projects
	 *
	 * @since 2.1.0
	 *
	 * @param string $org_name   name of the repo.
	 * @param int    $page_number page number of paginated response.
	 *
	 * @return array
	 */
	public function get_projects( $org_name, $page_number = 1 ) {
		$headers = array( 'accept' => 'application/vnd.github.inertia-preview+json' );

		$query_url = sprintf(
			'https://api.github.com/orgs/%1$s/projects?access_token=%2$s&per_page=100',
			esc_html( $org_name ),
			$this->token
		);
		return $this->get_all_github_data( $query_url, $headers );
	}

	/**
	 * Remote call to get project columns
	 *
	 * @since 2.1.0
	 *
	 * @param string $project_id   id comes from project object.
	 * @param int    $page_number page number of paginated response.
	 *
	 * @return array
	 */
	public function get_project_columns( $project_id, $page_number = 1 ) {
		$headers = array( 'accept' => 'application/vnd.github.inertia-preview+json' );

		$query_url = sprintf(
			'https://api.github.com/projects/%1$s/columns?access_token=%2$s&page=%3$s&per_page=100',
			esc_html( $project_id ),
			$this->token,
			$page_number
		);
		return $this->get_github_data( $query_url, $headers );
	}

	/**
	 * Remote call to get project column cards
	 *
	 * @since 2.1.0
	 *
	 * @param string $column_id   column id comes from column object.
	 * @param int    $page_number page number of paginated response.
	 *
	 * @return array
	 */
	public function get_project_column_cards( $column_id, $page_number = 1 ) {
		$headers = array( 'accept' => 'application/vnd.github.inertia-preview+json' );

		$query_url = sprintf(
			'https://api.github.com/projects/columns/%1$s/cards?access_token=%2$s&page=%3$s&per_page=100',
			esc_html( $column_id ),
			$this->token,
			$page_number
		);
		return $this->get_github_data( $query_url, $headers );
	}

	public function get_all_github_data( $query_url, $headers ) {
		$page        = 1;
		$all_results = array();
		// Fetch API until empty array will be returned.
		do {
			$paged_query_url  = $query_url . '&page=' . $page;
			$body             = $this->get_github_data( $paged_query_url, $headers );
			$all_results      = array_merge( $all_results, $body );
			$page++;
		} while ( ! empty( $body ) );
		return $all_results;
	}

	/**
	 * Remote call utility function for GitHub.
	 *
	 * @since 2.0.0
	 *
	 * @param string $query_url GitHub API URL to hit.
	 *
	 * @return array $response_body Response body for each call.
	 */
	private function get_github_data( $query_url, $headers = array() ) {
		$response_body = array();

		$data = wp_remote_get( esc_url_raw( $query_url ), array( 'headers' => $headers ) );

		if (
			is_wp_error( $data )
			|| 200 != $data['response']['code']
			|| empty( $data['body'] )
		) {
			return $response_body;
		}

		$response_body = json_decode( $data['body'] );

		return $response_body;
	}

	/**
	 * Remote call to get data for a specific repo
	 *
	 * @since 1.6.0
	 *
	 * @param string $repo_name Name of the repo we want data from.
	 *
	 * @return array
	 */
	private function get_repo_activity( $repo_name = '' ) {
		if ( empty( $repo_name ) ) {
			return array();
		}

		$query_url = sprintf(
			'https://api.github.com/repos/%1$s/events?access_token=%2$s',
			esc_html( $repo_name ),
			$this->token
		);

		return $this->get_github_data( $query_url );
	}
}
