<?php
/**
 * Event Submission Form Ticket Block.
 * Renders the ticket settings in the submission form.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community-tickets/modules/tickets.php
 *
 * @link https://evnt.is/1ao4 Help article for Community Events & Tickets template files.
 *
 * @since   3.1
 * @since   4.7.0
 * @since   4.7.4 Add translation comments and code comments to make the file easier to follow.
 * @since 4.8.2 Updated template link.
 *
 * @version 4.8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! current_user_can( 'edit_event_tickets' ) ) {
	return;
}

/** @var Tribe__Events__Community__Tickets__Main $community_tickets */

if ( ! $community_tickets->is_enabled() ) {
	return;
}

$options = get_option( $community_tickets::OPTIONNAME );

if ( empty( $options['enable_image_uploads'] ) ) {
	$image_uploads_class = 'tribe-image-uploads-disabled';
} else {
	$image_uploads_class = 'tribe-image-uploads-enabled';
}

$events_label_singular = tribe_get_event_label_singular();
$events_label_plural   = tribe_get_event_label_plural();

$community_events = Tribe__Events__Community__Main::instance();
$event_id         = $community_events->event_form()->get_event_id();
$event            = get_post( $event_id );

?>

<div id="tribetickets" class="tribe-section tribe-section-tickets <?php echo sanitize_html_class( $image_uploads_class ); ?>">
	<div class="tribe-section-header">
		<h3>
			<?php
			// @todo Future note: We will want to implement the tribe_get_ticket_label_plural() replacement here.
			esc_html_e( 'Tickets', 'tribe-events-community-tickets' );
			?>
		</h3>
	</div>

	<?php
	/**
	 * Allow developers to hook and add content to the beginning of this section
	 */
	do_action( 'tribe_events_community_section_before_tickets' );

	/** @var Tribe__Tickets__Metabox $metabox */
	$metabox = tribe( 'tickets.metabox' );
	?>

	<div class="tribe-section-content">
		<?php
		if (
			$community_tickets->is_enabled_for_event( $event_id )
			&& current_user_can( 'sell_event_tickets' )
		) {
			$metabox->render( $event->ID );
		} else {
			?>
			<p>
				<?php
				// @todo Future note: We will want to implement the tribe_get_ticket_label_plural_lowercase() replacement here.
				printf(
				// Translators: 1: link opening tag and URL 2: link closing tag
					esc_html__(
						'Before you can create tickets, please add your PayPal email address on the %1$sPayment options%2$s form.',
						'tribe-events-community-tickets'
					),
					'<a href="' . esc_url( $community_tickets->routes['payment-options']->url() ) . '">',
					'</a>'
				);
				?>
			</p>
			<?php
		}
		?>
	</div>

	<?php
	/**
	 * Allow developers to hook and add content to the end of this section
	 */
	do_action( 'tribe_events_community_section_after_tickets' );
	?>
</div>
