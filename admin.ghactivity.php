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
	add_options_page(
		__('GHActivity', 'ghactivity' ),
		__('GitHub Activity Settings', 'ghactivity' ),
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
	add_settings_section(
		'ghactivity_app_settings',
		__( 'GitHub App Settings', 'ghactivity' ),
		'ghactivity_app_settings_callback',
		'ghactivity'
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
	_e( 'Once you created your app, copy the "Client ID" and "Client Secret" values below:', 'ghactivity' );
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
				?>
			</form>
	</div>
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
	$input['client_id']      = sanitize_key( $input['client_id'] );
	$input['client_secret']  = sanitize_key( $input['client_secret'] );

	return $input;
}
