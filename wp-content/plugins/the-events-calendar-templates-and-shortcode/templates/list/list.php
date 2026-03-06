<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used to  generate list layout  html.
 */
$ev_time    = ect_tribe_event_time( $event_id, false );
$list_style = esc_attr( $style );
if ( $template == 'modern-list' ) {
	$list_style = 'style-2';
} elseif ( $template == 'classic-list' ) {
	$list_style = 'style-3';
}
$ev_post_img    = '';
$event_cost_div = '';
$size           = 'medium';
$ev_post_img    = ect_pro_get_event_image( $event_id, $size = 'large' );
$ect_cate       = ect_display_category( $event_id );
if ( tribe_get_cost( $event_id, true ) ) {
	$event_cost_div = '<div class="ect-list-cost">' . wp_kses_post( $ev_cost ) . '</div>';
}
$isStyleTemplateCombo1 = ( $style == 'style-2' || $style == 'style-4' || $style == 'style-3' ) && $template == 'default';
$isTemplateCombo2      = $template == 'modern-list' || $template == 'classic-list';
if ( $isStyleTemplateCombo1 || $isTemplateCombo2 ) {
	$bg_styles = "background-image:url('$ev_post_img');background-size:cover;background-position:bottom center;";
} else {
	$bg_styles = "background-image:url('$ev_post_img');background-size:cover;";
}
	$events_html .= '
	<div id="event-' . esc_attr( $event_id ) . '" ' . $cat_colors_attr . ' class="ect-list-post ' . esc_attr( $list_style ) . ' ' . esc_attr( $event_type ) . '" itemscope itemtype="http://schema.org/Event">
		<meta itemprop="name" content="' . get_the_title( $event_id ) . '">
		<meta itemprop="image" content="' . $ev_post_img . '">
		<div class="ect-list-post-left ">
			<div class="ect-list-img" style="' . $bg_styles . '">';
if ( $style != 'style-2' ) {
	$events_html .= '<a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" alt="' . wp_kses_post( get_the_title( $event_id ) ) . '" rel="bookmark">
					<div class="ect-list-date">' . wp_kses_post( $event_schedule ) . '</div>
				</a>';
}
			$events_html .= '</div>';
if ( $style == 'style-1' || $style == 'style-2' ) {
	if ( $socialshare == 'yes' ) {
		$events_html .= ect_pro_share_button( $event_id );
	}
}
if ( $style == 'style-4' ) {
	$events_html .= '<div class="ect-list-schedule"><div class="ect-list-schedule-wrap">
				' . $event_schedule . '</div></div>';
	if ( $socialshare == 'yes' ) {
		$events_html .= '<div class="ect-share-style-4">
					' . ect_pro_share_button( $event_id ) . '</div>';
	}
}
			$events_html .= '</div><!-- left-post close -->
		<div class="ect-list-post-right">
			<div class="ect-list-post-right-table">';
if ( $style == 'style-1' ) {
	if ( $hide_venue != 'yes' ) {
		if ( tribe_has_venue( $event_id ) ) {
			$events_html .= '<div class="ect-list-description">';
		} else {
			$events_html .= '<div class="ect-list-description" style="width:100%;">';
		}
	} else {
		$events_html .= '<div class="ect-list-description" style="width:100%;">';
	}
} else {
	$events_html .= '<div class="ect-list-description">';
}
if ( ! empty( $ect_cate ) ) {
	$events_html .= '<div class="ect-event-category ect-list-category">';
	$events_html .= wp_kses_post( $ect_cate );
	$events_html .= '</div>';
}
			$events_html .= '<h2 class="ect-list-title">' . wp_kses_post( $event_title ) . '</h2>';
if ( $style == 'style-3' ) {
	$events_html .= '<div class="ect-clslist-time">
					<span class="ect-icon"><i class="ect-icon-clock"></i></span>
					<span class="cls-list-time">' . $ev_time . '</span>
					</div>';
	if ( tribe_has_venue( $event_id ) ) {
		$events_html .= wp_kses_post( $venue_details_html );
	}
}
if ( $show_description == 'yes' || $show_description == '' ) {
	$events_html .= wp_kses_post( $event_content );
}
	$events_html .= $event_cost_div . '<a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" class="ect-events-read-more" rel="bookmark">' . $events_more_info_text . '</a>';

		$events_html .= '</div>';
if ( $style == 'style-3' ) {
	$events_html .= '</div></div>';
	if ( $socialshare == 'yes' ) {
		$events_html     .= '<div class="ect-clslist-event-details">';
			$events_html .= ect_pro_share_button( $event_id );
			$events_html .= '</div>';
	}
}
if ( $style == 'style-2' ) {
	$events_html .= '<div class="modern-list-right-side" >
			<div class="ect-list-date">' . wp_kses_post( $event_schedule ) . '</div>';
}
if ( $style == 'style-1' || $style == 'style-2' ) {
	if ( tribe_has_venue( $event_id ) ) {
		$events_html .= wp_kses_post( $venue_details_html );
	}
}
if ( $style == 'style-2' ) {
	$events_html .= '	</div>';
}
		$events_html .= '</div>';
if ( $style != 'style-3' ) {
	$events_html .= '</div><!-- right-wrapper close -->
	</div><!-- event-loop-end -->';
}


