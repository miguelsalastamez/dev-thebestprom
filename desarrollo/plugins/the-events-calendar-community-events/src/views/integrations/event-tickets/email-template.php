<?php
/**
 * Email Template
 * The template for the Event Submission Notification Email
 *
 * This template requires Event Tickets plugin installed to function properly.
 * If the plugin is not installed, this template will not work properly due to
 * a constraint on the specific functions being used.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/integration/event-tickets/email-template.php
 *
 * @link https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @version 5.0.7
 *
 * @since 5.0.7
 *
 * @var WP_Post $post           The WP_Post object representing the event.
 * @var int     $tribe_event_id The event/post ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Early bail if Event Tickets is not installed and active.
if ( ! did_action( 'tec_tickets_fully_loaded' ) ) {
	return;
}

if ( empty( $post ) ) {
	return;
}

$events_label_singular = tec_events_community_event_label_singular();
$start_date            = tribe_get_start_date( $tribe_event_id );
$end_date              = tribe_get_end_date( $tribe_event_id );
// Review/View Links.
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

?>
<html>
<body>
<h2><?php echo esc_html( get_the_title( $tribe_event_id ) ); ?></h2>

<?php if ( $start_date && $end_date ) : ?>
	<h4>
		<?php
		/* translators: 1: Start date. 2: End date. */
		printf( esc_html__( '%1$s - %2$s', 'tribe-events-community' ), esc_html( $start_date ), esc_html( $end_date ) );
		?>
	</h4>
	<hr/>
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
