<?php
// Don't load directly
defined( 'WPINC' ) or die;

/**
 * Header links for edit forms.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/modules/header-links.php
 *
 * @link https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @since  3.1
 * @since 4.8.2 Updated template link.
 *
 * @version 4.8.2
 *
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$post_id      = get_the_ID();
$event_status = get_post_status( $post_id );

$message_edit = sprintf(
	/* Translators: %s - Event (singular) */
	__( 'Edit %s', 'tribe-events-community' ),
	tribe( Tribe__Events__Community__Main::class )->get_event_label( 'singular' )
);

$message_add = sprintf(
	/* Translators: %s - Event (singular) */
	__( 'Add New %s', 'tribe-events-community' ),
	tribe( Tribe__Events__Community__Main::class )->get_event_label( 'singular' )
);

$message_view_submitted = sprintf(
	/* Translators: %s - Events (plural) */
	__( 'View Your Submitted %s', 'tribe-events-community' ),
	tribe( Tribe__Events__Community__Main::class )->get_event_label( 'plural' )
);

$label = $message_add;

// If the post ID is anything other than 0, we are editing.
if ( 'auto-draft' !== $event_status ) {
	$label = $message_edit;
}
/**
 * Filters the header links title for Community.
 *
 * This filter allows modifying the header links title based on the post type.
 *
 * @since 5.0.0
 *
 * @param string $label The current label.
 * @param int    $post_id The post ID to check.
 *
 * @return string The filtered title.
 */
$label = apply_filters( 'tec_events_community_header_links_title', $label, $post_id );

?>

<header class="my-events-header">
	<h2 class="my-events">
		<?php
		echo esc_html( $label );
		?>
	</h2>

	<?php if ( is_user_logged_in() ) : ?>
	<a
		href="<?php echo esc_url( tribe_community_events_list_events_link() ); ?>"
		class="tribe-button tribe-button-secondary"
	>
		<?php echo esc_html( $message_view_submitted ); ?>
	</a>
	<?php endif; ?>
</header>

<?php echo tribe_community_events_get_messages();
