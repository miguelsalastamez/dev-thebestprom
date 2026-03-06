<?php
/**
 * Provides the Community Tickets Payout service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Tooltips;
use Tribe\Community\Tickets\Payouts\Hooks;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Report;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Report_Tab;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Tabbed_View;
use Tribe\Community\Tickets\Payouts\PayPal\Adaptive_Payments;
use Tribe\Community\Tickets\Payouts\PayPal\Payouts_API as PayPal_Payouts_API;
use Tribe\Community\Tickets\Payouts\Payout;
use Tribe\Community\Tickets\Payouts\Order;
use Tribe\Community\Tickets\Payouts\Receiver;
use Tribe\Community\Tickets\Repositories\Payout as Payout_Repository;
use TEC\Common\Contracts\Service_Provider as Provider_Contract;

class Service_Provider extends Provider_Contract {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register() {
		$this->container->singleton( 'community-tickets.payouts', Payouts::class );
		$this->container->singleton( 'community-tickets.payouts.hooks', Hooks::class );
		$this->container->singleton( 'community-tickets.payouts.queue', Queue::class );
		$this->container->singleton( 'community-tickets.payouts.report', Report::class );
		$this->container->singleton( 'community-tickets.payouts.report.tab', Report_Tab::class );
		$this->container->singleton( 'community-tickets.payouts.report.tabbed-view', Tabbed_View::class );
		$this->container->singleton( 'community-tickets.payouts.tooltips', Tooltips::class );

		$this->container->bind( 'community-tickets.payouts.paypal.adaptive-payments', Adaptive_Payments::class );
		$this->container->bind( 'community-tickets.payouts.paypal.payouts-api', PayPal_Payouts_API::class );

		$this->container->bind( 'community-tickets.payouts.payout', Payout::class );
		$this->container->bind( 'community-tickets.payouts.order', Order::class );
		$this->container->bind( 'community-tickets.payouts.receiver', Receiver::class );

		$this->container->bind( 'community-tickets.repositories.payout', Payout_Repository::class );

		// Register the SP on the container
		$this->container->singleton( 'community-tickets.payouts.provider', $this );

		// Since the Payouts main class will act as a DI container itself let's provide it with the global container.
		Payouts::set_container( $this->container );

		$this->register_hooks();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function register_hooks() {
		tribe( 'community-tickets.payouts.hooks' )->register();

		add_action( 'init', tribe_callback( 'community-tickets.payouts.queue', 'action_init' ) );

		add_action( 'tribe_community_tickets_payouts_process_queue', tribe_callback( 'community-tickets.payouts.paypal.payouts-api', 'process_queue' ), 10, 2 );
	}
}
