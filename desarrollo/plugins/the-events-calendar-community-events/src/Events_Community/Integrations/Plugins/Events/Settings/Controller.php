<?php

namespace TEC\Events_Community\Integrations\Plugins\Events\Settings;

use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use TEC\Common\Integrations\Traits\Module_Integration;
use Tribe\Events\Admin\Settings;
use Tribe__Events__Main;

/**
 * Class Provider
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Organizers
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
		add_filter( 'tec_events_community_settings_strategy', [ $this, 'handler' ], 30 );
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
	 * Retrieves the settings instance from The Events Calendar plugin.
	 *
	 * @since 5.0.0
	 *
	 * @return Settings The settings instance from The Events Calendar plugin.
	 */
	public function settings(): Settings {
		return Tribe__Events__Main::instance()->settings();
	}
}
