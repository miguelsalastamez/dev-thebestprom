<?php
/**
 * Object to hold information about a single order.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use Tribe\Community\Tickets\Payouts;
use Tribe__Events__Community__Tickets__Fees as Fees;
use Tribe__Events__Community__Tickets__Gateway__Abstract as Gateway_Abstract;
use Tribe__Events__Community__Tickets__Gateway__PayPal as Gateway_PayPal;
use Tribe__Events__Community__Tickets__Main as Main;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main as ET_Plus_WooCommerce;
use WC_Cart;
use WC_Order;
use WP_Post;
use WP_User;

/**
 * Class Order
 *
 * @package Tribe\Community\Tickets\Payouts
 */
class Order {

	/**
	 * Order provider.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $provider = '';

	/**
	 * Order ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|int
	 */
	protected $order_id;

	/**
	 * List of receiver objects, null if not setup.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Receiver[]
	 */
	protected $receivers = [];

	/**
	 * Payment fee setting for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $payment_fee_setting = '';

	/**
	 * Total fees for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $fees;

	/**
	 * Total subtotal for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $subtotal;

	/**
	 * Total total for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|float
	 */
	protected $total;

	/**
	 * Order constructor.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
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
		$this->fees                = null;
		$this->order_id            = null;
		$this->payment_fee_setting = '';
		$this->provider            = '';
		$this->receivers           = [];
		$this->subtotal            = null;
		$this->total               = null;
	}

	/**
	 * Hydrate order data from WooCommerce Cart.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_Cart $cart WooCommerce Cart object.
	 *
	 * @return self Current object instance.
	 */
	public function hydrate_from_cart( $cart ) {
		$this->init();

		if ( ! $cart instanceof WC_Cart ) {
			return $this;
		}

		$items = $cart->get_cart();

		if ( empty( $items ) ) {
			return $this;
		}

		$this->provider = 'woo';

		$this->parse_items( $items );

		return $this;
	}

	/**
	 * Hydrate order data from WooCommerce Order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int|WC_Order $order WooCommerce Order object or Order ID.
	 *
	 * @return self Current object instance.
	 */
	public function hydrate_from_order( $order ) {
		$this->init();

		if ( ! $order instanceof WC_Order ) {
			if ( ! is_numeric( $order ) ) {
				return $this;
			}

			$order = new WC_Order( $order );
		}

		$items = $order->get_items();

		if ( empty( $items ) ) {
			return $this;
		}

		$this->provider = 'woo';
		$this->order_id = $order->get_id();

		$this->parse_items( $items );

		return $this;
	}

	/**
	 * Parse list of items from order or cart.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $items List of items from order or cart.
	 */
	public function parse_items( array $items ) {
		/** @var Fees $payout_fees */
		$payout_fees = tribe( 'community-tickets.fees' );

		/** @var \Tribe__Events__Community__Tickets__Fee_Handler $payout_fee_handler */
		$payout_fee_handler = tribe( 'community-tickets.fee-handler' );

		/** @var ET_Plus_WooCommerce $et_woo */
		$et_woo   = tribe( 'tickets-plus.commerce.woo' );
		$meta_key = $et_woo->event_key;

		/** @var Main $main */
		$main    = tribe( 'community-tickets.main' );
		$options = get_option( $main::OPTIONNAME );

		/** @var Gateway_PayPal $gateway */
		$gateway = $main->gateway( 'PayPal' );

		/** @var Payouts $payouts */
		$payouts                   = tribe( 'community-tickets.payouts' );
		$is_split_payments_enabled = $payouts->is_split_payments_enabled();

		// Get site receiver e-mail.
		if ( $is_split_payments_enabled && ! empty( $options['paypal_receiver_email'] ) ) {
			$site_receiver_email = $options['paypal_receiver_email'];
		} else {
			$woocommerce_options = get_option( 'woocommerce_paypal_settings', [] );

			$site_receiver_email = '';

			if ( ! empty( $woocommerce_options['receiver_email'] ) ) {
				$site_receiver_email = $woocommerce_options['receiver_email'];
			}
		}

		/** @var \Tribe__Events__Community__Tickets__Payment_Options_Form $payment_options_form */
		$payment_options_form = $main->payment_options_form();

		$receiver_list   = [];
		$this->receivers = [];

		// Get payment fee setting.
		$this->get_payment_fee_setting();

		foreach ( $items as $item ) {
			// Skip if we do not have a quantity.
			if ( empty( $item['quantity'] ) && empty( $item['qty'] ) ) {
				continue;
			}

			// Get event ID from product ID.
			$event_id = $this->get_event_id_from_product_id( $item['product_id'], $meta_key );

			// Event not found.
			if ( ! $event_id ) {
				continue;
			}

			// Event does not have fees enabled.
			if ( ! $payout_fees->has_event_fees( $event_id ) ) {
				continue;
			}

			// Get event object.
			$event = get_post( $event_id );

			// Event does not exist.
			if ( ! $event instanceof WP_Post ) {
				continue;
			}

			// Get user object from event creator ID.
			$event_creator    = get_user_by( 'id', $event->post_author );
			$receiver_user_id = 0;

			$receiver_key        = $site_receiver_email;
			$payment_fee_setting = $this->payment_fee_setting;

			// Get receiver e-mail from event creator.
			if ( $event_creator instanceof WP_User ) {
				$receiver_user_id = $event_creator->ID;

				if ( $is_split_payments_enabled ) {
					$creator_options = $payment_options_form->get_meta_options( $receiver_user_id );

					// Only override if it has a value.
					if ( ! empty( $creator_options['paypal_account_email'] ) ) {
						$receiver_key = $creator_options['paypal_account_email'];
					}

					/*
					 * If site payment fee setting is 'absorb' and the organizer fee display override is set to 'pass',
					 * and organizer fee display override is enabled, only support the 'pass' override.
					 */
					if (
						'absorb' === $payment_fee_setting
						&& 'pass' === $creator_options['payment_fee_setting']
						&& $payouts->is_organizer_fee_display_override_enabled()
					) {
						$payment_fee_setting = 'pass';
					}
				}
			}

			// Get correct quantity value.
			$quantity = ! empty( $item['quantity'] ) ? $item['quantity'] : $item['qty'];
			$quantity = max( $quantity, 1 );

			$price = 0;
			$total = 0;

			$price = $payout_fee_handler->get_ticket_price_from_product( $item );

			$total = $quantity * $price;

			// Handle receiver data.
			$receiver = [
				'tickets'             => [],
				'events'              => [],
				'user_id'             => $receiver_user_id,
				'key'                 => $receiver_key,
				'payment_fee_setting' => $payment_fee_setting,
			];

			// Setup initial receiver data.
			if ( isset( $receiver_list[ $receiver_key ] ) ) {
				$receiver = $receiver_list[ $receiver_key ];
			}

			// Handle receiver ticket data.
			$receiver_ticket = [
				'ticket_id'           => $item['product_id'],
				'event_id'            => $event_id,
				'quantity'            => (int) $quantity,
				'price'               => (float) $price,
				'total'               => (float) $total,
				'payment_fee_setting' => $payment_fee_setting,
			];

			// Add this ticket to receiver data.
			$receiver['tickets'][] = $receiver_ticket;

			// Handle receiver event data.
			$receiver_event = [
				'tickets'             => [],
				'event_id'            => $event_id,
				'price'               => 0.00,
				'total'               => 0.00,
				'payment_fee_setting' => $payment_fee_setting,
			];

			// Setup initial receiver event data.
			if ( isset( $receiver['events'][ $event_id ] ) ) {
				$receiver_event = $receiver['events'][ $event_id ];
			}

			// Add this ticket to receiver event data.
			$receiver_event['tickets'][] = $receiver_ticket;
			$receiver_event['price']     += $receiver_ticket['price'];
			$receiver_event['total']     += $receiver_ticket['total'];

			// Add this event to receiver data.
			$receiver['events'][ $event_id ] = $receiver_event;

			// Add this receiver to the list.
			$receiver_list[ $receiver_key ] = $receiver;
		}

		// Loop through receiver data and build unique receivers.
		foreach ( $receiver_list as $receiver_data ) {
			/** @var Receiver $receiver */
			$receiver = tribe( 'community-tickets.payouts.receiver' );

			// Build receiver from receiver data.
			$receiver->hydrate_from_receiver_data( $receiver_data, $this );

			// Add receiver to list.
			$this->receivers[] = $receiver;
		}
	}

	/**
	 * Get the order provider if set.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order provider if set.
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * Get the order gateway if set.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order gateway if set.
	 */
	public function get_gateway() {
		return get_post_meta( $this->order_id, '_tribe_split_payment_method', true );
	}

	/**
	 * Get order ID if set.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return null|int Order ID or null if not set.
	 */
	public function get_id() {
		return $this->order_id;
	}

	/**
	 * Set order ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $order_id Order ID.
	 */
	public function set_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * Get list of receivers for order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return Receiver[] List of receivers.
	 */
	public function get_receivers() {
		return $this->receivers;
	}

	/**
	 * Get the fees for the order.
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
	 * @return float Fees for the order.
	 */
	public function get_fees( array $args = [] ) {
		$gateway            = ! empty( $args['gateway'] ) ? $args['gateway'] : null;
		$limit_by_operation = ! empty( $args['limit_by_operation'] ) ? $args['limit_by_operation'] : null;
		$limit_by_event_id  = ! empty( $args['limit_by_event_id'] ) ? $args['limit_by_event_id'] : null;

		$all_data = empty( $limit_by_operation ) && empty( $limit_by_event_id );

		if ( $all_data && ! empty( $this->fees ) ) {
			return $this->fees;
		}

		// Get gateway if not set.
		if ( null === $gateway ) {
			/** @var Main $main */
			$main = tribe( 'community-tickets.main' );

			/** @var Gateway_PayPal $gateway */
			$gateway = $main->gateway( 'PayPal' );
		}

		$receivers = $this->get_receivers();

		$fees = 0;

		foreach ( $receivers as $receiver ) {
			$fees += $receiver->get_fees( $args );
		}

		if ( $all_data ) {
			$this->fees = $fees;
		}

		return $fees;
	}

	/**
	 * Get the subtotal amount for the order which excludes fees.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Subtotal amount for the order which excludes fees.
	 */
	public function get_subtotal() {
		if ( null !== $this->subtotal ) {
			return $this->subtotal;
		}

		// Get list of receivers.
		$receivers = $this->get_receivers();

		$subtotal = 0.00;

		foreach ( $receivers as $receiver ) {
			$subtotal += $receiver->get_subtotal();
		}

		$this->subtotal = $subtotal;

		return $subtotal;
	}

	/**
	 * Get the total amount for the order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Total amount for the order.
	 */
	public function get_total() {
		if ( null !== $this->total ) {
			return $this->total;
		}

		// Get list of receivers.
		$receivers = $this->get_receivers();

		$total = 0.00;

		foreach ( $receivers as $receiver ) {
			$total += $receiver->get_total();
		}

		$this->total = $total;

		return $total;
	}

	/**
	 * Get list of events for the order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array List of events for the order.
	 */
	public function get_events() {
		// Get list of receivers.
		$receivers = $this->get_receivers();

		$events = [
			[],
		];

		foreach ( $receivers as $receiver ) {
			$events[] = $receiver->get_events();
		}

		$events = array_merge( ...$events );

		return $events;
	}

	/**
	 * Get list of event totals for the order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array List of event totals for the order.
	 */
	public function get_event_totals() {
		// Get list of receivers.
		$receivers = $this->get_receivers();

		$totals = [];

		foreach ( $receivers as $receiver ) {
			$fees_per_event     = $receiver->get_fees_per_event();
			$subtotal_per_event = $receiver->get_subtotal_per_event();
			$total_per_event    = $receiver->get_total_per_event();

			foreach ( $total_per_event as $event_id => $total ) {
				$totals[ $event_id ] = [
					'fees'     => isset( $fees_per_event[ $event_id ] ) ? $fees_per_event[ $event_id ] : 0,
					'subtotal' => isset( $subtotal_per_event[ $event_id ] ) ? $subtotal_per_event[ $event_id ] : 0,
					'total'    => $total,
				];
			}
		}

		return $totals;
	}

	/**
	 * Get list of tickets for the order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array List of tickets for the order.
	 */
	public function get_tickets() {
		// Get list of receivers.
		$receivers = $this->get_receivers();

		$tickets = [
			[],
		];

		foreach ( $receivers as $receiver ) {
			$tickets[] = $receiver->get_tickets();
		}

		$tickets = array_merge( ...$tickets );

		return $tickets;
	}

	/**
	 * Get the current payment fee setting for the order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string The current payment fee setting for the order.
	 */
	public function get_payment_fee_setting() {
		if ( ! $this->payment_fee_setting ) {
			/** @var Main $main */
			$main = tribe( 'community-tickets.main' );

			$this->payment_fee_setting = $main->get_payment_fee_setting();
		}

		return $this->payment_fee_setting;
	}

	/**
	 * Get event ID from product ID using a specific meta key for lookup.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Meta key for lookup.
	 *
	 * @return int|false Event ID or false if the event was not found.
	 */
	protected function get_event_id_from_product_id( $product_id, $meta_key ) {
		// Get event ID from product meta.
		$event_id = (int) get_post_meta( $product_id, $meta_key, true );

		// No event ID found.
		if ( ! $event_id ) {
			return false;
		}

		return $event_id;
	}
}
