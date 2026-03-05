<?php

class Tribe__Events__Community__Event_Form {
	protected $event = null;
	protected $event_id = 0;
	protected $required_fields = [];
	protected $error_fields = [];

	public function __construct( $event, $required_fields = [], $error_fields = [] ) {
		$this->set_event( $event );
		$this->set_error_fields( $error_fields );
		$this->set_required_fields( $required_fields );
	}

	/**
	 * Sets the event for the form.
	 *
	 * This method checks if the provided event object is valid. If the event is not provided, is empty, or has an
	 * invalid ID, it will create a new auto-draft event if the user is logged in or anonymous submissions are enabled.
	 * Otherwise, it will set the event and its ID for further processing.
	 *
	 * @since 5.0.2
	 *
	 * @param WP_Post|null $event The event object. Can be null or an incomplete object without a valid ID.
	 */
	public function set_event( $event ) {
		$anonymous_submissions_enabled = tribe( 'community.main' )->allowAnonymousSubmissions;

		// Check if the user is logged in or if anonymous submissions are enabled.
		if ( ( ! is_user_logged_in() && ! $anonymous_submissions_enabled ) ) {
			// If neither condition is met, do not create an auto-draft.
			return;
		}

		// If the event object is invalid or has an invalid ID, create an auto-draft event and assign it.
		if ( empty( $event ) || ! is_object( $event ) || empty( $event->ID ) ) {
			$event_id = $this->create_auto_draft_event();
			$event    = get_post( $event_id );
		}

		// Set the event and event ID (whether newly created or valid).
		$this->event    = $event;
		$this->event_id = $event->ID;

		// Synchronize the global $post object with the current event.
		$this->sync_global_post();
	}

	/**
	 * Creates an auto-draft event.
	 *
	 * @since 5.0.2
	 *
	 * Creates a new auto-draft event post.
	 * Auto-drafts are used as placeholders for new events until they are fully populated with data.
	 *
	 * @return int The post ID of the created auto-draft event.
	 */
	private function create_auto_draft_event() {
		return wp_insert_post(
			[
				'post_title'  => _x( 'Auto Draft', 'Tribe Community fallback post title', 'tribe-events-community' ),
				'post_type'   => tribe( 'community.main' )->get_community_events_post_type(),
				'post_status' => 'auto-draft',
			]
		);
	}

	/**
	 * Synchronizes the global $post object with the current event.
	 *
	 * @since 5.0.2
	 *
	 * Ensures that the global $post object matches the $this->event object to avoid discrepancies when $post is used
	 * elsewhere in the code.
	 */
	protected function sync_global_post() {
		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $this->event;

		// Check if the post title matches the auto-draft placeholder title and set it to an empty string if it does.
		if ( _x( 'Auto Draft', 'Tribe Community fallback post title', 'tribe-events-community' ) === $post->post_title ) {
			$post->post_title = '';
		}
	}

	/**
	 * sets the error fields for the form
	 */
	public function set_error_fields( $error_fields = [] ) {
		$this->error_fields = $error_fields;
	}

	/**
	 * sets the required fields for the form
	 */
	public function set_required_fields( $required_fields = [] ) {
		$this->required_fields = $required_fields;
	}

	/**
	 * Returns the event id for the event form
	 */
	public function get_event_id() {
		return $this->event_id;
	}

	public function render() {
		$edit_template = $this->get_template_path();
		$this->setup_hooks();
		ob_start();
		tribe_doing_frontend( true );
		do_action( 'tribe_events_community_form', $this->event_id, $this->event, $edit_template );
		$output = ob_get_clean();
		$this->clear_hooks();
		return $output;
	}

	protected function get_template_path() {
		return Tribe__Events__Community__Templates::getTemplateHierarchy(
			'community/edit-event',
			[ 'disable_view_check' => true ]
		);
	}

	protected function setup_hooks() {
		/**
		 * Fires to allow custom hooks and setup for the community event form.
		 *
		 * This action is used to set up custom hooks and additional functionality for the community event form.
		 * It provides a way for developers to add their own hooks or customize the form behavior.
		 *
		 * @since 5.0.0
		 */
		do_action( 'tec_events_community_event_form_setup_hooks' );

		// hooks that will need to be removed after we're done rendering.
		add_action( 'tribe_community_events_field_has_error', [ $this, 'indicate_field_errors' ], 10, 2 );

		// @todo redscar - I believe the below isn't required anymore. Leaving commented out until I dive into it more.
		// add_filter( 'tribe_display_event_linked_post_dropdown_id', [ $this, 'filter_linked_post_id' ], 10, 2 );
		add_filter( 'get_edit_post_link', [ $this, 'filter_edit_post_url' ], 10, 3 );

		if ( ! empty( $_POST ) ) {
			// @todo redscar - Is this section still required, if yes we need to move it into the integration since most of these are TEC filters. Otherwise, we can remove.
			add_filter( 'tribe_get_event_website_url', [ $this, 'filter_website_url_value' ], 10, 2 );
			add_filter( 'tribe_community_custom_field_value', [ $this, 'filter_custom_field_value' ], 10, 2 );

			add_filter( 'tribe_get_organizer', [ $this, 'filter_organizer_value' ], 10, 1 );
			add_filter( 'tribe_get_organizer_phone', [ $this, 'filter_organizer_phone' ], 10, 1 );
			add_filter( 'tribe_get_organizer_website_url', [ $this, 'filter_organizer_website' ], 10, 1 );
			add_filter( 'tribe_get_organizer_email', [ $this, 'filter_organizer_email' ], 10, 1 );

			add_filter( 'tribe_get_venue', [ $this, 'filter_venue_name' ], 10, 1 );
			add_filter( 'tribe_get_phone', [ $this, 'filter_venue_phone' ], 10, 1 );
			add_filter( 'tribe_get_address', [ $this, 'filter_venue_address' ], 10, 1 );
			add_filter( 'tribe_get_city', [ $this, 'filter_venue_city' ], 10, 1 );
			add_filter( 'tribe_get_province', [ $this, 'filter_venue_province' ], 10, 1 );
			add_filter( 'tribe_get_state', [ $this, 'filter_venue_state' ], 10, 1 );
			add_filter( 'tribe_get_country', [ $this, 'filter_venue_country' ], 10, 1 );
			add_filter( 'tribe_get_zip', [ $this, 'filter_venue_zip' ], 10, 1 );
		}

		// Hooks that are fine to leave in place.
		add_action( 'tribe_events_community_form', [ $this, 'print_form' ], 10, 3 );
		add_filter( 'tribe_events_linked_posts_dropdown_enable_creation', [ $this, 'create_linked_posts_mode' ], 10, 2 );

		remove_filter( 'the_content', 'do_shortcode', 11 );

		// Get data from $_POST and override core function.
		add_filter( 'tribe_get_hour_options', [ $this, 'getHours' ], 10, 3 );
		add_filter( 'tribe_get_minute_options', [ $this, 'getMinutes' ], 10, 3 );
		add_filter( 'tribe_get_meridian_options', [ $this, 'getMeridians' ], 10, 3 );

		/**
		 * @todo redscar - move logic to integration?
		 */
		if ( class_exists( 'Tribe__Events__Tickets__Eventbrite__Main' ) ) {
			// Remove the eventbrite method hooked into the event form, if it exists.
			remove_action( 'tribe_events_cost_table', [ Tribe__Events__Tickets__Eventbrite__Main::instance(), 'eventBriteMetaBox' ], 1 );
		}

		/**
		 * @todo redscar - move logic to integration?
		 */
		if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
			remove_action( 'tribe_events_date_display', [ 'Tribe__Events__Pro__Recurrence__Meta', 'loadRecurrenceData' ] );
			add_action( 'tribe_events_date_display', [ $this, 'loadRecurrenceData' ] );
		}
	}

	public function clear_hooks() {
		remove_action( 'tribe_community_events_field_has_error', [ $this, 'indicate_field_errors' ], 10, 2 );
	}


	/**
	 * Return event start/end hours.
	 *
	 * @param string $hours The event hours.
	 * @param string $unused_date The date.
	 * @param bool $isStart Is it the project start?
	 * @return string The event's hours.
	 */
	public function getHours( $hours, $unused_date, $isStart ) {

		if ( $isStart ) {
			if ( isset( $_REQUEST[ 'EventStartHour' ] ) ) {
				$hour = intval( $_REQUEST[ 'EventStartHour' ] );
			}
		} else {
			if ( isset( $_REQUEST[ 'EventEndHour' ] ) ) {
				$hour = intval( $_REQUEST[ 'EventEndHour' ] );
			}
		}

		if ( isset( $hour ) ) {
			return $hour;
		}

		return $hours;
	}

	/**
	 * Return event start/end minutes.
	 *
	 * @param string $minutes The event minutes.
	 * @param string $unused_date The date.
	 * @param bool $isStart Is it the project start?
	 * @return string The event's minutes.
	 */
	public function getMinutes( $minutes, $unused_date, $isStart ) {

		if ( $isStart ) {
			if ( isset( $_REQUEST[ 'EventStartMinute' ] ) ) {
				$minute = intval( $_REQUEST[ 'EventStartMinute' ] );
			}
		} else {
			if ( isset( $_REQUEST[ 'EventEndMinute' ] ) ) {
				$minute = intval( $_REQUEST[ 'EventEndMinute' ] );
			}
		}

		if ( isset( $minute ) ) {
			return $minute;
		}

		return $minutes;
	}

	/**
	 * Return event start/end meridian.
	 *
	 * @param string $meridians The event meridians.
	 * @param string $unused_date The date.
	 * @param bool $isStart Is it the project start?
	 * @return string The event's meridian.
	 */
	public function getMeridians( $meridians, $unused_date, $isStart ) {

		if ( $isStart ) {
			if ( isset( $_REQUEST[ 'EventStartMeridian' ] ) )
				$meridian = $_REQUEST[ 'EventStartMeridian' ];
		} else {
			if ( isset( $_REQUEST[ 'EventEndMeridian' ] ) )
				$meridian = $_REQUEST[ 'EventEndMeridian' ];
		}

		if ( isset( $meridian ) ) {
			return $meridian;
		}

		return $meridians;
	}

	/**
	 * Load recurrence data for ECP.
	 *
	 * @param int $postId The event id.
	 * @return void
	 * @author Nick Ciske
	 * @since 1.0
	 */
	public function loadRecurrenceData( $postId ) {
		$context        = tribe( 'community.main' )->getContext();
		$tribe_event_id = $context['id'];
		include Tribe__Events__Templates::getTemplateHierarchy( 'community/modules/recurrence' );
	}

	/**
	 * Includes the specified template.
	 *
	 * @param int $tribe_event_id The event id.
	 * @param object $event The event object.
	 * @param string $template The template path.
	 * @return void
	 */
	public function print_form( $tribe_event_id, $event, $template ) {
		include $template;
	}

	public function indicate_field_errors( $error, $field ) {
		return $error || in_array( $field, $this->error_fields );
	}

	public function filter_website_url_value( $url, $unused_post_id ) {
		return isset( $_POST['EventURL'] ) ? stripslashes( $_POST['EventURL'] ) : $url;
	}

	public function filter_custom_field_value( $value, $fieldname ) {
		if ( isset( $_POST[ $fieldname ] ) ) {
			if ( is_array( $_POST[ $fieldname ] ) ) {
				return array_map( 'stripslashes', $_POST[ $fieldname ] );
			} else {
				return stripslashes( $_POST[ $fieldname ] );
			}
		} else {
			return $value;
		}
	}

	public function filter_linked_post_id( $ids, $type ) {
		if ( Tribe__Events__Organizer::POSTTYPE === $type ) {
			$community_events  = tribe( 'community.main' );
			$default_organizer = $community_events->getOption( 'defaultCommunityOrganizerID' );

			// Make saved organizer selections "sticky" in the event of form validation errors
			$submitted_ids = [];
			if ( isset( $_POST['organizer']['OrganizerID'] ) ) {
				$submitted_ids = (array) $_POST['organizer']['OrganizerID'];
			}

			// In all other cases, respect the default organizer setting
			if ( empty( $submitted_ids ) && ! empty( $default_organizer ) && empty( $ids ) ) {
				$submitted_ids = [
					$default_organizer
				];
			}

			$submitted_ids = array_map( 'intval', (array) $submitted_ids );
			$ids = array_map( 'intval', (array) $ids );

			// Wipe the default $organizer_ids array when it contains a zero value and when we have other IDs to hand
			if ( ! empty( $submitted_ids ) ) {
				$ids = array_filter( $ids );
			}

			return array_merge( $ids, $submitted_ids );

		} elseif ( Tribe__Events__Venue::POSTTYPE === $type ) {
			// if the venue_id was posted, use that
			if ( isset( $_POST['venue'] ) && isset( $_POST['venue']['VenueID'] ) ) {
				$ids = $_POST['venue']['VenueID'];
			}

			// if the venue_id is an array, get the first element
			if ( is_array( $ids ) ) {
				$ids = reset( $ids );
			}

			// grab the first element from the array
			$ids = stripslashes( $ids );

			$apply_default_community = ( 'auto-draft' === get_post_status( $this->event_id ) || ! absint( $this->event_id ) );
			if ( $apply_default_community && empty( $ids ) ) {
				$ids = tribe( 'community.main' )->getOption( 'defaultCommunityVenueID' );
			}

			return $ids;
		} else {
			return 0;
		}
	}

	/**
	 * Filters the Community Linked Post Edit URLs.
	 *
	 * @see `get_edit_post_link` filter
	 *
	 * @param string $link    The edit link.
	 * @param int    $post_id Post ID.
	 * @param string $context The link context. If set to 'display' then ampersands
	 *                        are encoded.
	 *
	 * @return string
	 */
	public function filter_edit_post_url( $link, $post_id, $context ) {
		// When empty the user does not have permission to edit.
		$can_edit = ! empty( $link );
		$community_edit_url = tribe( 'community.main' )->getUrl( 'edit', $post_id, null, get_post_type( $post_id ) );

		if ( ! empty( $community_edit_url ) && $can_edit ) {
			$link = $community_edit_url;
		}

		return $link;
	}

	public function filter_organizer_value( $name ) {
		return isset( $_POST['organizer']['Organizer'] ) ? stripslashes_deep( $_POST['organizer']['Organizer'] ) : $name;
	}

	public function filter_organizer_phone( $phone ) {
		return isset( $_POST['organizer']['Phone'] ) ? stripslashes_deep( $_POST['organizer']['Phone'] ) : $phone;
	}

	public function filter_organizer_website( $website ) {
		return isset( $_POST['organizer']['Website'] ) ? stripslashes_deep( $_POST['organizer']['Website'] ) : $website;
	}

	public function filter_organizer_email( $email ) {
		return isset( $_POST['organizer']['Email'] ) ? stripslashes_deep( $_POST['organizer']['Email'] ) : $email;
	}

	public function filter_venue_name( $name ) {
		return isset( $_POST['venue']['Venue'] ) ? stripslashes_deep( $_POST['venue']['Venue'] ) : $name;
	}

	public function filter_venue_phone( $phone ) {
		return isset( $_POST['venue']['Phone'] ) ? stripslashes_deep( $_POST['venue']['Phone'] ) : $phone;
	}

	public function filter_venue_address( $address ) {
		return isset( $_POST['venue']['Address'] ) ? stripslashes_deep( $_POST['venue']['Address'] ) : $address;
	}

	public function filter_venue_city( $city ) {
		return isset( $_POST['venue']['City'] ) ? stripslashes_deep( $_POST['venue']['City'] ) : $city;
	}

	public function filter_venue_province( $province ) {
		return isset( $_POST['venue']['Province'] ) ? stripslashes_deep( $_POST['venue']['Province'] ) : $province;
	}

	public function filter_venue_state( $state ) {
		return isset( $_POST['venue']['State'] ) ? stripslashes_deep( $_POST['venue']['State'] ) : $state;
	}

	public function filter_venue_country( $country ) {
		return isset( $_POST['venue']['Country'] ) ? stripslashes_deep( $_POST['venue']['Country'] ) : $country;
	}

	public function filter_venue_zip( $zip ) {
		return isset( $_POST['venue']['Zip'] ) ? stripslashes_deep( $_POST['venue']['Zip'] ) : $zip;
	}

	/**
	 * Used to enforce the "Users cannot create new venues|organizer" settings.
	 *
	 * @since 4.5.2
	 *
	 * @param bool   $enabled
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function create_linked_posts_mode( $enabled, $post_type ) {
		$community = tribe( 'community.main' );

		if (
			Tribe__Events__Venue::POSTTYPE === $post_type
			&& $community->getOption( 'prevent_new_venues', false )
		) {
			return false;
		}

		if (
			Tribe__Events__Organizer::POSTTYPE === $post_type
			&& $community->getOption( 'prevent_new_organizers', false )
		) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Indicates if either linked posts module (venues or organizers) should be rendered
	 * or not.
	 *
	 * By default this will return true unless there are no linked posts to choose from
	 * and creation of further linked posts is disabled (in which case the UI becomes
	 * useless noise).
	 *
	 * @since 4.5.2
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function should_show_linked_posts_module( $post_type ) {
		global $wpdb;

		$map = [
			Tribe__Events__Organizer::POSTTYPE => 'organizers',
			Tribe__Events__Venue::POSTTYPE => 'venues',
		];

		// Check if this is a post type we care about
		if ( ! isset( $map[ $post_type ] ) ) {
			return true;
		}

		$prevent_new_option = 'prevent_new_' . $map[ $post_type ];

		// If the prevent_new_* setting isn't turned on we can assume the module should display
		if ( ! tribe( 'community.main' )->getOption( $prevent_new_option, false ) ) {
			return true;
		}

		// Otherwise let's check and ensure there are some posts for the user to select from:
		// we'll apply the same logic as in Tribe__Events__Linked_Posts::saved_linked_post_dropdown()
		// in terms of determining which post statuses are relevant
		$pto = get_post_type_object( $post_type );

		$statuses = current_user_can( $pto->cap->edit_others_posts )
			? [ 'publish', 'draft', 'private', 'pending' ]
			: [ 'publish' ];

		$fetch_post = get_posts(
			[
				'post_type'              => $post_type,
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'post_status'            => $statuses,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$posts_are_available = (bool) count( $fetch_post );

		/**
		 * Dictates where the linked post module should be displayed or not.
		 *
		 * @since 4.5.2
		 *
		 * @param bool   $should_show_module
		 * @param string $post_type
		 */
		return apply_filters( 'tribe_events_community_should_show_linked_posts_module',
			$posts_are_available,
			$post_type
		);
	}

	public function should_show_series_module() {

		if ( ! apply_filters( 'tec_community_events_use_series', false ) ) {
			return false;
		}

		if ( ! class_exists( '\TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type' ) ) {
			return false;
		}

		$post_type = \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE;

		// Otherwise let's check and ensure there are some posts for the user to select from:
		// we'll apply the same logic as in Tribe__Events__Linked_Posts::saved_linked_post_dropdown()
		// in terms of determining which post statuses are relevant
		$pto = get_post_type_object( $post_type );

		$statuses = current_user_can( $pto->cap->edit_others_posts )
			? [ 'publish', 'draft', 'private', 'pending' ]
			: [ 'publish' ];

		$fetch_post = get_posts(
			[
				'post_type'              => $post_type,
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'post_status'            => $statuses,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$posts_are_available = (bool) count( $fetch_post );

		/**
		 * Dictates where the linked post module should be displayed or not.
		 *
		 * @since 4.10.0
		 *
		 * @param bool   $should_show_module
		 * @param string $post_type
		 */
		return apply_filters( 'tribe_events_community_should_show_series_module',
			$posts_are_available,
			$post_type
		);

	}
}
