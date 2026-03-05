<?php

namespace TEC\Community_Tickets\Tickets\Commerce;
class DefaultProvider {

	/**
	 * This function allows to set a different provider than the default one for ET when creating tickets.
	 * It checks the settings for the default provider and translate it to the class name.
	 * If the class name exists in the list of enabled providers, it returns it. Otherwise, it returns the default provider.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $default_provider default ticket module class name.
	 * @param array $provider_list array of ticket module class names.
	 *
	 * @return string default ticket module class name.
	 *
	 */
	public function find_module_to_use( string $default_provider, array $provider_list = [] ): string {

		$settings_provider = $this->get_default_provider_setting();

		// If the $settings_provider is empty, or no providers are enabled, return $default_provider and let ET take care of it.
		if ( empty( $settings_provider ) || count( $provider_list ) === 0 ) {
			return $default_provider;
		}

		$provider_to_use = $this->translate_provider( $settings_provider );

		// If $this->provider exists in the $provider_list then use that. Otherwise, use $default_provider.
		if ( in_array( $provider_to_use['class'], $provider_list ) ) {
			return $provider_to_use['class'];
		}

		return $default_provider;

	}

	/**
	 * Translate the provider name to its class name
	 *
	 * This function takes in the html_safe_class of the provider and returns the corresponding provider class name.
	 * It filters the list of active providers to match the provided html_safe_class, and returns the first element of the filtered array.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $provider html_safe_class provider name.
	 *
	 * @return array array of ticket module class names.
	 *
	 */
	public function translate_provider( string $provider ): array {
		$editor_config = tribe( 'tickets.editor.configuration' );

		// Get list of providers (excluding RSVP).
		$active_providers = $editor_config->get_providers();

		$translated_provider = array_values( array_filter( $active_providers, function ( $key ) use ( $provider ) {
			return $key['html_safe_class'] === $provider;
		}, ARRAY_FILTER_USE_BOTH ) );

		// Remove the provider from the array.
		return reset( $translated_provider );
	}

	/**
	 * Get the default provider setting.
	 *
	 * This function retrieves the default provider setting from the options. If no value is saved,
	 * it checks if there is any active provider and use the first one as default.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string $html_safe_class name of the provider.
	 *
	 */
	public function get_default_provider_setting(): string {
		$main              = tribe( 'community-tickets.main' );
		$settings_provider = $main->get_option( 'default_provider_handler' );
		$active_providers  = tribe( Admin\Settings::class )->generate_provider_options();

		// Don't assume the default provider is available. Do a check to verify it's available.
		if ( ! array_key_exists( $settings_provider, $active_providers ) ) {
			// The default provider is unavailable, lets use the first one available.

			// Set the $settings_provider to the key of the provider options array.
			$settings_provider = key( $active_providers );
			// Since the default provider is unavailable, reset the settings to the first one available as well.
			$main->set_option( 'default_provider_handler', $settings_provider );

		}

		return empty( $settings_provider ) ? '' : $settings_provider;
	}



}
