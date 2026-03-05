<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Add a custom category for panel widgets
 add_action( 'elementor/init', function() {
    \Elementor\Plugin::$instance->elements_manager->add_category( 
		'the-events-calendar-shortcode-and-templates-addon',                 // the name of the category
   	[
    		'title' => esc_html__( 'Events Calendar Shortcode and Templates', 'ect2' ),
    		'icon' => 'fa fa-header', //default icon
    	],
    	1 // position
    );
 } );

/**
 * Main Plugin Class
 *
 * Register new elementor widget.
 *
 * @since 1.0.0
 */
class EctElementorPro {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		$this->ect_add_actions();
	}

	/**
	 * Add Actions
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function ect_add_actions() {
	
		add_action( 'elementor/widgets/widgets_registered', array($this, 'ect_on_widgets_registered' ));

	}

	/**
	 * On Widgets Registered
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function ect_on_widgets_registered() {
		$this->ect_includes();
		$this->ect_register_widget();
	}

	/**
	 * Includes
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function ect_includes() {
		require __DIR__ . '/ect-elementor-shortcode.php';
		require __DIR__ . '/ect-elementor-calender-shortcode.php';
		require __DIR__ . '/ect-elementor-weekly-layout.php';
	}

	/**
	 * Register Widget
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function ect_register_widget() {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new EctElementorWidget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new EctCalendarElementorWidget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new EctWeeklyElementorWidget() );


	}
}

 new EctElementorPro();