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
		'ghactivity_label_scan',
		__( 'Initialize GitHub labels scan', 'ghactivity' ),
		'ghactivity_label_scan_callback',
		'ghactivity'
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
 * GitHub Label Scan section.
 *
 * @since 2.1.0
 */
function ghactivity_label_scan_callback() {
	echo '<p>';
	esc_html_e( 'This button will scan for all the label updates for recorded issues' );
	echo '</p>';

	echo '<div class="wrap">';
	echo '<button onclick="triggerLabelScan()">';
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
	global $wpdb;
	$gha = new GHActivity_Calls();

	$post_ids = $wpdb->get_col( $wpdb->prepare(
		'SELECT ID FROM wp_posts WHERE post_type = %s AND post_status = %s ORDER BY ID DESC',
		array( 'ghactivity_issue', 'publish' )
	) );

	$post_ids_chunks = array_chunk( $post_ids, 200 );
	foreach ( $post_ids_chunks as $post_ids_chunk ) {
		foreach ( $post_ids_chunk as $post_id ) {
			$issue_number = get_post_meta( $post_id, 'number', true );
			$repo_name    = get_terms( array(
				'object_ids' => $post_id,
				'taxonomy'   => 'ghactivity_repo',
			) )[0]->name;
			$response     = $gha->get_github_issue_events( $repo_name, $issue_number );
			$options      = array(
				'issue_number' => $issue_number,
				'repo_name'    => $repo_name,
				'post_id'      => $post_id,
			);
			$gha->update_issue_records( $response, $options );
			sleep( 20 );
		}
		sleep( 120 );
	}
	wp_die(); // this is required to terminate immediately and return a proper response.
}
