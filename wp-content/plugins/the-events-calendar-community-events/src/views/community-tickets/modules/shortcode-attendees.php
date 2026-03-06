<?php
/**
 * Attendees Table Template for Community Tickets Shortcode.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe-events/community-tickets/modules/shortcode-attendees.php
 *
 * @link https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @version 4.10.17
 */

/** @var Tribe__Tickets__Attendees $attendees */
$attendees = tribe( 'tickets.attendees' );

$attendees_table = $attendees->attendees_table;
$attendees_table->prepare_items();

$event_id = $attendees_table->event->ID;
$event    = $attendees_table->event;
$tickets  = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
$pto      = get_post_type_object( $event->post_type );
$singular = $pto->labels->singular_name;

/**
 * Whether or not we should display attendees title.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @param boolean $show_title
 */
$show_title = apply_filters( 'tribe_community_tickets_attendees_show_title', true );
?>

<div class="wrap tribe-report-page">
	<?php if ( $show_title ) : ?>
		<h1><?php esc_html_e( 'Attendees', 'tribe-events-community' ); ?></h1>
	<?php endif; ?>
	<div id="tribe-attendees-summary" class="welcome-panel tribe-report-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

				<?php
				/**
				 * Fires before the individual panels within the attendee screen summary are rendered.
				 *
				 * @since 5.0.0 Migrated to Community from Community Tickets.
				 *
				 * @param int $event_id Event ID.
				 */
				do_action( 'tribe_events_tickets_attendees_event_details_top', $event_id );
				?>

				<div class="welcome-panel-column welcome-panel-first">
					<h3>
						<?php
						printf(
						// Translators: 1: singular name of the post type ticket to which ticket is attached.
							esc_html_x(
								'%1$s Details',
								'attendee screen summary',
								'tribe-events-community'
							),
							esc_html( $singular )
						);
						?>
					</h3>

					<ul>
						<?php
						/**
						 * Provides an action that allows for the injections of fields at the top of the event details meta ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param int $event_id Event ID.
						 */
						do_action( 'tribe_tickets_attendees_event_details_list_top', $event_id );

						/**
						 * Provides an action that allows for the injections of fields at the bottom of the event details meta ul
						 *
						 * @since 5.0.0 Migrated to Community from Community Tickets.
						 *
						 * @param int $event_id Event ID.
						 */
						do_action( 'tribe_tickets_attendees_event_details_list_bottom', $event_id );
						?>
					</ul>
					<?php
					/**
					 * Provides an opportunity for various action links to be added below
					 * the event name, within the attendee screen.
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_community_tickets_attendees_do_event_action_links', $event_id );

					/**
					 * Provides an opportunity for various action links to be added below
					 * the action links
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_community_tickets_attendees_event_details_bottom', $event_id );
					?>

				</div>
				<div class="welcome-panel-column welcome-panel-middle">
					<h3>
						<?php
						echo esc_html_x( 'Overview', 'attendee screen summary', 'tribe-events-community' );
						?>
					</h3>
					<?php
					/**
					 * Provides an opportunity for adding HTML above the tickets.
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id );
					?>

					<ul>
						<?php
						foreach ( $tickets as $ticket ) :
							?>
							<li>
								<strong>
									<?php
									echo esc_html( $ticket->name )
									?>
									:</strong>&nbsp;
								<?php
								echo esc_html( tribe_tickets_get_ticket_stock_message( $ticket ) );
								?>
							</li>
							<?php
						endforeach;
						?>
					</ul>
					<?php
					do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id );
					?>
				</div>
				<div class="welcome-panel-column welcome-panel-last alternate">
					<?php
					/**
					 * Fires before the main body of attendee totals are rendered.
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_events_tickets_attendees_totals_top', $event_id );

					/**
					 * Trigger for the creation of attendee totals within the attendee
					 * screen summary box.
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_tickets_attendees_totals', $event_id );

					/**
					 * Fires after the main body of attendee totals are rendered.
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param int $event_id Event ID.
					 */
					do_action( 'tribe_events_tickets_attendees_totals_bottom', $event_id );
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
	/**
	 * Fires after the event summary table.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id Event ID.
	 */
	do_action( 'tribe_community_tickets_attendees_event_summary_table_after', $event_id ); ?>

	<form id="topics-filter" class="topics-filter" method="post">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( tribe_get_request_var( 'page', '' ) ); ?>" />
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>" />
		<?php $attendees_table->display(); ?>
	</form>
</div>
