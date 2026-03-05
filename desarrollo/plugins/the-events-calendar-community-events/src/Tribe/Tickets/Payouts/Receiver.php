<?php
/**
 * Object to hold information about a single receiver.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use Tribe__Events__Community__Tickets__Fees as Fees;
use Tribe__Events__Community__Tickets__Gateway__Abstract as Gateway_Abstract;
use Tribe__Events__Community__Tickets__Gateway__PayPal as Gateway_PayPal;
use Tribe__Events__Community__Tickets__Main as Main;

/**
 * Class Receiver
 *
 * @package Tribe\Community\Tickets\Payouts
 */
class Receiver {

	/**
	 * Order object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|Order
	 */
	protected $order;

	/**
	 * Order ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $order_id = 0;

	/**
	 * List of tickets for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|array
	 */
	protected $tickets;

	/**
	 * List of events containing their tickets for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|array
	 */
	protected $events;

	/**
	 * Key of receiver (e-mail address, API key, etc).
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $key = '';

	/**
	 * User ID of receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * Total fees for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $fees;

	/**
	 * Total fees for receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float[]
	 */
	protected $fees_per_event = [];

	/**
	 * Total subtotal for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $subtotal;

	/**
	 * Total subtotal for receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float[]
	 */
	protected $subtotal_per_event = [];

	/**
	 * Total total for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $total;

	/**
	 * Total total for receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float[]
	 */
	protected $total_per_event = [];

	/**
	 * Receiver constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize object property defaults.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function init() {
		$this->events             = null;
		$this->fees               = null;
		$this->fees_per_event     = [];
		$this->key                = '';
		$this->order              = null;
		$this->order_id           = 0;
		$this->subtotal           = null;
		$this->subtotal_per_event = [];
		$this->tickets            = null;
		$this->total              = null;
		$this->total_per_event    = [];
		$this->user_id            = null;
	}

	/**
	 * Hydrate receiver data from array.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $receiver_data Receiver information with ticket and event data.
	 * @param Order $order         Order object.
	 *
	 * @return self Current object instance.
	 */
	public function hydrate_from_receiver_data( array $receiver_data, Order $order ) {
		$this->init();

		$this->events   = $receiver_data['events'];
		$this->key      = $receiver_data['key'];
		$this->order    = $order;
		$this->order_id = $order->get_id();
		$this->tickets  = $receiver_data['tickets'];
		$this->user_id  = $receiver_data['user_id'];

		return $this;
	}

	/**
	 * Hydrate receiver data from Payout object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $payout Payout object.
	 *
	 * @return self Current object instance.
	 */
	public function hydrate_from_payout( Payout $payout ) {
		$this->init();

		$this->events   = $payout->get_events();
		$this->key      = $payout->get_receiver_key();
		$this->order_id = $payout->get_order_id();
		$this->tickets  = $payout->get_tickets();
		$this->user_id  = $payout->get_receiver_user_id();

		return $this;
	}

	/**
	 * Get the order object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return null|Order The Order object, or null if not set.
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Get the order provider.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order provider.
	 */
	public function get_order_provider() {
		return $this->order ? $this->order->get_provider() : '';
	}

	/**
	 * Get the order gateway.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order gateway.
	 */
	public function get_order_gateway() {
		return $this->order ? $this->order->get_gateway() : '';
	}

	/**
	 * Get the order ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Order ID.
	 */
	public function get_order_id() {
		return $this->order ? $this->order->get_id() : $this->order_id;
	}

	/**
	 * Get the list of tickets for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array|null List of tickets for receiver.
	 */
	public function get_tickets() {
		return $this->tickets;
	}

	/**
	 * Get the list of events containing their tickets for receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array|null List of events containing their tickets for receiver.
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * Get the key of receiver (e-mail address, API key, etc).
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Key of receiver (e-mail address, API key, etc).
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the user ID of receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int User ID of receiver.
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Get the fees for the receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $args {
	 *      List of arguments to override fees handling.
	 *
	 *      @var string           $fee_type           Fee type (optional).
	 *      @var Gateway_Abstract $gateway            Gateway object (optional).
	 *      @var string           $limit_by_operation Which operation to limit by (optional).
	 *      @var int              $limit_by_event_id  Which event ID to limit by (optional).
	 * }
	 *
	 * @return float Fees for the receiver.
	 */
	public function get_fees( array $args = [] ) {
		$limit_by_operation = ! empty( $args['limit_by_operation'] ) ? $args['limit_by_operation'] : null;
		$limit_by_event_id  = ! empty( $args['limit_by_event_id'] ) ? $args['limit_by_event_id'] : null;

		$all_data = empty( $limit_by_operation ) && empty( $limit_by_event_id );

		if ( $all_data && null !== $this->fees ) {
			return $this->fees;
		}

		$fees = array_sum( $this->get_fees_per_event( $args ) );

		if ( $all_data ) {
			$this->fees = $fees;
		}

		return $fees;
	}

	/**
	 * Get the fees for the receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $args {
	 *      List of arguments to override fees handling.
	 *
	 *      @var string           $fee_type           Fee type (optional).
	 *      @var Gateway_Abstract $gateway            Gateway object (optional).
	 *      @var string           $limit_by_operation Which operation to limit by (optional).
	 *      @var int              $limit_by_event_id  Which event ID to limit by (optional).
	 * }
	 *
	 * @return float[] Fees for the receiver per event.
	 */
	public function get_fees_per_event( array $args = [] ) {
		$fee_type           = ! empty( $args['fee_type'] ) ? $args['fee_type'] : null;
		$gateway            = ! empty( $args['gateway'] ) ? $args['gateway'] : null;
		$limit_by_operation = ! empty( $args['limit_by_operation'] ) ? $args['limit_by_operation'] : null;
		$limit_by_event_id  = ! empty( $args['limit_by_event_id'] ) ? $args['limit_by_event_id'] : null;

		$all_data = empty( $limit_by_operation ) && empty( $limit_by_event_id );

		if ( $all_data && ! empty( $this->fees_per_event ) ) {
			return $this->fees_per_event;
		}

		// Get gateway if not set.
		if ( null === $gateway ) {
			/** @var Main $main */
			$main = tribe( 'community-tickets.main' );

			/** @var Gateway_PayPal $gateway */
			$gateway = $main->gateway( 'PayPal' );
		}

		/** @var Fees $payout_fees */
		$payout_fees = tribe( 'community-tickets.fees' );

		$events = $this->get_events();

		$fees = [];

		foreach ( $events as $event ) {
			$event_id = $event['event_id'];
			$fee      = 0;

			// Limit by event ID if we need to limit.
			if ( null !== $limit_by_event_id && $event_id !== $limit_by_event_id ) {
				continue;
			}

			// Limit by operation if we need to limit.
			if ( ! empty( $limit_by_operation ) && $limit_by_operation !== $event['payment_fee_setting'] ) {
				continue;
			}

			if ( $payout_fees->is_per_event_fee( $fee_type ) ) {
				// Per event fees.
				$fee = $payout_fees->calculate_event_fee( [ $event ], $gateway );
			} elseif ( $payout_fees->is_per_ticket_fee( $fee_type ) ) {
				// Per ticket fees.
				$fee = $payout_fees->calculate_ticket_fee( $event['tickets'], $gateway );
			}

			$fees[ $event_id ] = $fee;
		}

		if ( $all_data ) {
			$this->fees_per_event = $fees;
		}

		return $fees;
	}

	/**
	 * Get the subtotal amount for the receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Subtotal amount for the receiver.
	 */
	public function get_subtotal() {
		if ( null !== $this->subtotal ) {
			return $this->subtotal;
		}

		$this->subtotal = (float) array_sum( wp_list_pluck( $this->get_tickets(), 'total' ) );

		return $this->subtotal;
	}

	/**
	 * Get the subtotal amount for the receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float[] Subtotal amount for the receiver per event.
	 */
	public function get_subtotal_per_event() {
		if ( ! empty( $this->subtotal_per_event ) ) {
			return $this->subtotal_per_event;
		}

		$events = $this->get_events();

		$this->subtotal_per_event = [];

		foreach ( $events as $event ) {
			$event_id = $event['event_id'];

			$this->subtotal_per_event[ $event_id ] = $event['total'];
		}

		return $this->subtotal_per_event;
	}

	/**
	 * Get the total amount for the receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Total amount for the receiver.
	 */
	public function get_total() {
		if ( null !== $this->total ) {
			return $this->total;
		}

		$total_per_event = $this->get_total_per_event();

		$this->total = array_sum( $total_per_event );

		return $this->total;
	}

	/**
	 * Get the total amount for the receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float[] Total amount for the receiver per event.
	 */
	public function get_total_per_event() {
		if ( ! empty( $this->total_per_event ) ) {
			return $this->total_per_event;
		}

		$subtotals           = $this->get_subtotal_per_event();
		$fees                = $this->get_fees_per_event();
		$events              = $this->get_events();
		$payment_fee_setting = $this->get_payment_fee_setting();

		$this->total_per_event = [];

		foreach ( $events as $event ) {
			$event_id = $event['event_id'];
			$subtotal = $subtotals[ $event_id ];
			$fee      = $fees[ $event_id ];
			$total    = $subtotal;

			// Deduct fees from the receiver's share.
			if ( 'absorb' === $event['payment_fee_setting'] ) {
				$total -= $fee;
			}

			$this->total_per_event[ $event_id ] = $total;
		}

		return $this->total_per_event;
	}

	/**
	 * Get payment fee setting.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Payment fee setting.
	 */
	public function get_payment_fee_setting() {
		if ( $this->order ) {
			$payment_fee_setting = $this->order->get_payment_fee_setting();
		} else {
			/** @var Fees $payout_fees */
			$payout_fees = tribe( 'community-tickets.fees' );

			$payment_fee_setting = $payout_fees->get_current_fee( 'operation' );
		}

		return $payment_fee_setting;
	}
}
