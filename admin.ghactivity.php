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
function ghactivity_enqueue_scripts( $hook ) {

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
add_action( 'admin_enqueue_scripts', 'ghactivity_enqueue_scripts' );
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
		'client_id',
		__( 'Client ID', 'ghactivity' ),
		'ghactivity_app_settings_id_callback',
		'ghactivity',
		'ghactivity_app_settings'
	);
	add_settings_field(
		'client_secret',
		__( 'Client Secret', 'ghactivity' ),
		'ghactivity_app_settings_secret_callback',
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
		__( 'To use the plugin, you will need to register an app on GitHub first. <a href="%1$s">click here</a> to register an app. You can use any page on your site as "Authorization callback URL".', 'ghactivity' ),
		esc_url( 'https://github.com/settings/applications/new' )
	);
	echo '<br/>';
	_e( 'Once you created your app, copy the "Client ID" and "Client Secret" values below. You will also want to enter your GitHub username.', 'ghactivity' );
	echo '</p>';
}

/**
 * GitHub App Settings option callbacks.
 *
 * @since 1.0
 */
// Client ID option.
function ghactivity_app_settings_id_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[client_id]" value="%s" class="regular-text" />',
		isset( $options['client_id'] ) ? esc_attr( $options['client_id'] ) : ''
	);
}

// Client Secret option.
function ghactivity_app_settings_secret_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[client_secret]" value="%s" class="regular-text" />',
		isset( $options['client_secret'] ) ? esc_attr( $options['client_secret'] ) : ''
	);
}

// GitHub Username option.
function ghactivity_app_settings_username_callback() {
	$options = (array) get_option( 'ghactivity' );
	printf(
		'<input type="text" name="ghactivity[username]" value="%s" />',
		isset( $options['username'] ) ? esc_attr( $options['username'] ) : ''
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
 * Settings Screen.
 *
 * @since 1.0
 */
function ghactivity_do_settings() {
	?>
	<div class="wrap">
		<h1><?php _e( 'GitHub Activity Settings', 'ghactivity' ); ?></h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'ghactivity_settings' );
					do_settings_sections( 'ghactivity' );
					submit_button();
					do_settings_sections( 'ghactivity_reports' );
				?>
			</form>

		<?php

		$options = (array) get_option( 'ghactivity' );
		if ( isset( $options['date_start'], $options['date_end'] ) ) :

		printf(
			'<h2>%s</h2>',
			__( 'Reports', 'ghactivity' )
		);

		// Number of commits during that period.
		$commit_count = (int) GHActivity_Calls::count_commits( $options['date_start'], $options['date_end'] );

		// Action count during that period.
		$action_count = GHActivity_Calls::count_posts_per_term( $options['date_start'], $options['date_end'] );

		printf(
			__( '<p>From %1$s until %2$s:</p>', 'ghactivity' ),
			date_i18n( get_option( 'date_format' ), strtotime( $options['date_start'] ) ),
			date_i18n( get_option( 'date_format' ), strtotime( $options['date_end'] ) )
		);

		// Count of each action during that period.
		printf(
			'<ul>
				<li>%1$s comments</li>
				<li>%2$s issues created</li>
				<li>%3$s issues closed</li>
				<li>%4$s issues edited</li>
				<li>%5$s commits</li>
				<li>%6$s PR Reviews</li>
				<li>%9$s PR created</li>
				<li>%10$s PR closed</li>
				<li>%11$s PR edited</li>
				<li>%8$s branches deleted</li>
				<li>%7$s Other</li>
			</ul>',
			$action_count['comment'],
			$action_count['issue-opened'],
			$action_count['issue-closed'],
			$action_count['issue-touched'],
			$commit_count,
			$action_count['reviewed-a-pr'],
			$action_count['did-something'],
			$action_count['deleted-a-branch'],
			$action_count['pr-opened'],
			$action_count['pr-closed'],
			$action_count['pr-touched'],
		);
		endif;
		?>
	</div><!-- .wrap -->
	<?php
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
	$input['client_id']     = sanitize_key( $input['client_id'] );
	$input['client_secret'] = sanitize_key( $input['client_secret'] );
	$input['date_start']    = sanitize_text_field( $input['date_start'] );
	$input['date_end']      = sanitize_text_field( $input['date_end'] );

	return $input;
}
