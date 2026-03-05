<?php
if ( !defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used only for dynamic styles in cover layouts.
 */
switch($style){

     /** STYLE-1 **/
     case "style-1":
     /*--- Featured Event Color - CSS ---*/
        $ect_output_css.='
        #ect-cover-wrapper .ect-cover-event.style-1.ect-featured-event .ect-cover-event-area{
          background-color:'. $featured_event_skin_color.';
        }
        ';
        /*--- Event Background Color - CSS ---*/
        $ect_output_css.='#ect-cover-wrapper .ect-cover-event.style-1.ect-simple-event .ect-cover-event-area .ect-cover-left{
          background:'.$event_desc_bg_color.';
        }';

     break;

     /** STYLE-2 **/
     case "style-2":
             /*--- Event Background Color - CSS ---*/
          $ect_output_css.='
          #ect-cover-wrapper .ect-cover-event.style-2.ect-simple-event .ect-cover-left-bottom{
               background:'.$event_desc_bg_color.';
          }
          ';
          /*--- Event Background Color - CSS ---*/
          $ect_output_css.='#ect-cover-wrapper .ect-cover-event.style-2.ect-featured-event .ect-cover-left-bottom{
               background:'. $featured_event_skin_color.';
          }';
     break;

     /** STYLE-3 **/
     case "style-3":
        /*--- Featured Event Color - CSS ---*/
          $ect_output_css.='
          #ect-cover-wrapper.ect-events-cover-style-3 .ect-cover-event.style-3.ect-featured-event .ect-cover-left-bottom{
               background: '. $featured_event_skin_color.';
          }
          #ect-cover-wrapper.ect-events-cover-style-3 .ect-featured-event .ect-event-category ul.tribe_events_cat li a{
               border:1px solid '.Ecttinycolor($featured_event_skin_color)->darken(10)->toString().';
          }
          ';
          /*--- Featured Event Font Color - CSS ---*/
          $ect_output_css.=' #ect-cover-wrapper .ect-cover-event.style-3.ect-featured-event .ect-cover-left-bottom{
              border-bottom: 6px solid '. $featured_event_font_color.';
          }';
          /*--- Event Background Color - CSS ---*/#ect-cover-wrapper .ect-simple-event .ect-cover-readmore a
          $ect_output_css.='
          #ect-cover-wrapper .ect-cover-event.style-3.ect-simple-event .ect-cover-left-bottom{
               background:'.$event_desc_bg_color.';
          }
          ';
          /*--- Event Description - CSS ---*/
          $ect_output_css.='
          #ect-cover-wrapper.ect-events-cover-style-3 .ect-cover-event.style-3.ect-featured-event .ect-cover-description .ect-event-content p
		{
			color: '.Ecttinycolor($ect_desc_color)->darken(10)->toString().';
		}
          ';
          /*--- Event Venue Color - CSS ---*/
          $ect_output_css.='
          #ect-cover-wrapper.ect-events-cover-style-3 .ect-cover-event.style-3.ect-featured-event .cover-view-venue span,
		#ect-cover-wrapper .ect-cover-event.style-3.ect-featured-event .ect-cover-cost .ect-rate-area span,
		#ect-cover-wrapper.ect-events-cover-style-3 .ect-cover-event.style-3.ect-featured-event .cover-view-venue a{
			color: '.Ecttinycolor($ect_venue_color)->darken(3)->toString().';
		}
          ';
          /*--- Event dates Color - CSS ---*/
          $ect_output_css.='#ect-cover-wrapper.ect-events-cover-style-3 .ect-cover-event.style-3.ect-simple-event .ect-cover-left-bottom{
				border-bottom:6px solid '.$ect_date_color.';
		}';


         
     break;
}

/*--- Main Skin Color - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper button.ctl-slick-prev,
#ect-cover-wrapper button.ctl-slick-next{
     background-color: '.Ecttinycolor($main_skin_color)->darken(3)->toString().';
} 
';
/*--- Featured Event Color - CSS ---*/			
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-cover-right-top
{
	background: '.$featured_event_skin_color.';
}
#ect-cover-wrapper .ect-featured-event .ect-cover-readmore a, #ect-cover-wrapper .ect-cover-readmore a{
     background: '.$featured_event_skin_color.';
}
.ect-featured-event .ect-event-date span.ect-date-viewport,
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-share-wrapper .ect-social-share-list a
{
     color: '.$featured_event_skin_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ect-google-map a,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ect-google-map{
background: '.$featured_event_skin_color.';
}
#ect-cover-wrapper .ect-featured-event .ect-event-category ul.tribe_events_cat li{
     border-color: '.Ecttinycolor($featured_event_skin_color)->darken(10)->toString().';
}
';
/*--- Featured Event Font Color - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-featured-event .ect-date-area.cover-view-schedule span,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ev-icon i,
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-cover-title a,
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-event-content p,
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-cover-cost span,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ect-icon,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span a,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span
{
     color:'.$featured_event_font_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-google-cale a,
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ect-google-map a{
	color:'.$featured_event_font_color.';
	
}
#ect-cover-wrapper .ect-cover-event.ect-featured-event span.ect-google-map,
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-google-cale
{
	border:2px solid '.$featured_event_font_color.';

}
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-cover-readmore a{
	color: '.$featured_event_font_color.';
	border:1px solid '.$featured_event_font_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-featured-event .ect-cover-left{
     border-left: 6px solid '.$featured_event_font_color.';
}

';
/*--- Event Background Color - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-right-top,
#ect-cover-wrapper .ect-cover-event.ect-simple-event span.ect-google-map,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-google-cale{
	background: '.$event_desc_bg_color.';
}
';
/*--- Event Title - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event .ect-cover-title a{
font-family: '.$ect_title_font_famiily.';
font-weight: '.$ect_title_styles['font-weight'].';
font-style: '.$ect_title_styles['font-style'].';
line-height: '.$ect_title_styles['line-height'].';
font-size: '.$ect_title_styles['font-size'].';
}
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-title a {
     color: '.$ect_title_color.';
}
';
/*--- Event Description - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-description .ect-event-content p,
#ect-carousel-wrapper .ect-events-carousel .slick-arrow {
	color: '.$ect_desc_color.';
}
#ect-cover-wrapper .ect-cover-description .ect-event-content p,
#ect-cover-wrapper .ect-cover-event .ect-cover-readmore a{
    '.$ect_desc_styles.';
}
';
/*--- Event Venue Color - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event.ect-simple-event .cover-view-venue span.ect-venue-details.ect-address,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .cover-view-venue span.ect-venue-details.ect-address a,
#ect-cover-wrapper .ect-cover-event.ect-simple-event span.ect-icon i,
#ect-cover-wrapper .ect-cover-cost,
#ect-cover-wrapper .ect-cover-event.ect-simple-event span.ect-google a.tribe-events-gmap,
#ect-accordion-wrapper .ect-accordion-venue {
     '.$ect_venue_styles.'
}
';
/*--- Event Dates Styles - CSS ---*/
$ect_output_css.='
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-date-area.cover-view-schedule span,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-share-wrapper .ect-social-share-list a,
#ect-cover-wrapper .ect-cover-event span.ev-icon i
{
	color: '.$ect_date_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-readmore a{
	border: 1px solid '.$ect_date_color.';
	color:'.$ect_date_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-left,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-cover-left-bottom
{
	border-left:6px solid '.$ect_date_color.';
}
#ect-cover-wrapper .ect-cover-event.ect-simple-event span.ect-google-map a,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-google-cale a{
	color:'.$ect_date_color.';
	
}
#ect-cover-wrapper .ect-cover-event.ect-simple-event span.ect-google-map,
#ect-cover-wrapper .ect-cover-event.ect-simple-event .ect-google-cale
{
	border:2px solid '.$ect_date_color.';	
}
#ect-cover-wrapper .ect-cover-event .ect-date-area.cover-view-schedule span{
font-family: '.$ect_date_font_family.';
font-weight: '.$ect_date_font_weight.';
font-style: '.$ect_date_font_style.';
line-height: '.$ect_date_line_height.';

}
#ect-cover-wrapper button.ctl-slick-prev,
#ect-cover-wrapper button.ctl-slick-next{
	color: '.$ect_date_color.';
	border:2px solid '.Ecttinycolor($ect_date_color)->darken(32)->toString().';
}
';
$ect_output_css.=' #ect-minimal-list-wrp .ect-share-wrapper .ect-social-share-list a{
     color: '.$main_skin_color.';
}
#ect-minimal-list-wrp .ect-share-wrapper i.ect-icon-share:before {
     background: '.$main_skin_color.';
}';
