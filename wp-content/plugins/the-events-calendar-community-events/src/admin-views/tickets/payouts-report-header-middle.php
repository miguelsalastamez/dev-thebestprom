<?php
/**
 * @var array $ticket_counts
 */

foreach ( $ticket_counts as $count ) {
	if ( (int) $count['total_found'] <= 0 ) {
		continue;
	}

	$payouts_text = _n( 'payout', 'payouts', $count['total_found'], 'tribe-events-community' );
	$tickets_text = _n( 'ticket sold', 'tickets sold', $count['total_quantity'], 'tribe-events-community' );

	echo sprintf(
		'<li><strong>%1$s:</strong> %2$s %3$s (%4$s %5$s)</li>',
		esc_html( $count['name'] ),
		esc_html( number_format_i18n( $count['total_found'] ) ),
		esc_html( $payouts_text ),
		esc_html( number_format_i18n( $count['total_quantity'] ) ),
		esc_html( $tickets_text )
	);
}
