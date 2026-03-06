<?php
/*
This File Is Used To Fetch Events data After Click Load More Button
*/
$prev_event_month = '';
$ev_cost          = '';
$display_year     = '';
$all_events       = tribe_get_events( $ect_args );
$i                = 0;
if ( $all_events ) {
	$events_more_info_btn  = ect_get_option( 'events_more_info' );
	$events_more_info_text = ! empty( $events_more_info_btn ) ? sanitize_text_field( $events_more_info_btn ) : esc_html__( 'Find out more', 'ect' );
	foreach ( $all_events as $post ) :
		setup_postdata( $post );
		$event_cost         = '';
		$event_title        = '';
		$event_schedule     = '';
		$event_venue        = '';
		$event_img          = '';
		$event_content      = '';
		$events_date_header = '';
		$event_day          = '';
		$event_address      = '';
		$event_id           = $post->ID;
		$excludePosts[]     = $event_id;
		$events_html        = '';
		$show_headers       = apply_filters( 'tribe_events_list_show_date_headers', true );
		if ( $show_headers ) {
			$event_year        = tribe_get_start_date( $event_id, false, 'Y' );
			$event_month       = tribe_get_start_date( $event_id, false, 'm' );
			$month_year_format = tribe_get_date_option( 'monthAndYearFormat', 'F Y' );
			if ( $prev_event_month != $event_month || ( $prev_event_month == $event_month && $prev_event_year != $event_year ) ) {
				$prev_event_month    = $event_month;
				$prev_event_year     = $event_year;
				$date_header         = sprintf( "<span class='tribe-events-list-separator-month'><span>%s</span></span>", esc_attr( tribe_get_start_date( $post, false, $month_year_format ) ) );
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
		$venue_details      = tribe_get_venue_details( $event_id );
		$has_venue_address  = ( ! empty( $venue_details['address'] ) ) ? 'location' : '';
		$venue_details_html = '';
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
		if ( $settings['hide_venue'] !== 'yes' && tribe_has_venue( $event_id ) ) {
			if ( $template == 'default' && $style == 'style-4' ) {
				$venue_details_html .= '<div class="modern-list-venue">';
			} elseif ( $template == 'classic-list' || $template == 'modern-list' || ( $template == 'default' && $style != 'style-4' ) ) {
				$venue_details_html .= '<div class="ect-list-venue ' . $template . '-venue">';
			} else {
				$venue_details_html .= '<div class="' . $template . '-venue">';
			}
			if ( tribe_has_venue( $event_id ) ) :
				if ( $settings['template'] == 'minimal-list' ) {
					$venue_details_html1  = '';
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
					$venue_details_html     .= '<!-- Venue Display Info -->
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
		// Organizer
		$organizer = tribe_get_organizer();
		if ( tribe_get_cost() ) :
			$event_cost = '<!-- Event Cost -->
			<div class="ect-event-cost">
				<span>' . tribe_get_cost( null, true ) . '</span>
			</div>';
		endif;
		if ( $template == 'classic-list' || $template == 'default' && $style == 'style-3' ) {
			$event_title = '<a itemprop="name" class="ect-event-url" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" rel="bookmark">' . get_the_title( $event_id ) . '</a>';
		} elseif ( $template == 'accordion-view' ) {
			$event_title = '<h3 class="ect-accordion-title">' . get_the_title( $event_id ) . '</h3>';
		} else {
			$event_title = '<a itemprop="name" class="ect-event-url" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" rel="bookmark">' . get_the_title( $event_id ) . '</a>';
		}
		if ( tribe_events_get_the_excerpt( $event_id ) ) {
			// $event_description  = '<!-- Event Description --><div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
			// $event_description .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
			// $event_description .= '</div>';
			$event_content  = '<!-- Event Content --><div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
			$event_content .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
			$event_content .= '</div>';
		}
		// $event_content  = '<div class="ect-event-content" itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
		// $event_content .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
		// // if ( $template === 'default' ) {
		// // 	$event_content .= '<div class="ect-list-cost">' . wp_kses_post( $ev_cost ) . '</div>';
		// // }
		// // $event_content .= '<a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" class="ect-events-read-more" rel="bookmark">' . $events_more_info_text . ' &raquo;</a>';
		// $event_content .= '</div>';// event day
		$event_day = '<span class="event-day">' . tribe_get_start_date( $event_id, true, 'l' ) . '</span>';
		// Address
		$venue_details = tribe_get_venue_details( $event_id );
		$event_address = ( ! empty( $venue_details['address'] ) ) ? $venue_details['address'] : '';
		$hide_venue    = $settings['hide_venue'];
		if ( $settings['template'] == 'grid-view' ) {
			$grid_style       = $settings['style'];
			$ect_grid_columns = $settings['ect_grid_columns'];
			require ECT_PRO_PLUGIN_DIR . '/templates/grid/grid.php';
		} elseif ( $settings['template'] == 'accordion-view' ) {
			$grid_style = $settings['style'];
			require ECT_PRO_PLUGIN_DIR . '/templates/accordion/accordion.php';
		} elseif ( $settings['template'] == 'minimal-list' ) {
			$list_style = $settings['style'];
			require ECT_PRO_PLUGIN_DIR . '/templates/minimal-list/minimal-list.php';
		} else {
			require ECT_PRO_PLUGIN_DIR . '/templates/list/list.php';
		}
		if ( isset( $response_type ) && $response_type == 'ajax' ) {
			$response['content'][] = $events_html;
		}
		$ev_cost = '';
		endforeach;
		wp_reset_postdata();
	if ( isset( $response_type ) && $response_type == 'ajax' ) {
		$response['success']        = true;
		$response['events']         = 'yes';
		$response['exclude_events'] = json_encode( $excludePosts );
	}
} else {
	$no_event_found_text = ect_get_option( 'events_not_found' );
	if ( ! empty( $no_event_found_text ) ) {
		$no_events = '<div class="ect-no-events"><p>' . filter_var( $no_event_found_text, FILTER_SANITIZE_STRING ) . '</p></div>';
	} else {
		$no_events = '<div class="ect-no-events"><p>' . __( 'There are no upcoming events at this time.', 'ect' ) . '</p></div>';
	}
	if ( isset( $response_type ) && $response_type == 'ajax' ) {
		$response['success']        = true;
		$response['events']         = 'no';
		$response['content']        = $no_events;
		$response['exclude_events'] = 0;
	} else {
		$events_html = $no_events;
	}
}

