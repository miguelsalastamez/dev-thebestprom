<li class="event-date">
	<strong><?php echo esc_html__( 'Event Date', 'tribe-events-community' ); ?>: </strong>
	<?php echo esc_html( tribe_get_start_date( $event_id, false, 'F j, Y'  ) ); ?>
</li>
<li class="post-type">
	<strong><?php echo esc_html__( 'Post type', 'tribe-events-community' ); ?>: </strong>
	<?php echo esc_html( strtolower( $pto->labels->singular_name ) ); ?>
</li>
