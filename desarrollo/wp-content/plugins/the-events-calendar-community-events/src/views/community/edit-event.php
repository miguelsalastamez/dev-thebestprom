<?php
/**
 * Event Submission Form
 * The wrapper template for the event submission form.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/edit-event.php
 *
 * @link    https://evnt.is/1ao4 Help article for Community & Tickets template files.
 *
 * @version 4.10.17
 *
 * @since   3.1
 * @since   4.8.2 Updated template link.
 * @since   4.8.10 Use datepicker format from the date utils library to autofill the start and end dates.
 * @since   4.10.17 Corrected template override path.
 * @since 5.0.0 Changed template override path back to `tribe-events`.
 * @since 5.0.0 refactored view to use `generate_form_layout`.
 *
 * @var int|string $tribe_event_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! isset( $tribe_event_id ) ) {
	$tribe_event_id = null;
}

$datepicker_format = Tribe__Date_Utils::get_datepicker_format_index();

/** @var Tribe__Events__Community__Main $main */
$main = tribe( 'community.main' );

?>

<?php tribe( Tribe__Events__Community__Templates::class )->tribe_get_template_part( 'community/modules/header-links' ); ?>

<?php do_action( 'tribe_events_community_form_before_template', $tribe_event_id ); ?>

<form method="post" enctype="multipart/form-data" data-datepicker_format="<?php echo esc_attr( $datepicker_format ); ?>">
	<input type="hidden" name="post_ID" id="post_ID" value="<?php echo absint( $tribe_event_id ); ?>"/>
	<?php wp_nonce_field( 'ecp_event_submission' ); ?>

	<?php $main->generate_form_layout( $tribe_event_id ); ?>

</form>

<?php do_action( 'tribe_events_community_form_after_template', $tribe_event_id ); ?>
