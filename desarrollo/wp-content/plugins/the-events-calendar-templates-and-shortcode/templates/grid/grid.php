<?php
// var_dump($cat_colors_attr);
$ev_post_img = '';
if ( isset( $attribute['columns'] ) && $attribute['columns'] > 2 ) {
	$size = 'medium';
} else {
	$size = 'large';
}
$ev_post_img = ect_pro_get_event_image( $event_id, $size = 'large' );
$ect_cate    = ect_display_category( $event_id );
if ( $ect_grid_columns == 2 ) {
	$ect_grid_columns = 'col-md-6';
} elseif ( $ect_grid_columns == 3 ) {
	$ect_grid_columns = 'col-md-4';
} elseif ( $ect_grid_columns == 6 ) {
	$ect_grid_columns = 'col-md-2';
} else {
	$ect_grid_columns = 'col-md-3';
}
  $events_html .= '<div id = "event-' . esc_attr( $event_id ) . '"' . $cat_colors_attr . ' class="ect-grid-event ' . $grid_style . ' ' . $event_type . ' ' . $ect_grid_columns . '" itemscope itemtype="http://schema.org/Event">
                <div class="ect-grid-event-area">
                <meta itemprop="name" content="' . get_the_title( $event_id ) . '">
                <meta itemprop="image" content="' . $ev_post_img . '">
                <meta itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">';
if ( $style != 'style-4' ) {
	$events_html .= '<div class="ect-grid-image">
                <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '">
                <img src="' . $ev_post_img . '" title="' . get_the_title( $event_id ) . '" alt="' . get_the_title( $event_id ) . '">
                </a>';
	if ( $style == 'style-2' ) {
		$events_html .= '<div class="ect-grid-date">
              ' . wp_kses_post( $event_schedule ) . '
              </div>';
	}
	if ( $style == 'style-1' || $style == 'style-2' ) {
		if ( $socialshare == 'yes' ) {
			$events_html .= ect_pro_share_button( $event_id ); }
	}
}
if ( $style == 'style-4' ) {
	$events_html .= '<div class="ect-grid-image-style4">
  <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '">
  <img src="' . $ev_post_img . '" title="' . get_the_title( $event_id ) . '" alt="' . get_the_title( $event_id ) . '">
  </a>';
	$events_html .= '<div class="ect-date-schedule"><div class="ect-date-schedule-wrap">
  ' . $event_schedule . '</div></div>';

	$events_html .= '</div>';
}
if ( $style == 'style-3' || $style == 'style-4' ) {
	if ( ! empty( $ect_cate ) ) {
		$events_html .= '<div class="ect-event-category ect-grid-categories">';
		$events_html .= $ect_cate;
		$events_html .= '</div>';
	}
	$events_html .= '</div>';
	$events_html .= '<div class="ect-grid-date">
' . $event_schedule . '
</div>';
	if ( $socialshare == 'yes' ) {
		$events_html .= ect_pro_share_button( $event_id ); }
} else {
	$events_html .= '</div>';
}
$events_html .= '<div class="ect-grid-content">';
if ( $style == 'style-1' ) {
	$events_html .= '<div class="ect-grid-date">
' . $event_schedule . '
</div>';
}
if ( $style == 'style-2' ) {
	if ( ! empty( $ect_cate ) ) {
		$events_html .= '
    <div class="ect-event-category ect-grid-categories">';
		$events_html .= $ect_cate;
		$events_html .= '</div>';
	}
}
$events_html .= '<div class="ect-grid-title"><h4>' . wp_kses_post( $event_title ) . '</h4></div>';
if ( $style == 'style-1' ) {
	if ( ! empty( $ect_cate ) ) {
		$events_html .= '
    <div class="ect-event-category ect-grid-categories">';
		$events_html .= $ect_cate;
		$events_html .= '</div>';
	}
}
if ( $show_description == 'yes' ) {
	$events_html .= '<div class="ect-grid-description">
  ' . $event_content . '</div>';
}
if ( tribe_has_venue( $event_id ) && $hide_venue != 'yes' ) {
	$events_html .= '<div class="ect-grid-venue">' . wp_kses_post( $venue_details_html ) . '</div>';
}
if ( $style == 'style-2' ) {
	$events_html .= '</div>';
}
if ( tribe_get_cost( $event_id, true ) ) {
	$events_html .= '<div class="ect-grid-footer">';

	$events_html .= '<div class="ect-grid-cost">' . $ev_cost . '</div>';
	$events_html .= '      <div class="ect-grid-readmore">
    <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
    </div></div>';
} else {
	$events_html .= '<div class="ect-grid-footer">';

	$events_html .= '<div class="ect-grid-readmore full-view">
    <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
    </div></div>';
}
  $events_html .= '</div></div>';
if ( $style == 'style-1' || $style == 'style-3' || $style == 'style-4' ) {
	$events_html .= '</div>';
}
