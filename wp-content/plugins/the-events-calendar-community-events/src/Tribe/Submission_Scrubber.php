<?php
/**
 * Submission Scrubber Class
 *
 * This file contains the definition for the Tribe__Events__Community__Submission_Scrubber class,
 * which is responsible for scrubbing inappropriate data out of a submitted event.
 *
 * @since 5.0.0
 */

use TEC\Events_Community\Submission\Cleaner;


/**
 * Class Tribe__Events__Community__Submission_Scrubber
 *
 * Scrubs inappropriate data out of a submitted event
 */
class Tribe__Events__Community__Submission_Scrubber {

	/**
	 * The submission data.
	 *
	 * @since 5.0.0
	 *
	 * @var array
	 */
	protected array $submission = [];

	/**
	 * The allowed fields for the submission.
	 *
	 * These fields are filtered with the 'tec_events_community_allowed_fields' filter.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Added `event_image`.
	 *
	 * @var array
	 */
	protected static array $allowed_fields = [
		'ID',
		'post_content',
		'post_title',
		'render_timestamp',
		'detach_thumbnail',
		'terms',
		'event_image',
	];

	/**
	 * Constructor for the Tribe__Events__Community__Submission_Scrubber class.
	 *
	 * Initializes the submission data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data.
	 */
	public function __construct( array $submission ) {
		$this->submission = $submission;
	}

	/**
	 * Scrubs the submission data, applying various filters and setting necessary fields.
	 *
	 * This method processes the submission data, applying a series of filters and
	 * transformations to ensure the data is clean and properly formatted. It allows
	 * for customization of the submission data through the 'tec_events_community_submission_scrub'
	 * filter.
	 *
	 * @since 5.0.0
	 *
	 * @return array The scrubbed submission data.
	 */
	public function scrub(): array {

		add_filter( 'wp_kses_allowed_html', [ $this, 'filter_allowed_html_tags' ], 10, 2 );
		/**
		 * Filters the submission data before it is processed further.
		 *
		 * Allows for the alteration of the submission array, enabling
		 * customizations to be made to the data structure or values as needed.
		 * This filter is ran prior to further sanitization.
		 *
		 * @since 5.0.0
		 *
		 * @param array $submission The current submission array.
		 */
		$this->submission = apply_filters( 'tec_events_community_submission_scrub', $this->submission );

		$this->fix_post_content_key();
		$this->remove_unexpected_fields();
		$this->filter_field_contents();

		// These should not be user-submitted.
		$this->set_post_type();
		$this->set_post_author();
		$this->set_post_status();

		remove_filter( 'wp_kses_allowed_html', [ $this, 'filter_allowed_html_tags' ], 10, 2 );

		/**
		 * Filters the submission data after it has been sanitized.
		 *
		 * Allows for final adjustments to be made to the submission
		 * data before it is returned.
		 *
		 * @since 5.0.0
		 *
		 * @param array $submission The scrubbed submission array.
		 */
		$this->submission = apply_filters( 'tribe_events_community_sanitize_submission', $this->submission );

		return $this->submission;
	}

	/**
	 * Updates the 'post_content' key in the submission array.
	 *
	 * Sets the 'post_content' key in the submission array to the value of 'tcepostcontent' if it exists,
	 * then removes the 'tcepostcontent' key.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function fix_post_content_key(): void {
		$this->submission['post_content'] = $this->submission['tcepostcontent'] ?? '';
		unset( $this->submission['tcepostcontent'] );
	}

	/**
	 * Assigns the Community post type to the 'post_type' key in the submission array.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_post_type(): void {
		$this->submission['post_type'] = tribe( Tribe__Events__Community__Main::class )->get_community_events_post_type();
	}

	/**
	 * Determines and assigns the post status for the submission.
	 *
	 * If the 'ID' key is not present or is empty in the submission array, assigns the default community status
	 * to the 'post_status' key. Otherwise, assigns the current status of the post with the given ID.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_post_status(): void {
		$this->submission['post_status'] = empty( $this->submission['ID'] )
			? tribe( 'community.main' )->defaultStatus
			: get_post_status( $this->submission['ID'] );
	}

	/**
	 * Assigns the current user ID to the 'post_author' key in the submission array.
	 *
	 * Sets the 'post_author' key in the submission array to the ID of the currently logged-in user.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_post_author(): void {
		$this->submission['post_author'] = get_current_user_id();
	}

	/**
	 * Removes unexpected fields from the submission, leaving only the whitelisted fields.
	 *
	 * To add additional whitelisted fields, use the `tec_events_community_allowed_fields` filter.
	 *
	 * If the field you are adding should be an array, use the `tec_events_community_allowed_fields_inner_key_{$key}` filter
	 * to whitelist the keys in the inner array.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Moved allowed fields logic to their own methods (get_allowed_fields,get_inner_allowed_fields).
	 *
	 * @return void
	 */
	protected function remove_unexpected_fields(): void {
		$submission_id = $this->submission['ID'];

		if ( empty( $submission_id ) ) {
			// If no ID then unset everything.
			$this->submission = [];
		}

		$allowed_fields = $this->get_allowed_fields( $submission_id );

		foreach ( $this->submission as $key => $value ) {
			// Remove keys not in the allowed fields list.
			if ( ! in_array( $key, $allowed_fields ) ) {
				unset( $this->submission[ $key ] );
				continue;
			}

			if ( is_array( $value ) ) {
				$allowed_inner_fields = $this->get_inner_allowed_fields( $key, $submission_id );

				if ( empty( $allowed_inner_fields ) ) {
					// If no allowed inner fields, unset the key.
					unset( $this->submission[ $key ] );
					continue;
				}

				// If there are allowed inner fields defined, validate the inner array.
				foreach ( $value as $sub_key => $sub_value ) {
					if ( ! in_array( $sub_key, $allowed_inner_fields ) ) {
						unset( $this->submission[ $key ][ $sub_key ] );
					}
				}
			}
		}
	}

	/**
	 * Filters the contents of specified fields in the submission array.
	 *
	 * This method applies the `filter_string` method to the contents of specific fields
	 * in the submission array, ensuring that the values of these fields are sanitized.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function filter_field_contents(): void {
		$fields = [
			'post_content',
			'post_title',
		];

		foreach ( $fields as $field ) {
			if ( isset( $this->submission[ $field ] ) ) {
				$this->submission[ $field ] = $this->filter_string( $this->submission[ $field ] );
			}
		}
	}

	/**
	 * Filters a string through the Cleaner class.
	 *
	 * This method is a wrapper for the actual filter_string method in the Cleaner class.
	 * It filters a given string based on the content filters defined in the Cleaner class.
	 *
	 * @since 5.0.0
	 *
	 * @param string $string The string to be filtered.
	 *
	 * @return string The filtered string.
	 */
	public function filter_string( $string ): string {
		$submission_cleaner = tribe( Cleaner::class );
		return $submission_cleaner->filter_string( $string );
	}

	/**
	 * Filters an array of strings through the Cleaner class.
	 *
	 * This method is a wrapper for the actual filter_string_array method in the Cleaner class.
	 * It filters each string in the given array based on the content filters defined in the Cleaner class.
	 *
	 * @since 5.0.0
	 *
	 * @param array $strings The array of strings to be filtered.
	 *
	 * @return array The array of filtered strings.
	 */
	public function filter_string_array( array $strings ): array {
		$submission_cleaner = tribe( Cleaner::class );
		return $submission_cleaner->filter_string_array( $strings );
	}

	/**
	 * Filters the allowed HTML tags through the Cleaner class.
	 *
	 * This method is a wrapper for the actual filter_allowed_html_tags method in the Cleaner class.
	 * It filters the allowed HTML tags for the wp_kses() sanitization of events submitted via the Community submission form.
	 *
	 * @since 5.0.0
	 *
	 * @param array  $tags    The array of HTML tags allowed through the wp_kses() filter.
	 * @param string $context The context for which the tags are being filtered.
	 *
	 * @return array The modified array of allowed HTML tags.
	 */
	public function filter_allowed_html_tags( $tags, $context ): array {
		$submission_cleaner = tribe( Cleaner::class );
		return $submission_cleaner->filter_allowed_html_tags( $tags, $context );
	}

	/**
	 * Retrieves the allowed fields for the event submission.
	 *
	 * This method applies a filter to modify the allowed fields for the event submission,
	 * allowing developers to add or modify the fields that are allowed in the event submission data.
	 * It also applies a deprecated filter for backward compatibility.
	 *
	 * @since 5.0.1
	 *
	 * @param int|null $submission_id The Submission ID.
	 *
	 * @return array Array of all allowed keys.
	 */
	public static function get_allowed_fields( ?int $submission_id = null ): array {
		/**
		 * Filter to modify the allowed fields for the event submission.
		 *
		 * Allows developers to add or modify the fields that are allowed
		 * in the event submission data.
		 *
		 * @since 5.0.0
		 *
		 * @param array $allowed_fields Array of all allowed keys.
		 * @param int $submission_id Submission ID.
		 */
		$allowed_fields = apply_filters( 'tec_events_community_allowed_fields', self::$allowed_fields, $submission_id );

		/**
		 * Apply the deprecated filter for backward compatibility.
		 *
		 * @since 5.0.0
		 * @deprecated 5.0.0 Use 'tec_events_community_allowed_fields' instead.
		 *
		 * @param array $allowed_fields Array of all allowed keys.
		 */
		$allowed_fields = apply_filters_deprecated(
			'tribe_events_community_allowed_event_fields',
			[ $allowed_fields ],
			'5.0.0',
			'tec_events_community_allowed_fields',
			'Use tec_events_community_allowed_fields instead.'
		);

		return $allowed_fields;
	}

	/**
	 * Retrieves the allowed inner fields for a given key in the event submission.
	 *
	 * This method applies a filter to add or modify the inner fields that are allowed
	 * for a specific top-level key in the submission data.
	 *
	 * @since 5.0.1
	 *
	 * @param string $key The top-level key for which inner fields are being filtered.
	 * @param int    $submission_id Submission ID.
	 *
	 * @return array An array of allowed inner fields for the given key.
	 */
	public static function get_inner_allowed_fields( string $key, int $submission_id = 0 ): array {
		/**
		 * Filter to add allowed inner fields for a given key.
		 *
		 * Allows developers to add or modify the inner fields
		 * that are allowed for a specific top-level key in the submission data.
		 *
		 * @since 5.0.0
		 *
		 * @param array  $allowed_inner_fields An array of allowed inner fields for the given key.
		 * @param string $key The top-level key for which inner fields are being filtered.
		 * @param int    $submission_id Submission ID, 0 if no submission is needed.
		 */
		return apply_filters( "tec_events_community_allowed_fields_inner_key_{$key}", [], $submission_id );
	}
}
