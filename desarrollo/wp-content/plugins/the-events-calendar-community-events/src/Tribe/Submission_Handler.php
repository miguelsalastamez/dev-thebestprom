<?php

use TEC\Events_Community\Submission\Messages;
use TEC\Events_Community\Submission\Save;
use TEC\Events_Community\Submission\Validator;

class Tribe__Events__Community__Submission_Handler {
	/**
	 * @var Tribe__Events__Community__Main
	 */
	protected $community = null;

	/**
	 * @var array
	 */
	protected $submission = [];

	/**
	 * @var array
	 */
	protected $original_submission = [];

	/**
	 * @var int
	 */
	protected $event_id = 0;

	/**
	 * @var bool|null
	 */
	protected $valid = null;

	/**
	 * @var array
	 */
	protected $messages = [];

	/**
	 * Submission scrubber.
	 *
	 * @since 4.5.5
	 *
	 * @var Tribe__Events__Community__Submission_Scrubber
	 */
	protected $scrubber;

	/**
	 * Constructor.
	 *
	 * @param array $submission The submission data.
	 * @param ?int  $event_id The event ID.
	 */
	public function __construct( array $submission, ?int $event_id ) {
		$this->community           = tribe( 'community.main' );
		$this->original_submission = $submission;
		$submission['ID']          = $event_id;
		$this->submission          = $submission;
		$this->scrubber            = new Tribe__Events__Community__Submission_Scrubber( $this->submission );
		$this->submission          = $this->scrubber->scrub();
		$this->messages            = Messages::get_instance();

		$this->apply_map_defaults();

		$this->event_id = $event_id;
	}

	/**
	 * Validates the submission.
	 *
	 * @since 5.0.0
	 *
	 * @return bool True if the submission is valid, false otherwise.
	 */
	public function validate(): bool {
		$validator = new Validator();
		return $validator->check_submission( $this->submission, $this->event_id );
	}

	/**
	 * Get the sanitized submission array.
	 *
	 * @since 5.0.0
	 *
	 * @return array The sanitized submission array.
	 */
	public function get_submission(): array {
		return $this->submission;
	}

	/**
	 * Save the event.
	 *
	 * @since 5.0.0
	 *
	 * @return false|int The event ID if the save operation was successful, or false on failure.
	 */
	public function save() {
		$save_submission = new Save( $this->submission );
		return $save_submission->save();
	}

	/**
	 * Get the validation messages.
	 *
	 * @since 5.0.0
	 *
	 * @return array The array of validation messages.
	 */
	public function get_messages(): array {
		return $this->messages->get_messages();
	}

	/**
	 * Default to enabling the Show Map and Show Map Link fields for newly submitted
	 * events and venues.
	 *
	 * Those settings are not currently exposed in the frontend submission form, so
	 * we're making an assumption that, in most cases, it is desirable to show both the
	 * map and the map link ... however, if by means of a customization those fields
	 * *have* been enabled (and the scrubber has been configured to allow their
	 * submission) then we will not attempt to set to a default.
	 *
	 * @since 4.5.5
	 */
	protected function apply_map_defaults(): void {
		/**
		 * Controls whether Community should attempt to automatically apply
		 * default settings for the event and venue Show Map/Show Map Link fields.
		 *
		 * @since 4.5.5
		 *
		 * @param bool $apply_map_defaults
		 */
		if ( ! apply_filters( 'tribe_events_community_apply_map_defaults', true ) ) {
			add_filter( 'tribe_events_venue_created_map_default', [ $this, 'set_map_default_value' ] );
			return;
		}

		/**
		 * Allow alteration of the submission array to add or modify keys as needed.
		 * Can be used to enable features such as `EventShowMap` or `VenueShowMapLink`.
		 *
		 * @since 5.0.0
		 *
		 * @param array $submission The current submission array.
		 */
		$this->submission = apply_filters( 'tec_events_community_alter_submission_mapping', $this->submission );
	}

	/**
	 * Function called by the filter tribe_events_community_apply_map_defaults to change the value to `false`.
	 * When the filter `tribe_events_community_apply_map_defaults` is set to false we also need to set the value of the
	 * checkboxes to false.
	 *
	 * @since 4.5.14
	 *
	 * @return string The default map value.
	 */
	public function set_map_default_value(): string {
		return 'false';
	}
}
