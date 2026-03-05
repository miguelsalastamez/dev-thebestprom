<?php
$ev_post_img = '';
$size        = 'medium';
$ev_post_img = ect_pro_get_event_image( $event_id, $size = 'large' );
$ect_cate    = ect_display_category( $event_id );

if ( $ect_grid_columns == 2 ) {
	$ect_grid_columns_cls = 'col-md-6';
} elseif ( $ect_grid_columns == 3 ) {
	$ect_grid_columns_cls = 'col-md-4';
} elseif ( $ect_grid_columns == 6 ) {
	$ect_grid_columns_cls = 'col-md-2';
} else {
	$ect_grid_columns_cls = 'col-md-3';
}

/**
 * Get Category Name
 */
$ect_event_in = array();
$ect_get_cat  = get_the_terms( $event_id, 'tribe_events_cat' );
if ( $ect_get_cat && ! is_wp_error( $ect_get_cat ) ) {
	foreach ( $ect_get_cat as $ect_slug_name ) {
		$ect_event_in[] = $ect_slug_name->slug;
	}
} else {
	$ect_cat_name = '';
}

$eventallFilters = implode( ',', $ect_event_in );

$events_html .= '<div 
id = "event-' . esc_attr( $event_id ) . '"' . $cat_colors_attr . ' 
class="ect-grid-event ' . $grid_style . ' ' . $event_type . ' ' . $ect_grid_columns_cls . '" 
data-filter="' . $eventallFilters . '"  
itemscope itemtype="http://schema.org/Event">
<meta itemprop="name" content="' . get_the_title( $event_id ) . '">
<meta itemprop="image" content="' . $ev_post_img . '">
<meta itemprop="description" content="' . esc_attr( wp_strip_all_tags( tribe_events_get_the_excerpt( $event_id ), true ) ) . '">
<div class="ect-grid-event-area">';

if ( $grid_style == 'style-4' ) {
	$events_html .= '<div class="ect-grid-image-style4">
  <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '">
  <img src="' . $ev_post_img . '" title="' . get_the_title( $event_id ) . '" alt="' . get_the_title( $event_id ) . '">
  </a>';
	if ( $socialshare == 'yes' ) {
		$events_html .=
		'<div class="ect-share-wrapper-' . $grid_style . '">
    ' . ect_pro_share_button( $event_id );
		$events_html .= '</div>';
	}

	$events_html .= '<div class="ect-date-schedule"><div class="ect-date-schedule-wrap">
' . wp_kses_post( $event_schedule ) . '</div></div>';

	$events_html .= '</div>';

} else {
	$events_html .= '<div class="ect-grid-image">
  <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '">
  <img src="' . $ev_post_img . '" title="' . get_the_title( $event_id ) . '" alt="' . get_the_title( $event_id ) . '">
  </a>';
	if ( $grid_style == 'style-1' ) {
		if ( $socialshare == 'yes' ) {
			$events_html .= ect_pro_share_button( $event_id ); }
	} else {
		if ( $grid_style == 'style-3' ) {
			if ( ! empty( $ect_cate ) ) {
				$events_html .= '<div class="ect-event-category ect-grid-categories">';
				$events_html .= wp_kses_post( $ect_cate );
				$events_html .= '</div>';
			}
			$events_html .= '</div>';
		}
		$events_html .= '<div class="ect-grid-date">
        ' . wp_kses_post( $event_schedule ) . '
        </div>';
		if ( $socialshare == 'yes' ) {
			$events_html .= ect_pro_share_button( $event_id ); }
	}
	if ( $grid_style != 'style-3' ) {
		$events_html .= '</div>';
	}
	$events_html .= '<div class="ect-grid-content">';
	if ( $grid_style == 'style-1' ) {
		$events_html .= '<div class="ect-grid-date">
    ' . wp_kses_post( $event_schedule ) . '
    </div>';
	}
	if ( $grid_style == 'style-2' ) {
		if ( ! empty( $ect_cate ) ) {
			$events_html .= '<div class="ect-event-category ect-grid-categories">';
			$events_html .= wp_kses_post( $ect_cate );
			$events_html .= '</div>';
		}
	}
	$events_html .= '<div class="ect-grid-title"><h4>' . $event_title . '</h4></div>';
	if ( $grid_style == 'style-1' ) {
		if ( ! empty( $ect_cate ) ) {
			$events_html .= '<div class="ect-event-category ect-grid-categories">';
			$events_html .= wp_kses_post( $ect_cate );
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
	if ( $grid_style == 'style-2' ) {
		$events_html .= '</div>';
	}
	if ( tribe_get_cost( $event_id, true ) ) {
		$events_html .= '<div class="ect-grid-footer"><div class="ect-grid-cost">' . $ev_cost . '</div>
    <div class="ect-grid-readmore">
    <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
    </div></div>';
	} else {
		$events_html .= '<div class="ect-grid-footer"><div class="ect-grid-readmore full-view">
    <a href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" title="' . get_the_title( $event_id ) . '" rel="bookmark">' . $events_more_info_text . '</a>
    </div></div>';
	}
	if ( $grid_style != 'style-2' ) {
		$events_html .= '</div>';
	}
}
$events_html .= '</div></div>';
