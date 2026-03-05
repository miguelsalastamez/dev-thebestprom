<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used only for dynamic styles in list layouts.
 */
switch ( $style ) {
	 /** STYLE-1 */
	case 'style-1':
		 /*--- Main Skin Color - CSS ---*/
		if ( $event_desc_bg_color === '#ffffff' ) {
			$ect_output_css .= '
               .ect-list-post.style-1.ect-simple-event .ect-list-post-right .ect-list-venue{
                    background: ' . Ecttinycolor( $main_skin_color )->lighten( 40 )->toString() . '; 
                    border: 1px solid;
                    border-color: ' . $main_skin_color . ';
                    
               }';
		}
		   $ect_output_css .= '.ect-list-post.style-1 .ect-list-post-left .ect-list-date {
               background: ' . $thisPlugin::ect_hex2rgba( $main_skin_color, .96 ) . ';
          }';


		 /*--- Featured Event Color - CSS ---*/
		if ( $event_desc_bg_color === '#ffffff' ) {
			$ect_output_css .= '
          .ect-list-post.ect-featured-event.style-1 .ect-list-post-right .ect-list-venue{
               background: ' . Ecttinycolor( $featured_event_skin_color )->lighten( 40 )->toString() . '; 
               border: 1px solid;
               border-color: ' . $featured_event_skin_color . ';
          }';
		}
		   $ect_output_css .= '   .ect-list-post.style-1.ect-featured-event .ect-list-post-left .ect-list-date {
               background: ' . $thisPlugin::ect_hex2rgba( $featured_event_skin_color, .85 ) . ';
          }
          ';
		 /*--- Event Title - CSS ---*/
		 $ect_output_css .= '
          .ect-list-posts.style-1 .ect-events-title a.ect-event-url{
               ' . $title_styles . ';
          }
          ';
		break;
	 /** STYLE-2 */
	case 'style-2':
		 /*--- Main Skin Color - CSS ---*/
		 $ect_output_css .= '.ect-list-post.style-2 .modern-list-right-side{
               background: ' . $main_skin_color . ';
               
          }
          ';
		  /*--- Featured Event Color - CSS ---*/
		 $ect_output_css .= '
          .ect-list-post.ect-featured-event.style-2 .modern-list-right-side{
               background: ' . $featured_event_skin_color . '; 
               
          }
          ';
		  /*--- Event Title - CSS ---*/
		 $ect_output_css .= '.ect-list-posts.style-2 .ect-events-title a.ect-event-url{
               ' . $title_styles . ';
          }
           .modern-list-right-side .ect-list-date .ect-date-area{
                    ' . $ect_date_style . ';
               }
          ';
		break;
	 /** STYLE-3 */
	case 'style-3':
		/*--- Main Skin Color - CSS ---*/
		$ect_output_css .= '.ect-list-post.style-3.ect-simple-event .ect-list-date{
          background: ' . $main_skin_color . ';
     }
     ';
		/*
		 Main Skin Alternate Color - CSS ---*/
		  // for share icon in style3
		$ect_output_css .= '
     .ect-list-post.style-3.ect-simple-event .ect-clslist-event-details a:hover{
          color: ' . $main_skin_alternate_color . ';
     }
     
     ';
		/*--- Featured Event Skin Color/Font Color - CSS ---*/
		$ect_output_css .= '
          .ect-list-post.ect-featured-event.style-3 .ect-list-date{
               background: ' . $featured_event_skin_color . '; 
               color: ' . $featured_event_font_color . ';
	     }
     ';
		/*--- Event Description - CSS ---*/
		$ect_output_css .= '
     .ect-list-post .ect-style3-desc .ect-event-content p{
          ' . $ect_desc_styles . ';
     }
     ';
		$ect_output_css .= '
     .style-3 .ect-list-date .ect-date-area{
          ' . $ect_date_style . ';
     }';


		break;
	default:
		/*--- Main Skin Color - CSS ---*/
		$ect_output_css .= '.ect-list-post.style-4 .ect-li st-schedule,
          .ect-list-post .style-4 .ect-list-schedule-wrap{
               background: ' . $main_skin_color . ';
          }';
		/*--- Featured Event Color - CSS ---*/
		$ect_output_css .= '.ect-list-post.ect-featured-event.style-4 .ect-list-schedule,
     .ect-list-post.ect-featured-event.style-4 .ect-list-schedule-wrap{
          border-color:' . Ecttinycolor( $featured_event_skin_color )->darken( 7 )->toString() . ';
          background: ' . $featured_event_skin_color . ';
     }
     ';
		/*--- Featured Event Font Color - CSS ---*/
		$ect_output_css .= '
     .ect-list-post.ect-featured-event.style-4 .ect-list-schedule-wrap span{
          color: ' . $featured_event_font_color . ';
     }
     ';
		// Date
		$ect_output_css .= '.ect-list-post.style-4 .ect-list-schedule-wrap span {
          ' . $ect_date_style . ';
     }
     .ect-list-post.style-4 .ect-list-schedule-wrap,.ect-list-post.style-4 .ect-list-schedule,.ect-slider-event.style-4 .ect-date-schedule{
          border-color:  ' . $ect_date_color . ';
     }
     ';
		break;
}
// Common Css For all list styles
/*--- Main Skin Color - CSS ---*/
$ect_output_css .= '.ect-list-post .ect-list-img {
     background-color: ' . Ecttinycolor( $main_skin_color )->darken( 3 )->toString() . ';
}

';
/*--- Main Skin Alternate Color - CSS ---*/
$ect_output_css .= '
.ect-list-post.ect-simple-event .ect-list-date .ect-date-area,
.ect-list-post.ect-simple-event .ect-list-date span.ect-custom-schedule,
.ect-list-post.ect-simple-event .ect-list-post-left .ect-list-date .ect-date-area,
.ect-list-post.ect-simple-event .ect-list-post-left .ect-list-date span.ect-custom-schedule{
     color: ' . $main_skin_alternate_color . ';
}
';
/*--- Featured Event Color - CSS ---*/
$ect_output_css .= '
.ect-list-post.ect-featured-event .ect-list-img {
     background-color: ' . Ecttinycolor( $featured_event_skin_color )->lighten( 3 )->toString() . ';
}
';
/*--- Featured Event Font Color - CSS ---*/
$ect_output_css .= '
#ect-events-list-content .ect-list-post.ect-featured-event .ect-list-date .ect-date-area,
.ect-list-post.ect-featured-event .ect-list-date span.ect-custom-schedule,
.ect-list-post.ect-featured-event .ect-list-post-left .ect-list-date .ect-date-area
{
     color: ' . $featured_event_font_color . ';
}
';
/*--- Event Background Color - CSS ---*/
$ect_output_css .= '
.ect-list-post{
     background: ' . $event_desc_bg_color . ';
}
';
/*--- Event Title - CSS ---*/
$readmoresize    = $ect_desc_style['font-size'];
$ect_output_css .= '.ect-list-post h2.ect-list-title,
     .ect-list-post h2.ect-list-title a.ect-event-url{
          ' . $title_styles . ';
}
.ect-list-post h2.ect-list-title a:hover {
    color: ' . Ecttinycolor( $ect_title_color )->darken( 10 )->toString() . ';

}
.ect-list-post .ect-rate-area span.ect-icon,
.ect-list-post .ect-rate-area span.ect-rate-icon,
.ect-list-post span.ect-rate-icon,
.ect-list-post .ect-rate-area span.ect-rate,
.ect-list-post .ect-rate-area .ect-ticket-info span,
.ect-list-post .ect-events-read-more {
	color: ' . $ect_title_color . ';
     font-size: ' . $readmoresize . '"px";
}

.ect-list-post .ect-events-read-more:hover {
     color: ' . $ect_desc_color . ';
}
';
/*--- Event Description - CSS ---*/
$ect_output_css .= '

.ect-list-post .ect-list-post-right .ect-list-description .ect-event-content p{
     ' . $ect_desc_styles . ';
}
.ect-list-description .ect-clslist-time{
     ' . $ect_venue_styles . ';
}
';

/*--- Event Venue Color - CSS ---*/
$ect_output_css .= '.ect-list-post .ect-list-venue .ect-venue-details,
     .ect-list-post .ect-list-venue .ect-google a{
               ' . $ect_venue_styles . ';
     }
     .ect-list-post .ect-list-venue .ect-icon {
          color:' . $ect_venue_color . ';
          font-size:' . $venue_font_size . ';
     }
     .ect-list-post .ect-list-venue .ect-google a{
          color: ' . Ecttinycolor( $ect_venue_color )->darken( 3 )->toString() . ';
     }';
if ( $main_skin_alternate_color !== '' ) {
	 $ect_output_css .= '   .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-venue-details,
               .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-icon,
               .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-google a{
                    color: ' . $main_skin_alternate_color . ';
               }';
} else {
	 $ect_output_css .= '   .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-venue-details,
               .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-icon,
               .ect-list-post.style-2.ect-simple-event .ect-list-venue .ect-google a{
                    ' . $ect_venue_styles . ';
               }';
}

	  $ect_output_css .= '     .ect-list-post.style-2.ect-featured-event .ect-list-venue .ect-venue-details,
                    .ect-list-post.style-2.ect-featured-event .ect-list-venue .ect-icon,
          .ect-list-post.style-2.ect-featured-event .ect-list-venue .ect-google a{
               ' . $ect_venue_styles . ';
               color: ' . $featured_event_font_color . ';
          }
          ';
// Date
$ect_output_css .= '.ect-list-post .ect-list-post-left .ect-list-date .ect-date-area,
     .ect-list-post .ect-list-post-left .ect-list-date span.ect-custom-schedule{
          ' . $ect_date_style . ';
     }
     
#ect-events-list-content .ect-list-post.ect-simple-event .ect-share-wrapper .ect-social-share-list a:hover{
	color: ' . $main_skin_color . ';
}
#ect-events-list-content .ect-list-post.ect-featured-event .ect-share-wrapper .ect-social-share-list a:hover{
	color: ' . $featured_event_skin_color . ';
}

#ect-events-list-content .ect-list-post:not(.style-2).ect-featured-event .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $featured_event_font_color . ';
     color: ' . $featured_event_skin_color . ';
}';
if ( $main_skin_alternate_color != '' ) {
	 $ect_output_css .= '#ect-events-list-content .ect-list-post:not(.style-2).ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
          background: ' . $main_skin_alternate_color . ';
          color: ' . $main_skin_color . ';
     }
     ';
} else {
	 $ect_output_css .= '#ect-events-list-content .ect-list-post:not(.style-2).ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
          background: #ffffff;
          color: ' . $main_skin_color . ';
     }
     ';
}
