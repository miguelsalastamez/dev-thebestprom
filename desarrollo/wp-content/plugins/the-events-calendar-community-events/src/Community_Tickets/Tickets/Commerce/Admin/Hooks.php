<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package TEC\Community_Tickets\Tickets\Commerce\Admin
 */

namespace TEC\Community_Tickets\Tickets\Commerce\Admin;

use TEC\Common\Contracts\Service_Provider;
/**
 * Class Hooks.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package TEC\Community_Tickets\Tickets\Commerce\Admin
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register() {
		tribe( Settings::class )->hooks();
	}

}
