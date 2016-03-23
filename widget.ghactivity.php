<?php
/**
 * GitHub Activity Widget.
 *
 * @since 1.3
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Register the widget.
 */
function ghactivity_register_widget() {
	register_widget( 'GHActivity_Widget' );
}
add_action( 'widgets_init', 'ghactivity_register_widget' );

class GHActivity_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'ghactivity_widget',
			__( 'GitHub Activity', 'ghactivity' ),
			array(
				'description' => __( 'A widget to display activity from your GitHub account.', 'ghactivity' ),
			)
		);
	}

	/**
	 * Widget Defaults.
	 *
	 * @return array $defaults Widget Default values.
	 */
	public static function defaults() {
		return array(
			'title' => __( 'My GitHub Activity', 'ghactivity' ),
		);
	}

	/**
	 * Display the widget administration form.
	 *
	 * @param array $instance Widget instance configuration.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );

		$title = stripslashes( $instance['title'] );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'ghactivity' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	/**
	 * Sanitize and update form details.
	 *
	 * @param array $new_instance New widget settings.
	 * @param array $old_instance Old widget settings.
	 *
	 * @return array $instance Saved widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = wp_kses( $new_instance['title'], array() );

		return $instance;
	}

	/**
	 * Display the Widget.
	 *
	 * @param  [type] $args     [description]
	 * @param  [type] $instance [description]
	 * @return [type]           [description]
	 */
	function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );

		/** This filter is documented in core/src/wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		/**
		 * Fires after the GitHub Activity widget title is displayed.
		 *
		 * @since 1.3
		 */
		do_action( 'ghactivity_widget_output' );

		echo $args['after_widget'];
	}
}

/**
 * Create a shortcode to display the widget anywhere.
 *
 * @since 1.3
 */
function ghactivity_do_widget( $instance ) {
	$instance = shortcode_atts(
		GHActivity_Widget::defaults(),
		$instance,
		'ghactivity'
	);

	// Add a class to allow styling
	$args = array(
		'before_widget' => sprintf( '<div class="%s">', 'ghactivity_widget' ),
	);

	ob_start();
	the_widget( 'GHActivity_Widget', $instance, $args );
	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'ghactivity', 'ghactivity_do_widget' );
