<?php
    $monday = date("d M Y", strtotime('monday this week'));
    $mondays = !empty($mondays)?$mondays:$monday;
    $empty_returnarray = array();
    for($i=0;$i<=6;$i++){
      $day = date( 'd', strtotime($mondays.'+'.$i.'day'));
      $get_day_name = date( 'D', strtotime($mondays.'+'.$i.'day'));
      $month_names = date( 'M', strtotime($mondays.'+'.$i.'day'));
      $event_date_check = date('Y-m-d',strtotime($mondays.'+'.$i.'day')); 
      $events_html.='<div class="ect-week-day">
                    <div class="ect-calendar">
                        <span class="ect-day">'.$get_day_name.'</span>
                        <span class="ect-date">'.date( 'd', strtotime($mondays.'+'.$i.'day')).'</span>
                        <span class="ect-month">'.$month_names.'</span>
                    </div>';
                  foreach($all_events as $sr =>$event){
                    if(!tribe_event_is_multiday($event->ID)){
                      if(substr($event->event_date, 0, 10) == $event_date_check){
                          $event_type = tribe( 'tec.featured_events' )->is_featured( $event->ID ) ? 'ect-featured-event' : 'ect-simple-event';
                          $event_time_weekly = tribe_get_start_time( $event->ID,'h:i A', null);
                          $evt_tme_wv = $event_time_weekly == null ? __('All Day','ect'): $event_time_weekly;
                          $events_html .='<div class="ect-event '.esc_attr($event_type).'">';
                          $events_html .='<div class="ect-title"><a href="'.esc_url($event->guid).'" target="_blank">'.$event->post_title.'</a></div>
                            <div class="ect-time"><i class="ect-icon-clock"></i>'.$evt_tme_wv .'</div>
                            </div>';
                       }
                    }
                    elseif(tribe_event_is_multiday($event->ID)){
                      $event_start_date = tribe_get_start_date($event->ID, false, 'Y-m-d', null);
                      $event_end_date   = tribe_get_end_date($event->ID,   false,   'Y-m-d',   null);
                      $empty_returnarray[$event->ID] = get_date_range($event_start_date,$event_end_date);
                      // var_dump($event_start_date,$event_end_date);
                    }
                  }
                  foreach($empty_returnarray as $key =>$multi_ev){

                    foreach($multi_ev as $ev_date){
                      if($event_date_check == $ev_date){
                        $event_type = tribe( 'tec.featured_events' )->is_featured( $key ) ? 'ect-featured-event' : 'ect-simple-event';
                        $event_time_weekly = tribe_get_start_time($key,'h:i A', null);
                        $evt_tme_wv = $event_time_weekly == null ? __('All Day','ect'): $event_time_weekly;
                        $events_html .='<div class="ect-event '.esc_attr($event_type).'">';
                        $events_html .='<div class="ect-title"><a href="'.esc_url(tribe_get_event_link($key,false)).'" target="_blank">'.get_the_title($key).'</a></div>
                          <div class="ect-time"><i class="ect-icon-clock"></i>'.$evt_tme_wv .'</div>
                          </div>';
                      }
                    }
                  } 
      $events_html.='</div>';
    }


