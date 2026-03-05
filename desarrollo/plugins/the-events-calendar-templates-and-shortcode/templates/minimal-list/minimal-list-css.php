<?php
if ( !defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * This file is used only for dynamic styles in minimal layouts.
 */
// Silence is golden.
switch($style)
{
    case "style-1":
                //Minimal List Main Skin Color
        // $ect_output_css .= '#ect-minimal-list-wrp .style-1 span.ect-minimal-list-time{
        //     color:#ff112d;
        // }';
        
        // Minimal List Featured Skin Color
        $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-1.ect-featured-event{
            // border-left: 3px solid '.$featured_event_skin_color.';
            border: 1px solid '.$featured_event_skin_color.';
        }
        #ect-minimal-list-wrp .ect-list-posts.style-1.ect-simple-event{
          
            border: 1px solid '.$main_skin_color.';
        }
        .ect-minimal-list-wrapper .ect-list-posts.style-1.ect-featured-event{
           //  border-left: 2px solid '.$featured_event_skin_color.' !important;
        }
        // #ect-minimal-list-wrp .style-1.ect-featured-event .ect-style-1-more a,
        // #ect-minimal-list-wrp .style-1.ect-featured-event .ect-event-datetime .ect-icon-clock,
        // #ect-minimal-list-wrp .style-1.ect-featured-event .ect-event-datetime span{
        //     color:'.$featured_event_skin_color.';
        // }
        ';
        // if($featured_event_font_color === '#ffffff'){
        //     $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-1.ect-featured-event .ect-event-datetimes span,
        //     #ect-minimal-list-wrp .style-1.ect-featured-event span.ect-minimal-list-time,
        //     #ect-minimal-list-wrp .style-1.ect-featured-event .ect-event-datetime .ect-icon-clock{
        //         color: #383838;
                
        //     } ';
        // }else{
        //     $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-1.ect-featured-event .ect-event-datetimes span,
        //     #ect-minimal-list-wrp .style-1.ect-featured-event span.ect-minimal-list-time,
        //     #ect-minimal-list-wrp .style-1.ect-featured-event .ect-event-datetime .ect-icon-clock{
        //         color:'.$featured_event_font_color.';
        //     } ';
        // }
        $ect_output_css .='.ect-list-posts.style-1.ect-featured-event .ect-event-date-tag{
            color: '.$featured_event_skin_color.';
        }
        .ect-list-posts.style-1.ect-simple-event .ect-event-date-tag{
            color: '.$main_skin_color.';
        }';
        
       

        //Title styles in minimal layouts
        $ect_output_css .='#ect-minimal-list-wrp .style-1 .ect-events-title a{
            '.$title_styles.'
        }#ect-minimal-list-wrp .style-1 .ect-style-1-more a,#ect-minimal-list-wrp .style-1 .ect-read-more a{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }';
        // $ect_output_css .='
        // #ect-minimal-list-wrp .style-1.ect-simple-event .ect-style-1-more a,
        // #ect-minimal-list-wrp .style-1.ect-simple-event .ect-event-datetime .ect-icon-clock{
        //     color:'.$ect_date_color.';
        // }';
        $ect_output_css .=' #ect-minimal-list-wrp .ect-list-posts.style-1 .ect-event-datetimes span,
        #ect-minimal-list-wrp .style-1 span.ect-minimal-list-time{
            font-family: '.$ect_date_font_family.';
            // color: '.$ect_date_color.';
            font-style:'.$ect_date_font_style.';
            line-height:'.$ect_date_line_height.';
        }

        #ect-minimal-list-wrp .style-1 .ect-event-datetime{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }
        ';
                break;
                case "style-2":
                    //Minimal List Main Skin Color
        //             $ect_output_css .= '#ect-minimal-list-wrp .style-2.ect-simple-event .ect-schedule-wrp{
        //     background:'.Ecttinycolor($main_skin_color)->lighten(10)->toString().';
        // }

        // #ect-minimal-list-wrp .style-2.ect-featured-event span.ect-date-viewport,#ect-minimal-list-wrp .style-2.ect-featured-event .ect-schedule-wrp {

        //     background: '.Ecttinycolor($featured_event_skin_color)->lighten(10)->toString().'; 
        // }';
        // $ect_output_css .='#ect-minimal-list-wrp .ect-month-header{
        //     border-bottom-color: '.Ecttinycolor($main_skin_color)->darken(10)->toString().';
        // }';
        $ect_output_css .='#ect-minimal-list-wrp .style-2 span.ect-event-title a{
            '.$title_styles.'
        }
        #ect-minimal-list-wrp .style-2 .ect-style-2-more a{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }';
        $ect_output_css .='.ect-list-posts.style-2.ect-featured-event .ect-event-date-tag{
            color: '.$featured_event_skin_color.';
        }
        .ect-list-posts.style-2.ect-simple-event .ect-event-date-tag{
            color: '.$main_skin_color.';
        }';
      
        //Title styles in minimal layouts
        $ect_output_css .='
        .ect-list-posts.style-2 .ect-events-title a.ect-event-url,
        #ect-minimal-list-wrp .style-2 span.ect-event-title a{
            '.$title_styles.'
        }#ect-minimal-list-wrp .style-2 .ect-style-2-more a{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }
       
        #ect-minimal-list-wrp .style-2.ect-simple-event span.ect-date-viewport,
        #ect-minimal-list-wrp .style-2.ect-simple-event .ect-schedule-wrp
        {
            // color:'.$ect_date_color.';
            font-family:'.$ect_date_font_family.';
        }

        ';
        $ect_output_css .='#ect-minimal-list-wrp .style-2 .minimal-list-venue span,
        #ect-minimal-list-wrp .style-2 span.ect-google a {
            '.$ect_venue_styles.'
        }';
                    break;
                    case "style-3":
                        $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event{
            border-left-color: '.Ecttinycolor($main_skin_color)->lighten(2)->toString().';
        }';
        //  $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3{
        //      border-top-color:'.Ecttinycolor($main_skin_color)->lighten(2)->toString().';
        //      border-right: '.Ecttinycolor($main_skin_color)->lighten(2)->toString().';
        //  }
        //  #ect-minimal-list-wrp .ect-list-posts.style-3:last-child{
        //     border-bottom-color:'.Ecttinycolor($main_skin_color)->lighten(2)->toString().';
        //  }';
        
        $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-featured-event{
            border-left: 4px solid '.$featured_event_skin_color.';
        }';
        $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event{
            border-left: 4px solid '.$main_skin_color.';
        }';
        // if(Ecttinycolor($featured_event_skin_color)->lighten(37)->toString() == '#ffffff'){
        //     $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-featured-event .ect-event-date-tag{
        //         background: '.Ecttinycolor($featured_event_skin_color)->lighten(20)->toString().';
        //     }';
        // }
        // else{
            $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-featured-event .ect-event-date-tag{
                background: '.Ecttinycolor($featured_event_skin_color)->lighten(20)->toString().';
            }';
      //  }
        // if(Ecttinycolor($main_skin_color)->lighten(37)->toString() == '#ffffff'){
        //     $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event .ect-event-date-tag{
        //         background: '.Ecttinycolor($main_skin_color)->lighten(17)->toString().';
        //     }';
        // }
        // else{
            $ect_output_css .='#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event .ect-event-date-tag{
                background: '.Ecttinycolor($main_skin_color)->lighten(17)->toString().';
            }';
        // }
       
        $ect_output_css .=' #ect-minimal-list-wrp .style-3 .ect-events-title a{
            '.$title_styles.'
        }#ect-minimal-list-wrp .style-3 .ect-rate-area{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }';
        //Title styles in minimal layouts
        $ect_output_css .=' #ect-minimal-list-wrp .style-3 .ect-events-title a{
            '.$title_styles.'
        }#ect-minimal-list-wrp .style-3 .ect-rate-area{
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
        }';
        //Minimal List Venue style
   
        $ect_output_css .='

        #ect-minimal-list-wrp .style-3 .ect-style-3-more a{
            color:'.$ect_date_color.';
        }
        #ect-minimal-list-wrp .style-3 .ect-event-datetime{
            font-family: '.$ect_date_font_family.';
            color: '.Ecttinycolor($ect_title_color)->lighten(10)->toString().';
            font-style:'.$ect_date_font_style.';
            line-height:'.$ect_date_line_height.';
        }
        ';
        break;
}

        // Global Color
        // Minimal List Featured Event Font Color
        // $ect_output_css .='.ect-featured-event .ect-event-date span.ect-date-viewport
        // {
        //     color: '.$featured_event_font_color.';
        // }
        // #ect-minimal-list-wrp .ect-featured-event .ect-event-date span.ect-month {
        //     color: '.Ecttinycolor($featured_event_font_color)->darken(10)->toString().'; 
        // }';
        //Not apply css in event bg color on this layout
        //Minimal List Date Style
        $ect_output_css .='
      
        #ect-minimal-list-wrp .style-3 .ect-event-datetimes span.ev-mo,
        #ect-minimal-list-wrp .style-3 .ect-event-datetimes{
             color:'.$ect_date_color.';
        }
        #ect-minimal-list-wrp .ect-share-wrapper .ect-social-share-list a{
	color: '.$main_skin_color.';
}
#ect-minimal-list-wrp .ect-share-wrapper i.ect-icon-share:before {
	background: '.$main_skin_color.';
}';