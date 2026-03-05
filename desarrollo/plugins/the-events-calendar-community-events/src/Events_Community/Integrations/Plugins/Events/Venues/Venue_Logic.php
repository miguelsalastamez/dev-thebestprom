<?php
/**
 * Venue Logic for The Events Calendar Integration
 *
 * Integrates with The Events Calendar plugin to handle various venue-related operations
 * within the Community plugin.
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Venues
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Venues;

use TEC\Events_Community\Submission\Cleaner;
use Tribe__Events__Community__Main;
use Tribe__Events__Main;
use Tribe__Main;
use TEC\Events_Community\Routes\Routes_Factory;
use TEC\Events_Community\Integrations\Plugins\Events\Venues\Routes\Route_Edit as Venue_Route_Edit;
use WP_Router;

/**
 * Venue_Logic
 *
 * Integrates with The Events Calendar plugin to handle various venue-related operations
 * within the Community plugin. This includes adding venue columns, fields, and mappings
 * to event submissions.
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Venues
 */
class Venue_Logic {

	/**
	 * List of allowed venue fields for submission.
	 *
	 * This array defines the fields that are allowed during the submission process.
	 * These fields are not used for displaying venue information.
	 *
	 * @since 5.0.0
	 *
	 * @var array
	 */
	protected array $submission_allowed_venue_fields
		= [
			'VenueID',
			'Venue',
			'Address',
			'City',
			'Country',
			'Province',
			'State',
			'Zip',
			'Phone',
			'URL',
			'ShowMapLink',
			'ShowMap',
		];

	/**
	 * Adds the venue column to the columns array.
	 *
	 * Appends the venue column to the provided columns array, inserting it after the 'title' column.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the venue column added.
	 */
	public function add_venue_column( array $columns ): array {
		$appended_columns = [
			'venue' => tribe_get_venue_label_singular(),
		];

		return tribe( Tribe__Main::class )->array_insert_after_key( 'title', $columns, $appended_columns );
	}

	/**
	 * Adds venue fields to the submission.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Refactored logic to fix overwriting the venue array incorrectly.
	 *
	 * @param array $submission The submission data.
	 *
	 * @return array The modified submission data.
	 */
	public function submission_add_venue_fields( array $submission ): array {
		if ( isset( $submission['venue'] ) && ! isset( $submission['Venue'] ) ) {
			$submission['venue'] = stripslashes_deep( $submission['venue'] );
			$submission['Venue'] = $this->filter_venue_data( $submission['venue'] );
			unset( $submission['venue'] );
		}

		if ( ! isset( $submission['Venue'] ) ) {
			$submission['Venue'] = [];
		}


		return $submission;
	}


	/**
	 * Filters the venue data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $venue_data The venue data to be filtered.
	 *
	 * @return array The filtered venue data.
	 */
	protected function filter_venue_data( array $venue_data ): array {
		if ( ! empty( $venue_data['VenueID'] ) ) {
			$venue_data['VenueID'] = array_map( 'intval', $venue_data['VenueID'] );
		}

		$fields = [
			'Venue',
			'Address',
			'City',
			'Country',
			'Province',
			'State',
			'Zip',
			'Phone',
			'ShowMapLink',
			'ShowMap',
		];

		$submission_scrubber = tribe( Cleaner::class );

		foreach ( $fields as $field ) {
			if ( ! isset( $venue_data[ $field ] ) ) {
				continue;
			}

			$venue_data[ $field ] = is_array( $venue_data[ $field ] )
				? $submission_scrubber->filter_string_array( $venue_data[ $field ] )
				: $submission_scrubber->filter_string( $venue_data[ $field ] );
		}

		return $venue_data;
	}

	/**
	 * Adds allowed venue fields to the submission's allowed fields mapping.
	 *
	 * Merges the provided allowed fields array with the submission's allowed venue fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_fields The original allowed fields array.
	 *
	 * @return array The modified allowed fields array with additional venue fields.
	 */
	public function add_allowed_fields_mapping( array $allowed_fields ): array {
		return array_merge( $allowed_fields, $this->submission_allowed_venue_fields );
	}

	/**
	 * Adds allowed inner venue fields to the submission's allowed inner fields mapping.
	 *
	 * Merges the provided allowed inner fields array with the submission's allowed venue fields.
	 * If the creation of new venues has been disabled, only accepts existing venue IDs.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 * @param int   $submission_id Submission ID.
	 *
	 * @return array The modified allowed inner fields array with additional venue fields.
	 */
	public function add_allowed_fields_inner_mapping( array $allowed_inner_fields, int $submission_id ): array {
		$allowed_fields = $this->submission_allowed_venue_fields;
		$allowed_fields = apply_filters( 'tec_events_community_submission_allowed_venue_fields', $allowed_fields );

		// Check if users are allowed to edit submissions.
		$allowed_to_edit_submissions = tribe( 'community.main' )->getOption( 'allowUsersToEditSubmissions', false );

		// Check if creating new venues is prevented.
		$prevent_new_venues = tribe( 'community.main' )->getOption( 'prevent_new_venues', false );

		// If we are editing a venue and allowed to edit submissions, merge allowed fields.
		if ( tribe_is_venue( $submission_id ) ) {
			if ( $allowed_to_edit_submissions ) {
				return array_merge( $allowed_inner_fields, $allowed_fields );
			}
		}

		// If creating new venues is prevented, only accept existing organizer IDs.
		if ( $prevent_new_venues ) {
			$allowed_fields = [ 'VenueID' ];
		}

		return array_merge( $allowed_inner_fields, $allowed_fields );
	}

	/**
	 * Alters the submission mapping to include additional venue fields.
	 *
	 * Ensures that 'VenueShowMapLink' and 'VenueShowMap' fields are set to true if they are not
	 * present in the allowed venue fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array with additional venue fields set to true.
	 */
	public function alter_submission_mapping( array $submission ): array {

		if ( ! in_array( 'VenueShowMapLink', $this->submission_allowed_venue_fields ) ) {
			$submission['VenueShowMapLink'] = true;
		}
		if ( ! in_array( 'VenueShowMap', $this->submission_allowed_venue_fields ) ) {
			$submission['VenueShowMap'] = true;
		}


		// Fix an issue with the venue state not being properly defined when submitting the event.
		if ( isset( $_POST['venue']['State'][0] ) ) {
			$_POST['venue']['State'] = esc_attr( $_POST['venue']['State'][0] );
		}

		return $submission;
	}

	/**
	 * Adds the venue field label.
	 *
	 * This method returns the singular venue label if the field is 'venue'.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param string $field The field name.
	 *
	 * @return string The updated label for the venue field.
	 */
	public function add_venue_field_label( string $label, string $field ): string {
		if ( 'venue' === $field ) {
			return tribe_get_venue_label_singular();
		}

		return $label;
	}

	/**
	 * Changes the header link title based on the post type.
	 *
	 * This method modifies the header link title if the post is an organizer.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param int    $post_id The post ID to check.
	 *
	 * @return string The modified or original label.
	 */
	public function change_header_link_title_for_venue( string $label, int $post_id ) {
		if ( $post_id && tribe_is_venue( $post_id ) ) {
			$label = _x( 'Edit Venue', 'Header text that displays when editing a venue.', 'tribe-events-community' );
		}
		return $label;
	}

	/**
	 * Generates a venue route and adds it to the routes factory.
	 *
	 * This method sets up the route for editing venues and registers it with the
	 * routes factory, ensuring that the correct router is associated with the route.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_Router $router The WP_Router object used to set up the route.
	 *
	 * @return void
	 */
	public function generate_venue_route( WP_Router $router ): void {
		// Define the class for the venue edit route.
		$route_class = Venue_Route_Edit::class;

		// Get the singleton instance of the Routes_Factory.
		$routes_factory = Routes_Factory::getInstance();

		// Create an instance of the route using the Tribe function.
		$route = tribe( $route_class );

		// Set the router for the route instance.
		$route->set_router( $router );

		// Setup the route.
		$route->setup();

		// Add the route to the routes factory.
		$routes_factory::add_route(
			'venue-edit',
			$route_class
		);
	}

	/**
	 * Adds venue settings to the Community settings tab.
	 *
	 * This method retrieves the existing venues and creates a dropdown field
	 * for selecting a default venue for submitted events. If the heading for
	 * form defaults doesn't exist, it adds the heading and then appends the venue
	 * fields under it.
	 *
	 * @since 5.0.0
	 *
	 * @param array $community_tab_fields The existing Community settings tab fields.
	 *
	 * @return array The modified Community settings tab fields.
	 */
	public function add_venue_settings( array $community_tab_fields ): array {
		// Initialize an empty array for venue options.
		$venue_options = [ _x( 'Use New Venue/No Default', 'Option for when there are no venues available.', 'tribe-events-community' ) ];

		// Get existing venues.
		$venues = Tribe__Events__Main::instance()->get_venue_info();

		// Flag to check if venues exist.
		$venue_exist = ! empty( $venues ) && is_array( $venues );

		// Check if there are any venues.
		if ( $venue_exist ) {
			$venue_exist = true;
			// Populate the venue options array.
			foreach ( $venues as $venue ) {
				$venue_options[ $venue->ID ] = $venue->post_title;
			}
		}

		// Define the venue fields.
		$venue_fields = [
			'defaultCommunityVenueID' => [
				'type'            => 'dropdown',
				'label'           => _x( 'Default venue for submitted events', 'Label for the default venue option.', 'tribe-events-community' ),
				'validation_type' => 'options',
				'default'         => 0,
				'options'         => $venue_options,
				'can_be_empty'    => true,
				'conditional'     => $venue_exist,
			],
		];

		$prevent_new_venues = [
			'prevent_new_venues' => [
				'type'            => 'checkbox_bool',
				'label'           => _x( 'Users cannot create new Venues', 'Label for preventing new venues.', 'tribe-events-community' ),
				'tooltip'         => _x( 'Users will be limited to choosing from existing venues.', 'Tooltip for preventing new venues.', 'tribe-events-community' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
		];

		$community_tab_fields += $venue_fields + $prevent_new_venues;

		return $community_tab_fields;
	}

	/**
	 * Adds a specific URL for editing venues to an array of edit URLs.
	 *
	 * This method constructs the URL for the 'Edit venue' route by using predefined
	 * rewrite slugs obtained from a Tribe__Events__Community__Main class instance. It
	 * ensures the URL is properly formatted with trailing slashes and then appends
	 * this new URL configuration to the passed array of existing edit URLs.
	 *
	 * @since 5.0.0
	 *
	 * @param array  $edit_urls The array of existing edit URLs to which the new URL will be added.
	 * @param string $base_url The base URL to which additional segments will be appended.
	 *
	 * @return array The array of edit URLs including the newly added venue edit URL.
	 */
	public function add_venue_route_edit_url( $edit_urls, $base_url ): array {
		$ce_main    = tribe( Tribe__Events__Community__Main::class );
		$venue_slug = $ce_main->get_rewrite_slug( 'venue' );
		$edit_slug  = $ce_main->get_rewrite_slug( 'edit' );

		// Ensure it's not already there in case the hook gets called twice.
		$existing = array_map( 'strtolower', wp_list_pluck( $edit_urls, 'name' ) );

		if ( in_array( $edit_slug . ' ' . $venue_slug, $existing ) ) {
			return $edit_urls;
		}

		// Correct URL construction by ensuring slashes are included properly.
		$edit_urls[] = [
			'name'  => 'Edit venue',
			'url'   => trailingslashit( $base_url . sanitize_title( $edit_slug ) ) . sanitize_title( $venue_slug ),
			'order' => 2,
		];

		return $edit_urls;
	}

	/**
	 * Adds an additional 'venue' rewrite slug to the default rewrite slugs array.
	 *
	 * This function appends a new key-value pair to the passed array, setting the 'venue' key
	 * to map to a corresponding slug string 'venue'.
	 *
	 * @since 5.0.0
	 *
	 * @param array $default_rewrite_slugs The original array of default rewrite slugs.
	 *
	 * @return array The modified array of rewrite slugs including the new 'venue' slug.
	 */
	public function add_additional_rewrite_slugs( $default_rewrite_slugs ): array {
		$default_rewrite_slugs['venue'] = 'venue';
		return $default_rewrite_slugs;
	}

	/**
	 * Modifies a base URL to append a custom segment for organizer-related actions.
	 *
	 * This method adjusts the provided base URL by adding a segment specific to the organizer post type,
	 * derived from a rewrite slug. It's designed to handle URLs specifically for venue actions,
	 * such as viewing or editing venue details. The method checks if the action is relevant to the
	 * organizer post type before appending the custom segment.
	 *
	 * @since 5.0.0
	 *
	 * @param string $final_url The base URL before modification.
	 * @param string $action The action for which the URL is intended (currently unused but retained for potential future use).
	 * @param int    $id The ID of the organizer, used to construct the final URL segment.
	 * @param int    $page Currently unused. Kept for potential extension where pagination or additional segments might be required.
	 * @param string $post_type The post type of the content; this function modifies the URL only if it matches the organizer post type.
	 * @param string $base_url The initial base URL to which the organizer-specific segment will be appended.
	 *
	 * @return string The modified URL with the organizer segment if applicable, otherwise the unmodified base URL.
	 */
	public function add_custom_url_for_venue_action( $final_url, $action, $id, $page, $post_type, $base_url ): string {
		if ( empty( $post_type ) ) {
			return $final_url;
		}

		if ( Tribe__Events__Main::VENUE_POST_TYPE !== $post_type ) {
			return $final_url;
		}

		$venue_slug = tribe('community.main')->get_rewrite_slug( 'venue' );
		$final_url  = trailingslashit( $base_url ) . trailingslashit( $venue_slug ) . $id;

		return $final_url;
	}

	/**
	 * Validates the custom venue information.
	 *
	 * This function checks the venue information in the submission. If the
	 * venue ID is empty, not set, or less than or equal to zero for any venue, the validation fails.
	 *
	 * @since 5.0.1
	 * @since 5.0.5 Update validation to check for venue name rather than all fields.
	 *
	 * @param mixed $value The value to filter.
	 * @param array $submission The submission data containing venue information.
	 *
	 * @return bool True if all venues are valid, false otherwise.
	 */
	public function custom_venue_validation( $value, $submission ): bool {
		$prevent_new_venues = tribe( 'community.main' )->getOption( 'prevent_new_venues', false );

		$venue_data = $submission['venue'] ?? null;

		// If the $venue_data is empty or not an array, it is not valid.
		if ( empty( $venue_data ) || ! is_array( $venue_data ) ) {
			return false;
		}

		// We will build a list of venues based on the data that was submitted.
		$venues = [];
		foreach ( $venue_data as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $index => $val ) {
					$venues[ $index ][ strtolower( $key ) ] = $val;
				}
			}
		}

		// Validate each venue. If one fails, all fail.
		foreach ( $venues as $index => $venue ) {
			// If preventing new venues and the ID is invalid, return false.
			if (
				$prevent_new_venues
				&& ( empty( $venue['venueid'] ) || 0 >= (int) $venue['venueid'] )
			) {
				return false;
			}

			// If new and no venue name is set, return false.
			if ( 0 >= (int) $venue['venueid'] && empty( $venue['venue'] ) ) {
				return false;
			}
		}

		return true;
	}
}
