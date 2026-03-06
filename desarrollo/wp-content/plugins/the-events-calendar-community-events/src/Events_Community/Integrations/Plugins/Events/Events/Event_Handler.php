<?php
/**
 * Event Handler Logic for The Events Calendar Integration
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Events;

use Tribe__Events__API;
use Tribe__Events__Main;
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
		$event      = get_post( $event_id );
		$submission = $this->clean_submission( $submission );

		// Check if the post isn't an auto-draft, indicating an update rather than a new post.
		if ( $event_id && 'auto-draft' !== $event->post_status ) {
			$saved = Tribe__Events__API::updateEvent( $event_id, $submission );

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
				$this->update_series( $submission, $event );
				$this->update_taxonomy( $submission, $event );
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
			$this->update_series( $submission, $event );
			$this->update_taxonomy( $submission, $event );
			return $event_id;
		}

		// Event creation/update failed.
		return false;
	}

	/**
	 * Creates a new event with the given submission data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data for the new event.
	 *
	 * @return int The new event ID if creation was successful, or false on failure.
	 */
	protected function create_event( array $submission ): int {
		return Tribe__Events__API::createEvent( $submission );
	}

	/**
	 * Updates an existing event with the given submission data.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $event_id The ID of the event to update.
	 * @param array $submission The submission data to update the event with.
	 *
	 * @return int The event ID if the update was successful, or false on failure.
	 */
	protected function update_event( int $event_id, array $submission ): int {
		return Tribe__Events__API::updateEvent( $event_id, $submission );
	}
	/**
	 * Updates the series of an event if applicable.
	 *
	 * This method checks if the submission includes a series and if the necessary function exists to update the event
	 * with the series. It then retrieves the new event and the series, and updates the event with the series if both
	 * are valid WP_Post objects.
	 *
	 * @since 5.0.0
	 * @since 5.0.6 Switched to using $event->ID.
	 *
	 * @param array   $submission The submission data.
	 * @param WP_Post $event The event data.
	 */
	protected function update_series( array $submission, WP_Post $event ) {
		// Check if the submission includes a recurrence and if the function to update the event with the series exists.
		if ( empty( $submission['recurrence'] ) || ! function_exists( 'tribe_update_event_with_series' ) ) {
			return;
		}

		// Retrieve the new event and the series as WP_Post objects.
		$new_event = get_post( $event->ID );
		$series    = get_post( $submission['Series'] );

		// Update the event with the series if both are valid WP_Post objects.
		if ( $event instanceof WP_Post && $series instanceof WP_Post ) {
			tribe_update_event_with_series( $new_event, $series );
		}
	}

	/**
	 * Updates the taxonomy terms of an event if applicable.
	 *
	 * This method checks if the submission includes taxonomy terms and if the user has the necessary permissions to
	 * assign terms. It then updates the event with the valid taxonomy terms.
	 *
	 * @since 5.0.0
	 * @since 5.0.1.1 Switched to using $event->ID and added additional checks for the $event variable.
	 *
	 * @param array   $submission The submission data.
	 * @param WP_Post $event The event data.
	 */
	protected function update_taxonomy( array $submission, WP_Post $event ): void {
		// Validate the existence of the ID field.
		if ( empty( $event->ID ) ) {
			return;
		}

		// Check if the submission includes taxonomy terms.
		if ( ! isset( $submission['tax_input'] ) ) {
			return;
		}

		/**
		 * Allows new taxonomies to be saved using the default Methods for Community Edit page.
		 *
		 * @since 5.0.0
		 *
		 * @param array $allowed_taxonomies The allowed taxonomies.
		 */
		$allowed_taxonomies = apply_filters(
			'tribe_community_events_allowed_taxonomies',
			[
				Tribe__Events__Main::TAXONOMY,
				'post_tag',
			]
		);

		// Iterate over each taxonomy term in the submission.
		foreach ( (array) $submission['tax_input'] as $taxonomy => $terms ) {
			// Skip non-valid taxonomies.
			if ( ! in_array( $taxonomy, $allowed_taxonomies ) ) {
				continue;
			}

			// Fetch the taxonomy object.
			$taxonomy_obj = get_taxonomy( $taxonomy );

			// Skip if the user can assign terms.
			if ( current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
				continue;
			}

			// Assign the terms to the event.
			wp_set_post_terms( $event->ID, $terms, $taxonomy, true );
		}
	}

	/**
	 * Cleans the submission data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data.
	 *
	 * @return array The cleaned submission data.
	 */
	protected function clean_submission( array $submission ): array {
		$submission = $this->sanitize_linked_post( 'venue', $submission );
		$submission = $this->sanitize_linked_post( 'organizer', $submission );
		$submission = $this->sanitize_linked_post( 'series', $submission );

		return $submission;
	}

	/**
	 * Sanitizes the linked post data submitted from the community forms.
	 *
	 * @since 5.0.0
	 *
	 * @param string $key Which Linked post we are dealing with.
	 * @param array  $submission Data submitted from Community form.
	 *
	 * @return array Returned data after cleanup.
	 */
	protected function sanitize_linked_post( string $key, array $submission ): array {
		$lowercase_key      = strtolower( $key );
		$uppercase_key      = ucfirst( $lowercase_key );
		$is_empty_lowercase = empty( $submission[ $lowercase_key ] );
		$is_empty_uppercase = empty( $submission[ $uppercase_key ] );

		if ( $is_empty_lowercase && $is_empty_uppercase ) {
			return $submission;
		}

		if ( ! $is_empty_lowercase ) {
			if ( ! empty( $submission[ $lowercase_key ][ $lowercase_key ] ) ) {
				$submission[ $lowercase_key ][ $lowercase_key ] = $this->sanitize_data( $submission[ $lowercase_key ][ $lowercase_key ] );
			}

			if ( ! empty( $submission[ $lowercase_key ][ $uppercase_key ] ) ) {
				$submission[ $lowercase_key ][ $uppercase_key ] = $this->sanitize_data( $submission[ $lowercase_key ][ $uppercase_key ] );
			}
		}

		if ( ! $is_empty_uppercase ) {
			if ( ! empty( $submission[ $uppercase_key ][ $lowercase_key ] ) ) {
				$submission[ $uppercase_key ][ $lowercase_key ] = $this->sanitize_data( $submission[ $uppercase_key ][ $lowercase_key ] );
			}

			if ( ! empty( $submission[ $uppercase_key ][ $uppercase_key ] ) ) {
				$submission[ $uppercase_key ][ $uppercase_key ] = $this->sanitize_data( $submission[ $uppercase_key ][ $uppercase_key ] );
			}
		}

		return $submission;
	}

	/**
	 * Sanitizes the data to prevent potential XSS.
	 *
	 * @since 5.0.0
	 *
	 * @param string|array $data The data to be sanitized.
	 *
	 * @return string|array Sanitized data.
	 */
	protected function sanitize_data( $data ) {
		if ( empty( $data ) ) {
			return $data;
		}

		$is_array = is_array( $data );

		$data = array_map( 'wp_specialchars_decode', array_map( 'wp_kses_post', array_map( 'html_entity_decode', (array) $data ) ) );

		if ( ! $is_array ) {
			$data = reset( $data );
		}

		return $data;
	}
}
