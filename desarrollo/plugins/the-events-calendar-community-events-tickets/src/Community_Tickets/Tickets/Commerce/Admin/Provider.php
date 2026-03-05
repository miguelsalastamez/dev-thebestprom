<?php

namespace TEC\Community_Tickets\Tickets\Commerce\Admin;

use TEC\Common\Contracts\Service_Provider;

class Provider extends Service_Provider {
	/**
	 * Register implementations.
	 *
	 * @since 4.8.4
	 */
	public function register() {

		$this->register_hooks();

		$this->container->singleton( Settings::class );

	}

	/**
	 * Add hooks.
	 *
	 * @since 4.8.4
	 */
	public function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having them registered to the container
		$this->container->singleton( Hooks::class, $hooks );

	}
}
