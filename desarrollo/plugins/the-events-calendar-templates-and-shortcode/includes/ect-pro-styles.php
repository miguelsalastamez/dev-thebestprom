<?php
class EctProStyles {

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	public function __construct() {
		 $this->registers();
	}
	/**
	 * Register all hooks
	 */

	public function registers() {
		$thisPlugin = $this;
		/*** Enqueued script and styles */
		// add_action('wp_enqueue_scripts', array($thisPlugin, 'ect_styles'));
		$thisPlugin->load_files();

	}
	/*** Register CSS style assets */
	public function ect_styles() {
		wp_register_style( 'ect-common-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-common-styles.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-timeline-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-timeline.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-list-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-list-view.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-minimal-list-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-minimal-list-view.css', null, ECT_PRO_VERSION, 'all' );
		// Advance List
		wp_register_style( 'ect-advance-list-datatable-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-datatable.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-datatable-responsive', ECT_PRO_PLUGIN_URL . 'assets/css/ect-datatable-responsive.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-advance-list-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-advance-list.css', null, ECT_PRO_VERSION, 'all' );

		// scripts
		wp_register_script( 'ect-sharebutton', ECT_PRO_PLUGIN_URL . 'assets/js/ect-sharebutton.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		wp_register_style( 'ect-sharebutton-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-sharebutton.css', null, ECT_PRO_VERSION, 'all' );
		// Advance list
		wp_register_script( 'ect-advance-list-datatable-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-datatable.min.js', array( 'jquery' ), null, true );
		wp_register_script( 'ect-advance-list-dt-res', ECT_PRO_PLUGIN_URL . 'assets/js/ect-datatable-responsive.js', array( 'jquery', 'ect-advance-list-datatable-js' ), null, true );
		wp_register_script( 'ect-advance-list-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-advance-list.js', array( 'jquery', 'ect-advance-list-datatable-js' ), null, true );

		// Week View
		wp_register_script( 'ect-weekly-view-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-weekly-view.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		wp_register_style( 'ect-weekly-view-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-weekly-view.css', array(), ECT_PRO_VERSION, 'all' );
	}
	public function load_files() {
		// Inside ect-Ecttinycolor folder exists darken,lighten color.
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Ecttinycolor.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/util.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Traits/Convert.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Traits/Names.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Traits/Combination.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Traits/Modification.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-tinycolor/Color.php';
	}
	/*** Load CSS styles based on template. */
	public static function ect_load_requried_assets( $template, $style ) {
		$thisPlugin = new self();
		wp_enqueue_style( 'ect-common-styles' );
		$custom_style = $thisPlugin::ect_custom_styles( $template, $style );
		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ) ) ) {
			wp_enqueue_style( 'ect-timeline-styles' );
			wp_add_inline_style( 'ect-timeline-styles', $custom_style );
		} elseif ( $template == 'minimal-list' ) {
			wp_enqueue_style( 'ect-minimal-list-styles' );
			wp_add_inline_style( 'ect-minimal-list-styles', $custom_style );
		} else {
			wp_add_inline_style( 'ect-list-styles', $custom_style );
			wp_enqueue_style( 'ect-list-styles' );
		}
	}
	public static function get_typeo_output( $settings ) {
		$output        = '';
		$important     = '';
		$font_family   = ( ! empty( $settings['font-family'] ) ) ? $settings['font-family'] : '';
		$backup_family = ( ! empty( $settings['backup-font-family'] ) ) ? ', ' . $settings['backup-font-family'] : '';
		if ( $font_family ) {
			$output .= 'font-family:"' . $font_family . '"' . $backup_family . $important . ';';
		}
		// Common font properties
		$properties = array(
			'color',
			'font-weight',
			'font-style',
			'font-variant',
			'text-align',
			'text-transform',
			'text-decoration',
		);
		foreach ( $properties as $property ) {
			if ( isset( $settings[ $property ] ) && $settings[ $property ] !== '' ) {
				$output .= $property . ':' . $settings[ $property ] . $important . ';';
			}
		}
		$properties       = array(
			'font-size',
			'line-height',
			'letter-spacing',
			'word-spacing',
		);
		$unit             = ( ! empty( $settings['unit'] ) ) ? $settings['unit'] : 'px';
		$line_height_unit = ( ! empty( $settings['line_height_unit'] ) ) ? $settings['line_height_unit'] : 'em';
		foreach ( $properties as $property ) {
			if ( isset( $settings[ $property ] ) && $settings[ $property ] !== '' ) {
				$unit    = ( $property === 'line-height' ) ? $line_height_unit : $unit;
				$output .= $property . ':' . $settings[ $property ] . $unit . $important . ';';
			}
		}
			return $output;
	}
	public static function ect_hex2rgba( $color, $opacity = false ) {
		$default = 'rgb(0,0,0)';
		// Return default if no color provided
		if ( empty( $color ) ) {
			return $default;
		}
		// Sanitize $color if "#" is provided
		if ( $color[0] == '#' ) {
			$color = substr( $color, 1 );
		}
		// Check if color has 6 or 3 characters and get values
		if ( strlen( $color ) == 6 ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $color;
		}
		// Convert hexadec to rgb
		$rgb = array_map( 'hexdec', $hex );
		// Check if opacity is set(rgba or rgb)
		if ( $opacity ) {
			if ( abs( $opacity ) > 1 ) {
				$opacity = 1.0;
			}
			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}
		// Return rgb(a) color string
		return $output;
	}
	/** This function is used to apply custom styles/typography settings */
	public static function ect_custom_styles( $template, $style ) {
		$thisPlugin                = new self();
		$ect_output_css            = '';
		$options                   = get_option( 'ects_options' );
		$all_saved_ff              = array();
		$custom_css                = ! empty( $options['custom_css'] ) ? $options['custom_css'] : '';
		$main_skin_color           = ! empty( $options['main_skin_color'] ) ? $options['main_skin_color'] : '#dbf5ff';
		$main_skin_alternate_color = ! empty( $options['main_skin_alternate_color'] ) ? $options['main_skin_alternate_color'] : '';
		$featured_event_skin_color = ! empty( $options['featured_event_skin_color'] ) ? $options['featured_event_skin_color'] : '#f19e59';
		$featured_event_font_color = ! empty( $options['featured_event_font_color'] ) ? $options['featured_event_font_color'] : '#3a2201';
		$event_desc_bg_color       = ! empty( $options['event_desc_bg_color'] ) ? $options['event_desc_bg_color'] : '#ffffff';
		$title_styles              = $thisPlugin::get_typeo_output( ! empty( $options['ect_title_styles'] ) ? $options['ect_title_styles'] : '' );
		$ect_title_styles          = ! empty( $options['ect_title_styles'] ) ? $options['ect_title_styles'] : '';
		$ect_title_color           = ! empty( $ect_title_styles['color'] ) ? $ect_title_styles['color'] : '';
		$ect_title_font_size       = ! empty( $ect_title_styles['font-size'] ) ? $ect_title_styles['font-size'] : '18';
		$ect_title_font_famiily    = ! empty( $ect_title_styles['font-family'] ) ? $ect_title_styles['font-family'] : '';
		$ect_desc_styles           = $thisPlugin::get_typeo_output( ! empty( $options['ect_desc_styles'] ) ? $options['ect_desc_styles'] : '' );
		$ect_venue_styles          = $thisPlugin::get_typeo_output( ! empty( $options['ect_desc_venue'] ) ? $options['ect_desc_venue'] : '' );
		$ect_date_style            = $thisPlugin::get_typeo_output( ! empty( $options['ect_dates_styles'] ) ? $options['ect_dates_styles'] : '' );
		$ect_date_style            = $thisPlugin::get_typeo_output( $options['ect_dates_styles'] );
		// Fetch Description Typograpy
		$ect_desc_style        = ! empty( $options['ect_desc_styles'] ) ? $options['ect_desc_styles'] : '';
		$ect_desc_color        = ! empty( $ect_desc_style['color'] ) ? $ect_desc_style['color'] : '#515d64';
		$ect_desc_font_famiily = ! empty( $ect_desc_style['font-family'] ) ? $ect_desc_style['font-family'] : 'Open Sans';
		// Fetch venue Typography
		$ect_venue_style        = ! empty( $options['ect_desc_venue'] ) ? $options['ect_desc_venue'] : '';
		$ect_venue_font_famiily = ! empty( $ect_venue_style['font-family'] ) ? $ect_venue_style['font-family'] : 'Open Sans';
		$ect_venue_font_size    = ! empty( $ect_venue_style['font-size'] ) ? $ect_venue_style['font-size'] : '15';
		$venue_font_size        = $ect_venue_font_size + $ect_venue_font_size / 3 . 'px';
		$ect_venue_color        = ! empty( $ect_venue_style['color'] ) ? $ect_venue_style['color'] : '#00445e';
		// Fetch Date Typography
		$ect_date_styles              = ! empty( $options['ect_dates_styles'] ) ? $options['ect_dates_styles'] : '';
		$ect_date_font_family         = ! empty( $ect_date_styles['font-family'] ) ? $ect_date_styles['font-family'] : 'Monda';
		$ect_date_font_size           = ! empty( $ect_date_styles['font-size'] ) ? $ect_date_styles['font-size'] : '20px';
		$ect_date_color               = ! empty( $ect_date_styles['color'] ) ? $ect_date_styles['color'] : '#00445e';
		$ect_date_font_weight         = ! empty( $ect_date_styles['font-weight'] ) ? $ect_date_styles['font-weight'] : 'bold';
		$ect_date_font_style          = ! empty( $ect_date_styles['font-style'] ) ? $ect_date_styles['font-style'] : '';
		$ect_date_line_height         = ! empty( $ect_date_styles['line-height'] ) ? $ect_date_styles['line-height'] : '1';
		$all_saved_ff['date_family']  = str_replace( ' ', '+', $ect_date_font_family );
		$all_saved_ff['venue_family'] = str_replace( ' ', '+', $ect_venue_font_famiily );
		$all_saved_ff['title_family'] = str_replace( ' ', '+', $ect_title_font_famiily );
		$all_saved_ff['desc_family']  = str_replace( ' ', '+', $ect_desc_font_famiily );
		$whitecolor                   = ! empty( $options['main_skin_alternate_color'] ) ? $options['main_skin_alternate_color'] : $ect_date_color;
		if ( $template != 'advance-list' || $template != 'week-view' ) {
			$ect_output_css .= '.ect-load-more a.ect-load-more-btn {
                 background-color: ' . $main_skin_color . ';
            }
            .ect-load-more a.ect-load-more-btn{
                 color: ' . $main_skin_alternate_color . ';
            }
            span.tribe-tickets-left {
                color:  ' . Ecttinycolor( $main_skin_alternate_color )->darken( 5 )->toString() . ';
            }
            .ect-load-more:before,
			.ect-load-more:after {
				background: ' . Ecttinycolor( $main_skin_alternate_color )->lighten( 20 )->toString() . ';
			}
            div[id*="event-"].ect-simple-event .ect-event-category ul.tribe_events_cat li a{
                color: ' . $main_skin_color . ';
                border-color: ' . $main_skin_color . ';
            }
            div[id*="event-"].ect-featured-event .ect-event-category ul.tribe_events_cat li a{
                color: ' . $featured_event_skin_color . ';
                border-color: ' . $featured_event_skin_color . ';
            }
            div[id*="event-"].ect-featured-event .ect-event-category ul.tribe_events_cat li a:hover{
                color: ' . $featured_event_font_color . ';
                background: ' . $featured_event_skin_color . ';
            }
            div[id*="event-"].ect-simple-event .ect-event-category ul.tribe_events_cat li a:hover{
                color: ' . $whitecolor . ';
                background: ' . $main_skin_color . ';
            }
            div[id*="event-"].ect-featured-event:not(.style-1) .ect-event-category ul.tribe_events_cat li a{
                color: ' . $featured_event_font_color . ';
                background: ' . $featured_event_skin_color . ';
            }
            div[id*="event-"].ect-simple-event:not(.style-1) .ect-event-category ul.tribe_events_cat li a{
                color: ' . $whitecolor . ';
                background: ' . $main_skin_color . ';
            }
            div[id*="event-"].ect-featured-event:not(.style-1) .ect-event-category ul.tribe_events_cat li a:hover{
                color: ' . $featured_event_skin_color . ';
                background: ' . $featured_event_font_color . ';
                border-color: ' . $featured_event_skin_color . ';
            }
            div[id*="event-"].ect-simple-event:not(.style-1) .ect-event-category ul.tribe_events_cat li a:hover{
                color: ' . $main_skin_color . ';    
                background: ' . $whitecolor . ';
                border-color: ' . $main_skin_color . ';
            }
            
            .ect-grid-event.ect-simple-event .ect-share-wrapper i.ect-icon-share:before{
				background: ' . $main_skin_color . ';
                color: ' . $main_skin_alternate_color . ';
			}
            .ect-grid-event.ect-featured-event .ect-share-wrapper i.ect-icon-share:before{
				background: ' . $featured_event_skin_color . ';
                color: ' . $featured_event_font_color . ';
			}
         
            ';
		}
		$load_google_font = ! empty( $options['ect_load_google_font'] ) ? $options['ect_load_google_font'] : 'yes';
		if ( $load_google_font == 'yes' ) {
			$safe_fonts    = array(
				'Arial',
				'Arial+Black',
				'Helvetica',
				'Times+New+Roman',
				'Courier+New',
				'Tahoma',
				'Verdana',
				'Impact',
				'Trebuchet+MS',
				'Comic+Sans+MS',
				'Lucida+Console',
				'Lucida+Sans+Unicode',
				'Georgia',
				'Palatino+Linotype',
			);
				$build_url = 'https://fonts.googleapis.com/css?family=';
			   $ff_names   = array();
			foreach ( $all_saved_ff as $key => $val ) {
				if ( ! in_array( $val, $safe_fonts ) ) {
					$ff_names[] = $val;
				}
			}
			if ( ! empty( $ff_names ) ) {
				$build_url .= implode( '|', array_filter( $ff_names ) );
				wp_enqueue_style( 'ect-google-font', "$build_url", array(), null, null, 'all' );
			}
		}
		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ) ) ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/timeline/timeline-css.php';
		} elseif ( $template == 'minimal-list' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/minimal-list/minimal-list-css.php';
		} elseif ( $template == 'grid-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/grid/grid-css.php';
		} elseif ( $template == 'masonry-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/grid/grid-css.php';
		} elseif ( $template == 'slider-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/slider/slider-css.php';
		} elseif ( $template == 'accordion-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/accordion/accordion-css.php';
		} elseif ( $template == 'cover-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/cover/cover-css.php';
		} elseif ( $template == 'carousel-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/carousel/carousel-css.php';
		} elseif ( $template == 'advance-list' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/advance-list/advance-list-css.php';
		} elseif ( $template == 'week-view' ) {
			require ECT_PRO_PLUGIN_DIR . 'templates/week-view/week-view-css.php';
		} else {
			require ECT_PRO_PLUGIN_DIR . 'templates/list/list-css.php';
		}
		if ( ! empty( $custom_css ) ) {
			return $thisPlugin::minify_css( $ect_output_css . $custom_css );
		} else {
			return $thisPlugin::minify_css( $ect_output_css );
		}
	}
	public static function minify_css( $input ) {
		if ( trim( $input ) === '' ) {
			return $input;
		}
		return preg_replace(
			array(
				// Remove comment(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
				// Remove unused white-space(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
				// Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
				'#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
				// Replace `:0 0 0 0` with `:0`
				'#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
				// Replace `background-position:0` with `background-position:0 0`
				'#(background-position):0(?=[;\}])#si',
				// Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
				'#(?<=[\s:,\-])0+\.(\d+)#s',
				// Minify string value
				'#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
				'#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
				// Minify HEX color code
				'#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
				// Replace `(border|outline):none` with `(border|outline):0`
				'#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
				// Remove empty selector(s)
				'#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
			),
			array(
				'$1',
				'$1$2$3$4$5$6$7',
				'$1',
				':0',
				'$1:0 0',
				'.$1',
				'$1$3',
				'$1$2$4$5',
				'$1$2$3',
				'$1:0',
				'$1$2',
			),
			$input
		);
	}
}
