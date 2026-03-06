<?php
/**
 * Payouts Tooltip Class
 *
 * @since 4.7.0
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

class Tooltips {
	/**
	 * Get Pending Order Completion Payouts Amount Tooltip.
	 *
	 * @since 4.7.0
	 *
	 * @return string HTML for the tooltip.
	 */
	public function get_pending_order_payouts_status_tooltip() {
		$message = esc_html__( 'Pending Order Completion includes all payouts that have an order that has not yet been completed.', 'tribe-events-community-tickets' );

		return tribe( 'tooltip.view' )->render_tooltip( $message );
	}

	/**
	 * Get Pending Payouts Amount Tooltip.
	 *
	 * @since 4.7.0
	 *
	 * @return string HTML for the tooltip.
	 */
	public function get_pending_payouts_status_tooltip() {
		$message = esc_html__( 'Pending includes all payouts pending processing through the queue.', 'tribe-events-community-tickets' );

		return tribe( 'tooltip.view' )->render_tooltip( $message );
	}

	/**
	 * Get Paid Payouts Amount Tooltip.
	 *
	 * @since 4.7.0
	 *
	 * @return string HTML for the tooltip.
	 */
	public function get_paid_payouts_status_tooltip() {
		$message = esc_html__( 'Paid includes all successful payouts for all Completed orders.', 'tribe-events-community-tickets' );

		return tribe( 'tooltip.view' )->render_tooltip( $message );
	}


	/**
	 * Get Failed Payouts Amount Tooltip.
	 *
	 * @since 4.7.0
	 *
	 * @return string HTML for the tooltip.
	 */
	public function get_failed_payouts_status_tooltip() {
		$message = esc_html__( 'Failed payouts happen when an order is Cancelled or Refunded before the Payout is paid, or when the Payout API fails to process the Payout.', 'tribe-events-community-tickets' );

		return tribe( 'tooltip.view' )->render_tooltip( $message );
	}

}
