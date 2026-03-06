<?php
/**
 * Organizer Logic for The Events Calendar Integration
 *
 * Integrates with The Events Calendar plugin to handle various organizer-related operations
 * within the Community plugin.
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Organizers
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Organizers;

use TEC\Events_Community\Integrations\Plugins\Events\Organizers\Routes\Route_Edit as Organizer_Route_Edit;
use TEC\Events_Community\Routes\Routes_Factory;
use TEC\Events_Community\Submission\Cleaner;
use Tribe__Events__Community__Main;
use Tribe__Events__Main;
use Tribe__Main;
use WP_Router;

/**
 * Organizer_Logic
 *
 * Integrates with The Events Calendar plugin to handle various organizer-related operations
 * within the Community plugin. This includes adding organizer columns, fields, and mappings
 * to event submissions.
 *
 * @since 5.0.0
 * @package TEC\Events_Community\Integrations\Plugins\Events\Organizers
 */
class Organizer_Logic {

	/**
	 * List of allowed organizer fields for submission.
	 *
	 * This array defines the fields that are allowed during the submission process.
	 * These fields are not used for displaying organizer information.
	 *
	 * @since 5.0.0
	 *
	 * @var array
	 */
	protected array $submission_allowed_organizer_fields
		= [
			'OrganizerID',
			'Organizer',
			'Phone',
			'Website',
			'Email',
		];

	/**
	 * Adds the organizer column to the columns array.
	 *
	 * This method appends the organizer column to the provided columns array, inserting it after the 'title' column.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the organizer column added.
	 */
	public function add_organizer_column( array $columns ): array {
		$appended_columns = [
			'organizer' => tribe_get_organizer_label_singular(),
		];

		return tribe( Tribe__Main::class )->array_insert_after_key( 'title', $columns, $appended_columns );
	}

	/**
	 * Adds organizer fields to the submission.
	 *
	 * @since 5.0.0
	 * @since 5.0.1 Refactored logic to fix overwriting the organizer array incorrectly.
	 *
	 * @param array $submission The submission data.
	 *
	 * @return array The modified submission data.
	 */
	public function submission_add_organizer_fields( array $submission ): array {
		if ( isset( $submission['organizer'] ) && ! isset( $submission['Organizer'] ) ) {
			$submission['organizer'] = stripslashes_deep( $submission['organizer'] );
			$submission['Organizer'] = $this->filter_organizer_data( $submission['organizer'] );
			unset( $submission['organizer'] );
		}

		if ( ! isset( $submission['Organizer'] ) ) {
			$submission['Organizer'] = [];
		}

		return $submission;
	}

	/**
	 * Filters and sanitizes organizer data.
	 *
	 * Processes the provided organizer data array, converting the 'OrganizerID' field
	 * to an array of integers if it is not empty. Also sanitizes the 'Phone', 'Website',
	 * and 'Email' fields using the Cleaner class.
	 *
	 * @since 5.0.0
	 *
	 * @param array $organizer_data The organizer data array to be filtered and sanitized.
	 *
	 * @return array The sanitized organizer data array.
	 */
	protected function filter_organizer_data( array $organizer_data ): array {
		if ( ! empty( $organizer_data['OrganizerID'] ) ) {
			$organizer_data['OrganizerID'] = array_map( 'intval', $organizer_data['OrganizerID'] );
		}

		$fields              = [
			'Phone',
			'Website',
			'Email',
		];
		$submission_scrubber = tribe( Cleaner::class );

		foreach ( $fields as $field ) {
			if ( ! isset( $organizer_data[ $field ] ) ) {
				continue;
			}

			$organizer_data[ $field ] = is_array( $organizer_data[ $field ] )
				? $submission_scrubber->filter_string_array( $organizer_data[ $field ] )
				: $submission_scrubber->filter_string( $organizer_data[ $field ] );
		}

		return $organizer_data;
	}

	/**
	 * Adds allowed fields to the submission's allowed fields mapping.
	 *
	 * Merges the given allowed fields array with the submission's allowed organizer fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_fields The original allowed fields array.
	 *
	 * @return array The modified allowed fields array with additional organizer fields.
	 */
	public function add_allowed_fields_mapping( array $allowed_fields ): array {
		return array_merge( $allowed_fields, $this->submission_allowed_organizer_fields );
	}

	/**
	 * Adds allowed inner fields to the submission's allowed inner fields mapping.
	 *
	 * Merges the given allowed inner fields array with the submission's allowed organizer fields.
	 * If the creation of new organizers has been disabled, only accepts existing organizer IDs.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 * @param int   $submission_id Submission ID.
	 *
	 * @return array The modified allowed inner fields array with additional organizer fields.
	 */
	public function add_allowed_fields_inner_mapping( array $allowed_inner_fields, int $submission_id ): array {
		// Get the allowed fields and apply filters.
		$allowed_fields = $this->submission_allowed_organizer_fields;
		$allowed_fields = apply_filters( 'tec_events_community_submission_allowed_organizer_fields', $allowed_fields );

		// Check if users are allowed to edit submissions.
		$allowed_to_edit_submissions = tribe( 'community.main' )->getOption( 'allowUsersToEditSubmissions', false );

		// Check if creating new organizers is prevented.
		$prevent_new_organizers = tribe( 'community.main' )->getOption( 'prevent_new_organizers', false );

		// If we are editing an organizer and allowed to edit submissions, merge allowed fields.
		if ( tribe_is_organizer( $submission_id ) ) {
			if ( $allowed_to_edit_submissions ) {
				return array_merge( $allowed_inner_fields, $allowed_fields );
			}
		}

		// If creating new organizers is prevented, only accept existing organizer IDs.
		if ( $prevent_new_organizers ) {
			$allowed_fields = [ 'OrganizerID' ];
		}

		return array_merge( $allowed_inner_fields, $allowed_fields );
	}

	/**
	 * Adds the organizer field label.
	 *
	 * This method returns the singular organizer label if the field is 'organizer'.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param string $field The field name.
	 *
	 * @return string The updated label for the organizer field.
	 */
	public function add_organizer_field_label( string $label, string $field ): string {
		if ( 'organizer' === $field ) {
			return tribe_get_organizer_label_singular();
		}

		return $label;
	}

	/**
	 * Changes the header link title based on the post type.
	 *
	 * This method modifies the header link title if the post is a organizer.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param int    $post_id The post ID to check.
	 *
	 * @return string The modified or original label.
	 */
	public function change_header_link_title_for_organizer( string $label, int $post_id ): string {
		if ( $post_id && tribe_is_organizer( $post_id ) ) {
			$label = _x( 'Edit Organizer', 'Header text that displays when editing a venue.', 'tribe-events-community' );
		}
		return $label;
	}

	/**
	 * Generates an organizer route and adds it to the routes factory.
	 *
	 * This method sets up the route for editing organizers and registers it with the
	 * routes factory, ensuring that the correct router is associated with the route.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_Router $router The WP_Router object used to set up the route.
	 *
	 * @return void
	 */
	public function generate_organizer_route( WP_Router $router ): void {
		// Define the class for the organizer edit route.
		$route_class = Organizer_Route_Edit::class;

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
			'organizer-edit',
			$route_class
		);
	}


	/**
	 * Adds organizer settings to the Community settings tab.
	 *
	 * This method retrieves the existing organizers and creates a dropdown field
	 * for selecting a default organizer for submitted events. If the heading for
	 * form defaults doesn't exist, it adds the heading and then appends the organizer
	 * fields under it.
	 *
	 * @since 5.0.0
	 *
	 * @param array $community_tab_fields The existing Community settings tab fields.
	 *
	 * @return array The modified Community settings tab fields.
	 */
	public function add_organizer_settings( array $community_tab_fields ): array {
		// Initialize an empty array for organizer options.
		$organizer_options = [ _x( 'No Default', 'Option for when there are no organizers available.', 'tribe-events-community' ) ];

		// Get existing organizers.
		$organizers = Tribe__Events__Main::instance()->get_organizer_info();

		// Flag to check if organizers exist.
		$organizers_exist = ! empty( $organizers ) && is_array( $organizers );

		// Check if there are any organizers.
		if ( $organizers_exist ) {
			// Populate the organizer options array.
			foreach ( $organizers as $organizer ) {
				$organizer_options[ $organizer->ID ] = $organizer->post_title;
			}
		}

		// Define the organizer fields.
		$organizer_fields = [
			'defaultCommunityOrganizerID' => [
				'type'            => 'dropdown',
				'label'           => _x( 'Default organizer for submitted events', 'Label for the default organizer option.', 'tribe-events-community' ),
				'validation_type' => 'options',
				'default'         => 0,
				'options'         => $organizer_options,
				'can_be_empty'    => true,
				'conditional'     => $organizers_exist,
			],
		];

		$prevent_new_organizers = [
			'prevent_new_organizers' => [
				'type'            => 'checkbox_bool',
				'label'           => _x( 'Users cannot create new Organizers', 'Label for preventing new organizers.', 'tribe-events-community' ),
				'tooltip'         => _x( 'Users will be limited to choosing from existing organizers.', 'Tooltip for preventing new organizers.', 'tribe-events-community' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
		];

		$community_tab_fields += $organizer_fields + $prevent_new_organizers;

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
	 * @return array The array of edit URLs including the newly added organizer edit URL.
	 */
	public function add_organizer_route_edit_url( $edit_urls, $base_url ): array {
		$ce_main        = tribe( Tribe__Events__Community__Main::class );
		$organizer_slug = $ce_main->get_rewrite_slug( 'organizer' );
		$edit_slug      = $ce_main->get_rewrite_slug( 'edit' );

		// Ensure it's not already there in case the hook gets called twice.
		$existing = array_map( 'strtolower', wp_list_pluck( $edit_urls, 'name' ) );

		if ( in_array( $edit_slug . ' ' . $organizer_slug, $existing ) ) {
			return $edit_urls;
		}

		// Correct URL construction by ensuring slashes are included properly.
		$edit_urls[] = [
			'name'  => 'Edit organizer',
			'url'   => trailingslashit( $base_url . sanitize_title( $edit_slug ) ) . sanitize_title( $organizer_slug ),
			'order' => 2,
		];

		return $edit_urls;
	}

	/**
	 * Adds an additional 'organizer' rewrite slug to the default rewrite slugs array.
	 *
	 * This function appends a new key-value pair to the passed array, setting the 'organizer' key
	 * to map to a corresponding slug string 'organizer'.
	 *
	 * @since 5.0.0
	 *
	 * @param array $default_rewrite_slugs The original array of default rewrite slugs.
	 *
	 * @return array The modified array of rewrite slugs including the new 'organizer' slug.
	 */
	public function add_additional_rewrite_slugs( $default_rewrite_slugs ): array {
		$default_rewrite_slugs['organizer'] = 'organizer';
		return $default_rewrite_slugs;
	}

	/**
	 * Modifies a base URL to append a custom segment for organizer-related actions.
	 *
	 * This method adjusts the provided base URL by adding a segment specific to the organizer post type,
	 * derived from a rewrite slug. It's designed to handle URLs specifically for organizer actions,
	 * such as viewing or editing organizer details. The method checks if the action is relevant to the
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
	public function add_custom_url_for_organizer_action( $final_url, $action, $id, $page, $post_type, $base_url ): string {
		if ( empty( $post_type ) ) {
			return $final_url;
		}

		if ( Tribe__Events__Main::ORGANIZER_POST_TYPE !== $post_type ) {
			return $final_url;
		}

		$organizer_slug = tribe('community.main')->get_rewrite_slug( 'organizer' );
		$final_url      = trailingslashit( $base_url ) . trailingslashit( $organizer_slug ) . $id;

		return $final_url;
	}

	/**
	 * Validates the custom organizer information.
	 *
	 * This function checks the organizer information in the submission. If the
	 * organizer ID is empty, not set, or less than or equal to zero for any organizer, the validation fails.
	 *
	 * @since 5.0.1
	 * @since 5.0.5 Update validation to check for empty organizer name if prevent_new_organizers is false.
	 *
	 * @param mixed $value The value to filter.
	 * @param array $submission The submission data containing organizer information.
	 *
	 * @return bool True if all organizers are valid, false otherwise.
	 */
	public function custom_organizer_validation( $value, $submission ): bool {
		$prevent_new_organizers = tribe( 'community.main' )->getOption( 'prevent_new_organizers', false );

		$organizer_data = $submission['organizer'] ?? null;

		// If the $organizers is empty or not an array, it is not valid.
		if ( empty( $organizer_data ) || ! is_array( $organizer_data ) ) {
			return false;
		}

		// We will build a list of organizers based on the data that was submitted.
		$organizers = [];

		foreach ( $organizer_data as $key => $value ) {
			$organizer_key = strtolower( $key );
			if ( is_array( $value ) ) {
				foreach ( $value as $index => $val ) {
					$organizers[ $index ][ $organizer_key ] = $val;
				}
			}
		}

		// Validate each organizer. If one fails, all fail.
		foreach ( $organizers as $index => $organizer ) {
			// If preventing new organizers and the ID is invalid, return false.
			if (
				$prevent_new_organizers
				&& ( empty( $organizer['organizerid'] ) || 0 >= (int) $organizer['organizerid'] )
			) {
				return false;
			}

			// If new and no organizer name is set, return false.
			if ( 0 >= (int) $organizer['organizerid'] && empty( $organizer['organizer'] ) ) {
				return false;
			}
		}

		return true;
	}
}
