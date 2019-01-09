<?php
/**
 * GHActivity Settings screen
 *
 * @since 1.0
 *
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Create Menu page.
 */
function ghactivity_menu() {
	global $ghactivity_settings_page;
	$ghactivity_settings_page = add_options_page(
		__( 'GHActivity', 'ghactivity' ),
		__( 'GitHub Activity Settings', 'ghactivity' ),
		'manage_options',
		'ghactivity',
		'ghactivity_do_settings'
	);
}
add_action( 'admin_menu', 'ghactivity_menu' );
/**
 * Enqueue scripts on GHActivity admin page.
 *
 * @since 1.1.0
 *
 * @param int $hook Hook suffix for the current admin page.
 */
function ghactivity_enqueue_admin_scripts( $hook ) {

	global $ghactivity_settings_page;

	// Only add our script to our admin page.
	if ( $ghactivity_settings_page !== $hook ) {
		return;
	}

	wp_register_script( 'ghactivity-settings', plugins_url( 'js/admin-settings.js' , __FILE__ ), array( 'jquery' ), GHACTIVITY__VERSION );
	$ghactivity_settings = array(
		'api_url'          => esc_url_raw( rest_url() ),
		'api_nonce'        => wp_create_nonce( 'wp_rest' ),
		'progress_message' => esc_html__( 'In Progress', 'ghactivity' ),
	);
	wp_localize_script( 'ghactivity-settings', 'ghactivity_settings', $ghactivity_settings );

	wp_enqueue_script( 'ghactivity-settings' );
}
add_action( 'admin_enqueue_scripts', 'ghactivity_enqueue_admin_scripts' );

/**
 * Create new option.
 */
function ghactivity_options_init() {
	register_setting( 'ghactivity_settings', 'ghactivity', 'ghactivity_settings_validate' );

	// Main GitHub App Settings Section.
	add_settings_section(
		'ghactivity_app_settings',
		__( 'GitHub App Settings', 'ghactivity' ),
		'ghactivity_app_settings_callback',
		'ghactivity'
	);
	add_settings_field(
		'username',
		__( 'GitHub Username. You can also enter a comma-separated list of usernames.', 'ghactivity' ),
		'ghactivity_app_settings_username_callback',
		'ghactivity',
		'ghactivity_app_settings'
	);
	add_settings_field(
		'access_token',
		__( 'Personal Access Token', 'ghactivity' ),
		'ghactivity_app_settings_token_callback',
		'ghactivity',
		'ghactivity_app_settings'
	);
	add_settings_field(
		'repos',
		__( 'Do you want to track popular issues in specific GitHub repos Enter them here.', 'ghactivity' ),
		'ghactivity_app_settings_repos_callback',
		'ghactivity',
		'ghactivity_app_settings'
	);
	add_settings_field(
		'display_private',
		__( 'Do you want to store information about private repositories?', 'ghactivity' ),
		'ghactivity_app_settings_privacy_callback',
		'ghactivity',
		'ghactivity_app_settings'
	);

	// Repos Section.
	add_settings_section(
		'ghactivity_repos_monitoring',
		__( 'Monitoring activity on specific repositories', 'ghactivity' ),
		'ghactivity_repos_monitoring_callback',
		'ghactivity'
	);

	// Init label scan Section.
	add_settings_section(
		'ghactivity_restart_triggers',
		__( 'Restart calculations', 'ghactivity' ),
		'ghactivity_retrigger_callback',
		'ghactivity'
	);
	add_settings_field(
		'ghactivity_label_scan',
		__( 'Initialize GitHub labels scan', 'ghactivity' ),
		'ghactivity_label_scan_callback',
		'ghactivity',
		'ghactivity_restart_triggers'
	);
	add_settings_field(
		'ghactivity_redo_graphs',
		__( 'Re-build Graphs', 'ghactivity' ),
		'ghactivity_redo_graphs_callback',
		'ghactivity',
		'ghactivity_restart_triggers'
	);
	add_settings_field(
		'ghactivity_query_label_slug',
		__( 'Query Label Slug', 'ghactivity' ),
		'ghactivity_query_label_slug_callback',
		'ghactivity',
		'ghactivity_restart_triggers'
	);

	// Full issue sync section.
	add_settings_section(
		'ghactivity_sync_settings',
		__( 'Full Sync', 'ghactivity' ),
		'ghactivity_sync_settings_callback',
		'ghactivity'
	);
	add_settings_field(
		'full_sync',
		__( 'Sync status', 'ghactivity' ),
		'ghactivity_sync_settings_full_sync_callback',
		'ghactivity',
		'ghactivity_sync_settings'
	);
}
add_action( 'admin_init', 'ghactivity_options_init' );

/**
 * GitHub App settings section.
 *
 * @since 1.0
 */
function ghactivity_app_settings_callback() {
	echo '<p>';
	printf(
		__( 'To use the plugin, you will need to create a new personal access token on GitHub first. <a href="%1$s">click here</a> to create a token. You only need to check the first "repo" checkbox.', 'ghactivity' ),
		esc_url( 'https://github.com/settings/tokens/new' )
	);
	echo '<br/>';
	_e( 'Once you created your app, copy the access token value below. You will also want to enter your GitHub username.', 'ghactivity' );
	echo '</p>';
}

/**
 * GitHub App Settings option callbacks.
 *
 * @since 1.0
 */
// GitHub Username option.
function ghactivity_app_settings_username_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[username]" value="%s" />',
		isset( $options['username'] ) ? esc_attr( $options['username'] ) : ''
	);
}

// Access Token option.
function ghactivity_app_settings_token_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[access_token]" value="%s" class="regular-text" />',
		isset( $options['access_token'] ) ? esc_attr( $options['access_token'] ) : ''
	);
}

// GitHub Username option.
function ghactivity_app_settings_repos_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[repos]" value="%s" />',
		isset( $options['repos'] ) ? esc_attr( $options['repos'] ) : ''
	);
}

// Do you want to store information from private repositories as well?
function ghactivity_app_settings_privacy_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="checkbox" name="ghactivity[display_private]" value="1" %s />',
		checked( true, (bool) ( isset( $options['display_private'] ) ? $options['display_private'] : false ), false )
	);
}

/**
 * GitHub Activity Reports section.
 *
 * @since 1.6.0
 */
function ghactivity_repos_monitoring_callback() {
	echo '<p>';
	esc_html_e( 'The plugin allows you to monitor all activity for the following repos, even from users not listed above.', 'ghactivity' );
	echo '</p>';

	$repos_to_monitor = GHActivity_Queries::get_monitored_repos( 'names' );

	// If we have repos to watch, let's get data for them.
	if ( ! empty( $repos_to_monitor ) ) {
		echo '<ul>';
		foreach ( $repos_to_monitor as $repo ) {
			printf(
				'<li>%s</li>',
				esc_html( $repo )
			);
		}
		echo '</ul>';
	}

	echo '<p>';
	esc_html_e( 'To monitor more repos, edit the repo you want to monitor, and check the box:', 'ghactivity' );
	printf(
		' <a href="%1$s">%2$s</a>',
		esc_url( admin_url( 'edit-tags.php?taxonomy=ghactivity_repo&post_type=ghactivity_event' ) ),
		esc_html__( 'Edit repositories recorded by the plugin.', 'ghactivity' )
	);
	echo '</p>';
}


/**
 * Full Sync Settings Section.
 *
 * @since 2.0.0
 */
function ghactivity_sync_settings_callback() {
	echo '<p>';
	esc_html_e( 'By default, Ghactivity only gathers data about the last 100 issues in your watched repositories, and then automatically logs all future issues. This section will allow you to perform a full synchronization of all the issues, at once.', 'ghactivity' );
	echo '</p>';
}

/**
 * Full Sync callback.
 */
function ghactivity_sync_settings_full_sync_callback() {
	$options          = (array) get_option( 'ghactivity' );
	$repos_to_monitor = GHActivity_Queries::get_monitored_repos( 'all' );
	$repos_to_monitor = array_map(
		function( $term ) {
			return $term->slug;
		},
	$repos_to_monitor );

	// Gather info about our watched repos, and the current sync status.
	if ( ! empty( $repos_to_monitor ) ) {
		foreach ( $repos_to_monitor as $repo ) {
			$sync_status = isset( $options[ $repo . '_full_sync' ] ) ? $options[ $repo . '_full_sync' ] : '';
			if ( ! empty( $sync_status ) ) {
				if ( 'done' === $sync_status['status'] ) {
					printf(
						__( 'All events for the %1$s repository have already been synchronized. Check them <a href="%2$s">here</a>.', 'ghactivity' ),
						esc_html( $repo ),
						esc_url( get_admin_url( null, 'edit.php?post_type=ghactivity_issue' ) )
					);
				} else {
					printf(
						__( 'Synchronization for the %1$s repository in progress. There are still %2$d pages to process.', 'ghactivity' ),
						esc_html( $repo ),
						absint( $sync_status['pages'] )
					);
				}
			} else {
				// we push to start the sync here.
				printf(
					'<div><strong>%1$s</strong>: <input class="full_sync" id="%1$s_full_sync" type="button" name="%1$s_full_sync" value="%2$s" class="button button-secondary" /><p id="%1$s_full_sync_details" style="display:none;"></p></div>',
					esc_attr( $repo ),
					esc_html__( 'Start synchronizing all issues', 'ghactivity' )
				);
			}
		}
	} else {
		esc_html_e( 'You currently do not monitor activity on any repository. You cannot use this option yet.', 'ghactivity' );
	}
}

/**
 * Sanitize and validate input.
 *
 * @since 1.0
 *
 * @param  array $input Saved options.
 * @return array $input Sanitized options.
 */
function ghactivity_settings_validate( $input ) {
	$input['username']        = sanitize_text_field( $input['username'] );
	$input['access_token']    = sanitize_key( $input['access_token'] );
	$input['display_private'] = (bool) $input['display_private'];
	$input['repos']           = sanitize_text_field( $input['repos'] );
	return $input;
}

/**
 * Settings Screen.
 *
 * @since 1.0
 */
function ghactivity_do_settings() {
	?>
	<div id="ghactivity_settings" class="wrap">
		<h1><?php esc_html_e( 'GitHub Activity Settings', 'ghactivity' ); ?></h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'ghactivity_settings' );
					/**
					 * Fires at the top of the Settings page.
					 *
					 * @since 1.2
					 */
					do_action( 'ghactivity_start_settings' );
					do_settings_sections( 'ghactivity' );
					submit_button();
					/**
					 * Fires at the bottom of the Settings page.
					 *
					 * @since 1.2
					 */
					do_action( 'ghactivity_end_settings' );
				?>
			</form>

		<?php
		/**
		 * Fires at the bottom of the Settings page, after the form.
		 *
		 * @since 1.2
		 */
		do_action( 'ghactivity_after_settings' );
		?>
	</div><!-- .wrap -->
	<?php
}

/**
 * Manual triggers section
 *
 * @since 2.0.0
 */
function ghactivity_retrigger_callback() {
	echo '<p>';
	esc_html_e( 'These buttons allow you to restart actions in your GHactivity logs.', 'ghactivity' );
	echo '</p>';
}

/**
 * Re-fetch query taxonomies
 *
 */
function ghactivity_query_label_slug_callback() {
	echo '<p>';
	esc_html_e( 'This button will re-build the Query Label Slug taxonomy.' );
	echo '</p>';

	echo '<div class="wrap">';
	echo '<button onclick="triggerQueryLabelScan()" class="button button-secondary">';
	esc_html_e( 'Trigger Query Label Rescan', 'ghactivity' );
	echo '</button>';
	echo '</div>';

	$ajax_nonce      = wp_create_nonce( 'ghactivity-label-scan-nonce' );
	$confirm_dialog  = esc_html__( 'Make sure not to run it more then once!', 'ghactivity' );
	$response_dialog = esc_html__( 'Got this from the server: ', 'ghactivity' );
	?>
	<script type="text/javascript" >
		function triggerQueryLabelScan($) {
			if ( ! confirm( '<?php echo esc_html( $confirm_dialog ); ?>' ) ) {
				return;
			}
			var data = {
				'action': 'query_label_slug_action',
				'security': '<?php echo esc_html( $ajax_nonce ); ?>',
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post( ajaxurl, data, function( response ) {
				alert( '<?php echo esc_html( $response_dialog ); ?>'  + response );
			} );
		}
	</script>
	<?php
}

add_action( 'wp_ajax_query_label_slug_action', 'query_label_slug_action' );

function query_label_slug_action() {
	check_ajax_referer( 'ghactivity-label-scan-nonce', 'security' );
	wp_suspend_cache_addition( true );
	do_action( 'gh_query_average_label_time' );
	wp_die(); // this is required to terminate immediately and return a proper response.
}



/**
 * GitHub Label Scan section.
 *
 * @since 2.1.0
 */
function ghactivity_label_scan_callback() {
	echo '<p>';
	esc_html_e( 'This button will scan for all the label updates for recorded issues' );
	echo '</p>';

	echo '<div class="wrap">';
	echo '<button onclick="triggerLabelScan()" class="button button-secondary">';
	esc_html_e( 'Trigger Label Rescan', 'ghactivity' );
	echo '</button>';
	echo '</div>';

	$ajax_nonce      = wp_create_nonce( 'ghactivity-label-scan-nonce' );
	$confirm_dialog  = esc_html__( 'This is time-consuming operation. Make sure not to run it more then once!', 'ghactivity' );
	$response_dialog = esc_html__( 'Got this from the server: ', 'ghactivity' );
	?>
		<script type="text/javascript" >
		function triggerLabelScan($) {
			if ( ! confirm( '<?php echo esc_html( $confirm_dialog ); ?>' ) ) {
				return;
			}
			var data = {
				'action': 'label_scan_action',
				'security': '<?php echo esc_html( $ajax_nonce ); ?>',
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post( ajaxurl, data, function( response ) {
				alert( '<?php echo esc_html( $response_dialog ); ?>'  + response );
			} );
		}
		</script>
		<?php
}

add_action( 'wp_ajax_label_scan_action', 'label_scan_action' );

/**
 * Goes through all the existing `ghactivity_issue` posts and update their labels & state
 */
function label_scan_action() {
	check_ajax_referer( 'ghactivity-label-scan-nonce', 'security' );
	wp_suspend_cache_addition( true );
	$gha   = new GHActivity_Calls();
	$repos = GHActivity_Queries::get_monitored_repos( 'names' );

	foreach ( $repos as $repo ) {
		$post_ids = GHActivity_Queries::get_all_open_gh_issues( $repo );
		foreach ( $post_ids as $post_id ) {
			set_time_limit( 300 );
			$issue_number = get_post_meta( $post_id, 'number', true );
			$response     = $gha->api->get_github_issue_events( $repo, $issue_number );
			$options      = array(
				'issue_number' => $issue_number,
				'repo_name'    => $repo,
				'post_id'      => $post_id,
			);
			error_log( 'Rescanning ' . $repo . ' :: ' . $issue_number );
			$gha->update_issue_records_meta( $response, $options );
		}
	}
	error_log( 'Done with rescan!' );
	wp_die(); // this is required to terminate immediately and return a proper response.
}

/**
 * Add a button to re-build our graphs.
 *
 */
function ghactivity_redo_graphs_callback() {
	printf(
		'<div><input id="ghactivity_redo_graphs" type="button" name="ghactivity_redo_graphs" value="%s" class="button button-secondary" /><p id="ghactivity_redo_graphs_output" style="display:none;"></p></div>',
		esc_html__( 'Re-build the report graphs', 'ghactivity' )
	);
}
