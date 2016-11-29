<?php
/**
 * GHActivity Chart generation
 *
 * @uses http://www.chartjs.org/
 *
 * @since 1.2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Chart tools.
 *
 * @since 1.2
 */
class GHActivity_Charts {
	protected $_found_shortcode = false;

	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts( $hook ) {
		global $ghactivity_settings_page;

		// Decide where scripts are enqueued in admin
		if (
			is_admin()
			&& $ghactivity_settings_page != $hook
			&& ! is_customize_preview()
		) {
			return;
		}

		/**
		 * Decide if the scripts should be enqueued in the frontend.
		 *
		 * We'll check for a widget and for a shortcode.
		 */

		// Look for the ghactivity shortcode.
		if ( ! is_admin() ) {
			if ( empty( $GLOBALS['posts'] ) || ! is_array( $GLOBALS['posts'] ) ) {
				return;
			}
			foreach ( $GLOBALS['posts'] as $p ) {
				if ( has_shortcode( $p->post_content, 'jeherve_ghactivity' ) ) {
					$this->_found_shortcode = true;
					break;
				}
			}
		}

		// Return if no widget or shortcode can be found.
		if (
			! is_admin()
			&& ! is_active_widget( 'GHActivity_Widget', false, 'ghactivity_widget' )
			&& ! $this->_found_shortcode
		) {
			return;
		}

		// General Chart.js minified source.
		wp_register_script( 'ghactivity-chartjs', plugins_url( 'js/chartjs.js' , __FILE__ ), array( 'jquery' ), GHACTIVITY__VERSION );

		/**
		 * Filter the data returned for each chart.
		 *
		 * @since 1.2
		 *
		 * @param array $chart_data Array of event objects to be used in a chart.
		 */
		$chart_data = (array) apply_filters( 'ghactivity_chart_data', array() );

		/**
		 * Filter the chart dimensions.
		 *
		 * @since 1.2
		 *
		 * @param array $dims Array of width and height values for a chart.
		 */
		$dims = (array) apply_filters( 'ghactivity_chart_dimensions', array( '300', '300' ) );

		/**
		 * Filter the Widget ID
		 *
		 * @since 1.3.1
		 *
		 * @param string $widget_id Unique Widget ID. Default to 'admin'.
		 */
		$widget_id = apply_filters( 'ghactivity_widget_id', 'admin' );

		wp_register_script( 'ghactivity-chartdata', plugins_url( 'js/chart-data.js' , __FILE__ ), array( 'jquery', 'ghactivity-chartjs' ), GHACTIVITY__VERSION );
		$chart_options = array(
			'doughtnut_data' => $chart_data,
			'width'          => absint( $dims[0] ),
			'height'         => absint( $dims[1] ),
			'doughnut_id'    => $widget_id,
		);
		wp_localize_script( 'ghactivity-chartdata', 'chart_options', $chart_options );

		wp_register_style( 'ghactivity-reports-charts', plugins_url( 'css/charts.css' , __FILE__ ), array(), GHACTIVITY__VERSION );

		if ( ! empty( $chart_data ) ) {
			wp_enqueue_script( 'ghactivity-chartjs' );
			wp_enqueue_script( 'ghactivity-chartdata' );
			wp_enqueue_style( 'ghactivity-reports-charts' );
		}
	}

	/**
	 * Print a doughnut chart.
	 *
	 * @since 1.2
	 *
	 * @param int $widget_id Widget ID.
	 *
	 * @echo string $chart Doughnut chart markup.
	 */
	public static function print_doughnut( $widget_id ) {
		if ( ! $widget_id ) {
			$widget_id = 'admin';
		}
		printf( '<div id="canvas-holder">
				<canvas id="chart-area-%s"/>
			</div>',
			$widget_id
		);
	}

	/**
	 * Create a color linked to an event type.
	 *
	 * @since 1.2
	 *
	 * @param string $event_type Name of an event type.
	 *
	 * @return string $color RGB Color.
	 */
	private static function get_color( $event_type ) {
		$type_hash = md5( $event_type );

		$r = hexdec( substr( $type_hash, 0, 2 ) );
		$g = hexdec( substr( $type_hash, 2, 2 ) );
		$b = hexdec( substr( $type_hash, 4, 2 ) );

		$color = $r . ',' . $g . ',' . $b;

		return $color;
	}

	/**
	 * Build an array of event type objects to feed to the chart.
	 *
	 * @since 1.2
	 *
	 * @param array $events Array of count of registered Events per event type.
	 *
	 * @return array $chart_data Array of event type objects to feed to the chart.
	 */
	public static function get_action_chart_data( $events ) {
		$chart_data = array();

		foreach( $events as $type => $count ) {
			// Get a set of colors.
			$rgb       = self::get_color( $type );
			$color     = 'rgb(' . $rgb . ')';
			$highlight = 'rgba(' . $rgb . ',0.6)';

			$chart_data[] = (object) array(
				'value'     => absint( $count ),
				'color'     => esc_attr( $color ),
				'highlight' => esc_attr( $highlight ),
				'label'     => esc_attr( $type ),
			);
		}

		return $chart_data;
	}
}
new GHActivity_Charts();
