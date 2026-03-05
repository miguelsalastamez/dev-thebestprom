<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used to  generate timeline layout  html.
 */
$timeline_style = $attribute['style'];
if ( $template == 'timeline' ) {
	$timeline_style = 'style-1';
} elseif ( $template == 'classic-timeline' ) {
	$timeline_style = 'style-2';
}


$ev_post_img = '';
$size        = 'large';
$ev_post_img = ect_pro_get_event_image( $event_id, $size = 'large' );
$ect_cate    = ect_display_category( $event_id );
if ( $i % 2 == 0 ) {
	$even_odd = 'even';
} else {
	$even_odd = 'odd';
}

if ( $timeline_style == 'style-1' || $timeline_style == 'style-4' ) {
	if ( $events_date_header !== '' && $timeline_style == 'style-1' ) {
		$events_html .= '<div class="ect-timeline-year">
						<div class="year-placeholder">' . $events_date_header . '</div>
						</div>';
	}
	if ( $events_date_header !== '' && $timeline_style == 'style-4' ) {
		$events_html .= '<div class="ect-timeline-year-' . $timeline_style . '">
						<div class="year-placeholder">' . $events_date_header . '</div>
						</div>';
	}

	$events_html .= '<div id="event-' . esc_attr( $event_id ) . '"' . $cat_colors_attr . ' class="ect-timeline-post ' . $even_odd . ' ' . $event_type . ' ' . $timeline_style . '">';

	if ( $timeline_style == 'style-1' ) {
		$events_html .= '<div class="timeline-dots"></div>';
	}
	if ( $timeline_style == 'style-4' ) {
		$events_html .= '<div class="timeline-dots-' . $timeline_style . '"></div>';
	}
	$events_html .= '<div class="timeline-content ' . $even_odd . '">';
	$events_html .= '<div class="ect-timeline-header">';
	if ( $ev_post_img ) {
		$events_html .= '<a class="timeline-ev-img" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '"><img src= "' . $ev_post_img . '"/></a>';
	}

	if ( $socialshare == 'yes' ) {
		$events_html .= ect_pro_share_button( $event_id ); }

	$events_html .= '</div>';
	$events_html .= '<div class="ect-timeline-main-content">';
	$events_html .= '<div class="ect-timeline-date">';
	$events_html .= $event_schedule;
	$events_html .= '</div>';
	$events_html .= '<h2 class="content-title">' . wp_kses_post( $event_title ) . '</h2>';
	if ( ! empty( $ect_cate ) ) {
		$events_html .= '<div class="ect-event-category ect-timeline-categories">';
		$events_html .= wp_kses_post( $ect_cate );
		$events_html .= '</div>';
	}
	if ( $show_description == 'yes' || $show_description == '' ) {
		$events_html .= wp_kses_post( $event_content );
	}
	$events_html .= $venue_details_html;
	if ( tribe_get_cost( $event_id, true ) ) {
		$events_html .= $ev_cost;
	}
	$events_html .= '</div>';
	$events_html .= '      <div class="ect-lslist-event-detail">
        <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
        </div>';
	$events_html .= '</div>';
	$events_html .= '</div>';
	$i++;
} elseif ( $timeline_style == 'style-2' ) {
	if ( $events_date_header !== '' ) {
		$events_html .= '<div class="ect-timeline-year">
                        <div class="year-placeholder">' . wp_kses_post( $events_date_header ) . '</div>
                        </div>';
	}
	$events_html .= '<div id="event-' . esc_attr( $event_id ) . '"' . $cat_colors_attr . ' class="ect-timeline-post even ' . $event_type . ' ' . $timeline_style . '">';
	$events_html .= '<div class="timeline-dots"></div>';
	$events_html .= '<div class="timeline-content even">';
	$events_html .= '<div class="ect-timeline-header">';
	if ( $ev_post_img ) {
		$events_html .= '<a class="timeline-ev-img" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '"><img src= "' . $ev_post_img . '"/></a>';
	}
	$events_html .= $event_schedule;
	if ( $socialshare == 'yes' ) {
		$events_html .= ect_pro_share_button( $event_id ); }
	$events_html .= '</div>';
	$events_html .= '<div class="ect-timeline-main-content">';
	if ( ! empty( $ect_cate ) ) {
		$events_html .= '<div class="ect-event-category ect-timeline-categories">';
		$events_html .= $ect_cate;
		$events_html .= '</div>';
	}
	$events_html .= '<h2 class="content-title">' . $event_title . '</h2>';
	if ( $show_description == 'yes' || $show_description == '' ) {
		$events_html .= $event_content;
	}
	$events_html .= wp_kses_post( $venue_details_html );
	$events_html .= '</div>';
	$events_html .= '<div class="ect-timeline-footer">';
	if ( tribe_get_cost( $event_id, true ) ) {
		$events_html .= $ev_cost;
	}
	$events_html .= '      <div class="ect-lslist-event-detail">
        <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
        </div></div>';
	$events_html .= '</div>';
	$events_html .= '</div>';
} else {
	if ( $events_date_header !== '' ) {
		$events_html .= '<div class="ect-timeline-year">
						<div class="year-placeholder">' . wp_kses_post( $events_date_header ) . '</div>
						</div>';
	}

	$events_html .= '<div id="event-' . esc_attr( $event_id ) . '"' . $cat_colors_attr . ' class="ect-timeline-post even ' . esc_attr( $event_type ) . ' ' . esc_attr( $timeline_style ) . '">';
	$events_html .= '<div class="timeline-dots"></div>';
	$events_html .= '<div class="timeline-content even">';
	$events_html .= '<div class="ect-timeline-header">';
	if ( $ev_post_img ) {
		$events_html .= '<a class="timeline-ev-img" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '"><img src= "' . $ev_post_img . '"/></a>';
	}
		$events_html .= $event_schedule;
	if ( $socialshare == 'yes' ) {
		$events_html .= ect_pro_share_button( $event_id ); }

	if ( ! empty( $ect_cate ) ) {
		$events_html .= '<div class="ect-event-category ect-timeline-categories">';
		$events_html .= wp_kses_post( $ect_cate );
		$events_html .= '</div>';
	}
	$events_html .= '</div>';
	$events_html .= '<div class="ect-timeline-main-content">';
	$events_html .= '<h2 class="content-title">' . wp_kses_post( $event_title ) . '</h2>';

	if ( $show_description == 'yes' || $show_description == '' ) {
		$events_html .= wp_kses_post( $event_content );
	}
	$events_html .= $venue_details_html;
	if ( tribe_get_cost( $event_id, true ) ) {
		$events_html .= '<div class="ect-timeline-footer">';
		$events_html .= $ev_cost;
		$events_html .= '      <div class="ect-lslist-event-detail">
        <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
        </div></div>';
	} else {
		$events_html .= '<div class="ect-timeline-footer">';
		$events_html .= '<div class="ect-lslist-event-detail full-view">
        <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
        </div></div>';
	}
	$events_html .= '</div>';
	$events_html .= '</div>';
	$events_html .= '</div>';
}
