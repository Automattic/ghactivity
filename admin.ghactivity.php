<?php
/**
 * GHActivity Settings screen
 *
 * @since 1.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Create Menu page.
 */
function ghactivity_menu() {
	global $ghactivity_settings_page;
	$ghactivity_settings_page = add_options_page(
		__('GHActivity', 'ghactivity' ),
		__('GitHub Activity Settings', 'ghactivity' ),
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
 */
function ghactivity_enqueue_admin_scripts( $hook ) {

	global $ghactivity_settings_page;

	wp_register_script( 'ghactivity-reports', plugins_url( 'js/reports.js' , __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), GHACTIVITY__VERSION );
	$report_options = array(
		'date_format' => 'yy-mm-dd',
	);
	wp_localize_script( 'ghactivity-reports', 'report_options', $report_options );

	wp_register_style( 'ghactivity-reports-datepicker', plugins_url( 'css/datepicker.css' , __FILE__ ), array(), GHACTIVITY__VERSION );

	if ( $ghactivity_settings_page != $hook ) {
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

	// Main GitHub App Settings Section
	add_settings_section(
		'ghactivity_app_settings',
		__( 'GitHub App Settings', 'ghactivity' ),
		'ghactivity_app_settings_callback',
		'ghactivity'
	);
	add_settings_field(
		'username',
		__( 'GitHub Username', 'ghactivity' ),
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

	// Reports Section
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
	$input['username']      = sanitize_user( $input['username'] );
	$input['client_id']     = sanitize_key( $input['access_token'] );
	$input['date_start']    = sanitize_text_field( $input['date_start'] );
	$input['date_end']      = sanitize_text_field( $input['date_end'] );

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
		<h1><?php _e( 'GitHub Activity Settings', 'ghactivity' ); ?></h1>
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
