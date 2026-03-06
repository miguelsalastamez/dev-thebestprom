<?php
if ( ! function_exists( 'tribe_community_tickets_is_frontend_attendees_report' ) ) {
	/**
	 * A handy function for knowing if we're on a front-end Attendees Report.
	 *
	 * @since 4.6.2
	 *
	 * @return boolean
	 */
	function tribe_community_tickets_is_frontend_attendees_report() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && 'view-attendees-report-route' === $wp_route;
	}
}

if ( ! function_exists( 'tribe_community_tickets_is_frontend_sales_report' ) ) {
	/**
	 * A handy function for knowing if we're on a front-end Sales Report.
	 *
	 * @since 4.6.2
	 *
	 * @return boolean
	 */
	function tribe_community_tickets_is_frontend_sales_report() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && 'view-sales-report-route' === $wp_route;
	}
}

/**
 * Builds and returns the correct payout repository.
 *
 * @since 4.7.0
 *
 * @param string $repository The slug of the repository to build/return.
 *
 * @return \Tribe\Community\Tickets\Repositories\Payout
 */
function tribe_payouts( $repository = 'default' ) {
	$map = [
		'default' => 'community-tickets.repositories.payout',
	];

	/**
	 * Filters the map relating payout repository slugs to service container bindings.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
	 * @param string $repository The currently requested implementation.
	 */
	$map = apply_filters( 'tribe_community_tickets_payout_repository_map', $map, $repository );

	return tribe( Tribe__Utils__Array::get( $map, $repository, $map['default'] ) );
}
