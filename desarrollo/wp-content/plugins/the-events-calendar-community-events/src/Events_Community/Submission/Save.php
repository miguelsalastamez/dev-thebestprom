<?php

namespace TEC\Events_Community\Submission;

use Tribe__Events__Community__Main;
use Tribe__Utils__Array;

/**
 * Class Save
 *
 * @since 5.0.0
 *
 * Handles saving, updating events, and uploading images for submissions.
 */
class Save {
	/**
	 * The submission data.
	 *
	 * @var array
	 */
	protected array $submission;

	/**
	 * @var Messages
	 */
	protected Messages $messages;

	/**
	 * @var Tribe__Events__Community__Main
	 */
	protected Tribe__Events__Community__Main $community;

	/**
	 * @var int
	 */
	protected int $event_id = 0;

	/**
	 * Flag indicating whether the handler has been set.
	 *
	 * This static property is used to ensure that the handler is only set once
	 * during the lifecycle of the `Save` class instance. It prevents the
	 * `tec_events_community_submission_save_handler` filter from being applied
	 * multiple times.
	 *
	 * @since 5.0.0
	 *
	 * @var bool
	 */
	protected static bool $handler_set = false;

	/**
	 * The callback handler for saving the event submission.
	 *
	 * This static property stores the handler function that is responsible for
	 * saving the event submission. The handler is set via the
	 * `tec_events_community_submission_save_handler` filter.
	 *
	 * @since 5.0.0
	 *
	 * @var ?callable
	 */
	protected static $handler = null;

	/**
	 * Save constructor.
	 *
	 * @param array $submission The submission data.
	 */
	public function __construct( array $submission ) {
		$this->messages   = Messages::get_instance();
		$this->submission = $submission;
		$this->event_id   = (int) $submission['ID'];
		$this->community  = tribe( 'community.main' );
	}

	/**
	 * Resets the handler state to ensure the filter is reapplied.
	 *
	 * This method resets the static properties `$handler_set` and `$handler` to
	 * ensure that the `tec_events_community_submission_save_handler` filter is
	 * reapplied. This is useful for preventing test leakage when running multiple
	 * tests that rely on different handlers.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public static function reset_handler() {
		self::$handler_set = false;
		self::$handler     = null;
	}

	/**
	 * Save the submission data.
	 *
	 * This method handles generic pieces such as the image upload.
	 * To create or update an event, that must be done via `tec_events_community_submission_save_handler`.
	 *
	 * @since 5.0.0
	 *
	 * @return int|false The event ID if the save operation was successful, or false on failure.
	 */
	public function save() {
		$events_label_singular           = $this->community->get_event_label( 'singular' );
		$events_label_singular_lowercase = $this->community->get_event_label( 'lowercase' );

		/**
		 * Filters the submission data before it is saved.
		 *
		 * This filter allows hooking into the submission data to make any necessary changes
		 * to the array keys or values before the data is saved. It can be used for modifying
		 * cases, altering data, or adding custom fields.
		 *
		 * @since 5.0.0
		 *
		 * @param array $submission The submission data.
		 */
		$this->submission = apply_filters( 'tec_events_community_before_save_submission', $this->submission );

		// The URL of the form submission page if the user want's to create a new event.
		$submit_url = $this->community->get_submission_url();

		if ( ! self::$handler_set ) {
			/**
			 * Filters the event submission save handler.
			 *
			 * This filter allows hooking into the event submission save process.
			 *
			 * Only the last handler that is sent to this filter will run. To overwrite the logic use a higher priority.
			 *
			 * @since 5.0.0
			 *
			 * @param callable $handler The callback handler for saving the event submission.
			 *
			 * @return callable The callback function for saving the event.
			 */
			self::$handler     = apply_filters( 'tec_events_community_submission_save_handler', '__return_false' );
			self::$handler_set = true; // Ensure filter is only applied once.
		}

		if ( ! is_callable( self::$handler ) ) {
			// Generic message if the handler is not available.
			$this->messages->add_message( __( 'Something went wrong saving the event.', 'tribe-events-community' ), 'error' );
			// In the chance there is an issue, return the Event ID as 0.
			return 0;
		}

		// Call the above handler to save the event.
		$success = call_user_func( self::$handler, $this->submission, $this->event_id );

		if ( ! $success ) {
			// translators: %s is the event type in lowercase.
			$error_message = sprintf( __( 'There was a problem saving your %s, please try again.', 'tribe-events-community' ), $events_label_singular_lowercase );

			// Add the error message.
			$this->messages->add_message( esc_html( $error_message ), 'error' );
			do_action( 'tribe_community_event_save_failure', $this->event_id );
			return 0;
		}

		// translators: %s is the event type.
		$saved_message    = sprintf( __( '%s updated.', 'tribe-events-community' ), $events_label_singular );
		$saved_event_link = $this->community->get_view_edit_links( $this->event_id );
		$message_string   = "{$saved_message} {$saved_event_link}";

		// Add the success message.
		$this->messages->add_message( $message_string, 'success' );

		// translators: %s is the event type in lowercase.
		$submit_another_message = sprintf( __( 'Submit another %s', 'tribe-events-community' ), $events_label_singular_lowercase );
		$submit_another_link    = '<a href="' . esc_url( $submit_url ) . '">' . esc_html( $submit_another_message ) . '</a>';

		// Add the link to submit another event.
		$this->messages->add_message( $submit_another_link, 'success' );
		do_action( 'tribe_community_event_save_updated', $this->event_id );

		$this->handle_image_upload();

		return $success;
	}

	/**
	 * Handle image upload.
	 *
	 * @since 5.0.0
	 *
	 * @return bool True if the image was handled successfully, false otherwise.
	 */
	public function handle_image_upload(): bool {
		$image_name = Tribe__Utils__Array::get( $_FILES, [ 'event_image', 'name' ] );
		$image_size = Tribe__Utils__Array::get( $_FILES, [ 'event_image', 'size' ] );

		if ( $image_name && $image_size <= $this->community->max_file_size_allowed() ) {
			$attachment_id = $this->insert_attachment( 'event_image', $this->event_id, true );

			if ( false === $attachment_id ) {
				$this->event_id = false;
				return false;
			}
		}

		if ( isset( $this->submission['detach_thumbnail'] ) && 'true' === (string) $this->submission['detach_thumbnail'] ) {
			delete_post_meta( $this->event_id, '_thumbnail_id' );
		}

		return true;
	}

	/**
	 * Insert an attachment.
	 *
	 * @since 5.0.0
	 *
	 * @param string $file_handler The upload.
	 * @param int    $post_id The post to attach the upload to.
	 * @param bool   $set_post_thumbnail To set or not to set the thumb.
	 *
	 * @return int|false The attachment's ID, or false on failure.
	 */
	protected function insert_attachment( string $file_handler, int $post_id, bool $set_post_thumbnail = false ) {
		// Check for successful upload.
		if ( $_FILES[ $file_handler ]['error'] !== UPLOAD_ERR_OK ) {
			return false;
		}

		$uploaded_file_type = wp_check_filetype( basename( $_FILES[ $file_handler ]['name'] ) );

		$this->load_media_dependencies();

		if ( ! $this->is_valid_image_type( $uploaded_file_type['type'] ) ) {
			$this->messages->add_message( esc_attr__( 'The file is not an Image', 'tribe-events-community' ), 'error' );
			return false;
		}

		$attach_id = media_handle_upload( $file_handler, $post_id );

		if ( false === $attach_id || ! $this->is_valid_image( $attach_id ) ) {
			return false;
		}

		if ( $set_post_thumbnail ) {
			update_post_meta( $post_id, '_thumbnail_id', $attach_id );
		}

		return $attach_id;
	}

	/**
	 * Load WordPress media dependencies.
	 */
	protected function load_media_dependencies(): void {
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}

	/**
	 * Check if the file type is a valid image type.
	 *
	 * @param string $file_type The MIME type of the file.
	 *
	 * @return bool True if the file type is valid, false otherwise.
	 */
	protected function is_valid_image_type( string $file_type ): bool {
		$allowed_file_types = $this->community->allowed_image_upload_mime_types();
		return in_array( $file_type, $allowed_file_types );
	}

	/**
	 * Validate if the uploaded file is a valid image.
	 *
	 * @since 5.0.0
	 *
	 * @param int $attach_id The attachment ID.
	 *
	 * @return bool True if the image is valid, false otherwise.
	 */
	protected function is_valid_image( int $attach_id ): bool {
		$image_path    = get_attached_file( $attach_id );
		$editor        = wp_get_image_editor( $image_path );
		$image_details = @getimagesize( $image_path );
		$status        = true;

		if ( is_wp_error( $editor ) ) {
			$this->messages->add_message( $editor->get_error_message(), 'error' );
			$status = false;
		} elseif ( false === $image_details ) {
			$this->messages->add_message( esc_attr__( 'The file is not an Image', 'tribe-events-community' ), 'error' );
			$status = false;
		} elseif ( empty( $image_details[0] ) || ! is_numeric( $image_details[0] ) || empty( $image_details[1] ) || ! is_numeric( $image_details[1] ) ) {
			$this->messages->add_message( esc_attr__( 'The image size is invalid', 'tribe-events-community' ), 'error' );
			$status = false;
		} elseif ( empty( $image_details[2] ) || ! $this->is_allowed_image_type( $image_details['mime'] ) ) {
			$this->messages->add_message( esc_attr__( 'The file is not a valid image', 'tribe-events-community' ), 'error' );
			$status = false;
		}

		if ( false === $status ) {
			// Something is wrong with the file, purge it.
			wp_delete_attachment( $attach_id, true );
		}

		return $status;
	}

	/**
	 * Check if the image type is allowed based on the MIME types.
	 * List can be altered by using the filter `tec_events_community_image_mime_types`.
	 *
	 * @param string $mime_type The mime type of the image.
	 *
	 * @return bool True if the image type is allowed, false otherwise.
	 */
	protected function is_allowed_image_type( string $mime_type ): bool {
		$mime_types = $this->community->allowed_image_upload_mime_types();

		return in_array( $mime_type, $mime_types );
	}
}
