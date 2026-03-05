<?php

namespace TEC\Events_Community\Integrations\Plugins\Tickets;

use TEC\Common\Integrations\Traits\Plugin_Integration;
use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;

/**
 * Class Provider
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Event_Tickets
 */
class Controller extends Plugin_Integration_Abstract {
	use Plugin_Integration;

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'event-tickets';
	}

	/**
	 * @inheritDoc
	 *
	 * @since 5.0.7 switched to using `tec_tickets_fully_loaded` instead of `tribe_tickets_plugin_loaded`.
	 */
	public function load_conditionals(): bool {
		return did_action( 'tec_tickets_fully_loaded' );
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		$this->container->register( Events\Controller::class );
		$this->container->register( Settings\Controller::class );
	}
}
