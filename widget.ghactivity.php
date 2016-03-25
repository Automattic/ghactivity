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
				'customize_selective_refresh' => true,
			)
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		if ( ! is_admin() || is_customize_preview() ) {
			add_filter( 'ghactivity_widget_id', array( $this, 'get_widget_id' ) );
		}
	}

	/**
	 * Widget Defaults.
	 *
	 * @return array $defaults Widget Default values.
	 */
	public static function defaults() {
		$saved_admin_options = (array) get_option( 'ghactivity' );

		return array(
			'title'      => __( 'My GitHub Activity', 'ghactivity' ),
			'date_start' => isset( $saved_admin_options['date_start'] ) ? esc_attr( $saved_admin_options['date_start'] ) : date( 'Y-m-d', strtotime( '-8 days' ) ),
			'date_end'   => isset( $saved_admin_options['date_end'] ) ? esc_attr( $saved_admin_options['date_end'] ) : date( 'Y-m-d', strtotime( '-1 day' ) )
		);
	}

	/**
	 * Enqueue scripts in the widget settings screen.
	 *
	 * @since 1.3.1
	 */
	public function enqueue_admin_scripts() {
		global $pagenow;

		if ( 'widgets.php' == $pagenow || 'customize.php' == $pagenow ) {
			wp_enqueue_script( 'ghactivity-reports' );
			wp_enqueue_style( 'ghactivity-reports-datepicker' );
		}
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

		$saved_admin_options = (array) get_option( 'ghactivity' );

		$title = stripslashes( $instance['title'] );

		$date_start = esc_attr( $instance['date_start'] );
		$date_end = esc_attr( $instance['date_end'] );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'ghactivity' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'date_start' ); ?>"><?php esc_html_e( 'From', 'ghactivity' ); ?></label>
			<input class="datepicker report-date" id="<?php echo $this->get_field_id( 'date_start' ); ?>" name="<?php echo $this->get_field_name( 'date_start' ); ?>" type="date" value="<?php echo esc_attr( $date_start ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'date_end' ); ?>"><?php esc_html_e( 'To', 'ghactivity' ); ?></label>
			<input class="datepicker report-date" id="<?php echo $this->get_field_id( 'date_end' ); ?>" name="<?php echo $this->get_field_name( 'date_end' ); ?>" type="date" value="<?php echo esc_attr( $date_end ); ?>" />
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

		$instance['title']      = wp_kses( $new_instance['title'], array() );
		$instance['date_start'] = sanitize_text_field( $new_instance['date_start'] );
		$instance['date_end']   = sanitize_text_field( $new_instance['date_end'] );

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
		 *
		 * @param int $this->id Widget ID.
		 */
		do_action( 'ghactivity_widget_output', $this->id );

		echo $args['after_widget'];
	}

	/**
	 * Return Widget ID to use in the chart markup and data js.
	 *
	 * @since 1.3.1
	 *
	 * @return string $widget_id Widget Unique ID.
	 */
	public function get_widget_id() {
		return $this->id;
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
