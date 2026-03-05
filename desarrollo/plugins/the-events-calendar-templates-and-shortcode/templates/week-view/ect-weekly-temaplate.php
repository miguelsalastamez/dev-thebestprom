<?php
class ect_weekly_temaplate extends EctProStyles {

     public function ect_weekly_shortcode($atts){
                    if ( !function_exists( 'tribe_get_events' ) ) {
                         return;
                    }
                    $output = '';
			     $events_html = '';
			     /*** Set shortcode default attributes */
			     $attribute = shortcode_atts( apply_filters( 'ect_weekly_shortcode_atts', array(
			     'category' => 'all',
			     'tags'=> '',
                    'limit' => '10',
			     'featured-only'=>'',
			     'venues'=> '',
                    'organizers'=>'',
			     ), $atts ), $atts);
                    $output = '';
                    $events_html = '';
                    $monday = date("d M Y", strtotime('monday this week'));
                    $friday = date( 'd M Y', strtotime('sunday this week') );
                    $multiday_start_date = date("d-m-Y", strtotime('-3 month'));
                    $featured_only='';
                    if($attribute['featured-only']=='all'){
                         $featured_only='';
                    }
                    elseif($attribute['featured-only']=="yes"){
                        $featured_only=true;
                    }
                    elseif($attribute['featured-only']=="no"){
                        $featured_only=false;
                    }
                    $ect_args=array(
			     	'post_status' => 'publish',
                         'start_date'   => date("Y-m-d", strtotime('monday this week')).'00:01',
                         'end_date'     =>date( "Y-m-d ", strtotime('sunday this week')).'23:59',
                         'posts_per_page' => $attribute['limit'],
			     	'order' => 'ASC',
                         'featured'=>$featured_only,
                    ); 
                    if (!empty($attribute['category'])) {
                         $category_array = explode(",",$attribute['category']);
                         if(!in_array('all',$category_array)){
                             $ect_args['tax_query'] = [
                                  [
                                      'taxonomy' => 'tribe_events_cat', 'field' => 'slug',
                                      'terms'    => $category_array
                                  ],
                              ];
                         }
                    } 
                    if($attribute['tags']!="") {
                         if ( strpos( $attribute['tags'], "," ) !== false ) {
                              $ect_args['tag'] = explode( ",", $attribute['tags'] );
                         }else{
                              $ect_args['tag']=$attribute['tags'];
                         }
                    }
                    if($attribute['venues']!="") {
                         if ( strpos( $attribute['venues'], "," ) !== false ) {
                             $ect_args['venue'] = explode( ",", $attribute['venues'] );
                         }else{
                             $ect_args['venue']=$attribute['venues'];
                         }
                     }
                     if($attribute['organizers']!="") {
                         if ( $post && preg_match( '/vc_row/', $post->post_content ) ) {						
                              $ORGargs = [
                                   'post_type'      => 'tribe_organizer',
                                   'posts_per_page' => 1,
                                   'post_name__in'  => [$attribute['organizers']],
                                   'fields'         => 'ids' 
                              ];
                              $orgID = get_posts( $ORGargs );
                              $ect_args['organizer'] = $orgID;	
                         }
                         else{
                              if ( strpos( $attribute['organizers'], "," ) !== false ) {
                                   $ect_args['organizer'] = explode( ",",$attribute['organizers'] );
                              }else{
                                   $ect_args['organizer']=$attribute['organizers'];
                              }
                         }
                    }
                    $extraData = [];
				$extraData['ajax_url'] = admin_url( 'admin-ajax.php' );
                    $extraData['nonce'] = wp_create_nonce('ect-next-prev-nonce');
                    /*=====================================
                     * Enqueue The weekly External Files
                     ======================================*/
                    wp_enqueue_style('ect-weekly-view-css');
				wp_enqueue_style('ect-common-styles');
				wp_enqueue_script('ect-weekly-view-js');
                    wp_localize_script('ect-weekly-view-js', 'extradata',$extraData);
                    $custom_style = $this::ect_custom_styles("week-view","");
                    wp_add_inline_style('ect-weekly-view-css',$custom_style );
                    // Get All Events According to Argument
                    
                    $all_events = tribe_get_events($ect_args);
                    // var_dump($all_events);
                    /**
                     * Get next Monday
                     */
                    $dateTime = new DateTime($monday);
                    $dateTime->modify('+7 day');
                    $dateTimeago =new DateTime($monday);
                    $dateTimeago->modify('-7 day');
                    $weekly_id = uniqid();
                    //Print out the date in a YYYY-MM-DD format.
                    $start_next_dates =  $dateTime->format("Y-m-d");
                    $start_date =$dateTimeago->format("Y-m-d");
				$settings = array("event_start_date"=>$multiday_start_date, "start_prev_week"=>$start_date,"next_week_day"=>$start_next_dates,"Category"=>$attribute['category'],
                    "tags"=>$attribute['tags'], "limit"=>$attribute['limit'], "featured"=>$featured_only, "venue"=>$attribute['venues'], "organizers"=>$attribute['organizers']);
                         $output .='<!========= Weekly Template Template '.ECT_PRO_VERSION.'=========>';
				     $output .='<div id="ect-weekly-events-wrapper" data-weekly-id="'.$weekly_id .'">
                         <div class="ect-week-nav">
				       <button class="ect-prev "><i class="ect-icon-left-double"></i></button>
				       <h2 class="ect-week"><span>'.$monday.' </span>'.'-'.'<span>'.$friday.'</span></h2>
				       <button class="ect-next"><i class="ect-icon-right-double"></i></button>
				     </div>';
                         if( is_array($all_events) && count($all_events)>0){
                             $output .='<div class="ect-week-days-wrapper">';
                             include(ECT_PRO_PLUGIN_DIR.'templates/week-view/weekly-view.php');
                             $output.= $events_html;
                             $output.= '</div>';
                         }else{
                              $no_event_found_text =ect_get_option( 'events_not_found' );
				          if(!empty($no_event_found_text)){
				          	$output.='<div class="ect-no-events"><p>'.filter_var($no_event_found_text,FILTER_SANITIZE_STRING).'</p></div>';
				          }else{
                                   $output.='<div class="ect-no-events"><p>'.__('There are no upcoming events at this time.','ect').'</p></div>';
				          }
                         }
                    $output.='<div class="ect_calendar_events_spinner"><div class="ect_spinner_img"><img src="'.ECT_PRO_PLUGIN_URL .'assets/images/ect-preloader.gif"><br/>Loading events...</div></div>';
                    $output.='<script type="application/json" id="ect-query-arg">'.json_encode($settings).'</script>';
                    $output.='</div>';
                    return $output;
     }
    
}
