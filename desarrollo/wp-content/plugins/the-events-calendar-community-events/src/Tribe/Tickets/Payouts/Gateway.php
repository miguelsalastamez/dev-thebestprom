<?php
/**
 * Adaptive Payments API functionality.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use WC_Order;

abstract class Gateway {

	/**
	 * Payout Order object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Order
	 */
	protected $order;

	/**
	 * Get list of receivers from order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array List of receiver keys and totals from order.
	 */
	public function get_receivers_from_order( WC_Order $order ) {
		$payout_receivers = [];

		$this->order = tribe( 'community-tickets.payouts.order' );

		// Hydrate from order.
		$this->order->hydrate_from_order( $order );

		// Get list of receivers from order.
		$receivers = $this->order->get_receivers();

		return $this->get_receivers_from_objects( $receivers );
	}

	/**
	 * Get list of receivers from order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout[] $payouts List of payout objects.
	 *
	 * @return array List of receiver keys and totals from order.
	 */
	public function get_receivers_from_payouts( array $payouts ) {
		$receivers = [];

		foreach ( $payouts as $payout ) {
			$receivers[] = $payout->get_receiver();
		}

		return $this->get_receivers_from_objects( $receivers );
	}

	/**
	 * Get list of receivers from Receiver objects, grouped by receiver key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Receiver[] $receivers List of receiver objects.
	 *
	 * @return array List of receiver keys and totals from order.
	 */
	public function get_receivers_from_objects( array $receivers ) {
		$payout_receivers = [];

		foreach ( $receivers as $receiver ) {
			$total = $receiver->get_total();

			if ( 0 < $total ) {
				$key = $receiver->get_key();

				if ( ! isset( $payout_receivers[ $key ] ) ) {
					$payout_receivers[ $key ] = [
						'key'    => $receiver->get_key(),
						'amount' => 0,
					];
				}

				$payout_receivers[ $key ]['amount'] += $total;
			}
		}

		return $payout_receivers;
	}
}
