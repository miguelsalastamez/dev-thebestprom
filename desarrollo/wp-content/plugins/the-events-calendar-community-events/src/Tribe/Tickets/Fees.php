<?php

use Tribe\Community\Tickets\Payouts\Order;
use Tribe__Events__Community__Tickets__Main as Main;

/**
 * Class Tribe__Events__Community__Tickets__Fees
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 */
class Tribe__Events__Community__Tickets__Fees {

	/**
	 * List of flat fee settings.
	 *
	 * @var array
	 */
	protected $flat_fees = [
		'flat',
		'flat-and-percentage',
		'flat-and-percentage-per-ticket',
		'flat-per-ticket',
	];

	/**
	 * List of percentage fee settings.
	 *
	 * @var array
	 */
	protected $percentage_fees = [
		'flat-and-percentage',
		'flat-and-percentage-per-ticket',
		'percentage',
	];

	/**
	 * List of per event fee settings.
	 *
	 * @var array
	 */
	protected $per_event = [
		'flat',
		'flat-and-percentage',
		'percentage',
	];

	/**
	 * List of per ticket fee settings.
	 *
	 * @var array
	 */
	protected $per_ticket = [
		'flat-and-percentage-per-ticket',
		'flat-per-ticket',
	];

	/**
	 * An Array of the Current Fee on the Site Set in
	 * Tribe__Events__Community__Tickets__Fee_Handler
	 *
	 * @var array
	 */
	public $current_fee = [];

	/**
	 * The Meta Key the Fee Data Saves to for an Order
	 *
	 * @var string
	 */
	public $ticket_fee_order_meta_key = '_community_tickets_order_fees';

	/**
	 * Hook to Get Current Site Fees
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 */
	public function hooks() {
		$this->current_fee = tribe( 'community-tickets.fee-handler' )->get_site_fee_data();
	}

	/**
	 * Get site fee type.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Site fee type.
	 */
	public function get_site_fee_type() {
		return $this->get_current_fee( 'type' );
	}

	/**
	 * Get Current Fee either by key or all fee information.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $key a key for a fee attribute
	 *
	 * @return array|mixed a specific fee attribute or the array of current fee attributes
	 *
	 */
	public function get_current_fee( $key = '' ) {

		if ( $key && isset( $this->current_fee[ $key ] ) ) {
			return $this->current_fee[ $key ];
		}

		return $this->current_fee;
	}

	/**
	 * Determine if fee is flat or not.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string|null $fee_type The site fee setting.
	 *
	 * @return bool Whether fee is flat or not.
	 */
	public function is_flat_fee( $fee_type = null ) {
		// Get fee type if not set.
		if ( null === $fee_type ) {
			$fee_type = $this->get_site_fee_type();
		}

		return in_array( $fee_type, $this->flat_fees, true );
	}

	/**
	 * Determine if fee is percentage or not.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string|null $fee_type The site fee setting.
	 *
	 * @return bool Whether fee is percentage or not.
	 */
	public function is_percentage_fee( $fee_type = null ) {
		// Get fee type if not set.
		if ( null === $fee_type ) {
			$fee_type = $this->get_site_fee_type();
		}

		return in_array( $fee_type, $this->percentage_fees, true );
	}

	/**
	 * Determine if fee is per event or not.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string|null $fee_type The site fee setting.
	 *
	 * @return bool Whether fee is per event or not.
	 */
	public function is_per_event_fee( $fee_type = null ) {
		// Get fee type if not set.
		if ( null === $fee_type ) {
			$fee_type = $this->get_site_fee_type();
		}

		return in_array( $fee_type, $this->per_event, true );
	}

	/**
	 * Determine if fee is per ticket or not.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string|null $fee_type The site fee setting.
	 *
	 * @return bool Whether fee is per ticket or not.
	 */
	public function is_per_ticket_fee( $fee_type = null ) {
		// Get fee type if not set.
		if ( null === $fee_type ) {
			$fee_type = $this->get_site_fee_type();
		}

		return in_array( $fee_type, $this->per_ticket, true );
	}

	/**
	 * Get whether the event qualifies to have fees or not.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id Event ID.
	 *
	 * @return bool Whether the event qualifies to have fees or not.
	 */
	public function has_event_fees( $event_id ) {
		/**
		 * Allow filtering of whether to add fees to all tickets no matter their event origin.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param boolean $add_fee  Whether to add fees to all tickets no matter their event origin.
		 * @param int     $event_id Event ID.
		 */
		$add_fee = apply_filters( 'tribe_community_tickets_add_fee_to_all_tickets', false, $event_id );

		// All events have fees.
		if ( $add_fee ) {
			return true;
		}

		// Get Event Origin and return false if not Community.
		$event_origin = get_post_meta( $event_id, '_EventOrigin', true );

		// CE events have fees.
		if ( 'community-events' === $event_origin ) {
			return true;
		}

		return false;
	}

	/**
	 * Get event object from product ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int         $product_id Product ID.
	 * @param null|string $operation  Fee operation.
	 *
	 * @return bool Whether the event qualifies to have fees or not.
	 */
	public function should_add_product_fee( $product_id, $operation = null ) {
		// Get event ID from product meta.
		$event_id = get_post_meta( $product_id, '_tribe_wooticket_for_event', true );

		// No event ID found.
		if ( ! $event_id ) {
			return false;
		}

		$has_event_fees = $this->has_event_fees( $event_id );

		// Event has no fees.
		if ( ! $has_event_fees ) {
			return false;
		}

		// Determine if event has specific operation set.
		if ( null !== $operation ) {
			/** @var Main $main */
			$main = tribe( 'community-tickets.main' );

			$payment_fee_setting = $main->get_payment_fee_setting( $event_id );

			// Operation does not match event's payment fee setting.
			if ( $operation !== $payment_fee_setting ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Does the Order Have an Event with Tickets Created with Community Tickets
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $order WC_Order WooCommerce Order Object
	 *
	 * @return bool $is_community true or false it is a community ticket order
	 */

	public function is_community_ticket_order( $order ) {

		$is_community = false;
		if ( ! $order ) {
		    return $is_community;
		}

		$items = $order->get_items( 'line_item' );
		foreach ( $items as $item ) {

			if ( $is_community ) {
				continue;
			}

		    $product_id = $item->get_product_id();

			$is_community = $this->should_add_product_fee( $product_id );

		}

		return $is_community;
	}

	/**
	 * Calculate fees for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array                                                     $order_breakdown Parsed order breakdown.
	 * @param Tribe__Events__Community__Tickets__Gateway__Abstract|null $gateway         Gateway object.
	 *
	 * @return float Fee that will be added to cart.
	 */
	public function calculate_fee( array $order_breakdown, $gateway = null ) {
		if ( null === $gateway ) {
			/** @var Tribe__Events__Community__Tickets__Main $main */
			$main     = tribe( 'community-tickets.main' );
			$gateway  = $main->gateway( 'PayPal' );
		}

		$fee = 0;

		// Check for receivers.
		if ( empty( $order_breakdown['receivers'] ) ) {
			return $fee;
		}

		foreach ( $order_breakdown['receivers'] as $receiver_email => $receiver ) {
			// Skip if order breakdown has no fees defined.
			if ( ! isset( $order_breakdown['fees'][ $receiver_email ] ) ) {
				continue;
			}

			$payment_fee_setting = $this->current_fee['operation'];

			if ( ! empty( $receiver['payment_fee_setting'] ) ) {
				$payment_fee_setting = $receiver['payment_fee_setting'];
			}

			// add operation is not supported on the flat and flat-and-percentage fee types
			if ( 'add' === $payment_fee_setting && in_array( $this->current_fee['type'], [ 'flat', 'flat-and-percentage' ], true ) ) {
				continue;
			}

			if ( 'event' === $order_breakdown['type'] ) {
				// Calculate fees per event.
				$fee += $this->calculate_event_fee( $order_breakdown['fees'][ $receiver_email ], $gateway );
			} else {
				// Calculate fees per ticket.
				$fee += $this->calculate_ticket_fee( $order_breakdown['fees'][ $receiver_email ], $gateway );
			}
		}

		return $fee;
	}

	/**
	 * Calculate fees per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array                                                     $events   List of events.
	 * @param Tribe__Events__Community__Tickets__Gateway__Abstract|null $gateway  Gateway object.
	 * @param int                                                       $event_id An optional Event ID.
	 *
	 * @return float Fee that will be added to event.
	 */
	public function calculate_event_fee( array $events, $gateway = null, $event_id = 0 ) {
		if ( null === $gateway ) {
			/** @var Tribe__Events__Community__Tickets__Main $main */
			$main     = tribe( 'community-tickets.main' );
			$gateway  = $main->gateway( 'PayPal' );
		}

		// If the fees are passed on to the end user, the calculations for the actual total works like this.
		$flat_fee     = $gateway->fee_flat();
		$percentage   = $gateway->fee_percentage();
		$flat_on_free = $gateway->site_fee_on_free();
		$fee          = 0;

		foreach ( $events as $event ) {
			$price = 0;

			// If there is an event ID specified, only return fees for that event.
			if ( ! empty( $event_id ) && $event_id !== $event['event_id'] ) {
				continue;
			}

			if ( isset( $event['total'] ) ) {
				$price = (float) $event['total'];
			} elseif ( isset( $event['price'] ) ) {
				$price = (float) $event['price'];
			}

			// If the ticket is free and "Add flat fees to free tickets" is disabled, skip free tickets.
			if ( ! $flat_on_free && $price <= 0 ) {
				continue;
			}

			// Calculation: Percentage of Ticket Price + Flat Fee for each event.
			if ( 0 < $percentage && 0 < $price ) {
				$fee += round( $price * ( $percentage / 100 ), 2 );
			}

			if ( 0 < $flat_fee ) {
				$fee += $flat_fee;
			}

			// Prevent fee from being more than the price if using 'absorb' payment fee setting.
			if ( 'absorb' === $event['payment_fee_setting'] ) {
				$fee = min( $price, $fee );
			}
		}

		return $fee;
	}

	/**
	 * Calculate fees per ticket.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array                                                     $tickets  List of tickets.
	 * @param Tribe__Events__Community__Tickets__Gateway__Abstract|null $gateway  Gateway object.
	 * @param int                                                       $event_id An optional Event ID.
	 *
	 * @return float Fee that will be added to ticket.
	 */
	public function calculate_ticket_fee( array $tickets, $gateway = null, $event_id = 0 ) {
		if ( null === $gateway ) {
			/** @var Tribe__Events__Community__Tickets__Main $main */
			$main     = tribe( 'community-tickets.main' );
			$gateway  = $main->gateway( 'PayPal' );
		}

		$flat_on_free = $gateway->site_fee_on_free();
		$fee          = 0;

		foreach ( $tickets as $ticket ) {
			$ticket_quantity = isset( $ticket['quantity'] ) ? (int) $ticket['quantity'] : 1;

			// If there is an event ID specified, only return fees for that event.
			if ( ! empty( $event_id ) && $event_id !== $ticket['event_id'] ) {
				continue;
			}

			$ticket_price = 0;

			if ( isset( $ticket['ticket-price'] ) ) {
				$ticket_price = (float) $ticket['ticket-price'];
			} elseif ( isset( $ticket['price'] ) ) {
				$ticket_price = (float) $ticket['price'];
			}

			// If the ticket is free and "Add flat fees to free tickets" is disabled, skip free tickets.
			if ( ! $flat_on_free && $ticket_price <= 0 ) {
				continue;
			}

			$ticket_fee = $this->get_ticket_fee_from_price( $ticket_price, $ticket['payment_fee_setting'] );

			// Prevent fee from being more than the price if using 'absorb' payment fee setting.
			if ( 'absorb' === $ticket['payment_fee_setting'] ) {
				$ticket_fee = min( $ticket_price, $ticket_fee );
			}

			$fee += $ticket_quantity * $ticket_fee;
		}

		return $fee;
	}

	/**
	 * Get the ticket price without fees.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int         $price     The price of the ticket.
	 * @param null|string $operation Fee operation being used.
	 *
	 * @return float The price without fees included.
	 */
	public function get_ticket_price_without_fee( $price, $operation = null ) {
		if ( null === $operation ) {
			$operation = $this->current_fee['operation'];
		}

		// Skip if pass is set.
		if ( 'pass' === $operation || 'none' === $this->current_fee['type'] ) {
			return $price;
		}

		$fee_data = $this->get_ticket_fee_data_from_price( $price, $operation );

		// Return extrapolated price.
		if ( 'add' === $operation ) {
			return $fee_data['price'];
		}

		// Return price minus fees.
		return $fee_data['price'] - $fee_data['fee'];
	}

	/**
	 * Get the Fee From the Ticket Price.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int         $price     The price of the ticket.
	 * @param null|string $operation Fee operation being used.
	 *
	 * @return float the fee for a ticket
	 *
	 * @todo SKC: Fix this so it checks on current event operation that's passed through and not current fee data.
	 */
	public function get_ticket_fee_from_price( $price, $operation = null ) {
		if ( null === $operation ) {
			$operation = $this->current_fee['operation'];
		}

		// Skip if pass is set and we aren't calculating fees.
		if ( 'none' === $this->current_fee['type'] ) {
			return 0;
		}

		$fee_data = $this->get_ticket_fee_data_from_price( $price, $operation );

		return $fee_data['fee'];
	}

	/**
	 * Get the ticket fee information from the ticket price.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int    $price     The price of the ticket.
	 * @param null|string $operation Fee operation being used.
	 *
	 * @return array Ticket fee information.
	 */
	public function get_ticket_fee_data_from_price( $price, $operation = null ) {
		if ( null === $operation ) {
			$operation = $this->current_fee['operation'];
		}

		$fee_data = [
			'price' => (float) $price,
			'fee'   => 0.00,
		];

		// Skip if no fees.
		if ( 'none' === $this->current_fee['type'] ) {
			return $fee_data;
		}

		$has_price = 0 < $fee_data['price'];

		// Skip if there is no price and no fees on free tickets.
		if ( ! $has_price && ( ! $this->current_fee['is-on-free'] || 'absorb' === $operation ) ) {
			return $fee_data;
		}

		$flat_fee = 0;

		// Calculate flat fee.
		if ( 0 < $this->current_fee['flat-fee'] ) {
			// Get flat fee.
			$flat_fee = $this->current_fee['flat-fee'];

			// Add flat fee.
			$fee_data['fee'] += $flat_fee;
		}

		// Calculate percentage fee.
		if ( 0 < $this->current_fee['percentage-fee'] ) {
			// Get percentage.
			$percentage = $this->current_fee['percentage-fee'] / 100;

			// Calculate percentage fee if the price needs fee added.
			$fee = round( $fee_data['price'] * $percentage, 2 );

			// Add percentage fee.
			$fee_data['fee'] += $fee;
		}

		return $fee_data;
	}

	/**
	 * Get the ticket fee data for a WooCommerce Order and optionally save it and/or add an order note.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int  $order_id       WooCommerce Order ID.
	 * @param bool $update_meta    Whether to update order meta with fee data.
	 * @param bool $add_order_note Whether to add order note to order.
	 *
	 * @return array|bool The current fee data, or false if no fees added.
	 */
	public function get_fee_data_for_an_order( $order_id, $update_meta = false, $add_order_note = false ) {
		$order = wc_get_order( $order_id );

		$fees_added = $this->is_community_ticket_order( $order );

		// No fees to add.
		if ( ! $fees_added ) {
			return false;
		}

		$gateway = tribe( 'community-tickets.main' )->gateway( 'PayPal' );
		$cart    = new Tribe__Events__Community__Tickets__Cart;

		/** @var Order $payout_order */
		$payout_order = tribe( 'community-tickets.payouts.order' );

		// Parse order items.
		$payout_order->parse_items( $order->get_items() );

		$this->current_fee['breakdown']       = $cart->parse_order( $order->get_items() );
		$this->current_fee['order_fee_total'] = $payout_order->get_fees();
		$this->current_fee['per_event']       = $payout_order->get_event_totals();
		$this->current_fee['order_subtotal']  = $order->get_subtotal();
		$this->current_fee['order_total']     = $order->get_total();

		// Add ticket fee data to meta data for reports.
		if ( $update_meta ) {
			update_post_meta( $order->get_id(), tribe( 'community-tickets.fees' )->ticket_fee_order_meta_key, $this->current_fee );
		}

		// Add an order note.
		if ( $add_order_note ) {
			$order_note = $this->get_fee_message();

			if ( $order_note ) {
				$currency = tribe( 'tickets.commerce.currency' );

				$order->add_order_note( $order_note );
				$order->add_order_note( esc_html_x( 'Total Fees Collected: ', 'the total fees collected for an order', 'tribe-events-community' ) . $currency->format_currency( $this->current_fee['order_fee_total'] ) );
				$order->save();
			}
		}

		return $this->current_fee;
	}

	/**
	 * Get the fee message.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string|false The fee message, or false if no fee message to show.
	 */
	public function get_fee_message() {
		$site_fee_type = $this->current_fee['type'];

		// Only build fees if we have one set or it is not none.
		if ( empty( $site_fee_type ) || 'none' === $site_fee_type ) {
			return false;
		}

		$message = $this->get_fee_message_prefix() . ' ' . $this->get_fee_message_amount() . ' ' . $this->get_fee_message_type();

		$free_message = $this->get_fee_message_free();

		if ( $free_message ) {
			$message .= ' ' . $free_message;
		}

		return $message;
	}

	/**
	 * Get the fee message prefix.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string The fee message prefix.
	 */
	public function get_fee_message_prefix() {
		if ( 'pass' === $this->current_fee['operation'] ) {
			return esc_html_x( 'A site fee of', 'the prefix to the fee message for added in subtotal', 'tribe-events-community' );
		}

		return esc_html_x( 'A fee of', 'the prefix to the fee message for fees deducted or added to price', 'tribe-events-community' );
	}

	/**
	 * Get the fee message amount.
	 *
	 * This method returns the formatted fee amount with the currency symbol and percentage sign (if applicable).
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string The formatted fee message amount.
	 */
	public function get_fee_message_amount() {
		// Get the default provider setting.
		$default_provider_setting = tribe( 'TEC\Community_Tickets\Tickets\Commerce\DefaultProvider' )->get_default_provider_setting();

		// Use the default provider if it's not set to 'TEC_Tickets_Commerce_Module', otherwise use TEC\Tickets\Commerce\Module.
		$provider = ( $default_provider_setting === 'TEC_Tickets_Commerce_Module' ) ? $default_provider_setting : TEC\Tickets\Commerce\Module::class;

		// Initialize an empty array to hold the formatted fees.
		$fee = [];

		// If percentage fee is enabled and set, add it to the fees array.
		if ( $this->current_fee['is-percentage-fee'] && $this->current_fee['percentage-fee'] ) {
			$fee[] = $this->current_fee['percentage-fee'] . '%';
		}

		// If flat fee is enabled and set, add it to the fees array.
		if ( $this->current_fee['is-flat-fee'] && $this->current_fee['flat-fee'] ) {
			$currency = tribe( 'Tribe__Tickets__Commerce__Currency' );

			$fee[] = $currency->get_formatted_currency_with_symbol( $this->current_fee['flat-fee'], 0, $provider, false );
		}

		// Return the formatted fee amounts as a string joined by ' + '. If there are no fees, return an empty string.
		return ( count( $fee ) > 0 ) ? implode( ' + ', $fee ) : '';
	}

	/**
	 * Get the fee message type.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string The fee message type.
	 */
	public function get_fee_message_type() {
		$type = [];

		$type['operation'] = esc_html_x( 'is deducted', 'the fee mathematical operation (deduct or add)', 'tribe-events-community' );

		if ( in_array( $this->current_fee['operation'], [ 'add', 'pass' ], true ) ) {
			$type['operation'] = esc_html_x( 'is added', 'the fee mathematical operation (deduct or add)', 'tribe-events-community' );
		}

		$type['type'] = esc_html_x( 'per ticket', 'the type of fee (per ticket or event)', 'tribe-events-community' );

		if ( $this->current_fee['is-per-event-fee'] ) {
			if ( ! $this->current_fee['is-flat-fee'] && $this->current_fee['is-percentage-fee'] ) {
				$type['type'] = esc_html_x( 'per transaction', 'the type of fee (per ticket, event, or transaction)', 'tribe-events-community' );
			} else {
				$type['type'] = esc_html_x( 'per event in the cart', 'the type of fee (per ticket, event, or transaction)', 'tribe-events-community' );
			}
		}

		return implode( ' ', $type );
	}

	/**
	 * Get the fee message free text.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string|false The fee message free text, or false if not shown.
	 */
	public function get_fee_message_free() {
		// Skip absorb operations, cases where there's no flat fee, or no flat fee on free tickets.
		if ( 'absorb' === $this->current_fee['operation'] || ! $this->current_fee['is-flat-fee'] || ! $this->current_fee['is-on-free'] ) {
			return false;
		}

		return esc_html_x( 'including free tickets', 'fee message to be added if flat fees set on free', 'tribe-events-community' );
	}
}
