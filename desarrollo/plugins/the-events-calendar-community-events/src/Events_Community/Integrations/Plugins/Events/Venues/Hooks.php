<?php
/**
 * Handles hooking all the actions and filters used by The Events Calendar Venues.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Events_Community\Integrations\Plugins\Events\Venues\Hooks::class ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Events_Community\Integrations\Plugins\Events\Venues\Hooks::class ), 'some_method' ] );
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Venues;

use TEC\Common\Contracts\Service_Provider;
use WP_Router;

/**
 * Class Hooks.
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0
	 */
	public function register(): void {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Emails component.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tribe_community_events_list_columns', [ $this, 'filter_add_venue_column' ], 12 );
		add_filter( 'tec_events_community_submission_scrub', [ $this, 'filter_submission_add_venue_fields' ] );
		add_filter( 'tec_events_community_allowed_fields', [ $this, 'filter_add_allowed_fields_mapping' ] );
		add_filter(
			'tec_events_community_allowed_fields_inner_key_Venue',
			[
				$this,
				'filter_add_allowed_fields_inner_mapping',
			],
			10,
			2
		);
		add_filter( 'tec_events_community_alter_submission_mapping', [ $this, 'filter_alter_submission_mapping' ] );
		add_filter( 'tribe_community_form_field_label', [ $this, 'filter_add_venue_field_label' ], 10, 2 );
		add_filter( 'tec_events_community_header_links_title', [ $this, 'filter_header_link_title_for_venue' ], 10, 2 );
		add_filter( 'tec_events_community_settings_content_creation_section', [ $this, 'filter_add_venue_settings' ], 12 );
		add_filter( 'tec_events_community_modify_default_rewrite_slugs', [ $this, 'filter_add_additional_rewrite_slugs' ] );
		add_filter( 'tribe_community_settings_edit_urls', [ $this, 'filter_add_venue_route_edit_url' ], 10, 2 );
		add_filter( 'tec_events_community_get_urls_for_actions', [ $this, 'filter_add_custom_url_for_action' ], 10, 6 );
		add_action( 'wp_router_generate_routes', [ $this, 'generate_venue_route' ] );
		add_action( 'tec_events_community_submission_custom_required_validation_venue', [ $this, 'filter_custom_venue_validation' ], 10, 2 );
	}

	/**
	 * Adds the venue column to the columns array.
	 *
	 * This method is a wrapper for the actual add_venue_column method in the Venue_Logic class.
	 * It processes the columns array to include the venue column.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the venue column added.
	 */
	public function filter_add_venue_column( array $columns ): array {
		return $this->container->make( Venue_Logic::class )->add_venue_column( $columns );
	}

	/**
	 * Adds venue fields to the submission data.
	 *
	 * This method is a wrapper for the actual submission_add_venue_fields method in the Venue_Logic class.
	 * It processes the submission array to include additional venue fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array with added venue fields.
	 */
	public function filter_submission_add_venue_fields( array $submission ): array {
		return $this->container->make( Venue_Logic::class )->submission_add_venue_fields( $submission );
	}

	/**
	 * Adds allowed fields to the submission's allowed fields mapping.
	 *
	 * This method is a wrapper for the actual add_allowed_fields_mapping method in the Venue_Logic class.
	 * It processes the allowed fields array to include additional fields for the submission.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_fields The original allowed fields array.
	 *
	 * @return array The modified allowed fields array with additional venue fields.
	 */
	public function filter_add_allowed_fields_mapping( array $allowed_fields ): array {
		return $this->container->make( Venue_Logic::class )->add_allowed_fields_mapping( $allowed_fields );
	}

	/**
	 * Adds allowed inner fields to the submission's allowed inner fields mapping.
	 *
	 * This method is a wrapper for the actual add_allowed_fields_inner_mapping method in the Venue_Logic class.
	 * It processes the allowed inner fields array to include additional inner fields for the submission.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 * @param int   $submission_id Submission ID.
	 *
	 * @return array The modified allowed inner fields array with additional venue fields.
	 */
	public function filter_add_allowed_fields_inner_mapping( array $allowed_inner_fields, int $submission_id ): array {
		return $this->container->make( Venue_Logic::class )->add_allowed_fields_inner_mapping( $allowed_inner_fields, $submission_id );
	}

	/**
	 * Alters the submission mapping to include additional fields.
	 *
	 * This method is a wrapper for the actual alter_submission_mapping method in the Venue_Logic class.
	 * It processes the allowed inner fields array to include additional fields for the submission mapping.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 *
	 * @return array The modified allowed inner fields array with additional venue fields.
	 */
	public function filter_alter_submission_mapping( array $allowed_inner_fields ): array {
		return $this->container->make( Venue_Logic::class )->alter_submission_mapping( $allowed_inner_fields );
	}

	/**
	 * Filters the label for the add venue field.
	 *
	 * This method retrieves the venue logic from the container and uses it to filter the label for the add venue field.
	 *
	 * @since 5.0.0
	 *
	 * @param string $field The field data.
	 * @param string $label The label for the field.
	 *
	 * @return string The filtered field data.
	 */
	public function filter_add_venue_field_label( string $field, string $label ): string {
		return $this->container->make( Venue_Logic::class )->add_venue_field_label( $field, $label );
	}

	/**
	 * Filter callback to change the header link title for venues.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param int    $post_id The post ID to check.
	 *
	 * @return string The modified or original label.
	 */
	public function filter_header_link_title_for_venue( string $label, int $post_id ): string {
		return $this->container->make( Venue_Logic::class )->change_header_link_title_for_venue( $label, $post_id );
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
		$this->container->make( Venue_Logic::class )->generate_venue_route( $router );
	}

	/**
	 * Filters the Community settings tab to add venue settings.
	 *
	 * This method retrieves the venue logic instance from the container and calls
	 * the method to add venue settings to the Community settings tab.
	 *
	 * @since 5.0.0
	 *
	 * @param array $community_tab The existing Community settings tab fields.
	 *
	 * @return array The modified Community settings tab fields.
	 */
	public function filter_add_venue_settings( array $community_tab ): array {
		return $this->container->make( Venue_Logic::class )->add_venue_settings( $community_tab );
	}

	/**
	 * Filter function that delegates the addition of an 'Edit venue' URL to the Venue_Logic class.
	 *
	 * @since 5.0.0
	 *
	 * @param array  $edit_urls The array of existing edit URLs.
	 * @param string $base_url The base URL used to construct the full edit URL.
	 *
	 * @return array The updated array of edit URLs after adding the venue edit URL.
	 */
	public function filter_add_venue_route_edit_url( $edit_urls, $base_url ): array {
		return $this->container->make( Venue_Logic::class )->add_venue_route_edit_url( $edit_urls, $base_url );
	}

	/**
	 * Filter function that delegates the addition of rewrite slugs to the Venue_Logic class.
	 *
	 * This method serves as a callback for a WordPress filter hook, enabling dynamic modifications
	 * to the array of rewrite slugs. It utilizes an instance of Venue_Logic to add or modify
	 * rewrite slugs within the provided array.
	 *
	 * @since 5.0.0
	 *
	 * @param array $rewrite_slugs The original array of rewrite slugs.
	 *
	 * @return array The modified array of rewrite slugs after additional slugs have been added.
	 */
	public function filter_add_additional_rewrite_slugs( $rewrite_slugs ): array {
		return $this->container->make( Venue_Logic::class )->add_additional_rewrite_slugs( $rewrite_slugs );
	}

	/**
	 * Filter function that delegates the creation of a custom URL for a specific action to the Venue_Logic class.
	 *
	 * This method is typically used as a callback in WordPress filter hooks related to URL management. It passes
	 * control to an instance of Venue_Logic to construct and append a custom URL based on the provided parameters,
	 * which include the action type, entity ID, post type, and base URL. The method is designed to facilitate the
	 * extension or modification of URL structures dynamically through the use of dependency injection.
	 *
	 * @since 5.0.0
	 *
	 * @param string  $final_url The current array of URLs which may be modified by appending a new URL.
	 * @param string $action The action type for which the URL is being constructed.
	 * @param int    $id The ID of the entity associated with the URL, usually a post or term ID.
	 * @param int    $page The pagination page number, if applicable.
	 * @param string $post_type The type of post the URL is associated with.
	 * @param string $base_url The base URL to which additional path segments will be appended.
	 *
	 * @return array The updated array of URLs after the inclusion of the new custom URL.
	 */
	public function filter_add_custom_url_for_action( $final_url, $action, $id, $page, $post_type, $base_url ): string {
		return $this->container->make( Venue_Logic::class )->add_custom_url_for_venue_action( $final_url, $action, $id, $page, $post_type, $base_url );
	}

	/**
	 * Validates the custom venue information.
	 *
	 * This function checks the venue information in the submission. If the
	 * venue ID is empty, not set, or -1 for any venue, the validation fails.
	 *
	 * @since 5.0.1
	 *
	 * @param mixed $value The value to filter.
	 * @param array $submission The submission data containing venue information.
	 *
	 * @return bool True if all venue are valid, false otherwise.
	 */
	public function filter_custom_venue_validation( $value, $submission ): bool {
		return $this->container->make( Venue_Logic::class )->custom_venue_validation( $value, $submission );
	}
}
