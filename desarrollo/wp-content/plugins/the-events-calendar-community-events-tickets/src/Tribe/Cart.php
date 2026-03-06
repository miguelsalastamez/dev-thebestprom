<?php

use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Order;
use Tribe__Events__Community__Tickets__Fees as Fees;
use Tribe__Events__Community__Tickets__Gateway__PayPal as Gateway_PayPal;
use Tribe__Events__Community__Tickets__Main as Main;

class Tribe__Events__Community__Tickets__Cart {

	/**
	 * Get the gateway used by this commerce adapter.
	 *
	 * @return Gateway_PayPal|WP_Error Gateway object or error object if class does not exist.
	 */
	public function gateway() {
		return tribe( 'community-tickets.main' )->gateway( 'PayPal' );
	}

	/**
	 * Parses order items and breaks them down into receivers, amounts, and opportunities for fees.
	 *
	 * @param array $items Items to loop over (cart items, order items, etc).
	 *
	 * @return array List of receivers and fees.
	 */
	public function parse_order( array $items ) {
		$receivers = [];
		$fees      = [];

		/** @var Order $order */
		$order = tribe( 'community-tickets.payouts.order' );

		// Parse order items.
		$order->parse_items( $items );

		// Get list of receivers.
		$receiver_list = $order->get_receivers();

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		/** @var Fees $payout_fees */
		$payout_fees = tribe( 'community-tickets.fees' );

		/** @var Main $main */
		$main = tribe( 'community-tickets.main' );

		/** @var Gateway_PayPal $gateway */
		$gateway = $main->gateway( 'PayPal' );

		$site_fee_type = $payout_fees->get_site_fee_type();
		$fee_type      = 'none';

		if ( $payout_fees->is_per_event_fee( $site_fee_type ) ) {
			// Per event fees.
			$fee_type = 'event';
		} elseif ( $payout_fees->is_per_ticket_fee( $site_fee_type ) ) {
			// Per ticket fees.
			$fee_type = 'ticket';
		} else {
			// No fees.
			return [
				'receivers' => [],
				'fees'      => 0.00,
				'type'      => $fee_type,
			];
		}

		// Get payment fee setting.
		$payment_fee_setting = $payout_fees->get_current_fee( 'operation' );

		foreach ( $receiver_list as $receiver ) {
			$receiver_email = $receiver->get_key();

			if ( ! isset( $receivers[ $receiver_email ] ) ) {
				// Set up the receiver.
				$receivers[ $receiver_email ] = [
					'user_id'             => $receiver->get_user_id(),
					'payment_fee_setting' => $payment_fee_setting,
					'email'               => $receiver_email,
					'amount'              => 0,
					'ticket-price'        => 0,
					'quantity'            => 0,
					'primary'             => 'false',
				];
			}

			$tickets = $receiver->get_tickets();

			foreach ( $tickets as $ticket ) {
				// Update quantity / amount.
				$receivers[ $receiver_email ]['ticket-price'] += $ticket['price'];
				$receivers[ $receiver_email ]['quantity']     += $ticket['quantity'];
				$receivers[ $receiver_email ]['amount']       += $ticket['total'];

				$receivers[ $receiver_email ]['payment_fee_setting'] = $ticket['payment_fee_setting'];

				if ( 'event' === $fee_type ) {
					// Charge fees per event.
					$fee_id = $ticket['event_id'];
				} elseif ( 'ticket' === $fee_type ) {
					// Charge fees per ticket.
					$fee_id = $ticket['ticket_id'];
				} else {
					// No fees.
					continue;
				}

				// Track flat fee deduction requirements for e-mail.
				if ( ! isset( $fees[ $receiver_email ] ) ) {
					$fees[ $receiver_email ] = [];
				}

				// Save fees information fees.
				if ( ! isset( $fees[ $receiver_email ][ $fee_id ] ) ) {
					$fees[ $receiver_email ][ $fee_id ] = [
						'event_id'            => $ticket['event_id'],
						'ticket-price'        => 0,
						'price'               => 0,
						'quantity'            => 0,
						'payment_fee_setting' => $ticket['payment_fee_setting'],
					];
				}

				// Continue adding quantity and price to the fees tracked.
				$fees[ $receiver_email ][ $fee_id ]['ticket-price'] = $ticket['price'];
				$fees[ $receiver_email ][ $fee_id ]['quantity']     += $ticket['quantity'];
				$fees[ $receiver_email ][ $fee_id ]['price']        += $ticket['total'];
			}

			$receivers[ $receiver_email ]['ticket-price'] = number_format( $receivers[ $receiver_email ]['ticket-price'], 2, '.', '' );
			$receivers[ $receiver_email ]['amount'] = number_format( $receivers[ $receiver_email ]['amount'], 2, '.', '' );
		}

		// Return parsed data.
		return [
			'receivers' => $receivers,
			'fees'      => $fees,
			'type'      => $fee_type,
		];
	}

	/**
	 * Calculate cart site fees. Site fees will only exist if the payment settings for at least one
	 * line item in an order is set to pass fees on to purchasers.
	 *
	 * @since 4.7.0 - only add fees to cart on pass through site fee
	 *
	 * @param WC_Cart $cart WooCommerce cart object.
	 */
	public function calculate_cart_fees( $cart ) {
		/** @var Fees $payout_fees */
		$payout_fees = tribe( 'community-tickets.fees' );

		// Get fee operation.
		$fee_operation = $payout_fees->get_current_fee( 'operation' );

		// Get fee type.
		$fee_type = $payout_fees->get_current_fee( 'type' );

		if ( 'none' === $fee_type ) {
			return;
		}

		/** @var Order $order */
		$order = tribe( 'community-tickets.payouts.order' );

		$order->hydrate_from_cart( $cart );

		// Only get fees for events with 'pass' set.
		$args = [
			'limit_by_operation' => 'pass',
		];

		// Calculate fees for order.
		$fees = $order->get_fees( $args );

		// Handle adding fee to the cart.
		if ( ! empty( $fees ) ) {
			$fee_text = __( 'Site Fees', 'tribe-events-community-tickets' );

			/**
			 * Filters the fee text to use in the cart.
			 *
			 * @since 4.7.0
			 *
			 * @param string $fee_text Fee text to use in cart.
			 *
			 */
			$fee_text = apply_filters( 'tribe_community_tickets_cart_fee_text', $fee_text );

			// Add fee to the cart.
			$cart->add_fee( $fee_text, $fees );
		}
	}

	/**
	 * Calculate Fees Per Events
	 *
	 * @deprecated 4.7.0
	 *
	 * @since 4.6.2
	 *
	 * @param array $events an array of events
	 *
	 * @return float Fee that must be added to event
	 */
	public function calculate_event_fee( $events = [] ) {
		_deprecated_function( __METHOD__, '4.7.0', 'tribe( \'community-tickets.fees\' )->calculate_event_fee( $events, $gateway )' );

		return tribe( 'community-tickets.fees' )->calculate_event_fee( $events, $this->gateway() );
	}

	/**
	 * Calculate Fees Per Ticket
	 *
	 * @deprecated 4.7.0
	 *
	 * @since 4.6.2
	 *
	 * @param array $tickets an array of tickets
	 *
	 * @return float Fee that must be added to ticket
	 */
	public function calculate_ticket_fee( $tickets = [] ) {
		_deprecated_function( __METHOD__, '4.7.0', 'tribe( \'community-tickets.fees\' )->calculate_ticket_fee( $tickets, $gateway )' );

		return tribe( 'community-tickets.fees' )->calculate_ticket_fee( $tickets, $this->gateway() );
	}

	/**
	 * When passing fees on to a user, we need to calculate the appropriate fees for a given amount
	 * such that the fees do not impact the amount that will be delivered to a community organizer.
	 *
	 * @deprecated 4.6.2
	 *
	 * @since 3.12
	 *
	 * @param float $price Price of ticket.
	 *
	 * @return float Fee that will be added to ticket.
	 */
	public function calculate_ticket_fee_for_pass_setting( $price ) {
		_deprecated_function( __METHOD__, '4.6.2', 'tribe( \'community-tickets.fees\' )->calculate_event_fee( $events, $gateway ) and calculate_ticket_fee( $tickets, $gateway )' );

		$gateway    = $this->gateway();
		$flat_fee   = $gateway->fee_flat();
		$percentage = $gateway->fee_percentage();

		// Calculation: Percentage of Price + Flat Fee.
		$fee = round( $price * ( $percentage / 100 ), 2 ) + $flat_fee;

		return $fee;
	}

}
