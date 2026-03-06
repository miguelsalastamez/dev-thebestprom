<?php
$ect_output_css.="
.ect-event.ect-featured-event,
.ect-event.ect-featured-event .ect-time,
.ect-event.ect-featured-event .ect-title
{
    background: $featured_event_skin_color; 
    color:$featured_event_font_color;
}
.ect-event.ect-simple-event{
    background-color: $main_skin_color; 
    color: $main_skin_alternate_color; 
}
#ect-weekly-events-wrapper{
    background-color:$event_desc_bg_color; 
}
h2.ect-week, .ect-week-day .ect-calendar,
.ect-week-nav button.ect-prev i,
.ect-week-nav button.ect-next i{
    font-size:".($ect_date_font_size-12)."px;
    color:$ect_date_color;
    font-family:$ect_date_font_family;
    font-weight:$ect_date_font_weight;
    font-style:$ect_date_font_style;
}
.ect-week-nav button.ect-prev,
.ect-week-nav button.ect-next{
    background-color:$main_skin_alternate_color;
}
.ect-week-day .ect-title
{
    $title_styles
}
.ect-week-day .ect-time{    
    font-size : ".($ect_date_font_size/2)."px;
    color:$ect_date_color;
    font-family:$ect_date_font_family;
    font-weight:$ect_date_font_weight;
    font-style:$ect_date_font_style;
}
";