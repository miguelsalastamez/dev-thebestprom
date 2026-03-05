<?php
/**
 * Object to hold information about a single payout.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use Tribe\Community\Tickets\Payouts;
use WP_Post;

class Payout {

	/**
	 * Amount to be paid.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float
	 */
	protected $amount = 0.00;

	/**
	 * Amount to be paid per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float[]
	 */
	protected $amount_per_event = [];

	/**
	 * Fees paid, if absorbed by receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float
	 */
	protected $fees = 0.00;

	/**
	 * Fees paid, if absorbed by receiver per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var float[]
	 */
	protected $fees_per_event = [];

	/**
	 * Order provider.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $order_provider = '';

	/**
	 * Order gateway.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $order_gateway = '';

	/**
	 * Order ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $order_id = 0;

	/**
	 * Receiver key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $receiver_key = '';

	/**
	 * Receiver user ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * List of tickets related to payout, if any.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|array
	 */
	protected $tickets;

	/**
	 * List of events related to payout, if any.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var null|array
	 */
	protected $events;

	/**
	 * Transaction ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $transaction_id = '';

	/**
	 * Date the payout was paid.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $date_paid = '';

	/**
	 * Payout ID, corresponds to the post ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $ID = 0;

	/**
	 * Payout title, corresponds to the post title.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Current payout status, corresponds to the post status.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $status = '';

	/**
	 * Failure message (if failed).
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * Payout receiver object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Receiver
	 */
	private $receiver;

	/**
	 * Payout constructor.
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
		$this->amount           = 0.00;
		$this->amount_per_event = [];
		$this->date_paid        = '';
		$this->error_message    = '';
		$this->events           = null;
		$this->fees             = 0.00;
		$this->fees_per_event   = [];
		$this->ID               = 0;
		$this->order_gateway    = '';
		$this->order_id         = 0;
		$this->order_provider   = '';
		$this->receiver         = null;
		$this->receiver_key     = '';
		$this->status           = '';
		$this->tickets          = null;
		$this->title            = '';
		$this->transaction_id   = '';
		$this->user_id          = 0;
	}

	/**
	 * Hydrate the object from a receiver
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Receiver $receiver
	 *
	 * @return self Current instance.
	 */
	public function hydrate_from_receiver( $receiver ) {
		$this->init();

		// Set initial values.
		$this->status = Payouts::STATUS_PENDING_ORDER;

		// Get values from receiver.
		$this->amount           = $receiver->get_total();
		$this->amount_per_event = $receiver->get_total_per_event();
		$this->events           = $receiver->get_events();
		$this->fees             = $receiver->get_fees();
		$this->fees_per_event   = $receiver->get_fees_per_event();
		$this->order_gateway    = $receiver->get_order_gateway();
		$this->order_id         = $receiver->get_order_id();
		$this->order_provider   = $receiver->get_order_provider();
		$this->receiver_key     = $receiver->get_key();
		$this->tickets          = $receiver->get_tickets();
		$this->user_id          = $receiver->get_user_id();

		// Save receiver for future reference.
		$this->receiver = $receiver;

		return $this;
	}

	/**
	 * Get an existing tribe_payout from the database and set up params.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int|WP_Post $post the post object or ID of the payout post.
	 *
	 * @return Payout|boolean self or false if not found.
	 */
	public function hydrate_from_post( $post ) {
		$this->init();

		// We've got nothing to go on, bail.
		if ( empty( $post ) ) {
			return false;
		}

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		// We can't find the post, bail.
		if ( ! Payouts::PAYOUT_OBJECT === get_post_type( $post ) ) {
			return false;
		}

		// List of keys to get.
		$keys_to_get = $this->get_supported_meta_keys();

		// Post object fields.
		$this->ID     = $post->ID;
		$this->title  = get_the_title( $post->ID );
		$this->status = $post->post_status;

		// Get post meta values.
		foreach ( $keys_to_get as $key ) {
			$meta_key = '_tribe_' . $key;

			$this->{$key} = get_post_meta( $post->ID, $meta_key, true );
		}

		if ( $this->order_id ) {
			$gateway = get_post_meta( $this->order_id, '_tribe_split_payment_method', true );

			if ( ! empty( $gateway ) ) {
				$this->order_gateway = $gateway;
			}
		}

		if ( $this->events ) {
			foreach ( $this->events as $event ) {
				$event_id = $event['event_id'];

				$this->fees_per_event[ $event_id ]   = (float) get_post_meta( $post->ID, '_tribe_event_fee_' . $event_id, true );
				$this->amount_per_event[ $event_id ] = (float) get_post_meta( $post->ID, '_tribe_event_amt_' . $event_id, true );
			}
		}

		return $this;
	}

	/**
	 * Handle saving of payout data.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int|false Payout ID, or false if it failed to fully save.
	 */
	public function save() {
		/** @var \Tribe\Community\Tickets\Repositories\Payout $repository */
		$repository = tribe_payouts();

		try {
			$repository->set( 'post_title', empty( $this->title ) ? 'Payout' : $this->title );
			$repository->set( 'post_content', sprintf( esc_html__( 'Payout for Order #%d', 'tribe-events-community' ), $this->order_id ) );
			$repository->set( 'post_status', $this->status );
			$repository->set( 'post_author', get_current_user_id() );
		} catch ( \Tribe__Repository__Usage_Error $exception ) {
			// Failed to set the data.
			return false;
		}

		// List of keys to save.
		$keys_to_save = $this->get_supported_meta_keys();

		$save_mode = ! empty( $this->ID ) ? 'edit' : 'create';

		// Update post meta.
		foreach ( $keys_to_save as $key ) {
			$meta_key = '_tribe_' . $key;

			// Delete values if they aren't set or are empty.
			if ( ! isset( $this->{$key} ) || in_array( $this->{$key}, [ '', [] ], true ) ) {
				if ( 'edit' === $save_mode ) {
					delete_post_meta( $this->ID, $meta_key );
				}

				continue;
			}

			$value = $this->{$key};

			try {
				$repository->set( $meta_key, $value );
			} catch ( \Tribe__Repository__Usage_Error $exception ) {
				// Failed to set the data.
				return false;
			}
		}

		try {
			// Handle saving.
			if ( 'edit' === $save_mode ) {
				// Update existing payout post.
				$repository->in( (array) $this->ID );

				$repository->save();
			} else {
				// Create new payout post.
				$created = $repository->create();

				if ( ! $created instanceof WP_Post ) {
					return false;
				}

				$this->ID = $created->ID;
			}
		} catch ( \Tribe__Repository__Usage_Error $exception ) {
			// Failed to save the data.
			return false;
		}

		// Payout not saved.
		if ( empty( $this->ID ) ) {
			return false;
		}

		// Save ticket IDs so they can be queried against.
		if ( ! empty( $this->tickets ) ) {
			delete_post_meta( $this->ID, '_tribe_ticket_id' );

			foreach ( $this->tickets as $ticket ) {
				$ticket_id = $ticket['ticket_id'];

				add_post_meta( $this->ID, '_tribe_ticket_id', $ticket_id );

				update_post_meta( $this->ID, '_tribe_ticket_qty_' . $ticket_id, $ticket['quantity'] );
			}
		}

		// Save event IDs so they can be queried against.
		if ( ! empty( $this->events ) ) {
			delete_post_meta( $this->ID, '_tribe_event_id' );

			// Get receiver.
			$fees_per_event  = [];
			$total_per_event = [];

			if ( null !== $this->receiver ) {
				$fees_per_event  = $this->receiver->get_fees_per_event();
				$total_per_event = $this->receiver->get_total_per_event();
			}

			foreach ( $this->events as $event ) {
				$event_id = $event['event_id'];

				add_post_meta( $this->ID, '_tribe_event_id', $event_id );

				update_post_meta( $this->ID, '_tribe_event_qty_' . $event_id, array_sum( wp_list_pluck( $event['tickets'], 'quantity' ) ) );

				if ( null !== $this->receiver ) {
					update_post_meta( $this->ID, '_tribe_event_fee_' . $event_id, $fees_per_event[ $event_id ] );
					update_post_meta( $this->ID, '_tribe_event_amt_' . $event_id, $total_per_event[ $event_id ] );
				}
			}
		}

		return $this->ID;
	}

	/**
	 * Get list of meta keys supported.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array List of meta keys supported.
	 */
	public function get_supported_meta_keys() {
		$keys = [
			'amount',
			'date_paid',
			'error_message',
			'events',
			'fees',
			'order_gateway',
			'order_id',
			'order_provider',
			'receiver_key',
			'tickets',
			'transaction_id',
			'user_id',
		];

		/**
		 * Filters the list of meta keys supported. These keys will have `_tribe_` prepended in get/update meta usage.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $keys List of meta keys supported.
		 */
		$keys = apply_filters( 'tribe_community_tickets_payout_supported_meta_keys', $keys );

		return $keys;
	}

	/**
	 * Get amount from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Amount from payout.
	 */
	public function get_amount() {
		return $this->amount;
	}

	/**
	 * Get amount from payout per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float[] Amount from payout per event.
	 */
	public function get_amount_per_event() {
		if ( ! empty( $this->amount_per_event ) ) {
			return $this->amount_per_event;
		}

		$receiver = $this->get_receiver();

		return $receiver->get_total_per_event();
	}

	/**
	 * Set amount from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param float $amount Amount to set.
	 */
	public function set_amount( $amount ) {
		$this->amount = $amount;
	}

	/**
	 * Get fees from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float Fees from payout.
	 */
	public function get_fees() {
		return $this->fees;
	}

	/**
	 * Get fees from payout per event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return float[] Fees from payout per event.
	 */
	public function get_fees_per_event() {
		if ( ! empty( $this->fees_per_event ) ) {
			return $this->fees_per_event;
		}

		$receiver = $this->get_receiver();

		return $receiver->get_fees_per_event();
	}

	/**
	 * Set fees from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param float $fees Fees to set.
	 */
	public function set_fees( $fees ) {
		$this->fees = $fees;
	}

	/**
	 * Get order provider from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order provider from payout.
	 */
	public function get_order_provider() {
		return $this->order_provider;
	}

	/**
	 * Set order provider from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $order_provider Order provider to set.
	 */
	public function set_order_provider( $order_provider ) {
		$this->order_provider = $order_provider;
	}

	/**
	 * Get order gateway from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Order gateway from payout.
	 */
	public function get_order_gateway() {
		return $this->order_gateway;
	}

	/**
	 * Set order gateway from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $order_gateway Order gateway to set.
	 */
	public function set_order_gateway( $order_gateway ) {
		$this->order_gateway = $order_gateway;
	}

	/**
	 * Get the payout order date.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string The payout order date.
	 */
	public function get_order_date() {
		$order = new \WC_Order( $this->order_id );

		return $order->get_date_created();
	}

	/**
	 * Get order ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Order ID from payout.
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Set order ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $order_id Order ID to set.
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * Get the payout receiver.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return bool|Receiver The payout receiver, or false if not found.
	 */
	public function get_receiver() {
		if ( $this->receiver ) {
			return $this->receiver;
		}

		/** @var Receiver $receiver */
		$receiver = tribe( 'community-tickets.payouts.receiver' );

		// Build receiver from payout.
		$this->receiver = $receiver->hydrate_from_payout( $this );

		return $this->receiver;
	}

	/**
	 * Get receiver key from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Receiver key from payout.
	 */
	public function get_receiver_key() {
		return $this->receiver_key;
	}

	/**
	 * Set receiver key from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $receiver_key Receiver key to set.
	 */
	public function set_receiver_key( $receiver_key ) {
		$this->receiver_key = $receiver_key;
	}

	/**
	 * Get receiver user ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int User ID from payout.
	 */
	public function get_receiver_user_id() {
		return $this->user_id;
	}

	/**
	 * Set receiver user ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $user_id User ID to set.
	 */
	public function set_receiver_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the payout receiver user.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return bool|\WP_User The payout receiver user, or false if not found.
	 */
	public function get_receiver_user() {
		return get_userdata( $this->user_id );
	}

	/**
	 * Get tickets from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array|null Tickets from payout, null if not set.
	 */
	public function get_tickets() {
		return $this->tickets;
	}

	/**
	 * Set tickets from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $tickets Tickets from payout, null if to set.
	 */
	public function set_tickets( array $tickets ) {
		$this->tickets = $tickets;
	}

	/**
	 * Get events from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array|null Events from payout, null if not set.
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * Set events from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $events Events from payout, null if to set.
	 */
	public function set_events( array $events ) {
		$this->events = $events;
	}

	/**
	 * Get transaction ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Transaction ID from payout.
	 */
	public function get_transaction_id() {
		return $this->transaction_id;
	}

	/**
	 * Set transaction ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $transaction_id Transaction ID to set.
	 */
	public function set_transaction_id( $transaction_id ) {
		$this->transaction_id = $transaction_id;
	}

	/**
	 * Get date paid from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Date paid from payout.
	 */
	public function get_date_paid() {
		return $this->date_paid;
	}

	/**
	 * Set date paid from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $date_paid Date paid to set.
	 */
	public function set_date_paid( $date_paid ) {
		$this->date_paid = $date_paid;
	}

	/**
	 * Get ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int ID from payout.
	 */
	public function get_id() {
		return $this->ID;
	}

	/**
	 * Set ID from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $id ID to set.
	 */
	public function set_id( $id ) {
		$this->ID = $id;
	}

	/**
	 * Get title from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Title from payout.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set title from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $title Title to set.
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * Get status from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Status from payout.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get status label from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string|null Status label from payout, or null if not found.
	 */
	public function get_status_label() {
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		return $payouts->get_status_label( $this->status );
	}

	/**
	 * Set status on Payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $status Status to set on Payout.
	 */
	public function set_status( $status ) {
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( null === $payouts->get_status_label( $status ) ) {
			return;
		}

		$this->status = $status;

		if ( 'paid' === $status ) {
			$this->date_paid = Date_Utils::reformat( strtotime( 'now' ), Date_Utils::DBDATETIMEFORMAT );
		}
	}

	/**
	 * Get error message from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string Error message from payout.
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Set error message from payout.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $error_message Error message to set.
	 */
	public function set_error_message( $error_message ) {
		$this->error_message = $error_message;
	}
}
