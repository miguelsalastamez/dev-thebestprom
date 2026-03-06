<?php
if ( !defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used to  generate cover layout  html.
 */

$ev_post_img=ect_pro_get_event_image($event_id,$size='large');
$ect_cate = ect_display_category($event_id);
$google_cale = \Tribe__Events__Main::instance()->esc_gcal_url( tribe_get_gcal_link($event_id) );
if (class_exists('Tribe\Extensions\EventsControl\Main')){
    $online_url = ''; 
    $get_status = get_post_meta( $event_id,'_tribe_events_control_status', true );
    $status = !empty($get_status)?$get_status:'scheduled';
    if($status=='canceled'){
        $reason = get_post_meta($event_id,'_tribe_events_control_status_canceled_reason', true);
    }
    elseif($status=='postponed'){
        $reason = get_post_meta($event_id,'_tribe_events_control_status_postponed_reason', true);
    }
    else{
        $reason = '';
    }
    $online = tribe_is_truthy( get_post_meta( $event_id,'_tribe_events_control_online', true ) );
    if($online){
        $online_url = get_post_meta( $event_id,'_tribe_events_control_online_url', true );
    }
}
else{
    $status='';
}
if($style=="style-1"){
    $ect_cover_left_cls = "ect-cover-left";
}
else{
    $ect_cover_left_cls = "ect-cover-right-top";
}
$events_html.='<div id="event-'.esc_attr($event_id) .'"'.$cat_colors_attr.' class="ect-cover-event '.$style.' '.$event_type.'" itemscope itemtype="http://schema.org/Event">
                <div class="ect-cover-event-area">
                <div class="ect-cover-right ect-cover-image"><a href="'.tribe_get_event_link($event_id).'">
                <img src="'.$ev_post_img.'" title="'.get_the_title($event_id) .'" alt="'.get_the_title($event_id) .'"></a>
                </div>';
                $events_html.='<div class='.$ect_cover_left_cls.'>';
                $events_html.='<div class="ect-cover-date">
                    <span class="ev-icon"><i class="ect-icon-calendar" aria-hidden="true"></i></span>
                    '.$event_schedule.'</div>';
                    if($style=="style-2" || $style=="style-3"){
                        $events_html.= '</div><div class="ect-cover-left-bottom">';
                    }
                    $events_html.='<div class="ect-cover-title">'.$event_title.'</div>';
                    if (tribe_has_venue($event_id) && $attribute['hide-venue']!="yes") {
                         $events_html.='<div class="ect-cover-venue">'.$venue_details_html.'</div>';
                    }
                    else {
                        $events_html.='';
                    }
                    if($show_description=="yes"){
                        $events_html.='<div class="ect-cover-description">'.$event_description.'</div>';
                    }       
                    if(!empty($ect_cate)){
                        $events_html.= '<div class="ect-event-category ect-cover-categories">'.$ect_cate.'</div>';
                    }
                    // if ( tribe_get_cost($event_id, true ) ) {
                    //     $events_html.= '<div class="ect-cover-cost">'.$ev_cost.'</div>';
                    // }
                    if($status!=''){
                        $events_html.='<div class="ect-tool-tip-wrapper ect-labels-wrap"><span class="ect-labels-'.$status.'">'.$status.'</span>';
                        if($reason!=''||$online_url!=''){
                        $events_html.='<div class="ect-tip-inr">';
                        if(!empty($reason)){
                            $events_html.='<span class="ect-reason">'.$reason.'</span>';
                        }
                        if(!empty($online_url)){
                            $events_html.='<span class="ect-online-url">'.__('Live stream:-','ect').'<a href="'.esc_url($online_url).'" target="_blank">'.__('Watch Now','ect').'</a></span>';
                        }
                        $events_html.='</div>';
                        }
                        $events_html.='</div>';
                    }
                    if ( tribe_get_map_link($event_id) ) {
						$events_html.='<span class="ect-google-map">'.esc_url(tribe_get_map_link_html($event_id)).'</span>';
					}
                    $events_html .='<div class="ect-google-cale"><a href='.$google_cale.'target="_blank" title="Add to Google Calendar">+ Google Calendar</a></div>';
                    $events_html.= '<div class="ect-cover-readmore"><a href="'.esc_url(tribe_get_event_link($event_id)).'" title="'.get_the_title($event_id) .'" rel="bookmark">'.$events_more_info_text.'</a></div>';
                    if($socialshare=="yes") {
                        $events_html.= '<div class="ect-cover-sharebtn">'.ect_pro_share_button($event_id).'</div>';
                    }
                    $events_html.= '</div>';
$events_html.='</div></div>';