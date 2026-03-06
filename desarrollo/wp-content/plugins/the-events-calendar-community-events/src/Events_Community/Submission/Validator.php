<?php

namespace TEC\Events_Community\Submission;

use Tribe__Events__Community__Main;
use Tribe__Events__Community__Submission_Scrubber;

/**
 * Class Validate
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Submission
 */
class Validator {
	/**
	 * Indicates whether the submission is valid.
	 *
	 * @var bool|null
	 */
	protected ?bool $submission_valid = null;

	/**
	 * Holds the validation messages that have occurred.
	 *
	 * @var array
	 */
	protected array $validation_messages = [];

	/**
	 * Instance of Tribe__Events__Community__Main.
	 *
	 * @var Tribe__Events__Community__Main
	 */
	protected Tribe__Events__Community__Main $ce_main;

	/**
	 * Instance of Messages.
	 *
	 * @var Messages
	 */
	protected Messages $messages;

	/**
	 * @var Tribe__Events__Community__Submission_Scrubber
	 */
	protected Tribe__Events__Community__Submission_Scrubber $submission_scrubber;

	/**
	 * Validator constructor.
	 */
	public function __construct() {
		$this->ce_main          = tribe( Tribe__Events__Community__Main::class );
		$this->messages         = Messages::get_instance();
		$this->submission_valid = false;
	}

	/**
	 * Checks the validity of the submission. This assumes that the submission has been properly scrubbed.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data to be validated.
	 * @param int   $event_id The Post/Event ID being edited or created.
	 *
	 * @return bool True if the submission is valid, false otherwise.
	 */
	public function check_submission( array $submission, int $event_id ): bool {
		// Clear previous messages.
		$this->messages->clear_messages();
		// At this point the form is valid.
		$this->submission_valid = true;

		if ( ! is_user_logged_in() ) {
			// If anonymous submission is not enabled, automatically make the validation false.
			if ( ! $this->ce_main->allowAnonymousSubmissions ) {
				$this->submission_valid = false;
			}
			// If the user is logged out, always do a spam check.
			$this->ce_main->spam_check( $submission ); // exits on failure.
		}

		if ( ! $this->validate_submission_has_required_fields( $submission ) ) {
			$this->submission_valid = false;
		}

		if ( ! $this->validate_field_contents( $submission ) ) {
			$this->submission_valid = false;
		}

		if ( ! $this->check_existing_post_validity( $event_id ) ) {
			$this->submission_valid = false;
		}

		/**
		 * Filters the validation status of the entire submission.
		 *
		 * This filter allows for custom validation logic to be applied to the overall submission.
		 *
		 * @since 5.0.0
		 *
		 * @param bool   $submission_valid The current validation status.
		 * @param array  $submission The submission data.
		 * @param object $this The current instance of the Validator class.
		 *
		 * @return bool The filtered validation status.
		 */
		$this->submission_valid = apply_filters( 'tribe_community_events_validate_submission', $this->submission_valid, $submission, $this );

		return $this->submission_valid;
	}

	/**
	 * Checks the validity of an existing event post.
	 *
	 * @since 5.0.0
	 *
	 * @param int $event_id The ID of the event post to check.
	 *
	 * @return bool True if the event post is valid, false otherwise.
	 */
	protected function check_existing_post_validity( int $event_id ): bool {
		$valid = true;
		$event = get_post( $event_id );

		if ( empty( $event ) ) {
			return false;
		}

		$is_author                       = get_current_user_id() === (int) $event->post_author;
		$is_draft                        = 'auto-draft' === $event->post_status;
		$user_can_edit                   = $this->ce_main->user_can_edit( $event_id, $this->ce_main->get_community_events_post_type() );
		$user_can_edit_their_submission  = $this->ce_main->user_can_edit_their_submissions( $event_id );
		$is_editing_enabled_for_user     = $user_can_edit || $user_can_edit_their_submission;
		$events_label_singular_lowercase = $this->ce_main->get_event_label( 'lowercase' );

		// If the event is a draft, and you are not an author, or if the event is not a draft and editing is disabled, set to false.
		if ( ( $is_draft && ! $is_author ) || ( ! $is_draft && ! $is_editing_enabled_for_user ) ) {
			$valid = false;
			/* translators: %s: Singular label of the event */
			$message = sprintf( __( 'There was a problem saving your %s, please try again.', 'tribe-events-community' ), $events_label_singular_lowercase );
			$this->messages->add_message( $message, 'error' );
		}

		return $valid;
	}

	/**
	 * Validates that the submission has all required fields.
	 *
	 * Checks each required field to ensure it has a value in the submission data.
	 * Utilizes dot notation for nested keys and allows extensions via hooks for custom validation logic.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data to be validated.
	 *
	 * @return bool True if all required fields are present and valid, false otherwise.
	 */
	protected function validate_submission_has_required_fields( array $submission ): bool {
		$required_fields = $this->ce_main->required_fields_for_submission();

		$valid = true;
		foreach ( $required_fields as $key ) {
			if ( ! $this->submission_has_value_for_key( $submission, $key ) ) {
				// translators: %s is the field label.
				$message_placeholder = __( '%s is required', 'tribe-events-community' );
				$message             = sprintf( $message_placeholder, $this->get_field_label( $key ) );
				$this->messages->add_message( $message, 'error' );
				$valid = false;
				break;
			}
		}

		return $valid;
	}

	/**
	 * Checks if the submission has a value for the given key.
	 *
	 * Supports dot notation for nested keys and allows extensions for custom logic
	 * via hooks specific to each key.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Updated logic so that $key is case insensitive.
	 *
	 * @param array  $submission The submission data.
	 * @param string $key The key to check, potentially in dot notation.
	 *
	 * @return bool True if the key exists and has a value, false otherwise.
	 */
	protected function submission_has_value_for_key( array $submission, string $key ): bool {
		$key = strtolower( $key );
		// Convert all keys in the submission to lowercase.
		$submission = $this->array_change_key_case_recursive( $submission, CASE_LOWER );

		/**
		 * Filters the custom validation for a specific submission key.
		 *
		 * Allows for custom validation logic for specific keys in the submission data.
		 * If a non-null value is returned by the filter, it will be used as the validation result.
		 * The key will be lowercase.
		 *
		 * @since 5.0.0
		 *
		 * @param mixed $custom_validation The custom validation result. Default null.
		 * @param array $submission The submission data.
		 */
		$custom_validation = apply_filters( "tec_events_community_submission_custom_required_validation_{$key}", null, $submission );

		if ( null !== $custom_validation ) {
			return (bool) $custom_validation;
		}

		// event_image has special validation, that is done here.
		if ( 'event_image' === $key ) {
			return $this->event_image_required( $submission['id'] );
		}
		// Support dot-separated paths such as "tax_input.tribe_events_cat".
		$keys = explode( '.', $key );

		foreach ( $keys as $key_part ) {
			// Convert the key part to lowercase.
			$key_part_lower = strtolower( $key_part );

			if ( ! is_array( $submission ) || empty( $submission[ $key_part_lower ] ) ) {
				// translators: %s is the field label.
				$message_placeholder = __( '%s is required', 'tribe-events-community' );
				$message             = sprintf( $message_placeholder, $this->get_field_label( $key ) );
				$this->messages->add_message( $message, 'error' );
				return false;
			}

			$submission = $submission[ $key_part_lower ];
		}

		return true;
	}

	/**
	 * Validates the contents of the submission fields.
	 *
	 * Checks each field in the submission to ensure its value is valid. Allows extensions
	 * via hooks for custom field validation logic.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data to be validated.
	 *
	 * @return bool True if all field contents are valid, false otherwise.
	 */
	protected function validate_field_contents( array $submission ): bool {
		$valid = true;

		foreach ( $submission as $key => $value ) {
			if ( ! $this->is_field_data_valid( $key, $value ) ) {
				// translators: %s is the field label that is invalid.
				$message_template = __( 'Invalid value for %s', 'tribe-events-community' );
				$message          = sprintf( $message_template, $this->get_field_label( $key ) );
				$this->messages->add_message( $message, 'error' );
				$valid = false;
			}
		}

		// Validate the image uploaded.
		$valid = $valid && $this->validate_image_upload();

		/**
		 * Filter to validate custom field contents.
		 *
		 * This filter allows custom validation logic to be applied to the submission fields.
		 *
		 * @since 5.0.0
		 *
		 * @param bool  $valid The current validation status.
		 * @param array $submission The submission data.
		 */
		$valid = apply_filters( 'tec_events_community_validate_field_contents', $valid, $submission );

		return $valid;
	}

	/**
	 * Validates the data for a specific field.
	 *
	 * This method uses a filter to allow custom validation logic for specific fields.
	 * The filter 'tribe_community_is_field_valid' can be used to modify the validation
	 * logic for different fields based on the field key and its value.
	 *
	 * @since 5.0.0
	 *
	 * @param string $key The field key to validate.
	 * @param mixed  $value The value of the field to validate.
	 *
	 * @return bool True if the field data is valid, false otherwise.
	 */
	protected function is_field_data_valid( string $key, $value ) {
		/**
		 * Filters the validation logic for a specific field.
		 *
		 * This filter allows custom validation logic to be applied to the data of specific fields.
		 * Developers can use this filter to modify the validation result based on the field key and its value.
		 *
		 * @since 5.0.0
		 *
		 * @param bool   $valid Initial validation status. Default is true.
		 * @param string $key The field key to validate.
		 * @param mixed  $value The value of the field to validate.
		 */
		return apply_filters( 'tribe_community_is_field_valid', true, $key, $value );
	}

	/**
	 * Retrieves the human-readable label for a given field.
	 *
	 * This method returns a human-readable label for a given field, translating field names
	 * into user-friendly labels where possible. It handles special cases such as post titles,
	 * post content, for all else it uses `format_field_name_as_label`.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Added additional logic for when $field is not the expected case.
	 *
	 * @param string $field The field name for which to retrieve the label.
	 *
	 * @return string The human-readable label corresponding to the field.
	 */
	public function get_field_label( string $field ): string {
		$allowed_fields_mapping = Tribe__Events__Community__Submission_Scrubber::get_allowed_fields( null );
		// Create a lowercase-keyed version of $allowed_fields_mapping for lookup.
		$lowercase_allowed_fields_mapping = array_combine(
			array_map( 'strtolower', $allowed_fields_mapping ),
			$allowed_fields_mapping
		);
		$lowercase_field                  = strtolower( $field );
		$correct_case_field               = $lowercase_allowed_fields_mapping[ $lowercase_field ] ?? $field;
		$events_label_singular            = tribe( Tribe__Events__Community__Main::class )->get_event_label( 'singular' );

		switch ( $correct_case_field ) {
			case 'post_title':
				// translators: %s is the event label (singular).
				$label = sprintf( __( '%s Title', 'tribe-events-community' ), $events_label_singular );
				break;
			case 'post_content':
				// translators: %s is the event label (singular).
				$label = sprintf( __( '%s Description', 'tribe-events-community' ), $events_label_singular );
				break;
			case 'terms':
				$label = _x( 'Terms of submission', 'field label for terms of submission', 'tribe-events-community' );
				break;
			default:
				$label = $this->format_field_name_as_label( $correct_case_field );
				break;
		}

		/**
		 * Filters the label for a community form field.
		 *
		 * @since 5.0.0
		 *
		 * @param string $label The label for the form field.
		 * @param string $field The field name.
		 *
		 * @return string The filtered label for the form field.
		 */
		return apply_filters( 'tribe_community_form_field_label', $label, $field );
	}

	/**
	 * Retrieves the attachment array for validation.
	 *
	 * This method retrieves the attachment array based on the provided file input key.
	 *
	 * @since 5.0.0
	 *
	 * @param string $file_input_key The key of the file input in the $_FILES array.
	 *
	 * @return array|null The attachment array, or null if no attachment is present.
	 */
	protected function get_attachment_array( string $file_input_key = 'event_image' ): ?array {
		// Check if the file input exists in the $_FILES array.
		if ( empty( $_FILES[ $file_input_key ] ) || empty( $_FILES[ $file_input_key ]['name'] ) ) {
			return null;
		}

		// Return the attachment array.
		return $_FILES[ $file_input_key ];
	}

	/**
	 * Formats a field name into a human-readable label.
	 *
	 * This function splits a camelCase or snake_case field name into separate words
	 * and capitalizes each word.
	 *
	 * @since 5.0.0
	 *
	 * @param string $field The field name to be formatted.
	 *
	 * @return string The formatted human-readable label.
	 */
	protected function format_field_name_as_label( string $field ): string {
		// Regular expression to split camelCase or snake_case words.
		$regex = '/(?#! splitCamelCase Rev:20140412)
        (?<=[a-z])      # Position is after a lowercase,
        (?=[A-Z])       # and before an uppercase letter.
    | (?<=[A-Z])        # Or g2of2; Position is after uppercase,
        (?=[A-Z][a-z])  # and before upper-then-lower case.
    /x';
		// Split the field name into separate words.
		$parts = preg_split( $regex, $field );
		// Join the words with spaces.
		$label = implode( ' ', $parts );
		// Replace underscores with spaces.
		$label = str_replace( '_', ' ', $label );
		// Capitalize each word.
		$label = ucwords( $label );
		return $label;
	}

	/**
	 * Validates the uploaded image for the submission.
	 *
	 * This method checks the uploaded image for errors and validates its MIME type.
	 * If an error is found or the MIME type is invalid, an error message is added
	 * to the validation messages and the validation status is set to false.
	 *
	 * @since 5.0.0
	 *
	 * @return bool True if the image upload is valid, false otherwise.
	 */
	protected function validate_image_upload(): bool {
		$valid      = true;
		$attachment = $this->get_attachment_array();

		if ( $attachment ) {
			if ( ! empty( $attachment['error'] ) ) {
				$this->messages->add_message( $this->get_img_upload_error_msg( $attachment['error'] ), 'error' );
				$valid = false;
			} elseif ( ! in_array( $attachment['type'], $this->ce_main->allowed_image_upload_mime_types() ) ) {
				$message = esc_html__( 'Images must be png, jpg, or gif', 'tribe-events-community' );
				$this->messages->add_message( $message, 'error' );
				$valid = false;
			}
		}

		return $valid;
	}

	/**
	 * Retrieves an error message corresponding to the given upload error code for images.
	 *
	 * @since 5.0.0
	 *
	 * @param int $upload_error_code The error code returned by the file upload process.
	 *
	 * @return string The error message corresponding to the upload error code.
	 */
	protected function get_img_upload_error_msg( int $upload_error_code ): string {
		switch ( $upload_error_code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'Image exceeded the allowed file size', 'tribe-events-community' );

			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
				return __( 'The image failed to upload successfully', 'tribe-events-community' );

			default:
				return __( 'The uploaded image could not be processed', 'tribe-events-community' );
		}
	}

	/**
	 * Recursively changes the keys of an array to the specified case.
	 *
	 * This function will recursively traverse an array and change
	 * all its keys to the specified case. It uses `array_change_key_case`
	 * internally for the actual key case change.
	 *
	 * @since 5.0.1
	 *
	 * @param array $arr The array to change keys case.
	 * @param int   $text_case Either `CASE_UPPER` or `CASE_LOWER`.
	 *
	 * @return array The array with all keys in the specified case.
	 */
	protected function array_change_key_case_recursive( $arr, $text_case = CASE_LOWER ) {
		return array_map(
			function ( $item ) use ( $text_case ) {
				if ( is_array( $item ) ) {
					$item = $this->array_change_key_case_recursive( $item, $text_case );
				}
				return $item;
			},
			array_change_key_case( $arr, $text_case )
		);
	}

	/**
	 * Checks if an event image is required.
	 *
	 * This function checks if an event image file has been uploaded and if there were no errors during the upload.
	 *
	 * @since 5.0.1
	 *
	 * @param int $event_id ID of the event to check if an image is still required.
	 *
	 * @return bool True if the event image is uploaded and there are no errors, false otherwise.
	 */
	public function event_image_required( int $event_id ): bool {
		// Check if the post has a thumbnail.
		if ( has_post_thumbnail( $event_id ) ) {
			return true; // Image already exists, so it's not required.
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( ! isset( $_FILES['event_image'] ) || UPLOAD_ERR_OK !== $_FILES['event_image']['error'] ) {
			return false;
		}

		return true; // Image is filled out.
	}
}
