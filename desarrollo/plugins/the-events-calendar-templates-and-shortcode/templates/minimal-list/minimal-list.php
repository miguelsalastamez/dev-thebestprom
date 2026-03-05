<?php
if ( !defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used to  generate minimal list layout  html.
 */
/**
 * Get event status from The Events Calendar Extension: Events Control
 */
if (class_exists('Tribe\Events\Event_Status\Event_Status_Provider')){
    $online_url = ''; 
    $get_status = get_post_meta( $event_id,'_tribe_events_status', true );
    $status = !empty($get_status)?$get_status:'scheduled';
    if($status=='canceled'){
        $reason = get_post_meta($event_id,'_tribe_events_status_reason', true);
    }
    elseif($status=='postponed'){
        $reason = get_post_meta($event_id,'_tribe_events_status_reason', true);
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
$ev_day=tribe_get_start_date($event_id, false, 'd' );
$ev_month=tribe_get_start_date($event_id, false, 'M' );
$events_html.='<div id="event-'.esc_attr($event_id).'" class="ect-list-posts '.esc_attr($list_style).' '.esc_attr($event_type).'">';

$events_html.='<div class="ect-event-date-tag">
<div class="ect-event-datetimes">';
    if($list_style!=="style-2"){
        $events_html.='  <span class="ev-day">'.esc_html($ev_day).'</span>
    <span class="ev-mo">'.esc_html($ev_month).'</span>';
    }else{
        $events_html.='
        <span class="ev-mo">'.wp_kses_post($ev_month).'</span>
    <span class="ev-day">'.wp_kses_post($ev_day).'</span>
        ';
    }
    $events_html.='	</div>
</div>';
$events_html.='<div class="ect-event-details">';


if($list_style=="style-3"){
    $events_html.='<div class="ect-event-datetime"><i class="ect-icon-clock"></i>
    <span class="ect-minimal-list-time">'.wp_kses_post($ev_time).'</span></div>';
}
if($status!=''){
    $events_html.='<div class="ect-events-title"><div>'.wp_kses_post($event_title).'</div>';
    $events_html.='<div class="ect-tool-tip-wrapper ect-labels-wrap"><span class="ect-labels-'.$status.'">'.wp_kses_post($status).'</span>';
    if($reason!=''||$online_url!=''){
            $events_html.='<div class="ect-tip-inr">';
            if(!empty($reason)){
                $events_html.='<span class="ect-reason">'.wp_kses_post($reason).'</span>';
            }
            if(!empty($online_url)){
                $events_html.='<span class="ect-online-url">'.__('Live stream:-','epta').'<a href="'.$online_url.'"target="_blank">'.__('Watch Now','ect').'</a></span>';
            }
            $events_html.='</div>';
        }
        $events_html.='</div></div>';
    }
else{
    $events_html.='<div class="ect-events-title">'.wp_kses_post($event_title).'</div>'; 
}
if($list_style=="style-1")
{
$events_html.=' <div class="ect-event-datetime"><i class="ect-icon-clock"></i>
<span class="ect-minimal-list-time">'.wp_kses_post($ev_time).'</span></div>';
}
if($list_style=="style-2")
{
if (tribe_has_venue($event_id)) {
    $events_html.=wp_kses_post($venue_details_html1);
    }
}
$events_html.='</div></div>';