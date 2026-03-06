<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( Tribe\Events\Payouts\Hooks::class ), 'filter_some_filter' ] );
 * remove_filter( 'some_filter', [ tribe( 'payouts.filters' ), 'filter_some_filter' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( Tribe\Events\Payouts\Hooks::class ), 'on_some_action' ] );
 * remove_action( 'some_action', [ tribe( 'payouts.hooks' ), 'on_some_action' ] );
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */
namespace Tribe\Community\Tickets\Payouts;

use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Report;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Report_Tab;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Tabbed_View;
use Tribe__Tabbed_View;
use TEC\Common\Contracts\Service_Provider;
use WP_Post;

/**
 * Class Hooks
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */
class Hooks extends Service_Provider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Payouts component.
	 *
	 * If youn have to add a function to an existing action,
	 * add it to the existing `on_` function.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function add_actions() {
		add_action( 'init', [ $this, 'on_init' ] );
		add_action( 'admin_menu', [ $this, 'on_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'on_admin_enqueue_scripts' ] );

		// Tribe-specific actions
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'on_tribe_tickets_orders_tabbed_view_register_tab_right' ], 10, 2 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_woocommerce_order_status_changed' ], 10, 4 );
		add_action( 'events_community_tickets_report_list_first', [ $this, 'on_events_community_tickets_report_list_first' ], 20 );
		add_action( 'events_community_tickets_report_list_middle', [ $this, 'on_events_community_tickets_report_list_middle' ], 20 );
		add_action( 'events_community_tickets_report_list_last', [ $this, 'on_events_community_tickets_report_list_last' ], 20 );
		add_action( 'tribe_events_tickets_post_capacity', [ $this, 'on_tribe_events_tickets_post_capacity' ] );
	}

	/**
	 * Holds all functions we want to fire on `init`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 */
	public function on_init() {
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		$payouts->register_post_type();
		$payouts->register_post_stati();
	}

	/**
	 * Holds all functions we want to fire on `admin_menu`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 */
	public function on_admin_menu() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		$report->register_payouts_page();
	}

	/**
	 * Holds all functions we want to fire on `admin_enqueue_scripts`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $hook
	 */
	public function on_admin_enqueue_scripts( $hook ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var \Tribe__Tickets__Attendees $attendees */
		$attendees = tribe( 'tickets.attendees' );

		// We're faking out Tribe__Tickets__Attendees here to ensure the styles get loaded.
		$attendees->enqueue_assets( $attendees->page_id );
	}

	/**
	 * Holds all functions we want to fire on `tribe_tickets_orders_tabbed_view_register_tab_right`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Tribe__Tabbed_View $tabbed_view
	 * @param WP_Post $post
	 */
	public function on_tribe_tickets_orders_tabbed_view_register_tab_right( Tribe__Tabbed_View $tabbed_view, WP_Post $post ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! $payouts->should_show_payouts_actions( $post ) ) {
			return;
		}

		/** @var Report_Tab $report_tab */
		$report_tab = tribe( 'community-tickets.payouts.report.tabbed-view' );

		$report_tab->register_payout_tab( $tabbed_view, $post );
	}

	/**
	 * Holds all functions we want to fire on `events_community_tickets_report_list_first`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param integer $event_id
	 */
	public function on_events_community_tickets_report_list_first( $event_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		$report->payout_details_column_first( $event_id );
		$report->event_action_links( $event_id );
	}

	/**
	 * Holds all functions we want to fire on `events_community_tickets_report_list_middle`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param integer $event_id
	 */
	public function on_events_community_tickets_report_list_middle( $event_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		$report->payout_details_column_middle( $event_id );
	}

	/**
	 * Holds all functions we want to fire on `events_community_tickets_report_list_last`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param integer $event_id
	 */
	public function on_events_community_tickets_report_list_last( $event_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		$report->payout_details_column_last( $event_id );
	}

	/**
	 * Holds all functions we want to fire on `tribe_events_tickets_post_capacity`.
	 *
	 * @param WP_Post|integer $event
	 * @return void
	 */
	public function on_tribe_events_tickets_post_capacity( $event ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		$report->get_payouts_report_button( $event );
	}

	public function on_woocommerce_order_status_changed( $order_id, $status_from, $status_to, $order ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		$payouts->order_status_changed( $order_id, $status_from, $status_to, $order );
	}

	/**
	 * Adds the filters required by each Payouts component.
	 *
	 * Each filter function needs to return the results of the
	 * called function, so if you have to add a function to an
	 * existing filter, you must create a new `filter_` function.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function add_filters() {
		add_filter( 'post_row_actions', [ $this, 'filter_add_payouts_row_action' ], 10, 2 );

		// Tribe-specific filters
		add_filter( 'tribe_tickets_orders_tabbed_view_tab_map', [ $this, 'filter_tribe_tickets_payout_tabbed_view_tab_map' ] );
		add_filter( 'tribe_community_tickets_payouts_tabbed_view_tab_map', [ $this, 'filter_tribe_tickets_payout_tabbed_view_tab_map' ], 12 );
		add_filter( 'tribe_filter_payouts_page_slug', [ $this, 'filter_tribe_filter_payouts_page_slug' ] );
		add_filter( 'tribe_filter_attendee_page_slug', [ $this, 'filter_add_payouts_resources_page_slug' ] );
	}

	/**
	 * Filter post_row_actions via add_payouts_row_action.
	 * Adds order related actions to the available row actions for the post.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function filter_add_payouts_row_action( array $actions, $post ) {
		if ( ! $this->is_enabled() ) {
			return $actions;
		}

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! $payouts->should_show_payouts_actions( $post ) ) {
			return $actions;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		return $report->add_payouts_row_action( $actions, $post );
	}

	/**
	 * Filter tribe_tickets_orders_tabbed_view_tab_map via filter_tribe_tickets_payout_tabbed_view_tab_map.
	 * Adds the payout tab slug to the tab slug map.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $tab_map
	 *
	 * @return array
	 */
	public function filter_tribe_tickets_payout_tabbed_view_tab_map( array $tab_map = [] ) {
		if ( ! $this->is_enabled() ) {
			return $tab_map;
		}

		/** @var Tabbed_View $tabbed_view */
		$tabbed_view = tribe( 'community-tickets.payouts.report.tabbed-view' );

		return $tabbed_view->filter_tribe_tickets_payout_tabbed_view_tab_map( $tab_map );
	}

	/**
	 * Filter tribe_filter_attendee_page_slug via add_payouts_resources_page_slug.
	 * Filter the page slugs that the payouts resources will load to add the order page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $slugs
	 *
	 * @return array
	 */
	public function filter_add_payouts_resources_page_slug( $slugs ) {
		if ( ! $this->is_enabled() ) {
			return $slugs;
		}

		/** @var Report $report */
		$report = tribe( 'community-tickets.payouts.report' );

		return $report->add_payouts_resources_page_slug( $slugs );
	}

	/**
	 * Determine whether payouts is enabled.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return bool Whether payouts is enabled.
	 */
	private function is_enabled() {
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		return $payouts->is_split_payments_enabled();
	}
}
