<?php 

   	// generate events dates html
function ect_event_schedule($event_id,$date_format,$template){
		/*Date Format START*/
		$event_schedule='';

		$ev_time=ect_tribe_event_time($event_id,false);
		if($date_format=="DM") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="MD") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M') ).'</span>
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd') ).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="FD") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd') ).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="DF") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="FD,Y") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).', </span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="MD,Y") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule"  itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).', </span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="MD,YT") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).', </span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							<span class="ev-time"><span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> '.$ev_time.'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="jMl") {
			$event_schedule='<div class="ect-date-area '.$template.'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'j' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							<span class="ev-weekday">'.esc_html(tribe_get_start_date($event_id, false, 'l') ).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="full") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							<span class="ev-time"><span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> '.$ev_time.'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="d.FY") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd') ).'. </span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="d.F") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'. </span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="d.Ml") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'. </span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'l' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="ldF") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'l' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="Mdl") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'M' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'l' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		else if($date_format=="dFT") {
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-time"><span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> '.$ev_time.'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		elseif($date_format=="custom"){
			$event_schedule = '<span class="ect-custom-schedule">'.tribe_events_event_schedule_details($event_id).'</span>';
			}
		else {
		
			$event_schedule='<div class="ect-date-area '.esc_attr($template).'-schedule" itemprop="startDate" content="'.tribe_get_start_date($event_id, false, 'Y-m-dTg:i').'">
							<span class="ev-day">'.esc_html(tribe_get_start_date($event_id, false, 'd' )).'</span>
							<span class="ev-mo">'.esc_html(tribe_get_start_date($event_id, false, 'F' )).'</span>
							<span class="ev-yr">'.esc_html(tribe_get_start_date($event_id, false, 'Y' )).'</span>
							</div>
							<meta itemprop="endDate" content="'.tribe_get_end_date($event_id, false, 'Y-m-dTg:i').'">';
		}
		/*Date Format END*/
		return $event_schedule;
}

// grab events time for later use
 function ect_tribe_event_time($post_id, $display = true ) 
 {
	$event =$post_id;

	if ( tribe_event_is_multiday( $event ) ) { // multi-date event
		$start_date = tribe_get_start_date(  $event, false, false );
		$end_date = tribe_get_end_date(  $event, false, false );
		if ( $display ) {
			printf( esc_html__( '%s - %s', 'ect' ), esc_html($start_date), esc_html($end_date) );
		}
		else {
			return sprintf( esc_html__( '%s - %s', 'ect' ), esc_html($start_date), esc_html($end_date) );
		}
	}
	elseif ( tribe_event_is_all_day( $event ) ) { // all day event
		if ( $display ) {
			printf( esc_html__( 'All day', 'the-events-calendar' ) );
		}
		else {
			return sprintf( esc_html__( 'All day', 'the-events-calendar' ) );
		}
	}
	else {
		$time_format = get_option( 'time_format' );
		$start_date = tribe_get_start_date( $event, false, $time_format );
		$end_date = tribe_get_end_date( $event, false, $time_format );
		if ( $start_date !== $end_date ) {
			if ( $display ) {
				printf( esc_html__( '%s - %s', 'ect' ), esc_html($start_date), esc_html($end_date) );
			}
			else {
				return sprintf( esc_html__( '%s - %s', 'ecct' ), esc_html($start_date), esc_html($end_date) );
			}
		}
		else {
			if ( $display ) {
				printf( esc_html__('%s','ect'), esc_html($start_date) );
			}
			else {
				return sprintf( esc_html__('%s','ect'), esc_html($start_date) );
			}
		}
	}
}



  
if ( ! function_exists( 'ect_tribe_tickets_buy_button' ) ) {

	/**
	 * Echos Remaining Ticket Count and Purchase Buttons for an Event
	 *
	 * @since  4.5
	 *
	 * @param bool $echo Whether or not we should print
	 *
	 * @return string
	 */
	function ect_tribe_tickets_buy_button( $event_id,$echo = true ) {
		//$event_id = get_the_ID();

		// check if there are any tickets on sale
		if ( ! tribe_events_has_tickets_on_sale( $event_id ) ) {
			return null;
		}

		// get an array for ticket and rsvp counts
		$types = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// if no rsvp or tickets return
		if ( ! $types ) {
			return null;
		}

		$html = array();
		$parts = array();

		// If we have tickets or RSVP, but everything is Sold Out then display the Sold Out message
		foreach ( $types as $type => $data ) {
			if ( ! $data['count'] ) {
				continue;
			}

			if ( ! $data['available'] ) {
				$parts[ $type . '-stock' ] = '<span class="tribe-out-of-stock">' . esc_html_x( 'Sold out', 'list view stock sold out', 'ect' ) . '</span>';

				// Only re-apply if we don't have a stock yet
				if ( empty( $html['stock'] ) ) {
					$html['stock'] = $parts[ $type . '-stock' ];
				}
			} else {
				$stock = $data['stock'];
				if ( $data['unlimited'] || ! $data['stock'] ) {
					// if unlimited tickets, tickets with no stock and rsvp, or no tickets and rsvp unlimited - hide the remaining count
					$stock = false;
				}

				$stock_html = '';

				if ( $stock ) {
					$threshold = Tribe__Settings_Manager::get_option( 'ticket-display-tickets-left-threshold', 0 );

					/**
					 * Overwrites the threshold to display "# tickets left".
					 *
					 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
					 * @param array $data      Ticket data.
					 * @param int   $event_id  Event ID.
					 *
					 * @since 4.10.1
					 */
					$threshold = absint( apply_filters( 'tribe_display_tickets_left_threshold', $threshold, $data, $event_id ) );

					if ( ! $threshold || $stock <= $threshold ) {

						$number = number_format_i18n( $stock );
						if ( 'rsvp' === $type ) {
							$text = _n( '%s spot left', '%s spots left', $stock, 'ect' );
						} else {
							$text = _n( '%s ticket left', '%s tickets left', $stock, 'ect' );
						}

						$stock_html = '<span class="tribe-tickets-left">'
							. esc_html( sprintf( $text, $number ) )
							. '</span>';
					}
				}

				$parts[ $type . '-stock' ] = $html['stock'] = $stock_html;

				if ( 'rsvp' === $type ) {
					$button_label  = __( 'RSVP Now','ect' );
					$button_anchor = '#rsvp-now';
				} else {
					$button_label  = __( 'Buy Now','ect' );
					$button_anchor = '#tpp-buy-tickets';
				}

				$permalink = get_the_permalink( $event_id );
				$query_string = parse_url( $permalink, PHP_URL_QUERY );
				$query_params = empty( $query_string ) ? array() : (array) explode( '&', $query_string );

			//	$button = '<form method="get" action="' . esc_url( $permalink . $button_anchor ) . '">';
		
				$html['link']= '<a href="'.esc_url( $permalink . $button_anchor ).'">' . $button_label . '</a>';
			
				
			}
		}

		/**
		 * Filter the ticket count and purchase button
		 *
		 * @since  4.5
		 *
		 * @param array $html     An array with the final HTML
		 * @param array $parts    An array with all the possible parts of the HTMl button
		 * @param array $types    Ticket and RSVP count array for event
		 * @param int   $event_id Post Event ID
		 */
		$html = apply_filters( 'tribe_tickets_buy_button', $html, $parts, $types, $event_id );
		$html = implode( "\n", $html );

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}
}