<?php
$ev_post_img        = '';
$tagList            = array();
$category_hide_seek = '';
$ev_post_img        = ect_pro_get_event_image( $event_id, $size = 'large' );
// condition to check if an event is an all day event then set date to all day
$event_all_day = get_post_meta( $event_id, '_EventAllDay', true );
if ( $event_all_day ) {
	$start_date = 'All day';
	$end_date   = '';
} else {
	// previous code to get the dates
	$start_date = explode( '-', $ev_time )[0];
	$end_date   = ( $start_date != 'All day' ) ? explode( '-', $ev_time )[1] : '';
}
$terms2 = get_the_tags( $event_id );
// $category_condition = ect_display_category($event_id);
// $category_hide_seek = empty($category_condition)? 'ect-cattag-hide':'';
$ect_cate_sett = ect_get_option( 'ect_display_categoery' );
if ( $ect_cate_sett == 'ect_disable_cat' ) {
	$category_hide_seek = 'ect-cattag-hide';
}
if ( is_array( $terms2 ) ) {
	foreach ( $terms2 as $term ) {
		array_push( $tagList, $term->name );
	}
}
$tagValues   = ! count( $tagList ) > 0 ? 'N/A' : implode( ',', $tagList );
$date_attr   = $date_format == 'default' ? 'dFY' : str_replace( 'D', 'd', $date_format );
$data_search = esc_html( tribe_get_start_date( $event_id, false, $date_attr ) );
/*** Advance List Body */
$events_html .= '<tr class="' . esc_attr( $event_type ) . '">';
$events_html .= '<td class="ect-advance-list-tittle-name "><a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" target="_blank" >' . $tittle . '</a></td>';

$events_html .= '<td class="ect-advance-list-mobi-serial ect-date-sort " id="ev-advance-date" data-search="' . esc_attr( $data_search ) . '" data-order="' . esc_attr( $date_order ) . '">' . $event_schedule . '</td>';
if ( ! empty( $end_date ) ) {
	$events_html .= '<td class="ect-advance-list-time" data-search="' . esc_attr( str_replace( ' ', '', $ev_time ) ) . '"><div class="ect-event-time"><span class="ect-str-date">' . $start_date . '</span>- <span class="end-date">' . $end_date . '</span></div></td>';
} else {
	$events_html .= '<td class="ect-advance-list-time" data-search="' . esc_attr( str_replace( ' ', '', $ev_time ) ) . '"><div class="ect-event-time"><span class="ect-str-date">' . $start_date . '</span></div></td>';

}
if ( $showimage == 'yes' ) {
	$events_html .= '<td><img src="' . esc_url( $ev_post_img ) . '"></td>';
}
if ( $show_description == 'yes' ) {
	$events_html .= '<td class="ect-advance-list-desc">' . tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) ) . '</td>';
}
$events_html .= '<td class="ect-advance-list-catTag ' . esc_attr( $category_hide_seek ) . '">' . ect_DISP_category( $event_id ) . '</td>';
$events_html .= '<td class="ect-cattag-hide">' . $tagValues . '</td>';
if ( $hide_venue === 'no' ) {
	if ( isset( $venue_details['linked_name'] ) ) {
		$events_html .= '<td class="ect-advance-list-venue">' . $venue_details['linked_name'] . '</td>';
	} else {
		$events_html .= '<td class="ect-advance-list-venue"></td>';
	}
}

$events_html .= '<td  class="ectbe_viewMore ect-adv-vm " id="ect-viewmoreBtn"><a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . esc_attr( get_the_title( $event_id ) ) . '" target="_blank"><span class="' . esc_attr( $event_type ) . '" id="ect-view-more">' . esc_html( $viewMoreTittle ) . '</a></span></td>';
$events_html .= '</tr>';
