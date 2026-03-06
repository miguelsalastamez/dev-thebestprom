<?php
/**
 * Handles hooking all the actions and filters used by The Events Calendar.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe(
 * TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users\Hooks::class ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe(
 * TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users\Hooks::class ), 'some_method' ] );
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Hooks.
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0
	 */
	public function register(): void {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Emails component.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tec_events_community_submission_anonymous_users_handler', [ $this, 'filter_anonymous_users_handler' ] );
	}

	/**
	 * Provides the anonymous users handler as a callable.
	 *
	 * This method sets up the handler for anonymous users by returning a callable
	 * from the Anonymous_Users_Logic class.
	 *
	 * @since 5.0.0
	 *
	 * @return callable The anonymous users handler.
	 */
	public function filter_anonymous_users_handler(): callable {
		return $this->container->make( Anonymous_Users_Logic::class )->handler();
	}
}
