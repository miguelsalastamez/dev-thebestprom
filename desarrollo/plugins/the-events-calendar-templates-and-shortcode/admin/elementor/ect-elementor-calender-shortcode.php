<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor EctElementorWidget
 *
 * Elementor widget for EctElementorWidget
 *
 * @since 1.0.0
 */
class EctCalendarElementorWidget extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ect-addon';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Events Calendar Layouts', 'ect2' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}


	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'The Events Calendar Shortcode and Templates Addon' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	// public function get_script_depends() {
	// 	return [ 'ctla' ];
	// }

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
       
		 
		//  var_dump($ect_venue_details);
	
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'The Events Calendar Shortcode', 'ect2' ),
			]
		);
		$this->add_control(
			'ect_content_notice',
			[
				'label' => __( '', 'ect2' ),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => '<strong style="color:red">For advanced Elementor settings please use our <a href="https://wordpress.org/plugins/events-widgets-for-elementor-and-the-events-calendar/">The Events Calendar Widgets For Elementor Plugin</a></strong>',
				'content_classes' => 'cool_timeline_notice',
			]
		);
        $this->add_control(
			'date_formats',
			[
				'label' => __( 'Date formats', 'cool-timeline' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'd F Y',
				'options' => [
         
					'd F Y' => __( 'Default (01 January 2019)', 'cool-timeline' ),
                    'M D, Y' => __( 'Md,Y (Jan 01, 2019)', 'cool-timeline' ),
                    'F D, Y' => __( 'Fd,Y (January 01, 2019)', 'cool-timeline' ),
                    'D M' => __( 'dM (01 Jan))', 'cool-timeline' ),
                  
                    'D F' => __( 'dF (01 January)', 'cool-timeline' ),
                    'M D' => __( 'Md (Jan 01)', 'cool-timeline' ),
                    'F D' => __( 'Fd (January 01)', 'cool-timeline' ),
                    'j M l' => __( 'jMl (1 Jan Monday)', 'cool-timeline' ),
                    'd. F Y' => __( 'd.FY (01. January 2019)', 'cool-timeline' ),
                    'd. F' => __( 'd.F (01. January)', 'cool-timeline' ),
                    'd. M l' => __( 'd.Ml (01. Jan Monday)', 'cool-timeline' ),
                    'M d l' => __( 'Mdl (Jan 01 Monday)', 'cool-timeline' ),
                    'l d F' => __( 'ldF (Monday 01 January)', 'cool-timeline' ),
                    
                ],
			]
        );
      
		$this->add_control(
           'catFilter',
           [
               'label' => __( 'Show Category Filter', 'cool-timeline' ),
               'type' => Controls_Manager::SELECT,
               'default' => 'true',
               'options' => [
                   'false' => __( 'NO', 'cool-timeline' ),
                   'true' => __( 'Yes', 'cool-timeline' ),
               ]
           ]
		);

		$this->add_control(
			'limit',
			[
			    'label' => __( 'Events Limit', 'cool-timeline' ),
			    'type' => Controls_Manager::NUMBER,
			    'default' => '10',
			]
		);
		
		
        $this->end_controls_section();
    }

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
	$settings = $this->get_settings();
     $date_format=isset($settings['date_formats'])?$settings['date_formats']:"default";
     $catFilter = isset( $settings['catFilter'] )? $settings['catFilter']: 'true';
	$eventlimit = isset( $settings['limit'] )? $settings['limit']: '10';
	$shortcode = '[ect-calendar-layout date-format="'.$date_format.'" show-category-filter="'.$catFilter.'" limit="'.$eventlimit.'"]';
	echo'<div class="ect-elementor-shortcode ect-free-addon">';
	
		
     echo wp_kses_post($shortcode);
     echo'</div>';
	}
}