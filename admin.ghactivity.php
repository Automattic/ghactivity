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
 * Enqueue Custom script on that page.
 *
 * @since 1.1
 *
 */
function ghactivity_enqueue_admin_scripts( $hook ) {

	global $ghactivity_settings_page;

	wp_register_script( 'ghactivity-reports', plugins_url( 'js/reports.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), GHACTIVITY__VERSION );
	$report_options = array(
		'date_format' => 'yy-mm-dd',
	);
	wp_localize_script( 'ghactivity-reports', 'report_options', $report_options );

	wp_register_style( 'ghactivity-reports-datepicker', plugins_url( 'css/datepicker.css', __FILE__ ), array(), GHACTIVITY__VERSION );

	if ( $ghactivity_settings_page !== $hook ) {
		return;
	}

	wp_enqueue_script( 'ghactivity-reports' );
	wp_enqueue_style( 'ghactivity-reports-datepicker' );
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
			'ghactivity_repos_monitoring',
			__( 'Initialize GitHub labels scan', 'ghactivity' ),
			'ghactivity_label_scan_callback',
			'ghactivity'
		);

	// Reports Section.
	add_settings_section(
		'ghactivity_reports',
		__( 'GitHub Activity Reports', 'ghactivity' ),
		'ghactivity_reports_callback',
		'ghactivity'
	);
	add_settings_field(
		'date_start',
		__( 'From', 'ghactivity' ),
		'ghactivity_date_start_callback',
		'ghactivity',
		'ghactivity_reports'
	);
	add_settings_field(
		'date_end',
		__( 'To', 'ghactivity' ),
		'ghactivity_date_end_callback',
		'ghactivity',
		'ghactivity_reports'
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

	$repos_query_args = array(
		'taxonomy'   => 'ghactivity_repo',
		'hide_empty' => false,
		'number'     => 10, // Just to make sure we don't get rate-limited by GH.
		'fields'     => 'id=>name',
		'meta_query' => array(
			array(
				'key'     => 'full_reporting',
				'value'   => true,
				'compare' => '='
			),
		),
	);
	$repos_to_monitor = get_terms( $repos_query_args );

	// If we have repos to watch, let's get data for them.
	if (
		! is_wp_error( $repos_to_monitor )
		&& is_array( $repos_to_monitor )
		&& ! empty( $repos_to_monitor )
	) {
		echo '<ul>';
		foreach ( $repos_to_monitor as $id => $name ) {
			printf(
				'<li>%s</li>',
				esc_html( $name )
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
 * GitHub Activity Reports section.
 *
 * @since 1.1
 */
function ghactivity_reports_callback() {
	echo '<p>';
	esc_html_e( 'Select the start and end dates of the report you want to generate below.', 'ghactivity' );
	echo '</p>';
}

/**
 * GitHub Activity Reports option callbacks.
 *
 * @since 1.1
 */
// Date From
function ghactivity_date_start_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[date_start]" value="%s" class="report-date" />',
		isset( $options['date_start'] ) ? esc_attr( $options['date_start'] ) : date( 'Y-m-d', strtotime( '-8 days' ) )
	);
}
// Date End
function ghactivity_date_end_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[date_end]" value="%s" class="report-date" />',
		isset( $options['date_end'] ) ? esc_attr( $options['date_end'] ) : date( 'Y-m-d', strtotime( '-1 day' ) )
	);
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
	$input['client_id']       = sanitize_key( $input['access_token'] );
	$input['display_private'] = (bool) $input['display_private'];
	$input['repos']           = sanitize_text_field( $input['repos'] );
	$input['date_start']      = sanitize_text_field( $input['date_start'] );
	$input['date_end']        = sanitize_text_field( $input['date_end'] );

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
					do_settings_sections( 'ghactivity_reports' );
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
	echo '<button onclick="triggerLabelScan()">Trigger Label Rescan</button>';
	echo '</div>';

	?>
		<script type="text/javascript" >
		function triggerLabelScan($) {
			if ( ! confirm( 'This is time-consuming operation. Make sure not to run it more then once!' ) ) {
				return;
			}
			var data = {
				'action': 'label_scan_action',
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post( ajaxurl, data, function( response ) {
				alert( 'Got this from the server: ' + response );
			} );
		}
		</script>
		<?php
}

add_action( 'wp_ajax_label_scan_action', 'label_scan_action' );

function label_scan_action() {
	// ini_set( 'memory_limit', '512M' );
	error_log( print_r( 'label_scan_action START!', 1 ) );
	$gha = new GHActivity_Calls();

	$query = new WP_Query( array(
		'post_type'      => 'ghactivity_issue',
		'posts_per_page' => -1,
	) );

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id      = $query->post->ID;
		$issue_number = get_post_meta( $post_id, 'number', true );
		$repo_name    = get_terms( array(
			'object_ids' => $post_id,
			'taxonomy'   => 'ghactivity_repo',
		) )[0]->name;
		$options      = array(
			'issue_number' => $issue_number,
			'repo_name'    => $repo_name,
			'post_id'      => $post_id,
		);
		$response     = $gha->get_github_issue_events( $repo_name, $issue_number );

		$gha->update_issue_labels( $response, $options );
	}
	wp_reset_postdata();
	error_log( print_r( 'label_scan_action DONE!', 1 ) );

	wp_die(); // this is required to terminate immediately and return a proper response.
}
