<?php
/**
 *
 * This file is responsible for creating all admin settings in Timeline Builder (post)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Can not load script outside of WordPress Enviornment!' );
}

if ( ! class_exists( 'ECT_event_shortcode' ) ) {
	class ECT_event_shortcode {


		/**
		 * The unique instance of the plugin.
		 */
		private static $instance;

		/**
		 * Gets an instance of our plugin.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * The Constructor
		 */
		public function __construct() {
			 // register actions
			$this->ECT_event_shortcode();
			add_action( 'admin_print_styles', array( $this, 'ect_custom_shortcode_style' ) );

		}

		public function ect_custom_shortcode_style() {
			echo '<style>span.dashicon.dashicons.dashicons-ect-custom-icon:before {
        content:"";
        background: url(' . ECT_PRO_PLUGIN_URL . 'assets/images/ect-icons.svg);
        background-size: contain;
        background-repeat: no-repeat;
        height: 20px;
        display: block;
        }

         #wp-content-wrap a[data-modal-id="ect_shortcode_generator"]:before {
        content: "";
        background: url(' . ECT_PRO_PLUGIN_URL . 'assets/images/ect-cal-icon.png);
        background-size: contain;
        background-repeat: no-repeat;
        height: 17px;
        display: inline-block;
        margin: 0px 1px -3px 0;
        width: 20px;
        }
        #ECTCSF-modal-ect_shortcode_generator .ECTCSF-modal-inner {
            height: 500px !important;        
        }
        #ECTCSF-modal-ect_shortcode_generator .ECTCSF-modal-content {            
            height:400px !important;   
        }  
           
        
        </style>';

		}

		public function ECT_event_shortcode() {
			 $id       = isset( $GLOBALS['_GET']['post'] ) ? $GLOBALS['_GET']['post'] : '';
			$post_type = isset( $GLOBALS['_GET']['post_type'] ) ? $GLOBALS['_GET']['post_type'] : get_post_type( $id );
			if ( $post_type !== 'page' && $post_type !== 'post' && $post_type != '' ) {
				return;
			}
			if ( class_exists( 'ECTCSF' ) ) {

				//
				// Set a unique slug-like ID
				$prefix = 'ect_shortcode_generator';

				// Create a shortcoder
				ECTCSF::createShortcoder(
					$prefix,
					array(
						'button_title' => 'Events Shortcodes',
						'insert_title' => 'Insert shortcode',
						'gutenberg'    => array(
							'title'       => 'Events Shortcodes',
							'icon'        => 'ect-custom-icon',
							'description' => 'A shortcode generator for Events Calendar',
							'category'    => 'widgets',
							'keywords'    => array( 'shortcode', 'ect', 'event', 'code' ),
						),
					)
				);

				//
				// A basic shortcode

				ECTCSF::createSection(
					$prefix,
					array(
						'title'     => 'Events Calendar Templates',
						'view'      => 'normal', // View model of the shortcode. `normal` `contents` `group` `repeater`
						'shortcode' => 'events-calendar-templates', // Set a unique slug-like name of shortcode.
						'fields'    => array(
							array(
								'id'          => 'category',
								'type'        => 'select',
								'title'       => 'Events Category',
								'placeholder' => 'Select Category',
								'chosen'      => true,
								'multiple'    => true,
								'default'     => 'all',
								'desc'        => "Don't select alternate category if already you have selecetd all categories",
								'settings'    => array(
									'width' => '50%',
								),
								'options'     => 'ect_select_category',
							),
							array(
								'id'         => 'template',
								'type'       => 'select',
								'title'      => 'Select Template',
								'default'    => 'default',
								'options'    => array(
									'default'        => 'Default List Layout',
									'timeline-view'  => 'Timeline Layout',
									'slider-view'    => 'Slider Layout',
									'carousel-view'  => 'Carousel Layout',
									'grid-view'      => 'Grid Layout',
									'masonry-view'   => 'Masonry Layout(Categories Filters)',
									'accordion-view' => 'Toggle List Layout',
									'minimal-list'   => 'Minimal List',
									'advance-list'   => 'Advance List',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'style',
								'type'       => 'select',
								'title'      => 'Template Style',
								'default'    => 'style-1',
								'options'    => array(
									'style-1' => 'Style 1',
									'style-2' => 'Style 2',
									'style-3' => 'Style 3',
								),
								'dependency' => array(
									'template',
									'!=',
									'advance-list',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),

							),

							array(
								'id'         => 'date_format',
								'type'       => 'select',
								'title'      => 'Date Formats',
								'default'    => 'default',
								'options'    => array(
									'default' => 'Default (01 January 2019)',
									'MD,Y'    => 'Md,Y (Jan 01, 2019)',
									'FD,Y'    => 'Fd,Y (January 01, 2019)',
									'DM'      => 'dM (01 Jan)',
									'DML'     => 'dML (01 Jan Monday)',
									'DF'      => 'dF (01 January)',
									'MD'      => 'Md (Jan 01)',
									'FD'      => 'Fd (January 01)',
									'MD,YT'   => 'Md,YT (Jan 01, 2019 8:00am-5:00pm)',
									'full'    => 'Full (01 January 2019 8:00am-5:00pm)',
									'jMl'     => 'jMl (1 Jan Monday)',
									'd.FY'    => 'd.FY (01. January 2019)',
									'd.F'     => 'd.F (01. January)',
									'ldF'     => 'ldF (Monday 01 January)',
									'Mdl'     => 'Mdl (Jan 01 Monday)',
									'd.Ml'    => 'd.Ml (01. Jan Monday)',
									'dFT'     => 'dFT (01 January 8:00am-5:00pm)',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'limit',
								'type'       => 'text',
								'title'      => 'Limit the events',
								'default'    => '10',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '!=', 'advance-list' ),

							),
							array(
								'id'         => 'limit',
								'type'       => 'text',
								'title'      => 'Limit the events',
								'default'    => '25',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'order',
								'type'       => 'select',
								'title'      => 'Events Order',
								'default'    => 'ASC',
								'options'    => array(
									'ASC'  => 'ASC',
									'DESC' => 'DESC',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'hide-venue',
								'type'       => 'select',
								'title'      => 'Hide Venue',
								'default'    => 'no',
								'options'    => array(
									'yes' => 'Yes',
									'no'  => 'NO',
								),
								'dependency' => array(
									'template',
									'!=',
									'minimal-list',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'time',
								'type'       => 'select',
								'title'      => 'Events Time (Past/Future Events)',
								'default'    => 'future',
								'options'    => array(
									'future' => 'Upcoming',
									'past'   => 'Past',
									'all'    => 'All',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'columns',
								'type'       => 'select',
								'title'      => 'Columns',
								'default'    => '2',
								'dependency' => array(
									'template',
									'any',
									'grid-view,masonry-view,carousel-view',
								),
								'options'    => array(
									'2' => '2',
									'3' => '3',
									'4' => '4',
									'5' => '5',
									'6' => '6',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),

							array(
								'id'         => 'autoplay',
								'type'       => 'select',
								'title'      => 'AutoPlay',
								'default'    => 'true',
								'dependency' => array(
									'template',
									'any',
									'slider-view,carousel-view,cover-view',

								),
								'options'    => array(
									'true'  => 'True',
									'false' => 'False',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),

							array(
								'id'         => 'featured-only',
								'type'       => 'select',
								'title'      => 'Show Only Featured Events',
								'default'    => 'false',
								'options'    => array(
									'true'  => 'Yes',
									'false' => 'NO',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),

							array(
								'id'         => 'show-description',
								'type'       => 'select',
								'title'      => 'Show Description?',
								'default'    => 'yes',
								'options'    => array(
									'yes' => 'Yes',
									'no'  => 'NO',
								),
								'dependency' => array(
									'template',
									'not-any',
									'minimal-list,advance-list',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'show-description',
								'type'       => 'select',
								'title'      => 'Show Description?',
								'default'    => 'no',
								'options'    => array(
									'yes' => 'Yes',
									'no'  => 'NO',
								),
								'dependency' => array(
									'template',
									'==',
									'advance-list',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'          => 'tags',
								'type'        => 'select',
								'title'       => 'Select Tags',
								'placeholder' => 'Select Tags',
								'chosen'      => true,
								'multiple'    => true,
								'settings'    => array(
									'width' => '50%',
								),
								'options'     => 'ect_get_tags',
							),
							array(
								'id'          => 'venues',
								'type'        => 'select',
								'title'       => 'Select Venue',
								'placeholder' => 'Select Venue',
								'options'     => 'post',
								'query_args'  => array(
									'post_status'    => 'publish',
									'post_type'      => 'tribe_venue',
									'posts_per_page' => -1,
								),
								'attributes'  => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'          => 'organizers',
								'type'        => 'select',
								'title'       => 'Select Organizer',
								'placeholder' => 'Select Organizer',
								'options'     => 'post',
								'query_args'  => array(
									'post_status'    => 'publish',
									'post_type'      => 'tribe_organizer',
									'posts_per_page' => -1,
								),
								'attributes'  => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'socialshare',
								'type'       => 'select',
								'title'      => 'Enable Social Share Buttons?',
								'default'    => 'no',
								'options'    => array(
									'yes' => 'Yes',
									'no'  => 'NO',
								),
								'dependency' => array(
									'template',
									'not-any',
									'minimal-list,advance-list',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'             => 'ect-date-range-field',
								'type'           => 'date',
								'title'          => 'Show events between date range',
								'custom_from_to' => true,
								'settings'       => array(
									'dateFormat'  => 'yy-mm-dd',
									'changeMonth' => true,
									'changeYear'  => true,
									'yearRange'   => '1800:2050',
								),
								'attributes'     => array(
									'style' => 'width: 20%;',
								),
							),
							array(
								'id'         => 'date-lbl',
								'type'       => 'text',
								'title'      => 'Event Date Label',
								'default'    => 'Date',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'time-lbl',
								'type'       => 'text',
								'title'      => 'Event Time Label',
								'default'    => 'Duration',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'event-lbl',
								'type'       => 'text',
								'title'      => 'Event Name Label',
								'default'    => 'Event name',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'desc-lbl',
								'type'       => 'text',
								'title'      => 'Event Description Label',
								'default'    => 'Description',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'location-lbl',
								'type'       => 'text',
								'title'      => 'Event Venue Label',
								'default'    => 'Location',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'category-lbl',
								'type'       => 'text',
								'title'      => 'Event Category Label',
								'default'    => 'Category',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),
							array(
								'id'         => 'vm-lbl',
								'type'       => 'text',
								'title'      => 'View More Label',
								'default'    => 'View More',
								'attributes' => array(
									'style' => 'width: 50%;',
								),
								'dependency' => array( 'template', '==', 'advance-list' ),
							),

						),
					)
				);

				// Calendar shortcode
				ECTCSF::createSection(
					$prefix,
					array(
						'title'     => 'Events Calendar Layout',
						'shortcode' => 'ect-calendar-layout',
						'fields'    => array(
							array(
								'id'         => 'date-format',
								'type'       => 'select',
								'title'      => 'Date Formats',
								'default'    => 'default',
								'options'    => array(
									'd F Y'  => 'Default (01 January 2019)',
									'M D,Y'  => 'Md,Y (Jan 01, 2019)',
									'F D,Y'  => 'Fd,Y (January 01, 2019)',
									'DM'     => 'dM (01 Jan)',
									'D F'    => 'dF (01 January)',
									'M D'    => 'Md (Jan 01)',
									'F D'    => 'Fd (January 01)',
									'j M l'  => 'jMl (1 Jan Monday)',
									'd. F Y' => 'd.FY (01. January 2019)',
									'd. F'   => 'd.F (01. January)',
									'l d F'  => 'ldF (Monday 01 January)',
									'd. M l' => 'd.Ml (01. Jan Monday)',
									'M d l'  => 'Mdl (Jan 01 Monday)',

								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'show-category-filter',
								'type'       => 'select',
								'title'      => 'Show Category Filter',
								'default'    => 'true',
								'options'    => array(
									'true'  => 'True',
									'false' => 'False',
								),
								'attributes' => array(
									'style' => 'width: 50%;',
								),
							),
							array(
								'id'         => 'limit',
								'type'       => 'text',
								'title'      => 'Limit the events',
								'default'    => '10',
								'attributes' => array(
									'style' => 'width: 50%;',
								),

							),

						),

					)
				);

				// weekly View Calendar
				/**
				ECTCSF::createSection($prefix, array(
					'title' => 'Events Weekly Layout',
					'shortcode' => 'ect-weekly-layout',
					'fields' => array(
						array(
							'id' => 'category',
							'type' => 'select',
							'title' => 'Events Category',
							'placeholder' => 'Select Category',
							'chosen' => true,
							'multiple' => true,
							'default' => 'all',
							'desc'=>"Don't select alternate category if already you have selecetd all categories",
							'settings' => array(
								'width' => '50%',
							),
							'options' => 'ect_select_category',
						),
						array(
							'id' => 'tags',
							'type' => 'select',
							'title' => 'Select Tags',
							'placeholder' => 'Select Tags',
							'chosen' => true,
							'multiple' => true,
							'settings' => array(
								'width' => '50%',
							),
							'options' => 'ect_get_tags',
						),
						array(
							'id' => 'featured-only',
							'type' => 'select',
							'title' => 'Show Featured Events',
							'default' => 'all',
							'options' => array(
								'all'=>'All',
								'yes' => 'Yes',
								'no' => 'NO',
							),
							'attributes' => array(
								'style' => 'width: 50%;',
							),
						),
						array(
							'id' => 'venues',
							'type' => 'select',
							'title' => 'Select Venue',
							'placeholder' => 'Select Venue',
							'options' => 'post',
							'query_args' => array(
								'post_status' => 'publish',
								'post_type' => 'tribe_venue',
								'posts_per_page' => -1,
							),
							'attributes' => array(
								'style' => 'width: 50%;',
							),
						),
						array(
							'id' => 'organizers',
							'type' => 'select',
							'title' => 'Select Organizer',
							'placeholder' => 'Select Organizer',
							'options' => 'post',
							'query_args' => array(
								'post_status' => 'publish',
								'post_type' => 'tribe_organizer',
								'posts_per_page' => -1,
							),
							'attributes' => array(
								'style' => 'width: 50%;',
							),
						),
						array(
							'id' => 'limit',
							'type' => 'text',
							'title' => 'Events Limit On Week',
							'default' => '10',
							'attributes' => array(
								'style' => 'width: 50%;',
							),

						),

					),

				));
				*/

			}

			/**
			 * Fetch all timeline items for shortcode builder options
			 *
			 * @return array $ids An array of timeline item's ID & title
			 */
			function ect_get_tags() {
				$tags = get_terms(
					array(
						'taxonomy'   => 'post_tag',
						'hide_empty' => true,
					)
				);

				$ect_tags     = array();
				$ect_tags[''] = __( 'Select Tags', 'cool-timeline' );

				if ( ! empty( $tags ) || ! is_wp_error( $tags ) ) {
					foreach ( $tags as $tag ) {

						$ect_tags[ $tag->slug ] = $tag->name;

					}
				}
				return $ect_tags;

			}

			function ect_select_category() {
				$terms                 = get_terms(
					array(
						'taxonomy'   => 'tribe_events_cat',
						'hide_empty' => true,
					)
				);
				$ect_categories        = array();
				$ect_categories['all'] = __( 'All Category', 'ect' );

				if ( ! empty( $terms ) || ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$ect_categories[ $term->slug ] = $term->name;
					}
				}

				return $ect_categories;

			}
		}

	}

}

new ECT_event_shortcode();
