<?php

namespace TEC\Events_Community\Integrations\Plugins\Events\Venues;

use Tribe__Events__Community__Submission_Scrubber;
use Tribe__Events__Main;

/**
 * Class Venue_Submission_Scrubber
 *
 * Handles the scrubbing of venue submissions.
 *
 * @since 5.0.0
 */
class Venue_Submission_Scrubber extends Tribe__Events__Community__Submission_Scrubber {
	/**
	 * Allowed fields for venue submission.
	 *
	 * @var array
	 */
	protected static array $allowed_fields = [
		'post_content',
		'post_title',
		'venue',
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
	 * Prepare venue data for saving.
	 *
	 * The following block of code is taken from the events calendar code that it uses to prepare the data of venue for saving.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_venue(): void {
		$this->submission['venue'] = stripslashes_deep( $this->submission['venue'] );
		$this->submission['venue'] = $this->filter_venue_data( $this->submission['venue'] );
	}

	/**
	 * Set the post type to venue.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function set_post_type(): void {
		$this->submission['post_type'] = Tribe__Events__Main::VENUE_POST_TYPE;
	}
}
