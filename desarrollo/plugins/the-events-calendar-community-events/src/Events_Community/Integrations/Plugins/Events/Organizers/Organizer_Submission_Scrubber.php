<?php

namespace TEC\Events_Community\Integrations\Plugins\Events\Organizers;

use Tribe__Events__Community__Submission_Scrubber;
use Tribe__Events__Main;

/**
 * Class Organizer_Submission_Scrubber
 *
 * Handles the scrubbing of organizer submissions.
 *
 * @since 5.0.0
 */
class Organizer_Submission_Scrubber extends Tribe__Events__Community__Submission_Scrubber {
	/**
	 * Allowed fields for organizer submission.
	 *
	 * @var array
	 */
	protected static array $allowed_fields = [
		'post_content',
		'post_title',
		'organizer',
	];

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The submission data.
	 */
	public function __construct( array $submission ) {
		parent::__construct( $submission );
	}

	/**
	 * Set the post type to organizer.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_post_type(): void {
		$this->submission['post_type'] = Tribe__Events__Main::ORGANIZER_POST_TYPE;
	}

	/**
	 * Filter organizer data.
	 *
	 * This method filters the organizer data to ensure it is properly formatted for saving.
	 *
	 * @since 5.0.0
	 *
	 * @param array $organizer_data The organizer data to filter.
	 *
	 * @return array The filtered organizer data.
	 */
	protected function filter_organizer_data( array $organizer_data ): array {
		if ( ! empty( $organizer_data['OrganizerID'] ) ) {
			$organizer_data['OrganizerID'] = array_map( 'intval', $organizer_data['OrganizerID'] );
		}

		$fields = [
			'Phone',
			'Website',
			'Email',
		];

		foreach ( $fields as $field ) {
			if ( isset( $organizer_data[ $field ] ) ) {
				$organizer_data[ $field ] = $this->filter_string( $organizer_data[ $field ] );
			}
		}

		return $organizer_data;
	}
}
