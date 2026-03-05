<?php
namespace TEC\Events_Community\Integrations\Plugins\Events\Venues;

use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use TEC\Common\Integrations\Traits\Module_Integration;
use TEC\Events_Community\Integrations\Plugins\Events\Venues\Routes\Route_Edit as Venue_Route_Edit;
use TEC\Events_Community\Integrations\Plugins\Events\Venues\Tribe__Events__Community__Venue_Submission_Scrubber;

/**
 * Class Provider
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Venue
 */
class Controller extends Plugin_Integration_Abstract {
	use Module_Integration;

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'the-events-calendar-venues';
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

		$this->container->singleton( Venue_Logic::class, Venue_Logic::class );
		$this->container->singleton( Venue_Route_Edit::class, Venue_Route_Edit::class );
		$this->container->singleton( Venue_Submission_Scrubber::class, Venue_Submission_Scrubber::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.0.0
	 */
	protected function register_hooks(): void {
		$hooks = new Hooks( $this->container );
		$hooks->register();


		// Allow Hooks to be removed, by having the them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
	}

}
