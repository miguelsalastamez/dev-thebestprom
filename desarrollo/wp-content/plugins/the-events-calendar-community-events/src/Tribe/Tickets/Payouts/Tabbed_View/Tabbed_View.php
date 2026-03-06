<?php
/**
 * The Payouts Tabbed_View object.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts\Tabbed_View
 * */

namespace Tribe\Community\Tickets\Payouts\Tabbed_View;

use \Tribe__Utils__Array as Utils_Array;

class Tabbed_View extends \Tribe__Tabbed_View {

	/**
	 * @var array A map that binds requested pages to tabs.
	 */
	protected $tab_map = [
		'tickets-attendees' => 'tribe-tickets-attendance-report',
	];

	/**
	 * Adds the payout tab slug to the tab slug map.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $tab_map array of tab slugs that the tabs are generated from.
	 *
	 * @return array
	 */
	public function filter_tribe_tickets_payout_tabbed_view_tab_map( array $tab_map = [] ) {
		$tab_map[ Report::$payouts_slug ] = tribe( 'community-tickets.payouts.report.tab' )->get_slug();
		return $tab_map;
	}

	/**
	 * Registers the payout tab among those the tabbed view should render.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param \Tribe__Tabbed_View $tabbed_view the tabbed view object.
	 * @param WP_Post            $post the post/event we're linking the tab view to.
	 */
	public function register_payout_tab( \Tribe__Tabbed_View $tabbed_view, \WP_Post $post ) {
		$payout_report     = new Report_Tab( $tabbed_view );
		$payout_report_url = Report::get_payouts_report_link( $post );
		$payout_report->set_url( $payout_report_url );
		$tabbed_view->register( $payout_report );
	}

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param bool $active_tab_slug Whether this tab should be set to active or not.
	 */
	public function render( $active = null ) {
		$post_id = Utils_Array::get( $_GET, 'event_id', Utils_Array::get( $_GET, 'post_id', false ), false );
		if ( empty( $post_id ) || ! $post = get_post( $post_id ) ) {
			return;
		}

		$view = new \Tribe__Tabbed_View();
		$view->set_label( apply_filters( 'the_title', $post->post_title, $post->post_id ) );
		$query_string = empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'];
		$request_uri  = 'edit.php' . $query_string;
		$view->set_url( remove_query_arg( 'tab', $request_uri ) );

		$this->tab_map = $this->get_tab_map();

		if ( ! empty( $active ) ) {
			$view->set_active( $active );
		} else {
			// try to set the active tab from the requested page
			parse_str( $request_uri, $query_args );
			if ( ! empty( $query_args['page'] ) && isset( $this->tab_map[ $query_args['page'] ] ) ) {

				$active = $this->tab_map[ $query_args['page'] ];
				$view->set_active( $active );
			}
		}

		$payout_report     = new Report_Tab( $view );
		$payout_report_url = Report::get_payouts_report_link( $post );
		$payout_report->set_url( $payout_report_url );
		$view->register( $payout_report );

		$orders_report     = new \Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab( $view );
		$orders_report_url = \Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$view->register( $orders_report );

		$attendees_report = new \Tribe__Tickets__Tabbed_View__Attendee_Report_Tab( $view );
		$post             = get_post( $post_id );
		$attendees_report->set_url( tribe( 'tickets.attendees' )->get_report_link( $post ) );
		$view->register( $attendees_report );

		echo $view->render();
	}

	/**
	 * Returns the attendee, orders and payouts tabbed view tabs to map the tab request slug to
	 * the registered tabs.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array $tab_map An associative array in the [ <query_var> => <tab_slug> ] format.
	 */
	protected function get_tab_map() {
		/**
		 * Filters the attendee, orders, and payouts tabbed view tabs to map the tab request slug to
		 * the registered tabs.
		 *
		 * The map will relate the GET query variable to the registered tab slugs.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $tab_map An associative array in the [ <query_var> => <tab_slug> ] format.
		 */
		$tab_map = apply_filters( 'tribe_community_tickets_payouts_tabbed_view_tab_map', $this->tab_map );

		return $tab_map;
	}
}
