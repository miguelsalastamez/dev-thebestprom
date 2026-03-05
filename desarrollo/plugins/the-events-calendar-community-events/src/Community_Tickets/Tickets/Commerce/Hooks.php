<?php
/**
 * Handles hooking all the actions and filters used by Tickets Commerce.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package TEC\Community_Tickets\Tickets\Commerce
 */

namespace TEC\Community_Tickets\Tickets\Commerce;

use TEC\Community_Tickets\Tickets\Commerce\DefaultProvider;
use TEC\Common\Contracts\Service_Provider;

/**
 * Class Hooks.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package TEC\Community_Tickets\Tickets\Commerce
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register() {
		add_filter( 'tribe_tickets_get_default_module', [ $this, 'overwrite_default_module' ], 50, 2 );
	}

	/**
	 * Allows the overwriting of the default_module used by Event Tickets.
	 * We use this so that the Default Provider assigned to Community Tickets is used.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $default_provider default ticket module class name.
	 * @param array $provider_list array of ticket module class names.
	 *
	 * @return string default ticket module class name.
	 */
	public function overwrite_default_module( string $default_provider, array $provider_list ): string {
		$default_provider_logic = tribe( DefaultProvider::class );

		return $default_provider_logic->find_module_to_use( $default_provider, $provider_list );

	}

}
