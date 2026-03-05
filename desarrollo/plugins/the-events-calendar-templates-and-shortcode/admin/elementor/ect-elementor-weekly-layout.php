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
class EctWeeklyElementorWidget extends Widget_Base {

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
		return 'ect-weekly-layout';
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
		return __( 'Events Weekly Layout', 'ect2' );
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
          $terms = get_terms(array(
			'taxonomy' => 'tribe_events_cat',
			'hide_empty' => false,
		));
		$ect_categories=array();
		$ect_categories['all'] = __('All Categories','cool-timeline');

		if (!empty($terms) || !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$ect_categories[$term->slug] =$term->name ;
			}
		}
		$tags =  get_terms(array(
			'taxonomy' => 'post_tag',
			'hide_empty' => false,
		));
		
		$ect_tags=array();
		$ect_tags[''] = __('Select All Tags','cool-timeline');

		if (!empty($tags) || !is_wp_error($tags)) {
			foreach ($tags as $tag) {
	
				$ect_tags[$tag->slug] =$tag->name ;
				
			}
		}
		
	/**
               *  Get organizer name
               */
              $args = get_posts(array(
                'post_status'=>'publish',
                'post_type'=>'tribe_organizer',
                 'posts_per_page'=>-1
              ));
              
              $ect_org_details=array();
              $ect_org_details[''] = 'all';
              if (!empty($args) || !is_wp_error($args)) {
                foreach ($args as $term) {
                  
                  $ect_org_details[$term->ID] =$term->post_name ;
                }
              }
              /**
               * Get venue detail
               */
              $get_venue = get_posts(array(
                'post_status'=>'publish',
                'post_type'=>'tribe_venue',
                 'posts_per_page'=>-1
              ));
              
              $ect_venue_details=array();
              $ect_venue_details[''] = 'all';
            
              if (!empty($get_venue) || !is_wp_error($get_venue)) {
                foreach ($get_venue as $venues) {
                 
                  $ect_venue_details[$venues->ID] =$venues->post_name ;
                }
              }
              $this->start_controls_section(
			'section_content',
			[
				'label' => __( 'The Events Weekly Layout', 'ect2' ),
			]
		);
           $this->add_control(
			'event_categories',
			[
				'label' => __( 'Categories', 'cool-timeline' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => $ect_categories
				
			]
		);
          $this->add_control(
               'tags',
               [
                   'label' => __( 'Select Tag (* Events by tag.)', 'cool-timeline' ),
                   'type' => Controls_Manager::SELECT,
                   // 'default' => 'no',
			    'label_block'=>'true',
                   'options' => $ect_tags,
               ]
              );
              $this->add_control(
               'organizers',
               [
                   'label' => __( 'Select Organizer(*Events by organizer.)', 'cool-timeline' ),
                   'type' => Controls_Manager::SELECT,
                   // 'default' => 'no',
			    'label_block'=>'true',
                   'options' => $ect_org_details,
                   
               ]
              );
              $this->add_control(
               'venues',
               [
                   'label' => __( 'Select Venue(* Events by venue.)', 'cool-timeline' ),
                   'type' => Controls_Manager::SELECT,
                   // 'default' => 'no',
			    'label_block'=>'true',
                   'options' => $ect_venue_details,
                   
               ]
              );
              $this->add_control(
			'limit',
			[
				'label' => __( 'Limit the events', 'cool-timeline' ),
				'type' => Controls_Manager::TEXT,
				'default' => '10',
				
			]
        );
          $this->add_control(
               'featured-only',
               [
                   'label' => __( 'Show Only Featured Events', 'cool-timeline' ),
                   'type' => Controls_Manager::SELECT,
                   'default' => 'all',
                   'options' => [
                       'all' => __( 'All', 'cool-timeline' ),
                       'no' => __( 'NO', 'cool-timeline' ),
                       'yes' => __( 'Yes', 'cool-timeline' ),
                   ]
               ]
              );	
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
          $start_date = isset( $settings['start_date'] )? $settings['start_date']: '';
          $end_date = isset( $settings['end_date'] )? $settings['end_date']: '';
          $venues=isset($settings['venues'])?$settings['venues']:"";
          $organizers=isset($settings['organizers'])?$settings['organizers']:"";
          $tag = isset($settings['tags'])?$settings['tags']:"";
		$number_of_events=isset($settings['limit'])?$settings['limit']:"10";
	     $featured= isset($settings['featured-only'])?$settings['featured-only']:"";
		$ect_categories = isset($settings['event_categories'])?$settings['event_categories']:"all";
          $shortcode = '[ect-weekly-layout category="' . $ect_categories .'" tags="' . $tag. '" limit="' . $number_of_events .
		' " featured-only="'.$featured.'" venues="'.$venues.'" organizers="'.$organizers.'"]';
		echo'<div class="ect-elementor-shortcode ect-free-addon">';
		if(is_admin()){
		   echo "<strong>It is only a shortcode builder. Kindly update/publish the page and check the actually events layout on front-end</strong><br/>";
		}
		echo $shortcode;
		echo'</div>';
	}
}