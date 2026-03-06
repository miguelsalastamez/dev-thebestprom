<?php
/**
 * Inserts the organizer's PayPal address after the organizer name.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe-events/community-tickets/modules/orders-report-after-organizer.php
 *
 * @link https://evnt.is/1ao4 Help article for Community Events & Tickets template files.
 *
 * @since   4.7.0
 * @since   4.7.4 Correct text domain. Reorganize file to make it easier to follow.
 * @since 4.8.2 Updated template link.
 *
 * @version 4.8.2
 *
 * @var $event     WP_Post Event post object.
 * @var $organizer WP_User Community Organizer user object.
 */
$meta = Tribe__Events__Community__Tickets__Payment_Options_Form::get_meta( $organizer->ID );

if ( empty( $meta['paypal_account_email'] ) ) {
	return;
}

$linked_email = sprintf(
	'<a href="mailto:%s">%s</a>',
	esc_attr( $meta['paypal_account_email'] ),
	esc_html( $meta['paypal_account_email'] )
);
?>
<div class="tribe-event-meta tribe-event-meta-organizer-paypal">
	<strong>
		<?php
		printf(
			// Translators: linked email address.
			esc_html_x(
				'Organizer PayPal: %s',
				'before linked email address',
				'tribe-events-community-tickets'
			),
			$linked_email
		);
		?>
	</strong>
</div>
