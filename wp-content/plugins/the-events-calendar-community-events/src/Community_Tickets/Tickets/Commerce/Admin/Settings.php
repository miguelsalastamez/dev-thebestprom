<?php
/**
 * Handles the Provider settings option from within the Community settings.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package TEC\Community_Tickets\Tickets\Commerce\Admin
 *
 */

namespace TEC\Community_Tickets\Tickets\Commerce\Admin;

use Tribe__Events__Community__Tickets__Main as Main;
use Tribe__Main;

class Settings {

	/**
	 * List of whitelisted providers allowed to be used on Community Tickets.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string[]
	 */
	public static $whitelisted_providers = [
		'TEC_Tickets_Commerce_Module',
		'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
		'Tribe__Tickets_Plus__Commerce__EDD__Main',
	];

	/**
	 * Setup hooks for the settings page to include the default_provider logic.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return void
	 */
	public function hooks() {
		// Priority set to 20, so it runs after the settings for Community Tickets.
		add_filter( 'tec_events_community_settings_tickets_section', [ $this, 'add_additional_options' ], 20, 1 );
		add_action( 'admin_notices', [ $this, 'tribe_community_tickets_no_providers_available' ] );
	}

	/**
	 * Add the `Default Provider` section to the settings.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $fields Array of fields
	 *
	 * @return array $fields with the additional `default_provider_handler` included. If no providers are available,
	 *               the original array of fields is returned. If there is only one provider available,
	 *               the name of the provider is displayed with no option to switch them.
	 *               If there are multiple providers available, a dropdown with all options is displayed.
	 */
	public function add_additional_options( $fields ) {

		$provider_list = $this->generate_provider_options();

		// No providers available, return $fields.
		if ( count( $provider_list ) === 0 ) {
			return $fields;
		}

		// If 1 provider is set, display the name with no option to switch them.
		if ( count( $provider_list ) === 1 ) {
			$provider_handler_settings = [
				'default_provider_handler' => [
					'type'  => 'wrapped_html',
					'label' => __( 'Default Provider', 'tribe-events-community' ),
					'html'  => current( $provider_list )
				],
			];
		} else {
			// Multiple providers available, display dropdown with all options.
			$provider_handler_settings = [
				'default_provider_handler' => [
					'type'            => 'dropdown',
					'label'           => __( 'Default Provider', 'tribe-events-community' ),
					'tooltip'         => __( 'Provider to be used for events created via Community. Changing this will only affect new tickets.', 'tribe-events-community' ),
					'default'         => 'TEC_Tickets_Commerce_Module',
					'validation_type' => 'options',
					'parent_option'   => Main::OPTIONNAME,
					'options'         => $provider_list,
				],
			];
		}

		// Add our new settings below the `enable_image_uploads` field.
		$fields = Tribe__Main::array_insert_after_key( 'enable_image_uploads', $fields, $provider_handler_settings );

		return $fields;

	}

	/**
	 * Create a list of providers that are available.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array $provider_options List of available providers. Each element is an array
	 *               with the key as 'html_safe_class' and value as the name of the provider.
	 */
	public function generate_provider_options(): array {
		$provider_options = [];

		$whitelisted_providers = $this->whitelisted_providers();

		foreach ( $whitelisted_providers as $provider ) {
			$provider_options[ $provider['html_safe_class'] ] = $provider['name'];
		}

		/**
		 * Allows filtering of the Tickets Commerce provider.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $provider_options List of providers Community Tickets has the ability to use.
		 */
		return apply_filters( 'tec_community_tickets_settings_provider_options', $provider_options );

	}

	/**
	 * Defines an array of Providers that are supported.
	 * Currently, only Ticket Commerce and WooCommerce are supported.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array Whitelisted list of valid providers that are available to use with Community Tickets.
	 */
	public function whitelisted_providers(): array {

		// Grab the list of providers that are set up
		$editor_config    = tribe( 'tickets.editor.configuration' );
		$active_providers = $editor_config->get_providers();

		/**
		 * Allows the overwriting of the provider whitelist.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $supported_options an array of providers using the `html_safe_class` names.
		 */
		$supported_options = apply_filters( 'tec_community_tickets_settings_provider_whitelist', self::$whitelisted_providers );

		return array_values( array_filter( $active_providers, function ( $key ) use ( $supported_options ) {
			return in_array( $key['html_safe_class'], $supported_options );
		}, ARRAY_FILTER_USE_BOTH ) );
	}

	/**
	 * Checks if any providers are enabled, if not displays a message.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Message displaying to set up providers.
	 */
	public function tribe_community_tickets_no_providers_available() {

		$provider_list = $this->generate_provider_options();

		// If providers are set, return with no error message.
		if ( count( $provider_list ) > 0 ) {
			return;
		}

		// Translators: %1$s and %2$s are the opening and closing anchor tags, respectively.
		$warning_msg = __( 'To begin using the tickets functionality within The Events Calendar: Community, Please %1$ssetup a provider%2$s.', 'tribe-events-community' );

		return printf(
			 '<div class="error"><p>' . $warning_msg . '</p></div>',
			'<a href="https://theeventscalendar.com/knowledgebase/k/tickets-commerce/" target="_blank" rel="noopener noreferrer">',
					'</a>'
		);

	}
}
