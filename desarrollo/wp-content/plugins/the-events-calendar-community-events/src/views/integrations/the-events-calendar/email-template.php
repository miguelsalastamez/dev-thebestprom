<?php
/**
 * Email Template for Event Submission Notification
 *
 * This template is used for sending email notifications when a new event is submitted.
 * It displays event details including title, date, venue, organizers, and description.
 *
 * This template requires The Events Calendar plugin installed to function properly.
 * If the plugin is not installed, this template will not work properly due to
 * a constraint on the specific functions being used.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe-events/integration/the-events-calendar/email-template.php
 *
 * @link https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @since 5.0.7
 * @version 5.0.7
 *
 * @var WP_Post $post           The WP_Post object representing the event.
 * @var int     $tribe_event_id The event ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Early bail if The Events Calendar is not installed and active.
if ( ! did_action( 'tribe_events_bound_implementations' ) ) {
	return;
}

if ( empty( $post ) ) {
	return;
}

$events_label_singular = tec_events_community_event_label_singular();
$start_date            = tribe_get_start_date( $tribe_event_id );
$end_date              = tribe_get_end_date( $tribe_event_id );
$venue_id              = tribe_get_venue_id( $tribe_event_id );
$organizer_ids         = tribe_get_organizer_ids( $tribe_event_id );
$organizer_count       = count( $organizer_ids );

$admin_url = add_query_arg(
	[
		'action' => 'edit',
		'post'   => $tribe_event_id,
	],
	admin_url( 'post.php' )
);

$review_text = sprintf(
/* translators: %s: Event label singular. */
	esc_html__( 'Review %s', 'tribe-events-community' ),
	esc_html( $events_label_singular )
);

$view_text = sprintf(
/* translators: %s: Event label singular. */
	esc_html__( 'View %s', 'tribe-events-community' ),
	esc_html( $events_label_singular )
);

/**
 * Allows filtering of the organizer label format.
 *
 * @since 5.0.7
 *
 * @param string $organizer_label The organizer label format.
 * @param int    $organizer_count The number of organizers.
 */
$organizer_label = apply_filters(
	'tec_events_community_email_organizer_label',
	sprintf(
	/* translators: %s: Organizer label (e.g., Organizer/Organizers). */
		'%s',
		_n( 'Organizer', 'Organizers', $organizer_count, 'tribe-events-community' )
	),
	$organizer_count
);
?>
<html>
<body>

<h2><?php echo esc_html( get_the_title( $tribe_event_id ) ); ?></h2>

<?php if ( $start_date && $end_date ) : ?>
	<h4>
		<?php

		printf(
		/* translators: 1: Start date. 2: End date. */
			esc_html__( '%1$s - %2$s', 'tribe-events-community' ),
			esc_html( $start_date ),
			esc_html( $end_date )
		);

		if ( function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $tribe_event_id ) ) {
			echo ' | ';

			printf(
			/* translators: %s: Event label singular. */
				esc_html__( 'Recurring %s', 'tribe-events-community' ),
				esc_html( $events_label_singular )
			);
		}
		?>
	</h4>
	<hr/>
<?php endif; ?>

<?php if ( $venue_id ) : ?>
	<h3>
		<?php

		printf(
		/* translators: %s: Event label singular. */
			esc_html__( '%s Venue', 'tribe-events-community' ),
			esc_html( $events_label_singular )
		);
		?>
	</h3>
	<p>
		<a href="<?php echo esc_url( get_edit_post_link( $venue_id ) ); ?>">
			<?php echo esc_html( tribe_get_venue( $tribe_event_id ) ); ?>
		</a>
	</p>
<?php endif; ?>

<?php if ( ! empty( $organizer_ids ) ) : ?>
	<h3><?php echo esc_html( $organizer_label ); ?></h3>
	<?php foreach ( $organizer_ids as $organizer_id ) : ?>
		<p>
			<a href="<?php echo esc_url( get_edit_post_link( $organizer_id ) ); ?>">
				<?php echo esc_html( tribe_get_organizer( $organizer_id ) ); ?>
			</a>
		</p>
	<?php endforeach; ?>
<?php endif; ?>

<h3><?php esc_html_e( 'Description', 'tribe-events-community' ); ?></h3>
<?php echo wp_kses_post( $post->post_content ); ?>

<hr/>

<h4>
	<a href="<?php echo esc_url( $admin_url ); ?>"><?php echo esc_html( $review_text ); ?></a>
	<?php if ( 'publish' === get_post_status( $tribe_event_id ) ) : ?>
		<?php echo ' | '; ?>
		<a href="<?php echo esc_url( get_permalink( $tribe_event_id ) ); ?>">
			<?php echo esc_html( $view_text ); ?>
		</a>
	<?php endif; ?>
</h4>

</body>
</html>
