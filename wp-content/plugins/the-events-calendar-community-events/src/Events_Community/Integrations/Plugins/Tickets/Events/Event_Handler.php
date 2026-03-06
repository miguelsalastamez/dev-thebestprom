<?php
/**
 * Event Handler Logic for Event Tickets Integration
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */

namespace TEC\Events_Community\Integrations\Plugins\Tickets\Events;

use Tribe__Events__Community__Main;
use WP_Error;
use WP_Post;

/**
 * Class Event_Handler
 *
 * The Event_Handler class integrates with The Events Calendar plugin to handle saving
 * events for Community.
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */
class Event_Handler {

	/**
	 * List of valid meta tags that we allow.
	 *
	 * @var array
	 */
	protected static array $valid_meta_tags = [
		'_EventOrigin',
	];

	/**
	 * Returns a callback function for saving the event.
	 *
	 * @since 5.0.0
	 *
	 * @return callable The callback function for saving the event.
	 */
	public function handler(): callable {
		return tribe_callback( self::class, 'save_event' );
	}

	/**
	 * Handles the saving of an event, either updating an existing event or creating a new one.
	 *
	 * @since 5.0.0
	 *
	 * @param array    $submission The submission data.
	 * @param int|null $event_id The event ID, or null if creating a new event.
	 *
	 * @return false|int The event ID if the save operation was successful, or false on failure.
	 */
	public function save_event( array $submission, ?int $event_id ) {
		$event = get_post( $event_id );

		// Check if the post isn't an auto-draft, indicating an update rather than a new post.
		if ( $event instanceof WP_Post && 'auto-draft' !== $event->post_status ) {
			$saved = $this->update_event( $event_id, $submission );

			if ( $saved ) {
				/**
				 * Fires after a new event is successfully updated.
				 * An updated event is an event that existed prior.
				 *
				 * @since 5.0.0
				 *
				 * @param int $event_id The ID of the newly created event.
				 */
				do_action( 'tribe_community_event_updated', $event_id );
				return $event_id;
			}
			return false;
		}

		// If we are here, it means we are dealing with an auto-draft or a new post.
		$submission['post_status'] = tribe( 'community.main' )->getOption( 'defaultStatus' );
		$submission['EventOrigin'] = 'community-events';

		// Check if we have an event ID, indicating an auto-draft that needs to be updated.
		if ( $event_id ) {
			$saved = $this->update_event( $event_id, $submission );
		} else {
			// Create a new event.
			$saved = $this->create_event( $submission );
		}

		// Check if the event was saved successfully.
		if ( $saved ) {
			$event_id = $saved;
			/**
			 * Fires after a new event is successfully created.
			 * A created event is an event that never existed.
			 *
			 * @since 5.0.0
			 *
			 * @param int $event_id The ID of the newly created event.
			 */
			do_action( 'tribe_community_event_created', $event_id );
			return $event_id;
		}

		// Event creation/update failed.
		return false;
	}

	/**
	 * Updates an existing event with given data.
	 *
	 * This method updates an event's details in the database using the provided submission data.
	 * It applies filters to allow for modification of the submission data before updating the post.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $event_id The ID of the event to be updated.
	 * @param array $submission Data used to update the event.
	 *
	 * @return int The event ID if the update was successful, or false on failure.
	 */
	public static function update_event( $event_id, $submission ) {
		$event_id                     = absint( $event_id );
		$post                         = get_post( $event_id );
		$submission['ID']             = $event_id;
		$submission['post_type']      = tribe( Tribe__Events__Community__Main::class )->get_community_events_post_type();
		$submission['comment_status'] = 'closed';

		// Allow for the change of the date and the status in the same update request.
		if (
			isset( $submission['post_date'], $submission['post_status'] )
			&& in_array( $post->post_status, [ 'draft', 'pending', 'auto-draft' ] )
			&& $submission['post_status'] !== $post->post_status
		) {
			$submission['edit_date'] = true;
		}

		/**
		 * Allow hooking prior the update of an event and meta fields.
		 *
		 * @since 5.0.0
		 *
		 * @param int     $event_id The event ID we are modifying.
		 * @param WP_Post $post The event itself.
		 *
		 * @param array   $submission The fields we want saved.
		 */
		$submission = apply_filters( 'tec_events_community_event_tickets_event_update_args', $submission, $event_id, $post );

		/**
		 * Disallow the update for an event.
		 *
		 * @since 5.0.0
		 *
		 * @param int  $event_id The event ID.
		 *
		 * @param bool $disallow_update Flag to control the update of a post false by default.
		 */
		if ( apply_filters( 'tec_events_community_event_tickets_event_prevent_update', false, $event_id ) ) {
			return $event_id;
		}
		if ( wp_update_post( $submission ) ) {
			self::save_event_meta( $event_id, $submission, $post );
		}

		return $event_id;
	}

	/**
	 * Creates a new event with the provided data.
	 *
	 * This method inserts a new event into the database using the provided submission data.
	 * It applies filters to allow for modification of the submission data before insertion.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission Data for creating the new event.
	 *
	 * @return int|WP_Error The new event ID if creation was successful, or WP_Error on failure.
	 */
	public static function create_event( $submission ) {
		$submission['post_type']      = tribe( Tribe__Events__Community__Main::class )->get_community_events_post_type();
		$submission['comment_status'] = 'closed';

		/**
		 * Allow filtering of arguments in prior to inserting the event and meta fields.
		 *
		 * @since 5.0.0
		 *
		 * @param array $submission The fields we want saved.
		 */
		$submission = apply_filters( 'tec_events_community_event_tickets_event_insert_args', $submission );

		if ( is_wp_error( $submission ) ) {
			return $submission;
		}

		$event_id = wp_insert_post( $submission, true );

		if ( ! is_wp_error( $event_id ) ) {
			self::save_event_meta( $event_id, $submission, get_post( $event_id ) );
		}

		return $event_id;
	}

	/**
	 * Used by createEvent and updateEvent - saves all the various event meta.
	 *
	 * @since 5.0.0
	 *
	 * @param int     $event_id The event ID we are modifying meta for.
	 * @param array   $data The meta fields we want saved.
	 * @param WP_Post $event The event post, itself.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function save_event_meta( $event_id, $data, $event = null ) {
		if ( is_wp_error( $data ) ) {
			/**
			 * Fires when saving event meta fails due to invalid data.
			 *
			 * This action allows for handling cases where invalid data prevents an event meta from being saved.
			 * Example of invalid data: an EventStartMinute of `60`, since it should be 0-59.
			 *
			 * @since 5.0.0
			 *
			 * @param int     $event_id The event ID we are modifying meta for.
			 * @param array   $data The original data attempted to save.
			 * @param WP_Post $event The event post object, if available.
			 */
			do_action( 'tribe_events_event_save_failed_invalid_meta', $event_id, $data, $event );
			return false;
		}

		/**
		 * Fires before updating event meta fields.
		 *
		 * This action can be used to hook custom functionality before the meta data of an event is updated.
		 *
		 * @since 5.0.0
		 *
		 * @param int     $event_id The event ID.
		 * @param array   $data The meta fields that are about to be saved.
		 * @param WP_Post $event The event post object, if available.
		 */
		do_action( 'tribe_events_event_save', $event_id, $data, $event );

		// Update meta fields.
		foreach ( self::$valid_meta_tags as $tag ) {
			$html_element = ltrim( $tag, '_' );
			if ( isset( $data[ $html_element ] ) ) {
				// Sanitize if string.
				if ( is_string( $data[ $html_element ] ) ) {
					$data[ $html_element ] = tec_sanitize_string( $data[ $html_element ] );
				}
				// Handle arrays of data for multiple entries under a single meta key.
				if ( is_array( $data[ $html_element ] ) ) {
					delete_post_meta( $event_id, $tag );
					foreach ( $data[ $html_element ] as $value ) {
						add_post_meta( $event_id, $tag, $value );
					}
				} else {
					// Handle single entry data.
					update_post_meta( $event_id, $tag, $data[ $html_element ] );
				}
			}
		}

		/**
		 * Fires after all event meta has been updated.
		 *
		 * This action can be used to hook custom functionality after the meta data of an event has been fully updated.
		 *
		 * @since 5.0.0
		 *
		 * @param int     $event_id The event ID.
		 * @param array   $data The meta fields that were saved.
		 * @param WP_Post $event The event post object, if available.
		 */
		do_action( 'tribe_events_update_meta', $event_id, $data, $event );

		return true;
	}
}
