<?php

namespace TEC\Events_Community\Integrations\Plugins\Tickets\Settings;

use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use TEC\Common\Integrations\Traits\Module_Integration;
use Tribe\Tickets\Admin\Settings;
use Tribe__Events__Community__Main;

/**
 * Class Provider
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Tickets\Organizers
 */
class Controller extends Plugin_Integration_Abstract {
	use Module_Integration;

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'the-events-calendar-settings';
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
	 * Adds the actions required for the Settings Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required for the Settings Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tec_events_community_settings_strategy', [ $this, 'handler' ], 15 );
		add_filter( 'tec_events_community_settings_content_creation_section', [ $this, 'add_event_tickets_settings' ] );
		add_filter( 'tec_events_community_posttype', [ $this, 'get_post_type' ], 10, 3 );
	}

	/**
	 * Returns a callable for handling settings.
	 *
	 * @since 5.0.0
	 *
	 * @return callable The callback function for handling settings.
	 */
	public function handler(): callable {
		return tribe_callback( self::class, 'settings' );
	}

	/**
	 * Retrieves the settings instance from the Event Tickets plugin.
	 *
	 * @since 5.0.0
	 *
	 * @return Settings The settings instance from the Event Tickets plugin.
	 */
	public function settings(): Settings {
		return tribe( Settings::class );
	}

	/**
	 * Adds event tickets settings to the community tab.
	 *
	 * This method modifies the community tab settings to include a dropdown for the default post type,
	 * provided that the Community post type is 'post' or 'page'. It updates the settings only
	 * if the current post type matches one of the specified event tickets post types.
	 *
	 * @since 5.0.0
	 *
	 * @param array $community_tab The existing community tab settings.
	 *
	 * @return array The modified community tab settings with event tickets settings added.
	 */
	public function add_event_tickets_settings( array $community_tab ): array {
		$et_post_types = [ 'page', 'post' ];

		$ce_post_type = tribe( Tribe__Events__Community__Main::class )->get_community_events_post_type();

		// If our ce_post_type is something other than 'post' or 'page' return that with no dropdown.
		if ( ! in_array( $ce_post_type, $et_post_types ) ) {
			return $community_tab;
		}

		$args               = [
			'public' => true,
		];
		$public_post_types  = get_post_types( $args );
		$default_post_types = array_intersect( $public_post_types, $et_post_types );

		// Update `default_post_type` to become a dropdown instead of html. Merge our new changes into the original array.
		$community_tab['default_post_type'] = array_merge(
			$community_tab['default_post_type'],
			[
				'type'            => 'dropdown',
				'validation_type' => 'options',
				'default'         => 'page',
				'options'         => $default_post_types,
			]
		);

		return $community_tab;
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
	public function get_post_type( $value ) {
		return tribe_get_option( 'default_post_type', $value );
	}
}
