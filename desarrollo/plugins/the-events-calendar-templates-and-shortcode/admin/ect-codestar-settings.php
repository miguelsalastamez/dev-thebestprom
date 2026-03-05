<?php
/**
 *
 * This file is responsible for creating all admin settings in Timeline Builder (post)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Can not load script outside of WordPress Enviornment!' );
}
if ( ! class_exists( 'ECTProSettings' ) ) {
	class ECTProSettings {
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
			$this->create_settings_panel();
		}
		public function create_settings_panel() {
			// Set a unique slug-like ID
			$prefix = 'ects_options';
			if ( class_exists( 'ECTCSF' ) ) {
				// Create options
				ECTCSF::createOptions(
					$prefix,
					array(
						'framework_title'    => 'Events Shortcodes (PRO) For The Events Calendar Settings',
						'menu_title'         => 'Shortcodes Settings',
						'menu_slug'          => 'tribe-events-shortcode-template-settings',
						'menu_type'          => 'submenu',
						'menu_parent'        => 'cool-plugins-events-addon',
						'menu_icon'          => ECT_PRO_PLUGIN_URL . 'assets/css/ect-icon.svg',
						'nav'                => 'inline',
						'show_bar_menu'      => false,
						'show_reset_section' => false,
						'show_sub_menu'      => false,
					)
				);
				// Create a section
				ECTCSF::createSection(
					$prefix,
					array(
						'title'  => 'General Settings',
						'fields' => array(
							array(
								'title'   => 'Main Skin Color',
								'id'      => 'main_skin_color',
								'type'    => 'color',
								'desc'    => 'It is a main color scheme for all designs',
								'default' => '#5bbd8a',
							),
							array(
								'title'   => 'Main Skin Alternate Color / Font Color',
								'id'      => 'main_skin_alternate_color',
								'type'    => 'color',
								'desc'    => 'Text/Font color where background color is Main Skin.',
								'default' => '#ffffff',
							),
							array(
								'title'   => 'Featured Event Skin Color',
								'id'      => 'featured_event_skin_color',
								'type'    => 'color',
								'desc'    => 'This skin color applies on featured events',
								'default' => '#008cff',
							),
							array(
								'title'   => 'Featured Event Font Color',
								'id'      => 'featured_event_font_color',
								'type'    => 'color',
								'desc'    => 'This color applies on some fonts of featured events',
								'default' => '#ffffff',
							),
							array(
								'title'   => 'Event Background Color',
								'id'      => 'event_desc_bg_color',
								'type'    => 'color',
								'desc'    => 'This skin color applies on background of event description area.',
								'default' => '#ffffff',
							),
							array(
								'title'            => 'Event Title Styles',
								'id'               => 'ect_title_styles',
								'type'             => 'typography',
								'font_weight'      => 'bold',
								'font_style'       => 'normal',
								'desc'             => 'Select a style',
								'default'          => array(
									'color'              => '#383838',
									'font-family'        => 'Monda',
									'font-size'          => '18',
									'line-height'        => '1.5',
									'font-weight'        => '700',
									// 'font-style'=>'normal',
									  'line_height_unit' => 'em',
								),
								'line_height_unit' => 'em',
							),
							array(
								'title'            => 'Events Description Styles',
								'id'               => 'ect_desc_styles',
								'type'             => 'typography',
								'desc'             => 'Select Styles',
								'default'          => array(
									'color'       => '#a5a5a5',
									'font-family' => 'Open Sans',
									'font-size'   => '15',
									'line-height' => '1.5',
								),
								'line_height_unit' => 'em',
							),
							array(
								'title'            => 'Event Venue Styles',
								'id'               => 'ect_desc_venue',
								'type'             => 'typography',
								'desc'             => 'Select a style',
								'default'          => array(
									'color'       => '#a5a5a5',
									'font-family' => 'Open Sans',
									'font-size'   => '15',
									'font-style'  => 'italic',
									'line-height' => '1.5',
								),
								'line_height_unit' => 'em',
							),
							array(
								'title'            => 'Event Dates Styles',
								'id'               => 'ect_dates_styles',
								'type'             => 'typography',
								'desc'             => 'Select a style',
								'default'          => array(
									'color'       => '#ffffff',
									'font-family' => 'Monda',
									'font-size'   => '36',
									'font-weight' => '700',
									'line-height' => '1',
								),
								'line_height_unit' => 'em',
							),
						),
					)
				);
				// Create a section
				ECTCSF::createSection(
					$prefix,
					array(
						'title'  => 'Extra Settings',
						'fields' => array(
							// A textarea field
							array(
								'title' => 'Custom CSS',
								'id'    => 'custom_css',
								'type'  => 'code_editor',
								'desc'  => 'Put your custom CSS rules here',
								'mode'  => 'css',
							),
							array(
								'title'   => 'No Event Text (Message to show if no event will available)',
								'id'      => 'events_not_found',
								'default' => 'There are no upcoming events at this time',
								'type'    => 'text',
								'desc'    => '',
							),
							array(
								'title'       => 'Update Find Out More label',
								'id'          => 'events_more_info',
								'default'     => '',
								'placeholder' => 'Enter Find Out More label',
								'type'        => 'text',
								'desc'        => 'Default value is Find out more, Add the string you want to show',
							),
							array(
								'id'    => 'ect_no_featured_img',
								'type'  => 'media',
								'title' => 'Default Image (select a default image, if no featured image for the event)',
							),
							array(
								'id'          => 'ect_display_categoery',
								'type'        => 'select',
								'title'       => 'Display category in templates',
								'placeholder' => '',
								'options'     => array(
									'ect_enable_cat'  => 'Enable',
									'ect_disable_cat' => 'Disable',
								),
								'default'     => 'ect_disable_cat',
							),
							array(
								'id'      => 'ect_load_google_font',
								'type'    => 'select',
								'title'   => 'Load Google Font',
								'options' => array(
									'yes' => 'Yes',
									'no'  => 'No',
								),
								'default' => 'yes',
							),
						),
					)
				);
				ECTCSF::createSection(
					$prefix,
					array(
						'title'  => 'Shortcode Attributes',
						'fields' => array(
							array(
								'title'   => 'Default Shortcode',
								'type'    => 'heading',
								'content' => '<code>[events-calendar-templates template="default" style="style-1" category="all" date_format="default" start_date="" end_date="" limit="10" order="ASC" hide-venue="no" time="future" featured-only="false" columns="2" autoplay="true" tags="" venues="" organizers="" socialshare="no"]</code>',
							),
							array(
								'type'     => 'callback',
								'function' => array( $this, 'ect_shortcode_attr' ),
							),
							array(
								'title'   => 'Shortcode For Calendar Template',
								'type'    => 'heading',
								'content' => '<code>[ect-calendar-layout date-format="d F Y" show-category-filter="true" limit="10"]</code></code><small style="color:red;font-size: 19px;"></small>',
							),
							array(
								'type'     => 'callback',
								'function' => array( $this, 'ect_calendar_shortcode_attr' ),
							),
						),
					)
				);
			}
		}
		function ect_calendar_shortcode_attr() {
				echo '<style>
          .tf-custom table tr th, .tf-custom table tr td{border:1px solid #ddd}
          table {width: 50%;text-align: center;margin: auto;}
          </style>
				<table style="border:1px solid #ddd">
							<tr  style="border:1px solid #ddd"><th style="border:1px solid #ddd">Attribute</th><th  style="border:1px solid #ddd">Value</th></tr>
              <tr  style="border:1px solid #ddd"><td  style="border:1px solid #ddd">limit</td>
							<td  style="border:1px solid #ddd"><ul>
							<li>Any positive number to limit the fetched events</li>
							<li>Default value:10 will be used if left blank</li>
							</ul></td></tr>
              <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">date_format</td>
              <td style="border:1px solid #ddd"><ul>
              <li><strong>default</strong> (01 January 2021)</li>
              <li><strong>MD,Y</strong> (Jan 01, 2021)</li>
              <li><strong>FD,Y</strong> (January 01, 2021)</li>
              <li><strong>dM</strong> (01 Jan)</li>
              <li><strong>dF</strong> (01 January)</li>
              <li><strong>Md</strong> (Jan 01)</li>
              <li><strong>Fd</strong> (January 01)</li>
              <li><strong>jMl </strong> (1 Jan Monday)</li>
              <li><strong>d.FY</strong> (01. January 2021)</li>
              <li><strong>d.F </strong> (01. January)</li>
              <li><strong>d.Ml </strong> (01. Jan Monday)</li>
              <li><strong>d.F </strong> (01. January)</li>
              <li><strong>ldF  </strong> (Monday 01 January)</li>
              <li><strong>Mdl </strong> (Jan 01 Monday)</li>
              </ul></td></tr>
              <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">show-category-filter</td>
              <td style="border:1px solid #ddd"><ul>
              <li><strong>true</li>
              <li><strong>false</li>
              </ul></td></tr>
							</table>';
		}
		function ect_shortcode_attr() {
			$ect_admin_url = admin_url( 'edit.php?page=tribe-common&tab=display&post_type=tribe_events' );
				echo '
      <style>
      table.ect-shortcodes-tbl{
        
        width: 50%;
        text-align: center;
        margin: auto;
      }
      table.ect-shortcodes-tbl tr td{
      padding:15px;
      }</style>
      <h3>Shortcode Attributes</h3>
      <table class="ect-shortcodes-tbl" style="border:1px solid #ddd;">
      <tr style="border:1px solid #ddd"><th style="border:1px solid #ddd">Attribute</th><th style="border:1px solid #ddd">Value</th></tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">template</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>default</strong></li>
      <li><strong>grid-view</strong></li>
      <li><strong>carousel-view</strong> </li>
      <li><strong>slider-view</strong> </li>
      <li><strong>timeline-view</strong></li>
      <li><strong>masonry-view</strong></li>
      <li><strong>minimal-list</strong></li>
      <li><strong>accordion-view</strong> </li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td  style="border:1px solid #ddd">style</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>style-1</strong></li>
      <li><strong>style-2</strong></li>
      <li><strong>style-3</strong></li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">category</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>all</strong></li>
      <li><strong>category-slug (* You can also add comma separated multiple categories - cat1,cat2,cat3)</li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">date_format</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>default</strong> (01 January 2019)</li>
      <li><strong>MD,Y</strong> (Jan 01, 2019)</li>
      <li><strong>MD,Y</strong> (January 01, 2019)</li>
      <li><strong>DM</strong> (01 Jan)</li>
      <li><strong>DML</strong> (01 Jan Monday)</li>
      <li><strong>DF</strong> (01 January)</li>
      <li><strong>MD</strong> (Jan 01)</li>
      <li><strong>FD</strong> (January 01)</li>
      <li><strong>MD,YT</strong> (Jan 01, 2019 8:00am-5:00pm)</li>
      <li><strong>full</strong> (01 January 2019 8:00am-5:00pm)</li>
      <li><strong>custom</strong>( Please check TEC settings for custom date format <a href = "' . $ect_admin_url . '">Click here </a>)</li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">start_date<br/>end_date</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>YY-MM-DD</strong> (show events in between a date interval)</li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">limit</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>10</strong> (number of events to show)</li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">order</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>ASC</strong></li>
      <li><strong>DESC</strong></li>
      </ul></td></tr>

      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">hide_venue</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>yes</strong></li>
      <li><strong>no</strong></li>
      </ul></td></tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">time</td>
      <td style="border:1px solid #ddd"><ul>
      <li><strong>future</strong> (show future events)</li>
      <li><strong>past</strong> (show past events)</li>
      <li><strong>all</strong> (show all events)</li>
      </ul></td></tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">featured-only</td>
      <td style="border:1px solid #ddd"><ul>
      <li>true (show only featured events.)</li>
      <li>false</li>
      </ul></td></tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">columns</td>
							<td style="border:1px solid #ddd"><ul>
							<li>6 (number of columns in grid or carousel view.)</li>
							</ul></td></tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">autoplay</td>
							<td style="border:1px solid #ddd"><ul>
							<li>true (autoplay slider in carousel or slider template.)</li>
							<li>false</li>
							</ul></td>
      </tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">tags</td>
							<td style="border:1px solid #ddd"><ul>
							<li>tag-slug (* You can also add comma separated multiple tags - tag1,tag2,tag3)</li>
							</ul></td>
      </tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">venues</td>
							<td style="border:1px solid #ddd"><ul>
							<li>venue-id</li>
							</ul></td>
      </tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">organizers</td>
							<td style="border:1px solid #ddd"><ul>
							<li>organizers-id</li>
							</ul></td>
      </tr>
      <tr style="border:1px solid #ddd"><td style="border:1px solid #ddd">socialshare</td>
          <td style="border:1px solid #ddd"><ul>
            <li><strong>yes</strong></li>
            <li><strong>no</strong></li>
          </ul>
          </td>
      </tr>
    </table>';
		}

	}

}
