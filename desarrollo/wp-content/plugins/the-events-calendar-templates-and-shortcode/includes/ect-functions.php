<?php 
// A Custom function for get an option
if ( ! function_exists( 'ect_get_option' ) ) {
    function ect_get_option( $option = '', $default = null ) {
      $options = get_option( 'ects_options' ); // Attention: Set your unique id of the framework
      return ( isset( $options[$option] ) ) ? $options[$option] : $default;
    }
  }
/**
 * category Filter function
 */
function ect_cats_list() {
   
    $ect_cat_arr=array();
        if(version_compare(get_bloginfo('version'),'4.5.0', '>=') ){
            $terms = get_terms(array(
            'taxonomy' => 'tribe_events_cat',
            'hide_empty' =>true,
        ));
    }
    else{
        $terms = get_terms('tribe_events_cat', array('hide_empty' => true,) );
    }
    if (!empty($terms) || !is_wp_error($terms)) {
        $allPosts=0;
        foreach ($terms as $term) {
            $ect_cat_arr[$term->slug] =array("name"=>$term->name,"count"=>$term->count);
            $allPosts+=$term->count;	
        }
        $ect_cat_arr['all']=array("name"=>__('All','ect'),"count"=>$allPosts);
    }
    return $ect_cat_arr;
}
// generate category filters list HTML
function create_cat_filter_html($selected_cat,$post_per_page){
	$ect_all_categories=ect_cats_list();
    $html_output='';
    if(count($ect_all_categories)>1){
        $html_output .='<div class="ect-fitlers-wrapper">
        <ul class="ect-categories">';
        $active_cat='';
        asort($ect_all_categories);
        $prefetch='';
        foreach($ect_all_categories as $slug=> $details){
            $totalPosts=$details['count'];
            if($totalPosts>0){
            if($totalPosts>$post_per_page){
                $pages=ceil($totalPosts/$post_per_page);
            }else{
                $pages=0;
            }
                if(preg_match("/{$slug}/i", $selected_cat)){
                $active_cat='ect-active';
                $prefetch='true';
            }else{
                $active_cat='';
                $prefetch='false';
            }
            if($slug=="all"){
                $slug="";
            }else{
                $slug=$slug;
            }
        $html_output .= '<li data-paged="0" data-prefetch="'.$prefetch.'" 
         data-pages="'.$pages.'"
          data-posts="'.$totalPosts.'" class="ect-cat-items '.$active_cat.'"
            data-filter="'. $slug.'">'. $details['name'].'</li>';
        }
        } 
        $html_output.='</ul></div>';
        return $html_output;
    }
 }
// admin side timing
function ect_set_notice_timing(){
    if(version_compare(get_option('ect-pro-v'),'1.7', '<')){		
        set_transient( 'ect-assn-timing', true, DAY_IN_SECONDS);
    }
    if( isset( $_GET['ect_disable_notice'] ) && !empty( $_GET['ect_disable_notice'] ) ){
        $rs=delete_transient( 'ect-assn-timing' );
        update_option('ect-pro-v',ECT_PRO_VERSION);
    }
}
/**
 * This file is used to share events.
 * 
 * @package the-events-calendar-templates-and-shortcode/includes
 */
function ect_pro_share_button($event_id){
    $ect_sharecontent = '';
    $ect_geturl = esc_url(get_permalink($event_id));
    $ect_gettitle = sanitize_title(get_the_title($event_id));
    $subject= str_replace("+"," ",$ect_gettitle);
    // Construct sharing URL
      $ect_twitterURL = esc_url('https://twitter.com/intent/tweet?text='.$ect_gettitle.'&amp;url='.$ect_geturl.'');
      $ect_whatsappURL = esc_url('https://web.whatsapp.com/send/?text='.$ect_gettitle . ' ' . $ect_geturl);
      $ect_facebookurl = esc_url('https://www.facebook.com/sharer/sharer.php?u='.$ect_geturl.'');
      $ect_emailUrl = esc_url('mailto:?Subject='.$subject.'&Body='.$ect_geturl.'');
     $ect_linkedinUrl = esc_url("http://www.linkedin.com/shareArticle?mini=true&amp;url=$ect_geturl");
      // Add sharing button at the end of page/page content
      $ect_sharecontent .= '<div class="ect-share-wrapper">';
      $ect_sharecontent .= '<i class="ect-icon-share"></i>';
      $ect_sharecontent .= '<div class="ect-social-share-list">';
      $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_facebookurl).'" target="_blank" title="Facebook" aria-haspopup="true"><i class="ect-icon-facebook"></i></a>';
      $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_twitterURL).'" target="_blank" title="Twitter" aria-haspopup="true"><i class="ect-icon-twitter"></i></a>';
      $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_linkedinUrl).'" target="_blank" title="Linkedin" aria-haspopup="true"><i class="ect-icon-linkedin"></i></a>';
      $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_emailUrl).'" target="_blank" title="Email" aria-haspopup="true"><i class="ect-icon-mail"></i></a>';
      $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_whatsappURL).'" target="_blank" title="WhatsApp" aria-haspopup="true"><i class="ect-icon-whatsapp"></i></a>';
      $ect_sharecontent .= '</div></div>';
      return $ect_sharecontent;
}
function ect_DISP_category($event_id){
	$ectbe_cate ='';
	$ectbe_cate = get_the_term_list($event_id, 'tribe_events_cat','',',','');
	return $ectbe_cate;
}
function ect_pro_get_event_image($event_id,$size){
	$default_img = ECT_PRO_PLUGIN_URL."assets/images/event-template-bg.png";
    $ev_post_img='';
    $feat_img_url = wp_get_attachment_image_src(get_post_thumbnail_id($event_id),$size);
    if(!empty($feat_img_url) && $feat_img_url[0] !=false){
        $ev_post_img = $feat_img_url[0];
        }elseif ($feat_img_url==''|| $feat_img_url==false){
            $tect_settings = get_option('ects_options');
            $non_feat_img = !empty($tect_settings['ect_no_featured_img'])?$tect_settings['ect_no_featured_img']:'';
            if(is_array($non_feat_img)){
                $non_feat_img_url = $non_feat_img['id'];
            }
            else{
                $non_feat_img_url = $non_feat_img;
            }
            if ($non_feat_img_url!='' && is_numeric( $non_feat_img_url ) ){
                $imageAttachment = wp_get_attachment_image_src( $non_feat_img_url,$size);
                $ev_post_img= $imageAttachment[0];
            }else{
                $ev_post_img=$default_img;
            }
        }else{
            $ev_post_img=$default_img;
        }
        return esc_url($ev_post_img);
}
function ect_display_category($event_id){
	$ect_cate = '';
	$ect_cate_sett = ect_get_option('ect_display_categoery' );
	if($ect_cate_sett=='ect_enable_cat'){
		$ect_cate = get_the_term_list($event_id, 'tribe_events_cat', '<ul class="tribe_events_cat"><li>', '</li><li>', '</li></ul>' );
	}
	return $ect_cate;
}
/**
 * Genrated tags List
 */
function ect_get_ev_post_tag($args = array()){
	$options = [];
	$tags = get_terms($args);
	if (is_wp_error($tags)) {
	    return [];
	}
	foreach ($tags as $tag) {
        if($tag->count>0){
            $options[$tag->term_id] = $tag->name;
        }
	}
    $options['all'] = 'All';
	return $options;
}

//Past Events for events clander
function get_date_range($start,$end){
	$range = array();
	$interval = new DateInterval('P1D');
	$realEnd = new DateTime($end);
	$realEnd->add($interval);
	$period = new DatePeriod(new DateTime($start), $interval, $realEnd);
	// Use loop to store date into array
	foreach($period as $date){				
		$range[] = $date->format('Y-m-d');
	}
	return $range;
}
function ect_category_select_box_list($selected_cat){
    $events_cate = '';
    $catgory_List =  ect_cats_list();
    if($selected_cat=='all'){
        foreach ( $catgory_List as $term )
        { 
            $cat_Val = ucwords( $term['name'] );
            $events_cate .= '<option value="'.esc_attr($cat_Val).'">' . $cat_Val . '</option>';
        }
    }else{
            $selected_cate = explode(",",$selected_cat);
            foreach($selected_cate as $value){
                $selectName = get_term_by('slug', $value, 'tribe_events_cat')->name;
                $events_cate .= '<option value="'.esc_attr($selectName).'">' . ucwords($selectName) . '</option>';
            }
    }
    return $events_cate;
}
 function ect_tags_select_box_list($tag_array){
    $events_tag = '';
    if(!empty($tag_array)){
        $category_array = explode(",",$tag_array);
        foreach ( $category_array as $term )
        { 
          $select_box_value = get_term_by('slug',$term,'post_tag');
          $tag_Val = ucwords($select_box_value->name);
          $events_tag .= '<option value="' . esc_attr($select_box_value->name) . '">' . $tag_Val . '</option>';
        }
    }else{
        $tag_List =  ect_get_ev_post_tag(array('taxonomy' => 'post_tag','hide_empty' => false));
        foreach ( $tag_List as $term )
        {
          $tag_Val = ucwords($term);
          $events_tag .= '<option value="' . esc_attr($term) . '">' . $tag_Val . '</option>';
        }
    }
    return $events_tag;
 }