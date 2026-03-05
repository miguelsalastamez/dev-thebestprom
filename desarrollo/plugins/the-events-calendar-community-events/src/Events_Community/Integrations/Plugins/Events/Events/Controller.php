<?php
namespace TEC\Events_Community\Integrations\Plugins\Events\Events;

use DateTime;
use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use TEC\Common\Integrations\Traits\Module_Integration;
use TEC\Events_Community\Submission\Cleaner;
use TEC\Events_Community\Submission\Messages;
use Tribe__Admin__Helpers;
use Tribe__Date_Utils;
use Tribe__Events__Community__Main;
use Tribe__Events__Main;
use Tribe__Main;
use Tribe__Events__Community__Templates;

/**
 * Class Provider
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Events
 */
class Controller extends Plugin_Integration_Abstract {
	use Module_Integration;

	/**
	 * List of allowed event fields for submission.
	 *
	 * This array defines the fields that are allowed during the submission process.
	 * These fields are not used for displaying event information.
	 *
	 * @since 5.0.0
	 *
	 * @var array
	 */
	protected array $submission_allowed_event_fields
		= [
			'EventAllDay',
			'EventStartDate',
			'EventStartTime',
			'EventEndDate',
			'EventEndTime',
			'EventTimezone',
			'EventURL',
			'EventCurrencySymbol',
			'EventCurrencyPosition',
			'EventCost',
			'tax_input',
			'Series',
			'is_recurring',
			'recurrence',
		];

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'the-events-calendar-event-logic';
	}

	/**
	 * @inheritDoc
	 */
	public function load_conditionals(): bool {
		// If TEC is enabled, we always want to load this logic.
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		// Register the Service Provider for Hooks.
		$this->register_hooks();

		$this->container->singleton( Event_Handler::class, Event_Handler::class );
		$this->container->singleton( Event_Logic::class, Event_Logic::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.0.0
	 */
	protected function register_hooks(): void {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required for the Events Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required for the Events Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tribe_community_events_get_event_query', [ $this, 'get_events' ], 10, 3 );
		add_filter( 'tec_events_community_posttype', [ $this, 'get_post_type' ], 20, 3 );
		add_filter( 'tribe_community_events_list_columns', [ $this, 'add_category_column' ], 13 );
		add_filter( 'tribe_community_events_list_columns', [ $this, 'add_date_columns' ], 15 );
		add_filter( 'tec_events_community_form_layout', [ $this, 'add_event_calendar_fields_to_event_form', ], 13 );
		add_filter( 'tec_events_community_alter_submission_mapping', [ $this, 'alter_submission_mapping' ], 10 );
		add_filter( 'tec_events_community_submission_scrub', [ $this, 'filter_event_data' ], 10 );
		add_filter( 'tec_events_community_submission_scrub', [ $this, 'filter_custom_field_urls' ], 10 );
		add_filter( 'tec_events_community_submission_scrub', [ $this, 'filter_tax_input' ], 10 );
		add_filter( 'tec_events_community_submission_scrub', [ $this, 'filter_event_series' ], 10 );
		add_filter( 'tec_events_community_allowed_fields', [ $this, 'add_allowed_fields_mapping' ], 10 );
		add_filter( 'tec_events_community_submission_save_handler', [ $this, 'custom_save_handler' ], 30, 1 );
		add_filter( 'tec_events_community_validate_field_contents', [ $this, 'validate_event_date' ], 10, 2 );
		add_filter( 'tribe_community_form_field_label', [ $this, 'add_field_labels' ], 10, 2 );
		add_filter( 'tec_events_community_events_listing_show_prev_next_nav', '__return_true', 15 );
		add_filter( 'tec_events_community_events_listing_display_options_dropdown', '__return_true', 15 );
		add_filter(
			'tec_events_community_allowed_fields_inner_key_recurrence',
			[
				$this,
				'add_allowed_fields_inner_mapping_recurrence',
			],
			10,
			2
		);
		add_filter(
			'tec_events_community_allowed_fields_inner_key_tax_input',
			[
				$this,
				'add_allowed_fields_inner_mapping_tax_input',
			],
			10,
			2
		);
		add_filter( 'tec_events_community_event_slug', [ $this, 'overwrite_default_event_slug' ], 15 );
		add_filter( 'tec_events_community_email_alert_template_path', [ $this, 'override_email_alert_template_path' ], 15 );
	}

	/**
	 * Custom save handler for the event.
	 *
	 * This method retrieves the event handler from the container and returns its handler.
	 *
	 * @since 5.0.0
	 * @return callable The callback function for saving the event.
	 */
	public function custom_save_handler(): callable {
		return $this->container->make( Event_Handler::class )->handler();
	}

	/**
	 * Retrieves events based on the provided arguments.
	 *
	 * @since 5.0.0
	 *
	 * @param mixed $data Data to filter.
	 * @param array $args Query arguments.
	 * @param bool  $full Whether to return the full query.
	 *
	 * @return \WP_Query The filtered events query.
	 */
	public function get_events( $data, $args, $full ): \WP_Query {
		return tribe_get_events( $args, $full );
	}

	/**
	 * Retrieves the event post type.
	 *
	 * @since 5.0.0
	 *
	 * @param mixed $value The post type value.
	 *
	 * @return string The event post type.
	 */
	public function get_post_type( $value ): string {
		return \Tribe__Events__Main::POSTTYPE;
	}

	/**
	 * Adds a category column to the columns array.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the category column added.
	 */
	public function add_category_column( array $columns ): array {
		$appended_columns = [
			'category' => esc_html__( 'Category', 'tribe-events-community' ),
		];
		return tribe( Tribe__Main::class )->array_insert_after_key( 'title', $columns, $appended_columns );
	}

	/**
	 * Adds the date columns to the columns array.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the date columns added.
	 */
	public function add_date_columns( array $columns ): array {
		$appended_columns = [
			'start_date' => esc_html__( 'Start Date', 'tribe-events-community' ),
			'end_date'   => esc_html__( 'End Date', 'tribe-events-community' ),
		];

		return tribe( Tribe__Main::class )->array_insert_after_key( 'category', $columns, $appended_columns );
	}

	/**
	 * Adds event calendar fields to the event form.
	 *
	 * @since 5.0.0
	 *
	 * @param array $modules The modules to add.
	 *
	 * @return array The modified modules with event calendar fields added.
	 */
	public function add_event_calendar_fields_to_event_form( $modules ): array {
		$event_url = $_POST['EventURL'] ?? tribe_get_event_website_url();
		$event_url = esc_attr( $event_url );

		$appended_modules_after_description = [
			'event-datepickers' => [
				'template' => 'integrations/the-events-calendar/modules/datepickers',
			],
		];

		$appended_modules_after_image = [
			'event-datepickers'    => [
				'template' => 'integrations/the-events-calendar/modules/datepickers',
			],
			'event-taxonomy_event' => [
				'template' => 'integrations/the-events-calendar/modules/taxonomy',
				'data'     => [ 'taxonomy' => Tribe__Events__Main::TAXONOMY ],
			],
			'event-taxonomy_tag'   => [
				'template' => 'integrations/the-events-calendar/modules/taxonomy',
				'data'     => [ 'taxonomy' => 'post_tag' ],
			],
			'event-venue'          => [
				'template' => 'integrations/the-events-calendar/modules/venue',
			],
			'event-organizer'      => [
				'template' => 'integrations/the-events-calendar/modules/organizer',
			],
			'event-website'        => [
				'template' => 'integrations/the-events-calendar/modules/website',
				'data'     => [ 'event_url' => $event_url ],
			],
			'event-series'         => [
				'template' => 'integrations/the-events-calendar/modules/series',
			],
			'event-custom'         => [
				'template' => 'integrations/the-events-calendar/modules/custom',
			],
			'event-cost'           => [
				'template' => 'integrations/the-events-calendar/modules/cost',
			],
		];
		$modules                      = tribe( Tribe__Main::class )->array_insert_after_key( 'description', $modules, $appended_modules_after_description );
		$modules                      = tribe( Tribe__Main::class )->array_insert_after_key( 'image', $modules, $appended_modules_after_image );
		return $modules;
	}

	/**
	 * Retrieves custom field keys.
	 *
	 * @since 5.0.0
	 *
	 * @return array The custom field keys.
	 */
	protected function get_custom_field_keys(): array {
		$custom_fields = tribe_get_option( 'custom-fields' );
		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return [];
		}
		$keys = [];
		foreach ( $custom_fields as $field ) {
			$keys[] = $field['name'];
		}
		return $keys;
	}

	/**
	 * Alters the submission mapping to include allowed inner fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array.
	 */
	public function alter_submission_mapping( $submission ): array {

		if ( ! in_array( 'EventShowMap', $this->get_custom_field_keys() ) ) {
			$submission['EventShowMap'] = true;
		}
		if ( ! in_array( 'EventShowMapLink', $this->get_custom_field_keys() ) ) {
			$submission['EventShowMapLink'] = true;
		}

		if ( ! isset( $submission['ID'] ) ) {
			return $submission;
		}

		if ( tribe_get_event_meta( $submission['ID'], '_EventHideFromUpcoming' ) ) {
			$submission['EventHideFromUpcoming'] = 'yes';
		}

		if ( get_post_field( 'menu_order', $submission['ID'] ) == -1 ) {
			$submission['EventShowInCalendar'] = 'yes';
		}

		if ( tribe( 'tec.featured_events' )->is_featured( $submission['ID'] ) ) {
			$submission['feature_event'] = 'yes';
		}

		return $submission;
	}

	/**
	 * Adds allowed fields mapping to the allowed fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_fields The allowed fields.
	 *
	 * @return array The modified allowed fields.
	 */
	public function add_allowed_fields_mapping( $allowed_fields ): array {
		return array_merge( $allowed_fields, $this->submission_allowed_event_fields );
	}

	/**
	 * Adds allowed inner fields to the submission's allowed inner fields mapping.
	 *
	 * Merges the given allowed inner fields array with the submission's allowed recurrence fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 * @param int   $submission_id Submission ID.
	 *
	 * @return array The modified allowed inner fields array with additional recurrence fields.
	 */
	public function add_allowed_fields_inner_mapping_recurrence( array $allowed_inner_fields, int $submission_id ): array {
		$allowed_fields = [
			'rules',
			'exclusions',
		];
		return array_merge( $allowed_fields, $allowed_inner_fields );
	}

	/**
	 * Adds allowed inner fields to the submission's allowed inner fields mapping.
	 *
	 * Merges the given allowed inner fields array with the submission's allowed taxonomy fields.
	 *
	 * @since 5.0.1
	 *
	 * @param array $allowed_inner_fields The original allowed inner fields array.
	 * @param int   $submission_id Submission ID.
	 *
	 * @return array The modified allowed inner fields array with additional recurrence fields.
	 */
	public function add_allowed_fields_inner_mapping_tax_input( array $allowed_inner_fields, int $submission_id ): array {
		$allowed_fields = [
			'tribe_events_cat',
			'post_tag',
		];
		return array_merge( $allowed_fields, $allowed_inner_fields );
	}

	/**
	 * Filters event data for allowed fields.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The event data to filter.
	 *
	 * @return array The filtered event data.
	 */
	public function filter_event_data( $submission ): array {
		$fields = [
			'EventURL',
			'EventCurrencySymbol',
			'EventCost',
		];

		$submission_scrubber = tribe( Cleaner::class );

		foreach ( $fields as $field ) {
			if ( ! isset( $submission[ $field ] ) ) {
				continue;
			}

			$submission[ $field ] = is_array( $submission[ $field ] )
				? $submission_scrubber->filter_string_array( $submission[ $field ] )
				: $submission_scrubber->filter_string( $submission[ $field ] );
		}

		return $submission;
	}

	/**
	 * Filters custom field URLs in the submission, ensuring they are valid URLs.
	 *
	 * This method iterates over the custom fields defined in the options and checks if
	 * they are of type 'url'. If a URL is not valid, it attempts to prepend 'http://'
	 * to make it valid. If it still fails validation, the URL is left unchanged.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array with validated URLs.
	 */
	public function filter_custom_field_urls( $submission ): array {
		$custom_fields = tribe_get_option( 'custom-fields' );

		if ( ! $custom_fields ) {
			return $submission;
		}

		foreach ( $custom_fields as $field ) {
			if ( 'url' !== $field['type'] ) {
				continue;
			}

			$field_name = $field['name'];

			if ( empty( $submission[ $field_name ] ) ) {
				continue;
			}

			$url = $submission[ $field_name ];

			if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			$url_with_http = 'http://' . $url;

			if ( filter_var( $url_with_http, FILTER_VALIDATE_URL ) ) {
				$submission[ $field_name ] = $url_with_http;
			}
		}

		return $submission;
	}

	/**
	 * Filters the Tax Input
	 * Especially important for `post_tags` for when it's not an integer
	 * WordPress ends up creating Tags with the Numeric ID as the Name
	 *
	 * @link https://codex.wordpress.org/Function_Reference/wp_set_post_terms#Notes
	 *
	 * @since  5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array with filtered taxonomy input.
	 */
	public function filter_tax_input( $submission ): array {
		if ( empty( $submission['tax_input'] ) ) {
			return $submission;
		}

		foreach ( $submission['tax_input'] as $taxonomy => &$terms ) {
			// Clean the value and convert to array if not already.
			if ( ! is_array( $terms ) ) {
				$terms = explode( ',', esc_attr( trim( $terms ) ) );
			}

			// Convert terms to IDs and remove empty items.
			$terms = array_filter( array_map( 'intval', $terms ) );
		}

		return $submission;
	}

	/**
	 * Filters the event series data in the submission.
	 *
	 * This method processes the 'series' field in the submission array. If the series ID is not set,
	 * it assigns a default value of 0 to the 'Series' field. If the series ID is present, it converts
	 * the first ID to an integer, assigns it to the 'Series' field, and removes the 'series' field from the submission.
	 *
	 * @since 5.0.0
	 *
	 * @param array $submission The current submission array.
	 *
	 * @return array The modified submission array with processed series data.
	 */
	public function filter_event_series( $submission ): array {
		if ( ! isset( $submission['series']['id'] ) ) {
			$submission['Series'] = 0;

			return $submission;
		}

		$submission['Series'] = (int) array_shift( $submission['series']['id'] );
		unset( $submission['series'] );

		return $submission;
	}

	/**
	 * Validate the event data. The start time should always occur prior to the end time.
	 *
	 * @since 5.0.0
	 *
	 * @param bool                $valid Whether the form is valid or not.
	 * @param array<string,mixed> $submission Submission data.
	 *
	 * @return bool
	 */
	public function validate_event_date( bool $valid, array $submission ): bool {
		$messages = Messages::get_instance();
		// Check if EventStartDate or EventEndDate are empty.
		if ( empty( $submission['EventStartDate'] ) || empty( $submission['EventEndDate'] ) ) {
			$messages->add_message(
				esc_html__(
					'Start Time must be prior to End Time.',
					'tribe-events-community'
				),
				'error'
			);
			return false;
		}

		// Check if EventStartTime or EventEndTime are empty.
		if ( empty( $submission['EventStartTime'] ) || empty( $submission['EventEndTime'] ) ) {
			$messages->add_message(
				esc_html__(
					'Start Time must be prior to End Time.',
					'tribe-events-community'
				),
				'error'
			);
			return false;
		}

		$datepicker_format = Tribe__Date_Utils::datepicker_formats( tribe_get_option( 'datepickerFormat' ) );
		$datepicker_format = $datepicker_format . ' h:ia';

		// Convert our date into a valid date object for testing.
		$start_date = DateTime::createFromFormat( $datepicker_format, $submission['EventStartDate'] . ' ' . $submission['EventStartTime'] );
		$end_date   = DateTime::createFromFormat( $datepicker_format, $submission['EventEndDate'] . ' ' . $submission['EventEndTime'] );

		// Validate if the start date and time is before the end date and time.
		if ( $start_date > $end_date ) {
			$messages->add_message(
				esc_html__(
					'Start Time must be prior to End Time.',
					'tribe-events-community'
				),
				'error'
			);
			return false;
		}

		return true;
	}

	/**
	 * Adds the custom labels for specific fields.
	 *
	 * This method returns the singular if the field is found below.
	 *
	 * @since 5.0.0
	 *
	 * @param string $label The current label.
	 * @param string $field The field name.
	 *
	 * @return string The updated label for the specific field.
	 */
	public function add_field_labels( string $label, string $field ): string {
		switch ( $field ) {
			case 'tax_input.tribe_events_cat':
				// translators: %s is the field label.
				return sprintf( _x( '%s Category', 'field label for event categories', 'tribe-events-community' ), tec_events_community_event_label_singular() );
			case 'tax_input.post_tag':
				return _x( 'Tag', 'field label for post tags', 'tribe-events-community' );
			default:
				return $label;
		}
	}

	/**
	 * Shows the event cost in the admin if the event is from the community origin and has a cost.
	 *
	 * This method checks if the current post is available and the admin screen is being viewed.
	 * If the event's origin is from the community and it has a cost, it adds a filter to show the cost field in the admin.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function show_event_cost(): void {
		global $post;

		if ( ! $post || ! Tribe__Admin__Helpers::instance()->is_screen() ) {
			return;
		}

		$origin = tribe_get_event_meta( $post->ID, '_EventOrigin', true );
		$cost   = tribe_get_event_meta( $post->ID, '_EventCost', true );
		if ( tribe( Tribe__Events__Community__Main::class )->filterPostOrigin() === $origin && $cost ) {
			add_filter( 'tribe_events_admin_show_cost_field', '__return_true' );
		}
	}

	/**
	 * Custom content to display above the frontend content.
	 *
	 * This method outputs custom content above the main content on the frontend. If The Events Calendar is active,
	 * it triggers the `tribe_events_before_html` action.
	 *
	 * @since 5.0.0
	 *
	 * @param string $output The current output.
	 * @param string $slug The slug of the content.
	 *
	 * @return string The modified output with the custom content added above.
	 */
	public function frontend_custom_above_content( string $output, string $slug ): string {
		ob_start();
		tribe_events_before_html();
		$custom_content = ob_get_clean();
		return $output . $custom_content;
	}

	/**
	 * Custom content to display below the frontend content.
	 *
	 * This method outputs custom content below the main content on the frontend. If The Events Calendar is active,
	 * it triggers the `tribe_events_after_html` action.
	 *
	 * @since 5.0.0
	 *
	 * @param string $output The current output.
	 * @param string $slug The slug of the content.
	 *
	 * @return string The modified output with the custom content added below.
	 */
	public function frontend_custom_below_content( string $output, string $slug ): string {
		ob_start();
		tribe_events_after_html();
		$custom_content = ob_get_clean();
		return $output . $custom_content;
	}

	/**
	 * Removes the upsells from the frontend Event Form.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function remove_upsells(): void {
		remove_action( 'tribe_events_cost_table', [ Tribe__Events__Main::instance(), 'maybeShowMetaUpsell' ] );
	}

	/**
	 * Overwrites the default event slug for Community.
	 *
	 * For example, https://websiteurl/{event_slug}/community
	 *
	 * @since 5.0.4
	 *
	 * @return string The event slug defined in the tribe options, or 'events' by default.
	 */
	public function overwrite_default_event_slug(): string {
		// Get the event slug from options with a default fallback.
		return tribe_get_option( 'eventsSlug', 'events' );
	}

	/**
	 * Override the email template path to use TEC's template.
	 *
	 * @since 5.0.7
	 *
	 * @param string $template_path The default template path.
	 *
	 * @return string The TEC template path.
	 */
	public function override_email_alert_template_path( string $template_path ): string {
		$tec_template = Tribe__Events__Community__Templates::getTemplateHierarchy( 'integrations/the-events-calendar/email-template' );

		return ! empty( $tec_template ) && file_exists( $tec_template ) ? $tec_template : $template_path;
	}
}
