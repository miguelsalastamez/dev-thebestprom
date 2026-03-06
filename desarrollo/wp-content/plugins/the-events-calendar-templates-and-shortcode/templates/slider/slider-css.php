<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used only for dynamic styles in slider layouts.
 */
// Default List Main Skin Color
switch ( $style ) {
	case 'style-1':
		/*--- Featured Event Color - CSS ---*/
		$ect_output_css .= '
    #ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-event-area {
	 background: ' . $thisPlugin::ect_hex2rgba( $featured_event_skin_color, .94 ) . ';   
    }';
		if ( $event_desc_bg_color === '#ffffff' ) {
			$ect_output_css .= '#ect-slider-wrapper .ect-simple-event.style-1 .ect-slider-event-area {
			background-color:' . Ecttinycolor( $main_skin_color )->lighten( 40 )->toString() . ';
		   }	
		  ';
		} else {
			$ect_output_css .= '#ect-slider-wrapper .ect-simple-event.style-1 .ect-slider-event-area {
			background-color:' . $event_desc_bg_color . ';
		   }
		  ';
		}

		/*--- Featured Event Font Color - CSS ---*/
		$ect_output_css .= '
     #ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-title h4,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-title h4 a,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-venue,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-cost,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-cost .ect-ticket-info span,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-date-area.slider-view-schedule span,
	#ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-description .ect-event-content p{
          color: ' . $featured_event_font_color . ';
     }

	 #ect-slider-wrapper .ect-featured-event.style-1 .ect-event-category ul.tribe_events_cat li a{
		color: ' . $featured_event_font_color . ';
		border-color: ' . $featured_event_font_color . ';
	 }
     #ect-slider-wrapper .ect-featured-event.style-1 .ect-slider-venue a{
      color: ' . Ecttinycolor( $featured_event_font_color )->darken( 5 )->toString() . ';
     }';
		break;

	case 'style-2':
		/*--- Event Background Color - CSS ---*/
			$ect_output_css .= '
			#ect-slider-wrapper .style-2 .ect-slider-left{
				background-color: ' . $event_desc_bg_color . ';
			}
			#ect-slider-wrapper .ect-featured-event.style-2 .ect-slider-datearea .ect-date-area span{
				color: ' . $featured_event_skin_color . ';
			}';
		break;

	case 'style-3':
		/*--- Main Skin Color - CSS ---*/
		$ect_output_css .= '
		#ect-slider-wrapper .style-3 .ect-slider-left {
		background-color: ' . $event_desc_bg_color . ' ;
		}
		';
		break;
}
	/*--- Event Background Color - CSS ---*/
	$ect_output_css .= '
     #ect-slider-wrapper .ect-slider-event-area{
          background-color: ' . $event_desc_bg_color . ';
     }
	#ect-slider-wrapper .ect-events-slider .slick-arrow i {
		background-color: ' . $event_desc_bg_color . ';
		box-shadow: 2px 2px 0px 1px ' . Ecttinycolor( $event_desc_bg_color )->darken( 1 )->toString() . ';
	}
    ';
	/*--- Event Title - CSS ---*/
	 $ect_output_css .= '
    	#ect-slider-wrapper .ect-slider-title h4,
	#ect-slider-wrapper .ect-slider-title h4 a{
        ' . $title_styles . ';
     }
    ';
	/*
	--- Event Description - CSS ---*/
  $ect_output_css .= '
  #ect-slider-wrapper .ect-slider-description .ect-event-content p{
  ' . $ect_desc_styles . ';
  }
  #ect-slider-wrapper .ect-events-slider .slick-arrow {
	color: ' . $ect_desc_color . ';
	}
  ';
/*--- Event Venue Style - CSS ---*/
	$ect_output_css .= '#ect-slider-wrapper .ect-slider-venue{
     ' . $ect_venue_styles . ';
    }
    #ect-slider-wrapper .ect-slider-cost,
	#ect-slider-wrapper .ect-slider-cost .ect-ticket-info span {
		color:' . $ect_title_color . ';
		font-size:' . $ect_title_font_size . 'px;
		font-family: ' . $ect_title_font_famiily . ';

	}
	#ect-slider-wrapper .ect-slider-venue a{
		color: ' . Ecttinycolor( $ect_venue_color )->darken( 6 )->toString() . ';
		font-family: ' . $ect_venue_font_famiily . ';
	}
    ';
	/*--- Event Dates Styles - CSS ---*/



	$ect_output_css .= '#ect-slider-wrapper .ect-slider-datearea .ect-slider-date-full		
	{ color: ' . $ect_desc_color . '}
#ect-slider-wrapper .ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $main_skin_color . ';
}
#ect-slider-wrapper .ect-featured-event .ect-share-wrapper i.ect-icon-share:before {
	background: ' . $featured_event_skin_color . ';
}
#ect-slider-wrapper .ect-simple-event .ect-share-wrapper .ect-social-share-list a:hover {
	color: ' . $main_skin_color . ';
}
#ect-slider-wrapper .ect-featured-event .ect-share-wrapper .ect-social-share-list a:hover {
	color: ' . $featured_event_skin_color . ';
}
';
$ect_output_css     .= '
#ect-slider-wrapper .ect-slider-date,
#ect-slider-wrapper .ect-date-area span{
     ' . $ect_date_style . ';
}
';
if ( $ect_date_styles['font-size'] > '20' ) {
	 $ect_output_css .= '
          #ect-slider-wrapper .ect-slider-date,
          #ect-slider-wrapper .ect-date-area span{
               font-size:20px;
          }
          ';
}

	$ect_output_css .= '
  #ect-slider-wrapper .ect-simple-event .ect-date-area.slider-view-schedule span{
    color: ' . $main_skin_color . ';
  }
  #ect-slider-wrapper .ect-featured-event:not(.style-1) .ect-date-area.slider-view-schedule span{
    color: ' . $featured_event_skin_color . ';
  }
  ';

 $ect_output_css .= '
#ect-slider-wrapper .ect-featured-event .ect-slider-readmore a{
  background: ' . $featured_event_skin_color . ';   
  color: ' . $featured_event_font_color . ' !important;
}
#ect-slider-wrapper .ect-simple-event .ect-slider-readmore a{
  background: ' . $main_skin_color . ';   
  color: ' . $main_skin_alternate_color . ' !important;
}
';

/**------------------------------------Share css------------------------------ */
$ect_output_css .= '
 .ect-slider-event.ect-featured-event .ect-share-wrapper .ect-social-share-list a:hover{
     color: ' . $featured_event_skin_color . ';
 }
 .ect-slider-event.ect-simple-event .ect-share-wrapper .ect-social-share-list a:hover{
     color: ' . $main_skin_color . ';
 }';
