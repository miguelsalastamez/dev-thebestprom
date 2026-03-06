<?php

namespace TEC\Community_Tickets\Tickets\Commerce;

use TEC\Common\Contracts\Service_Provider;

class Provider extends Service_Provider {
	/**
	 * Register implementations.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register() {

		// If not enabled, do not load Tickets Commerce Logic.
		if ( ! tec_ct_tickets_commerce_enabled() ) {
			return;
		}
		$this->register_hooks();

		// Loads admin area.
		$this->container->register( Admin\Provider::class );

	}

	/**
	 * Add hooks.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having them registered to the container
		$this->container->singleton( Hooks::class, $hooks );

	}
}
