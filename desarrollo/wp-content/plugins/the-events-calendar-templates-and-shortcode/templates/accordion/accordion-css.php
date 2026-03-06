<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used only for dynamic styles in accordion layouts.
 */
switch ( $style ) {

	 /** STYLE-1 */
	case 'style-1':
		$ect_output_css .= '
        #ect-accordion-wrapper .ect-accordion-event.style-1.ect-featured-event{
			border-left-color: ' . $featured_event_skin_color . ';
		}
          #ect-accordion-wrapper .ect-accordion-event.style-1.ect-simple-event{
			border-left-color: ' . $main_skin_color . ';
		}
          ';

		if ( $ect_date_color === '#ffffff' ) {
			 $ect_output_css .= '
               #ect-accordion-wrapper .style-1 .ect-date-area.accordion-view-schedule{
                    color: ' . $ect_title_color . ';
               }    
               ';
		} else {
			 $ect_output_css .= '
               #ect-accordion-wrapper .style-1 .ect-date-area.accordion-view-schedule{
                    color: ' . $ect_date_color . ';
               }    
               ';
		}




		break;

	 /** STYLE-2 */
	case 'style-2':
		 $ect_output_css .= '
          #ect-accordion-wrapper .ect-accordion-event.style-2 .ect-accordion-date{
               background: ' . $main_skin_color . ';
               color:' . $main_skin_alternate_color . ';
          }
          #ect-accordion-wrapper .style-2 .ect-accordion-date span.ev-yr,
		#ect-accordion-wrapper .style-2 .ect-accordion-date span.ev-time,
		#ect-accordion-wrapper .style-2 .ect-accordion-date span.ev-weekday {
			background: ' . Ecttinycolor( $main_skin_color )->darken( 12 )->toString() . ';
		}
          ';
		 /*--- Featured Event Color - CSS ---*/
		 $ect_output_css .= '
          #ect-accordion-wrapper .ect-featured-event.style-2 .ect-accordion-date{
               background: ' . $featured_event_skin_color . ';
               color:' . $featured_event_font_color . ';
          }
          #ect-accordion-wrapper .ect-accordion-event.style-2.ect-featured-event.active-event{
               border-left-color: ' . $featured_event_skin_color . ';
          }
          #ect-accordion-wrapper .ect-accordion-event.style-2.ect-simple-event.active-event{
               border-left-color: ' . $main_skin_color . ';
          }
          #ect-accordion-wrapper .ect-featured-event.style-2 .ect-accordion-date span.ev-yr,
		#ect-accordion-wrapper .ect-featured-event.style-2 .ect-accordion-date span.ev-time,
		#ect-accordion-wrapper .ect-featured-event.style-2 .ect-accordion-date span.ev-weekday {
			background: ' . Ecttinycolor( $featured_event_skin_color )->darken( 12 )->toString() . ';
		}
          ';
		 /*--- Featured Event Font Color - CSS ---*/
		 $ect_output_css .= '
          #ect-accordion-wrapper .ect-featured-event.style-2 .ect-accordion-date{
               color: ' . $featured_event_font_color . ';
          }';

		break;

	 /** STYLE-3 */
	case 'style-3':
		 $ect_output_css .= '
          #ect-accordion-wrapper .ect-simple-event.style-3 {
               border-color: ' . Ecttinycolor( $main_skin_color )->darken( 7 )->toString() . ';
               background: ' . $event_desc_bg_color . ';
          }
          #ect-accordion-wrapper .ect-simple-event.style-3.active-event .ect-accordion-header:after {
               color: ' . Ecttinycolor( $main_skin_color )->darken( 12 )->toString() . ';
          }
		#ect-accordion-wrapper .ect-featured-event.style-3.active-event .ect-share-wrapper i.ect-icon-share:before,
          #ect-accordion-wrapper .ect-featured-event.style-3.active-event .ect-accordion-content a.ect-events-read-more{
               background: ' . $featured_event_skin_color . ';
          }	
          ';

		if ( $ect_date_color === '#ffffff' && $event_desc_bg_color === '#ffffff' ) {
			 $ect_output_css .= '
               #ect-accordion-wrapper .style-3 .ect-date-area.accordion-view-schedule{
                    color: ' . $ect_title_color . ';
               }    
               ';
		} else {
			 $ect_output_css .= '
               #ect-accordion-wrapper .style-3 .ect-date-area.accordion-view-schedule{
                    color: ' . $ect_date_color . ';
               }    
               ';
		}

		 /*--- Featured Event Color - CSS ---*/
		 $ect_output_css .= '
          #ect-accordion-wrapper .ect-featured-event.style-3 {
               
               border-color: ' . Ecttinycolor( $featured_event_skin_color )->lighten( 7 )->toString() . ';
               background-color: ' . $event_desc_bg_color . ';
          }
          #ect-accordion-wrapper .ect-featured-event.style-3.active-event .ect-accordion-header:after {
			color: ' . Ecttinycolor( $featured_event_skin_color )->darken( 12 )->toString() . ';
		}
          #ect-accordion-wrapper .ect-featured-event.active-event .ect-accordion-header:after
	     {
			color: ' . Ecttinycolor( $featured_event_skin_color )->darken( 12 )->toString() . ';
		}
          #ect-accordion-wrapper .ect-featured-event .ect-accordion-header:after
	     {
			color: ' . Ecttinycolor( $featured_event_skin_color )->darken( 12 )->toString() . ';
		}
          ';
		break;
	default:
		$ect_output_css .= '#ect-accordion-wrapper.ect-accordion-view.style-4 span.month-year-box {
	color: ' . $main_skin_color . ';
     }
     ';
		break;
}

/**Commomn Css */

/*--- Main Skin Color - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-header:after{
	color: ' . $main_skin_color . ';
}
#ect-accordion-wrapper .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $main_skin_color . ';
}
';
/*--- Main Skin Alternate Color - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper .ect-share-wrapper i.ect-icon-share:before{
     color: ' . $main_skin_alternate_color . ';
}
';
/*--- button background Color - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-event.ect-featured-event .ect-accordion-footer .ect-accordion-content a{
     background: ' . $featured_event_skin_color . ';
     color: ' . $featured_event_font_color . ';
}
#ect-accordion-wrapper .ect-accordion-event.ect-simple-event .ect-accordion-footer .ect-accordion-content a{
     background: ' . $main_skin_color . ';
     color: ' . $main_skin_alternate_color . ';
}
';
/*--- Event Title - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper h3.ect-accordion-title {
    ' . $title_styles . ';
}
';
/*--- Event Description - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-content,
#ect-accordion-wrapper .ect-accordion-content p
{
	' . $ect_desc_styles . ';
}

#ect-accordion-wrapper .ect-accordion-date-full {
	color:' . Ecttinycolor( $ect_desc_color )->darken( 5 )->toString() . ';
}
#ect-accordion-wrapper .ect-accordion-cost,
#ect-accordion-wrapper .ect-accordion-cost .tribe-tickets-left{
     color:' . $ect_title_color . ';
}
';
/*--- Event Venue Color - CSS ---*/
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-venue {
     ' . $ect_venue_styles . '
}
#ect-accordion-wrapper .ect-accordion-venue .ect-icon {
     font-size:' . $venue_font_size . ';
}
#ect-accordion-wrapper .ect-accordion-venue .ect-google a {
     color: ' . Ecttinycolor( $ect_venue_color )->darken( 5 )->toString() . ';
}
';
/*--- Event Dates Styles - CSS ---*/
if ( $ect_date_font_size > '30' ) {
	 $ect_date_font_size = '30';
}
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-date,
#ect-accordion-wrapper.ect-accordion-view span.month-year-box{
     ' . $ect_date_style . ';
}
#ect-accordion-wrapper .ect-accordion-date span{
     font-size : ' . $ect_date_font_size . 'px;
}
';
/**------------------------------------Share css------------------------------ */
$ect_output_css .= '#ect-accordion-wrapper .ect-accordion-event.ect-featured-event .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $featured_event_skin_color . ';
}
#ect-accordion-wrapper .ect-accordion-event.ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $main_skin_color . ';
}
#ect-accordion-wrapper  .ect-accordion-event.ect-featured-event .ect-share-wrapper .ect-social-share-list a:hover{
     color: ' . $featured_event_skin_color . ';
}
#ect-accordion-wrapper .ect-accordion-event.ect-simple-event .ect-share-wrapper .ect-social-share-list a:hover{
   color: ' . $main_skin_color . ';
}';

$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-event.ect-simple-event.active-event{
     background-color: ' . $event_desc_bg_color . ';
}
';
$ect_output_css .= '
#ect-accordion-wrapper .ect-accordion-event.ect-featured-event.active-event{
background-color: ' . $event_desc_bg_color . ';
}
';
