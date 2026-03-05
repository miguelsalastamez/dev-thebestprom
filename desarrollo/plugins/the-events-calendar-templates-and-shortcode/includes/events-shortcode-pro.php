<?php

class EventsShortcodePro {

	/**
	 * @var array
	 */
	private $options;
	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	public function __construct( array $options = array() ) {
		$this->options = $options;
		$this->registers();
	}
	/**
	 * Register all hooks
	 */
	public function registers() {
		/*** Include helpers functions*/
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-event-helpers.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-functions.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-assets-manager.php';
		require_once ECT_PRO_PLUGIN_DIR . 'includes/ect-pro-styles.php';
		require_once ECT_PRO_PLUGIN_DIR . 'templates/week-view/ect-weekly-temaplate.php';
		$weekView   = new ect_weekly_temaplate();
		$thisPlugin = $this;
		/*** ECT main shortcode */
		add_shortcode( 'events-calendar-templates', array( $thisPlugin, 'ect_shortcodes' ) );
		add_shortcode( 'ect-weekly-layout', array( $weekView, 'ect_weekly_shortcode' ) );
		// load more events for masonry layout
		add_action( 'wp_ajax_ect_common_load_more', array( $thisPlugin, 'ect_common_loadmore_handler' ) );
		add_action( 'wp_ajax_nopriv_ect_common_load_more', array( $thisPlugin, 'ect_common_loadmore_handler' ) );
		add_action( 'wp_ajax_ect_catfilters_load_more', array( $thisPlugin, 'ect_catfilters_load_more' ) );
		add_action( 'wp_ajax_nopriv_ect_catfilters_load_more', array( $thisPlugin, 'ect_catfilters_load_more' ) );

		add_action( 'wp_ajax_ect_get_prev_nxt_data', array( $this, 'ect_weekly_button' ) );
		add_action( 'wp_ajax_nopriv_ect_get_prev_nxt_data', array( $this, 'ect_weekly_button' ) );

		require_once ECT_PRO_PLUGIN_DIR . '/templates/calendar/calendar.php';

		$ect_assets = new Ect_Assets_Manager();
		$ect_styles = new EctProStyles();

	}
	/*** ECT main shortcode */
	public function ect_shortcodes( $atts ) {
		if ( ! function_exists( 'tribe_get_events' ) ) {
			return;
		}
		$load_nonce = wp_create_nonce( 'load-more-nonce' );
		global $wp_query, $post;
		global $more;
		$more        = false;
		$output      = '';
		$events_html = '';
		$build_query = array();
		/*** Set shortcode default attributes */
		$attribute = shortcode_atts(
			apply_filters(
				'ect_shortcode_atts',
				array(
					'template'         => 'default',
					'style'            => 'style-1',
					'category'         => 'all',
					'date_format'      => 'default',
					'start_date'       => '',
					'end_date'         => '',
					'time'             => 'future',
					'order'            => 'ASC',
					'limit'            => '10',
					'columns'          => '3',
					'hide-venue'       => 'no',
					'autoplay'         => 'false',
					'featured-only'    => '',
					'show-description' => '',
					'event_tax'        => '',
					'month'            => '',
					'icons'            => '',
					'tags'             => '',
					'venues'           => '',
					'organizers'       => '',
					'date-lbl'         => '',
					'time-lbl'         => '',
					'event-lbl'        => '',
					'desc-lbl'         => '',
					'category-lbl'     => '',
					'location-lbl'     => '',
					'vm-lbl'           => '',
					'socialshare'      => 'no',
				),
				$atts
			),
			$atts
		);

		$selected_cat     = $attribute['category'];
		$selected_tag     = $attribute['tags'];
		$show_description = ! empty( $attribute['show-description'] ) ? $attribute['show-description'] : '';
		$count            = 0;
		$no_events        = '';
		$template         = isset( $attribute['template'] ) ? $attribute['template'] : 'default';
		$dateTittle       = ! empty( $attribute['date-lbl'] ) ? $attribute['date-lbl'] : 'Date';

		$timerangeTittle  = ! empty( $attribute['time-lbl'] ) ? $attribute['time-lbl'] : 'Duration';
		$evTittle         = ! empty( $attribute['event-lbl'] ) ? $attribute['event-lbl'] : 'Event Name';
		$eventDescTittle  = ! empty( $attribute['desc-lbl'] ) ? $attribute['desc-lbl'] : 'Description';
		$categoryTittle   = ! empty( $attribute['category-lbl'] ) ? $attribute['category-lbl'] : 'Category';
		$eventVenueTittle = ! empty( $attribute['location-lbl'] ) ? $attribute['location-lbl'] : 'Location';
		$viewMoreTittle   = ! empty( $attribute['vm-lbl'] ) ? $attribute['vm-lbl'] : 'View More';

		$date_format    = $attribute['date_format'];
		$tabs_menu_html = '';
		$tabs_cont_html = '';
		$ev_cost        = '';
		$car_sl_styles  = '';
		$activetb       = 1;
		$slider_pp_id   = 'ect-' . $attribute['template'] . '-' . $attribute['style'] . rand( 1, 10000 );
		if ( $attribute['style'] != '' ) {
			$car_sl_styles = '-' . $attribute['style'];
		}
		$hide_venue              = $attribute['hide-venue'];
		$ect_carousel_id         = 'ect-events-carousel' . $car_sl_styles;
		$ect_grid_id             = 'ect-grid-view-' . $attribute['style'];
		$ect_masonry_cls         = 'ect-masonry-view-' . $attribute['style'];
		$ect_slider_templates_id = 'ect-events-slider' . $car_sl_styles;
		$ect_cover_template_id   = 'ect-events-cover' . $car_sl_styles;
		$design                  = 'default-design';
		$autoplay                = $attribute['autoplay'];
			$carousel_slide_show = isset( $attribute['columns'] ) ? $attribute['columns'] : '';
			$style               = isset( $attribute['style'] ) ? $attribute['style'] : 'default';
		$socialshare             = $attribute['socialshare'];
		$m_output                = '';
		$d                       = ect_get_option( 'events_not_found' );
		// load assets according to the type and layout
		Ect_Assets_Manager::ect_load_requried_assets( $template, $style, $slider_pp_id, $autoplay, $carousel_slide_show );
		if ( $socialshare == 'yes' ) {
			wp_enqueue_script( 'ect-sharebutton', ECT_PRO_PLUGIN_URL . 'assets/js/ect-sharebutton.js', array( 'jquery' ), ECT_PRO_VERSION, true );
			wp_enqueue_style( 'ect-sharebutton-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-sharebutton.css', null, ECT_PRO_VERSION, 'all' );
		}
		/*
		Build ECT query
		*/
		$category_array = array();
		$tags_array     = array();

		$prev_event_month  = '';
		$prev_event_year   = '';
		$meta_date_compare = '>=';
		if ( $attribute['time'] == 'past' ) {
			$meta_date_compare = '<';
		} elseif ( $attribute['time'] == 'all' ) {
			$meta_date_compare = '';
		}
		$attribute['key']       = '_EventStartDate';
		$attribute['meta_date'] = '';
		$meta_date_date         = '';
		if ( $meta_date_compare != '' ) {
			$meta_date_date         = current_time( 'Y-m-d H:i:s' );
			$attribute['key']       = '_EventStartDate';
			$attribute['meta_date'] = array(
				array(
					'key'     => '_EventEndDate',
					'value'   => $meta_date_date,
					'compare' => $meta_date_compare,
					'type'    => 'DATETIME',
				),
			);
		}
		$featured_only = '';
		if ( $attribute['featured-only'] == 'true' ) {
			$featured_only = true;
		} elseif ( $attribute['featured-only'] == 'false' ) {
			$featured_only = '';
		}
		$ect_args = apply_filters(
			'ect_args_filter',
			array(
				'post_status'    => 'publish',
				'posts_per_page' => $attribute['limit'],
				'meta_key'       => $attribute['key'],
				'orderby'        => 'event_date',
				'order'          => $attribute['order'],
				'featured'       => $featured_only,
				'meta_query'     => $attribute['meta_date'],
			),
			$attribute,
			$meta_date_date,
			$meta_date_compare
		);
		if ( $attribute['tags'] != '' ) {
			if ( strpos( $attribute['tags'], ',' ) !== false ) {
				$ect_args['tag'] = explode( ',', $attribute['tags'] );
			} else {
				$ect_args['tag'] = $attribute['tags'];
			}
		}
		if ( $attribute['venues'] != '' ) {
			if ( strpos( $attribute['venues'], ',' ) !== false ) {
				$ect_args['venue'] = explode( ',', $attribute['venues'] );
			} else {
				$ect_args['venue'] = $attribute['venues'];
			}
		}
		if ( $attribute['organizers'] != '' ) {
			if ( strpos( $attribute['organizers'], ',' ) !== false ) {
						$ect_args['organizer'] = explode( ',', $attribute['organizers'] );
			} else {
				$ect_args['organizer'] = $attribute['organizers'];
			}
		}
		// var_dump($attribute['category']);
		// if($attribute['category']!="all") {
		// if ( $attribute['category'] ) {
		// if ( strpos( $attribute['category'], "," ) !== false ) {
		// $attribute['category'] = explode( ",", $attribute['category'] );
		// $attribute['category'] = array_map( 'trim',$attribute['category'] );
		// }
		// else {
		// $attribute['category'] = $attribute['category'];
		// }
		// $ect_args['tax_query'] = array(
		// array(
		// 'taxonomy' => 'tribe_events_cat',
		// 'field' => 'slug',
		// 'terms' =>$attribute['category'],
		// ));
		// }
		// }
		if ( ! empty( $attribute['category'] ) ) {
			$category_array = explode( ',', $attribute['category'] );

			if ( ! in_array( 'all', $category_array ) ) {
				$ect_args['tax_query'] = array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'tribe_events_cat',
						'field'    => 'slug',
						'terms'    => $category_array,
					),
				);
			}
		}
		if ( ! empty( $attribute['start_date'] ) ) {
			$ect_args['start_date'] = $attribute['start_date'];
		}
		if ( ! empty( $attribute['end_date'] ) ) {
			$ect_args['end_date'] = $attribute['end_date'];
		}
		$grid_style       = $attribute['style'];
		$ect_grid_columns = $attribute['columns'];
		$list_style       = $attribute['style'];
		/*
		end main query
		*/
		 /*
			Fetch Events data
		*/
		$excludePosts = array();
		if ( $template == 'accordion-view' && $style == 'style-4' ) {
			$ect_args['posts_per_page'] = -1;
		}
		$all_events                 = tribe_get_events( $ect_args );
		$ect_args['posts_per_page'] = -1;
		$total_events               = count( tribe_get_events( $ect_args ) );
		$ect_args['posts_per_page'] = $attribute['limit'];
		$i                          = 0;
		$display_year               = '';
		$last_year                  = '';
		if ( $all_events ) {
			$events_more_info_btn  = ect_get_option( 'events_more_info' );
			$events_more_info_text = ! empty( $events_more_info_btn ) ? sanitize_text_field( $events_more_info_btn ) : esc_html__( 'Find out more', 'ect' );
			// var_dump($events_more_info_text);
			foreach ( $all_events as $sr_no => $post ) :
				setup_postdata( $post );
				$event_description  = '';
				$event_cost         = '';
				$event_title        = '';
				$event_schedule     = '';
				$event_venue        = '';
				$event_img          = '';
				$event_content      = '';
				$events_date_header = '';
				$no_events          = '';
				$event_day          = '';
				$event_address      = '';
				$event_id           = $post->ID;
				$excludePosts[]     = $event_id;
				$tittle             = $post->post_title;
				// $tittle_link = $post->guid;
				$show_headers = apply_filters( 'tribe_events_list_show_date_headers', true );
				if ( $show_headers ) {
					$event_year        = tribe_get_start_date( $event_id, false, 'Y' );
					$event_month       = tribe_get_start_date( $event_id, false, 'm' );
					$month_year_format = tribe_get_date_option( 'monthAndYearFormat', 'M Y' );
					if ( $prev_event_month != $event_month || ( $prev_event_month == $event_month && $prev_event_year != $event_year ) ) {
						$prev_event_month    = esc_attr( $event_month );
						$prev_event_year     = esc_attr( $event_year );
						$date_header         = sprintf( "<span class='month-year-box'><span>%s</span></span>", esc_attr( tribe_get_start_date( $post, false, 'M Y' ) ) );
						$events_date_header .= '<!-- Month / Year Headers -->';
						$events_date_header .= $date_header;
					}
				}
				$post_parent = '';
				if ( $post->post_parent ) {
					$post_parent = ' data-parent-post-id="' . absint( $post->post_parent ) . '"';
				}
				$event_type = tribe( 'tec.featured_events' )->is_featured( $post->ID ) ? 'ect-featured-event' : 'ect-simple-event';
				// Venue
				$venue_details       = tribe_get_venue_details( $event_id );
				$has_venue_address   = ( ! empty( $venue_details['address'] ) ) ? 'location' : '';
				$venue_details_html  = '';
				$venue_details_html1 = '';
				/*** Get Event Categories Colors */
				$cat_bgcolor = $cat_txtcolor = $cat_bg_styles = $cat_txt_styles = $cat_colors_attr = '';
				$event_cats  = get_the_terms( $event_id, 'tribe_events_cat' );
				if ( ! empty( $event_cats ) && $event_type != 'ect-featured-event' ) {
					foreach ( $event_cats as $category ) {
						if ( ! empty( get_term_meta( $category->term_taxonomy_id, '_event_bgColor', true ) ) ) {
							$cat_bgcolor     = get_term_meta( $category->term_taxonomy_id, '_event_bgColor', true );
							$cat_txtcolor    = get_term_meta( $category->term_taxonomy_id, '_event_textColor', true );
							$cat_colors_attr = 'data-cat-bgcolor="' . $cat_bgcolor . '" data-cat-txtcolor="' . $cat_txtcolor . '"';
							$cat_bg_styles   = 'style="background:#' . $cat_bgcolor . ';color:#' . $cat_txtcolor . ';box-shadow:none;"';
							$cat_txt_styles  = 'style="color:#' . $cat_bgcolor . ';box-shadow:none;"';
						}
					}
				}
				// Setup an array of venue details for use later in the template
				if ( $attribute['hide-venue'] != 'yes' && tribe_has_venue() ) {

					// if($template=="modern-list" || ($template=="default" && $style=="style-2" || $template=="default" && $style=="style-4")){
					// $venue_details_html.='<div class="modern-list-venue">';
					// }
					// else if($template=="classic-list" || ($template=="default" && $style!="style-2")) {
					// $venue_details_html.='<div class="ect-list-venue '.$template.'-venue">';
					// }
					// else {
					// $venue_details_html.='<div class="'.$template.'-venue">';
					// }

					if ( $template == 'default' && $style == 'style-4' ) {
						$venue_details_html .= '<div class="modern-list-venue">';
					} elseif ( $template == 'classic-list' || $template == 'modern-list' || ( $template == 'default' && $style != 'style-4' ) ) {
						$venue_details_html .= '<div class="ect-list-venue ' . $template . '-venue">';
					} else {
						$venue_details_html .= '<div class="' . $template . '-venue">';
					}
					if ( tribe_has_venue() ) :

						if ( $template === 'minimal-list' ) {
							$venue_details_html1 .= '<div class="' . $template . '-venue">';
							if ( isset( $venue_details['linked_name'] ) ) {
								$venue_details_html1 .= '<span class="ect-icon"><i class="ect-icon-location" aria-hidden="true"></i></span>';
								$venue_details_html1 .= '<span class="ect-venue-name">
                                ' . $venue_details['linked_name'] . '</span>
                                ';
								if ( tribe_get_map_link() ) {
									$venue_details_html1 .= '<span class="ect-google">' . tribe_get_map_link_html() . '</span>';
								}
							}
							$venue_details_html1 .= '</div>';
						} else {

							if ( ! empty( $venue_details['address'] ) && isset( $venue_details['linked_name'] ) ) {
								$venue_details_html .= '<span class="ect-icon"><i class="ect-icon-location" aria-hidden="true"></i></span>';
							}
							$venue_details_html .= '<!-- Venue Display Info -->
                        <span class="ect-venue-details ect-address" itemprop="location" itemscope itemtype="http://schema.org/Place">
                        <meta itemprop="name" content="' . tribe_get_venue( $event_id ) . '">
                        <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
                        <meta itemprop="name" content="' . tribe_get_venue( $event_id ) . '">';
							$venue_details_html .= implode( ',', $venue_details );
							$venue_details_html .= '</div>';
							if ( tribe_get_map_link() ) {
								$venue_details_html .= '<span class="ect-google">' . tribe_get_map_link_html() . '</span>';
							}
							$venue_details_html .= '</span>';

						}
					endif;
					$venue_details_html .= '</div>';
				}
				if ( tribe_get_cost( $event_id ) ) :
					$ev_cost = '<div class="ect-rate-area" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <span class="ect-rate-icon"><i class="ect-icon-ticket" aria-hidden="true"></i></span>
                <span class="ect-rate" itemprop="price" content="' . tribe_get_cost( $event_id, false ) . '">' . tribe_get_cost( $event_id, true ) . '</span>
                <meta itemprop="priceCurrency" content="' . tribe_get_event_meta( $event_id, '_EventCurrencySymbol', true ) . '" />';
					if ( class_exists( 'Tribe__Tickets__Main' ) ) {
						$ev_cost .= '<span class="ect-ticket-info">';
						$ev_cost .= ect_tribe_tickets_buy_button( $event_id, false );
						$ev_cost .= '</span>';
					}
					$ev_cost .= '</div>';
			endif;
				$event_schedule = ect_event_schedule( $event_id, $date_format, $template );
				$ev_time        = ect_tribe_event_time( $event_id, false );
				$evt_type       = 'single-day';
				if ( tribe_event_is_multiday( $event_id ) ) {
					$evt_type = 'multiday';
				} elseif ( tribe_event_is_all_day( $event_id ) ) {
					$evt_type = 'all-day';
				}
				// Organizer
				$organizer = tribe_get_organizer();
				if ( tribe_get_cost() ) :
					$event_cost = '<!-- Event Cost -->
                <div class="ect-event-cost">
                <span>' . tribe_get_cost( null, true ) . '</span>
                </div>';
			endif;
				if ( $template == 'classic-list' || $template == 'default' && $style == 'style-3' ) {
					$event_title = '<a class="ect-event-url" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" rel="bookmark">' . get_the_title( $event_id ) . '</a>';
				} elseif ( $template == 'accordion-view' ) {
					$event_title = '<h3 class="ect-accordion-title">' . get_the_title( $event_id ) . '</h3>';
				} else {
					$event_title = '<a class="ect-event-url" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" rel="bookmark">' . get_the_title( $event_id ) . '</a>';
				}
				/**
				 * Event Description without Find out More
				 */
				if ( tribe_events_get_the_excerpt( $event_id ) ) {
					// $event_description  = '<!-- Event Description --><div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
					// $event_description .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
					// $event_description .= '</div>';
					$event_content  = '<!-- Event Content --><div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
					$event_content .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
					$event_content .= '</div>';
				}
				// $event_content  = '<!-- Event Content --><div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
				// $event_content .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
					// if ( $template === 'default' ) {
					// if ( tribe_get_cost( $event_id ) ) {
					// $event_content .= '<div class="ect-list-cost">' . wp_kses_post( $ev_cost ) . '</div>';
					// }
					// $event_content .= '<a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" class="ect-events-read-more" rel="bookmark">' . $events_more_info_text . '</a>';
					// }

				// event day
				$event_day = '<span class="event-day">' . tribe_get_start_date( $event_id, true, 'l' ) . '</span>';
				// Address
				$venue_details = tribe_get_venue_details( $event_id );
				$event_address = ( ! empty( $venue_details['address'] ) ) ? $venue_details['address'] : '';
				// load layouts based upon template type
				if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ) ) ) {
					require ECT_PRO_PLUGIN_DIR . '/templates/timeline/timeline.php';
				} elseif ( in_array( $template, array( 'default', 'classic-list', 'modern-list' ) ) ) {
					require ECT_PRO_PLUGIN_DIR . '/templates/list/list.php';
				} elseif ( in_array( $template, array( 'slider-view' ) ) ) {
					require ECT_PRO_PLUGIN_DIR . '/templates/slider/slider.php';
				} elseif ( $template == 'grid-view' ) {
					$ect_grid_columns = $attribute['columns'];
					$grid_style       = $attribute['style'];
					$hide_venue       = $attribute['hide-venue'];
					require ECT_PRO_PLUGIN_DIR . '/templates/grid/grid.php';
				} elseif ( $template == 'cover-view' ) {
					require ECT_PRO_PLUGIN_DIR . '/templates/cover/cover.php';
				} elseif ( in_array( $template, array( 'carousel-view' ) ) ) {
					require ECT_PRO_PLUGIN_DIR . '/templates/carousel/carousel.php';
				} elseif ( in_array( $template, array( 'masonry-view' ) ) ) {
					$grid_style       = $attribute['style'];
					$ect_grid_columns = $attribute['columns'];
					require ECT_PRO_PLUGIN_DIR . '/templates/masonry/masonry.php';
				} elseif ( $template == 'accordion-view' ) {
					$ect_compare = '';
					require ECT_PRO_PLUGIN_DIR . '/templates/accordion/accordion.php';
				} elseif ( $template == 'minimal-list' ) {
					include ECT_PRO_PLUGIN_DIR . '/templates/minimal-list/minimal-list.php';
				} elseif ( $template == 'advance-list' ) {
					$category_array[0] = empty( $category_array ) ? 'all' : 'all';
					$showimage         = 'no';
					$advance_list_id   = rand( 1, 10000 );/** Unique Id */
					$date_order        = strtotime( tribe_get_start_date( $event_id, true, 'M d Y h:i:s A' ) );
					$event_type        = tribe( 'tec.featured_events' )->is_featured( $event_id ) ? 'ect-featured-event' : 'ect-simple-event';
					include ECT_PRO_PLUGIN_DIR . '/templates/advance-list/advance-list.php';
				}
				$ev_cost = '';
			endforeach;
			wp_reset_postdata();
		} else {
			$no_event_found_text = ect_get_option( 'events_not_found' );
			if ( ! empty( $no_event_found_text ) ) {
				$no_events = '<div class="ect-no-events"><p>' . filter_var( $no_event_found_text, FILTER_SANITIZE_STRING ) . '</p></div>';
			} else {
				$no_events = '<div class="ect-no-events"><p>' . __( 'There are no upcoming events at this time.', 'ect' ) . '</p></div>';
			}
		}
		$catCls = is_array( $attribute['category'] ) ? implode( ' ', $attribute['category'] ) : $attribute['category'];
		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ) ) ) {
			if ( $template == 'timeline' ) {
				$style = 'style-1';
			} elseif ( $template == 'classic-timeline' ) {
				$style = 'style-2';
			}
			/*** Gerneral options */
			// create wrapper elements for all layouts
			$wrp_cls     = '';
			$layout_cls  = '';
			$layout_wrp  = 'both-sided-wrapper';
			$wrp_cls     = 'default-layout';
			$wrapper_cls = 'white-timeline-wrapper';
			$output     .= '<!=========Events Timeline Template ' . ECT_PRO_VERSION . '=========>';
			$output     .= '<div id="event-timeline-wrapper" class="' . $catCls . ' ' . $style . '">';
			$output     .= '<div class="cool-event-timeline">';
			$output     .= $events_html;
			$output     .= '</div></div>';
		} elseif ( in_array( $template, array( 'slider-view' ) ) ) {
			$output .= '<!=========Slider View Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-slider-wrapper" class="' . $ect_slider_templates_id . ' ' . $catCls . '">';
			$output .= '<div class="ect-slider-outr ect-events-slider"> 
            <section id="' . $slider_pp_id . '" class="ect-slider-view ect-events-slider" data-sizes="50vw">';
			$output .= $events_html;
			$output .= '</section></div></div>';
		} elseif ( $template == 'cover-view' ) {
			$output .= '<!=========Cover View Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-cover-wrapper" class="' . $ect_cover_template_id . ' ' . $catCls . '">';
			$output .= '<div class="ect-cover-outr ect-events-cover"> 
            <section id="' . $slider_pp_id . '" class="ect-cover-view ect-events-cover" data-sizes="50vw">';
			$output .= $events_html;
			$output .= '</section></div></div>';
		} elseif ( $template == 'grid-view' ) {
			$output .= '<!=========Grid View Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-grid-wrapper" class="tect-grid-wrapper ' . $ect_grid_id . ' ' . $catCls . '">';
			$output .= '<div class="row">';
			$output .= $events_html;
			$output .= '</div>';
			if ( $all_events && $total_events > $attribute['limit'] ) {
				$settings = array(
					'hide_venue'       => $hide_venue,
					'date_format'      => $date_format,
					'socialshare'      => $socialshare,
					'template'         => $template,
					'style'            => $style,
					'show_description' => $show_description,
					'ect_grid_columns' => $attribute['columns'],
				);
				$output  .= '<div class="ect-load-more ' . $style . '">
                <a href="#" class="ect-load-more-btn">
                <img class="ect-preloader" style="display:none;" src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text">' . __( 'Load More', 'ect' ) . '</span></a>
                <section data-exclude-events="' . json_encode( $excludePosts ) . '"  id="ect-lm-settings" data-load-more="' . __( 'Load more', 'ect' ) . '"  data-loaded="' . __( 'No Event Found', 'ect' ) . '" 
                data-loading="' . __( 'Loading', 'ect' ) . '" data-settings=' . json_encode( $settings ) . ' data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-load-nonce="' . $load_nonce . '">
                </section>
                <script type="application/json" id="ect-query-arg">' . json_encode( $ect_args ) . '</script>
                </div>';
			}
			$output .= '</div>';
		} elseif ( in_array( $template, array( 'carousel-view' ) ) ) {
			$output .= '<!=========Carousel View Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-carousel-wrapper" class="' . $ect_carousel_id . ' ' . $catCls . '">';
			$output .= '<div class="ect-carousel-outer ect-events-carousel"><section id="' . $slider_pp_id . '" class="ect-carousel ect-events-carousel" data-sizes="50vw">';
			$output .= $events_html;
			$output .= '</section></div></div>';
		} elseif ( $template == 'minimal-list' ) {
			$output .= '<!=========Events Static list Template ' . ECT_PRO_VERSION . '=========>';
			if ( $all_events && class_exists( 'Tribe__Events__JSON_LD__Event' ) ) {
				$output .= Tribe__Events__JSON_LD__Event::instance()->get_markup( $all_events );
			}
			$output .= '<div id="ect-events-minimal-list-' . rand( 1, 10000 ) . '" class="ectt-simple-list-wrapper">';
			$output .= '<div id="ect-minimal-list-wrp" class="ect-minimal-list-wrapper ' . $catCls . '">';
			$output .= $events_html;
			$output .= '</div>';
			if ( $all_events && $total_events > $attribute['limit'] ) {
				$settings = array(
					'hide_venue'       => $hide_venue,
					'date_format'      => $date_format,
					'socialshare'      => $socialshare,
					'template'         => $template,
					'style'            => $style,
					'show_description' => $show_description,

				);
				$output .= '<div class="ect-load-more ' . $style . '">
                <a href="#" class="ect-load-more-btn">
                <img class="ect-preloader" style="display:none;" src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text">' . __( 'Load More', 'ect' ) . '</span></a>
                <section data-exclude-events="' . json_encode( $excludePosts ) . '"  id="ect-lm-settings" data-load-more="' . __( 'Load more', 'ect' ) . '"  data-loaded="' . __( 'No Event Found', 'ect' ) . '" 
                data-loading="' . __( 'Loading', 'ect' ) . '" data-settings=' . json_encode( $settings ) . ' data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-load-nonce="' . $load_nonce . '">
                <div id="ect-cat-load-more" style="display:none;"><img class="ect-preloader"  src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text"></span></div>
                </section>
                <script type="application/json" id="ect-query-arg">' . json_encode( $ect_args ) . '</script></div>';
			}
			$output .= '</div>';
		} elseif ( $template == 'masonry-view' ) {
			$template      = 'masonary';
			$settings      = array(
				'hide_venue'       => $hide_venue,
				'grid_style'       => $grid_style,
				'ect_grid_columns' => $ect_grid_columns,
				'date_format'      => $date_format,
				'socialshare'      => $socialshare,
				'show_description' => $show_description,
			);
			$post_per_page = $attribute['limit'];
			$totalPosts    = 0;
			$pages         = 0;
			/*
				Category filters for masonry layout
			*/
			$output .= '<div class="ect-masonry-template-cont">';
			$output .= '<!=========masonry View Template ' . ECT_PRO_VERSION . '=========>';
			if ( $all_events ) {
				$output .= create_cat_filter_html( $selected_cat, $post_per_page );
				$output .= '<div  id="ect-grid-wrapper" class="ect-masonary-cont ' . $ect_masonry_cls . '">';
				$output .= $events_html;
				$output .= '</div>';
				unset( $ect_args['tax_query'] );
				$output .= '<div class="ect-masonay-load-more">';
				if ( $total_events > $attribute['limit'] ) {
					$output .= '<a href="#" class="ect-load-more-btn">
                <img class="ect-preloader" style="display:none;" src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text">' . __( 'Load More', 'ect' ) . '</span></a>';
				}
				$output .= '<section data-exclude-events="' . json_encode( $excludePosts ) . '"  id="ect-lm-settings" data-load-more="' . __( 'Load more', 'ect' ) . '"  data-loaded="' . __( 'No Event Found', 'ect' ) . '" 
            data-loading="' . __( 'Loading', 'ect' ) . '" data-settings=' . json_encode( $settings ) . ' data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-load-nonce="' . $load_nonce . '">
            </section>
            <script type="application/json" id="ect-query-arg">' . json_encode( $ect_args ) . '</script></div>';
			}
			$output .= '</div>';
		} elseif ( $template == 'accordion-view' ) {
			$output .= '<!=========Accordion View Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-accordion-wrapper" class="ect-accordion-view ' . $style . ' ect-cat-' . $catCls . '">';
			$output .= '<div class="ect-accordion-container">';
			if ( $style == 'style-4' ) {
				$arrows  = '';
				$arrows .= '<div class="ect-accordion-arrows ect-accordion-' . $slider_pp_id . '">
                <div class="ect-accordn-slick-prev ect-accordn-slick-prev-' . $slider_pp_id . '"><i class="ect-icon-left"></i></div>
                <div class="ect-accordn-slick-next ect-accordn-slick-next-' . $slider_pp_id . '"><i class="ect-icon-right"></i></div>
                </div>';
				$output .= $arrows;
				$output .= '<section id="' . $slider_pp_id . '" class="ect-accordion-view ect-events-accordion">';
				$output .= $events_html;
				if ( end( $all_events ) ) {
					$output .= '</div><!--close end element div!-->';
				}
				$output .= '</section>';
			} else {
				$output .= $events_html;
			}
			$output .= '</div>';
			if ( $style != 'style-4' ) {
				if ( $all_events && $total_events > $attribute['limit'] ) {
					$settings = array(
						'hide_venue'       => $hide_venue,
						'date_format'      => $date_format,
						'socialshare'      => $socialshare,
						'template'         => $template,
						'style'            => $style,
						'show_description' => $show_description,
					);
					$output  .= '<div class="ect-load-more ' . $style . '">
                <a href="#" class="ect-load-more-btn">
                <img class="ect-preloader" style="display:none;" src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text">' . __( 'Load More', 'ect' ) . '</span></a>
                <section data-exclude-events="' . json_encode( $excludePosts ) . '"  id="ect-lm-settings" data-load-more="' . __( 'Load more', 'ect' ) . '"  data-loaded="' . __( 'No Event Found', 'ect' ) . '" 
                data-loading="' . __( 'Loading', 'ect' ) . '" data-settings=' . json_encode( $settings ) . ' data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-load-nonce="' . $load_nonce . '">
                <div id="ect-cat-load-more" style="display:none;"><img class="ect-preloader"  src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text"></span></div>
                </section>
                <script type="application/json" id="ect-query-arg">' . json_encode( $ect_args ) . '</script></div>';
				}
			}
			$output .= '</div>';
		} elseif ( $template == 'advance-list' ) {
			$advance_list_id    = rand( 1, 10000 );
			$showimage          = 'no';
			$ect_cate_sett      = ect_get_option( 'ect_display_categoery' );
			$category_hide_seek = '';
			if ( $ect_cate_sett == 'ect_disable_cat' ) {
				$category_hide_seek = 'ect-cattag-hide';
			}
			$output .= '<!=========Advance List Template ' . ECT_PRO_VERSION . '=========>';
			if ( ! empty( $events_html ) ) {
				$output .= '<table data-id="' . esc_attr( $advance_list_id ) . '" id="ect-table-List' . esc_attr( $advance_list_id ) . '" class="ect-advance-list display">';
				/** Table Content*/
				$output .= '<div class="ect-static-value">
                  <span class="ect-adl-nxt' . esc_attr( $advance_list_id ) . '">' . __( 'NEXT', 'ect' ) . '</span>
                  <span class="ect-adl-prev' . esc_attr( $advance_list_id ) . '">' . __( 'PREV', 'ect' ) . '</span>
                  <span class="ect-adl-text' . esc_attr( $advance_list_id ) . '">' . __( 'Events', 'ect' ) . '</span>
                  <span class="ect-adl-intottal' . esc_attr( $advance_list_id ) . '">' . __( 'in Total', 'ect' ) . '</span>
                  <span class="ect-adl-search' . esc_attr( $advance_list_id ) . '">' . __( 'Search...', 'ect' ) . '</span>
                  <span class="ect-adl-uid" ></span>
                </div>';
				$output .= '<div class="ect-category-filter" id="ect-category-filter' . $advance_list_id . '" >';
				/** Category Select Box*/
				$output .= '<select id="ect-cat-filter' . $advance_list_id . '" class="ect-cat-filter">
            <option value="" hidden>' . __( 'Select By Category', 'ect' ) . '</option>';
				$output .= ect_category_select_box_list( $selected_cat );
				/*** Tags Select Box*/
				$output .= '</select>';
				$output .= ' <select id="ect-tagFilter' . $advance_list_id . '" class="ect-tagFilter">
              <option value="" hidden>' . __( 'Select By Tag', 'ect' ) . '</option>';
				$output .= ect_tags_select_box_list( $attribute['tags'] );
				$output .= '</select>';
				$output .= '<div id="ect-refresh' . esc_attr( $advance_list_id ) . '" class="ect-advance-list-refresh"><span class=reload>&#x21bb;</span></div></div>';
				$output .= '<thead  class ="ect-advance-list-head" data-postperpage="' . esc_attr( $attribute['limit'] ) . '" ><tr>';
				$output .= '<th class="ect-adv-ev" >' . esc_html( $evTittle ) . '</th>';
				$output .= '<th  class="ect-advance-list-mobi-serial ect-adv-date" >' . esc_html( $dateTittle ) . '</th>';
				$output .= '<th  class="ect-adv-time">' . esc_html( $timerangeTittle ) . '</th>';
				if ( $showimage == 'yes' ) {
					$output .= '<th  class="ect-img ect-adv-img">' . __( 'Image', 'ect' ) . '</th>';
				}
				if ( $show_description == 'yes' ) {
					$output .= '<th class="ect-adv-desc">' . esc_html( $eventDescTittle ) . '</th>';
				}
				// if($ect_cate_sett=='ect_enable_cat'){
				$output .= '<th class="' . $category_hide_seek . '" data-catfilter="Category">' . esc_html( $categoryTittle ) . '</th>';
				// }
				$output .= '<th class="ect-cattag-hide tag-column">' . __( 'Tags', 'ect' ) . '</th>';
				if ( $hide_venue != 'yes' ) {
					$output .= '<th class="ect-adv-venue">' . esc_html( $eventVenueTittle ) . '</th>';
				}
				$output .= '<th class="ect-view-more ect-adv-vm"></th>';
				$output .= '</tr></thead><tbody>';
				$output .= $events_html;
				$output .= '</tbody></table>';
			}
		} else {
			$output .= '<!=========list Template ' . ECT_PRO_VERSION . '=========>';
			$output .= '<div id="ect-events-list-content" class="ectt-list-wrapper">';
			$output .= '<div id="list-wrp" class="ect-list-wrapper ' . $catCls . '">';
			$output .= $events_html;
			$output .= '</div>';

			if ( $all_events && $total_events > $attribute['limit'] ) {
				$settings = array(
					'hide_venue'       => $hide_venue,
					'date_format'      => $date_format,
					'socialshare'      => $socialshare,
					'template'         => $template,
					'style'            => $style,
					'show_description' => $show_description,

				);
				$output .= '<div class="ect-load-more ' . $style . '">
                <a href="#" class="ect-load-more-btn">
                <img class="ect-preloader" style="display:none;" src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text">' . __( 'Load More', 'ect' ) . '</span></a>
                <section data-exclude-events="' . json_encode( $excludePosts ) . '"  id="ect-lm-settings" data-load-more="' . __( 'Load more', 'ect' ) . '"  data-loaded="' . __( 'No Event Found', 'ect' ) . '" 
                data-loading="' . __( 'Loading', 'ect' ) . '" data-settings=' . json_encode( $settings ) . ' data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-load-nonce="' . $load_nonce . '">
                <div id="ect-cat-load-more" style="display:none;"><img class="ect-preloader"  src="' . ECT_PRO_PLUGIN_URL . 'assets/images/preloader.svg"> <span class="ect-btn-text"></span></div>
                </section>
                <script type="application/json" id="ect-query-arg">' . json_encode( $ect_args ) . '</script></div>';
			}
				$output .= '</div>';
		}

		return $output . $no_events;
	}
	public function ect_common_loadmore_handler() {
		$nounce_val = $_POST['load_ajax_nonce'];

		if ( ! check_ajax_referer( 'load-more-nonce', 'load_ajax_nonce', false ) ) {
			wp_send_json_error( 'Invalid security token.' );
		}

			$ect_args         = $_POST['query'];
			$settings         = $_POST['settings'];
			$date_format      = $settings['date_format'];
			$hide_venue       = $settings['hide_venue'];
			$styles           = $settings['style'];
			$style            = $settings['style'];
			$template         = $settings['template'];
			$last_year        = $_POST['last_year_val'];
			$response         = array();
			$events_html      = '';
			$output           = '';
			$no_events        = '';
			$response_type    = 'ajax';
			$socialshare      = $settings['socialshare'];
			$show_description = $settings['show_description'];
			// $ect_args['paged']=(int)$_POST['paged'];
			$ect_args['post__not_in'] = json_decode( $_POST['exclude_events'] );
			$excludePosts             = array();
			require ECT_PRO_PLUGIN_DIR . '/includes/ect-load-more-handler.php';
			echo json_encode( $response );
			wp_die();

	}
	/*
	Masonry Layout load more handlers
	*/
	function ect_catfilters_load_more() {
		// $nounce_val=$_POST['masonry_ajax_nonce'];

		if ( ! check_ajax_referer( 'load-more-nonce', 'masonry_ajax_nonce', false ) ) {
			wp_send_json_error( 'Invalid security token.' );
		}
		$template                 = 'masonry-view';
		$grid_style               = '';
		$date_format              = '';
		$ect_args                 = $_POST['query'];
		$settings                 = $_POST['settings'];
		$ect_grid_columns         = $settings['ect_grid_columns'];
		$grid_style               = $settings['grid_style'];
		$date_format              = $settings['date_format'];
		$hide_venue               = $settings['hide_venue'];
		$ect_args['paged']        = (int) $_POST['paged'];
		$ect_args['post__not_in'] = json_decode( $_POST['exclude_events'] );
		$excludePosts             = array();
		$cat                      = $_POST['cat'];
		$response                 = array();
		$events_html              = '';
		$output                   = '';
		$no_events                = '';
		$response_type            = 'ajax';
		$socialshare              = $settings['socialshare'];
		$show_description         = $settings['show_description'];
		if ( $cat == '' ) {
			unset( $ect_args['tax_query'] );
		} else {
			$ect_args['tax_query'] = array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'name',
					'terms'    => $cat,
				),
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'slug',
					'terms'    => $cat,
				),
			);
		}
		// unset($ect_args['featured']);
		// unset($ect_args['posts_per_page']);
		include ECT_PRO_PLUGIN_DIR . '/includes/ect-masonry-loop.php';
		echo json_encode( $response );
		wp_die();
	}
	// Week View Next Prev Button
	public function ect_weekly_button() {
		$prev_category        = '';
		$start_prev_week_date = $_POST['weekly_date'];
		$prev_category        = $start_prev_week_date['Category'];
		$prev_category        = $prev_category == 'all' ? null : $prev_category;
		$tags                 = $start_prev_week_date['tags'];
		$venue                = $start_prev_week_date['venue'];
		$limit                = $start_prev_week_date['limit'];
		$organizers           = $start_prev_week_date['organizers'];
		$featured_only        = $start_prev_week_date['featured'];
		$event_start_date     = $start_prev_week_date['event_start_date'];
		if ( $featured_only == 'all' ) {
			 $featured_only = '';
		} elseif ( $featured_only == 'true' ) {
			$featured_only = true;
		} elseif ( $featured_only == 'false' ) {
			$featured_only = false;
		}
		if ( $start_prev_week_date['click'] == 'next' ) {
			 $mondays = $start_prev_week_date['next_week_day'];
		} else {
			 $mondays = $start_prev_week_date['start_prev_week'];
		}
		$month_name      = date( 'M', strtotime( $mondays ) );
		$year            = date( 'Y', strtotime( $mondays ) );
		$j               = 6;
		$year_next       = date( 'Y', strtotime( $mondays . '+' . $j . 'day' ) );
		$selected_friday = date( 'd', strtotime( $mondays . '+' . $j . 'day' ) );
		$get_month       = date( 'M', strtotime( $mondays . '+' . $j . 'day' ) );
		$get_day         = date( 'd', strtotime( $mondays ) );
		$display_monday  = $get_day . ' ' . $month_name . ' ' . $year;
		$Start_date      = $year . '-' . $month_name . '-' . $get_day;
		$friday          = $selected_friday . ' ' . $month_name . ' ' . $year;
		$last_day        = $selected_friday . ' ' . $get_month . ' ' . $year_next;
		$end_date        = $year_next . '-' . $get_month . '-' . $selected_friday;
		$current_week    = date( 'd', strtotime( 'monday this week+1day' ) );
		$dateTime        = new DateTime( $mondays );
		$dateTime->modify( '-7 day' );
		$friday_for_events = date( 'Y-m-d', strtotime( $mondays . '+' . $j . 'day' ) );
		// Print out the date in a YYYY-MM-DD format.
		$start_prev_date = $dateTime->format( 'Y-m-d' );
		/**
		 * prev week
		*/
		$dateTime = new DateTime( $mondays );
		$dateTime->modify( '+7 day' );
		// Print out the date in a YYYY-MM-DD format.
		$start_next_dates = $dateTime->format( 'Y-m-d' );
		$settings         = array(
			'event_start_date' => $event_start_date,
			'start_prev_week'  => $start_prev_date,
			'next_week_day'    => $start_next_dates,
			'Category'         => $prev_category,
			'tags'             => $tags,
			'venue'            => $venue,
			'organizers'       => $organizers,
			'limit'            => $limit,
			'featured'         => $featured_only,
		);
		$all_events       = tribe_get_events(
			array(
				'start_date'     => $Start_date . '00:01',
				'end_date'       => $end_date . '23:59',
				'tag'            => $tags,
				'featured'       => $featured_only,
				'category'       => $prev_category,
				'venue'          => $venue,
				'organizer'      => $organizers,
				'posts_per_page' => $limit,
				'featured'       => $featured_only,
			)
		);
		$output           = '';
		$events_html      = '';
		$output          .= '<div class="ect-week-nav">
               <button class="ect-prev"><i class="ect-icon-left-double"></i></button>
               <h2 class="ect-week">' . $display_monday . ' ' . '-' . ' ' . $last_day . '</h2>
               <button class="ect-next"><i class="ect-icon-right-double"></i></button>
            </div>';
		if ( is_array( $all_events ) && count( $all_events ) > 0 ) {
			$output .= '<div class="ect-week-days-wrapper">';
			include_once ECT_PRO_PLUGIN_DIR . '/templates/week-view/weekly-view.php';
			$output .= $events_html;
			$output .= '</div>';
		} else {
			$no_event_found_text = ect_get_option( 'events_not_found' );
			if ( ! empty( $no_event_found_text ) ) {
				$output .= '<div class="ect-no-events"><p>' . filter_var( $no_event_found_text, FILTER_SANITIZE_STRING ) . '</p></div>';
			} else {
				 $output .= '<div class="ect-no-events"><p>' . __( 'There are no upcoming events at this time.', 'ect' ) . '</p></div>';
			}
			 $output .= '</div>';
		}
		   $output .= '<script type="application/json" id="ect-query-arg">' . json_encode( $settings ) . '</script>';
		   $output .= '<div class="ect_calendar_events_spinner"><div class="ect_spinner_img"><img src="' . ECT_PRO_PLUGIN_URL . 'assets/images/ect-preloader.gif"><br/>Loading events...</div></div>';
		   $output .= '</div>';
		   echo $output;
		   die();
	}
}
