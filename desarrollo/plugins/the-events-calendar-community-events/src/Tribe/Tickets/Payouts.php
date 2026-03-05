<?php
/**
 * The Main Payouts Class.
 *
 * This class should remain as "thin" as possible - containing only what is necessary.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets
 */

namespace Tribe\Community\Tickets;

use Tribe\Community\Tickets\Payouts\Order;
use Tribe\Community\Tickets\Payouts\Payout;
use Tribe\Community\Tickets\Payouts\PayPal\Adaptive_Payments;
use Tribe\Community\Tickets\Payouts\PayPal\Payouts_API;
use Tribe\Community\Tickets\Payouts\Tabbed_View\Report;
use Tribe__Container as Container;
use Tribe__Tickets__Main as Tickets_Main;

class Payouts {
	/**
	 * Name of the CPT that holds Payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	const PAYOUT_OBJECT = 'tribe_payout';

	/**
	 * The pending order status key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	const STATUS_PENDING_ORDER = 'tribe-payout-hold';

	/**
	 * The pending status key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	const STATUS_PENDING = 'tribe-payout-pending';

	/**
	 * The paid status key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	const STATUS_PAID = 'tribe-payout-paid';

	/**
	 * The failed status key.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	const STATUS_FAILED = 'tribe-payout-failed';

	/**
	 * Allowed statuses for payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var array
	 */
	protected $supported_stati = [];

	/**
	 * An instance of the DI container.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Container
	 */
	protected static $container;

	/**
	 * Instance of the main Community Tickets class.
	 *
	 * @since 5.0.0 Migrated to Community Events from Community Tickets.
	 *
	 * @var Tribe__Events__Community__Tickets__Main
	 */
	public $main;

	/**
	 * contructobots roll out!
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function __construct() {
		$this->payouts_report();

		//** @var Tribe__Events__Community__Tickets__Main $main */
		$this->main = tribe( 'community-tickets.main' );

		add_action( 'woocommerce_checkout_order_processed', [ $this, 'generate_payouts_for_order' ], 10, 2 );
	}

	/**
	 * Register our custom post type.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register_post_type() {
		$payout_post_args = [
			'label'           => 'Payouts',
			'labels'          => [
				'name'          => __( 'Payouts', 'tribe-events-community' ),
				'singular_name' => __( 'Payout', 'tribe-events-community' ),
			],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => false,
			'query_var'          => false,
			'rewrite'            => false,
			'show_in_menu'       => false,
			'show_ui'            => false,

			// @todo: add capability requirement 'view attendees'
		];

		/**
		 * Filter the arguments that craft the payout post type.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @see register_post_type
		 *
		 * @param array $payout_post_args Post type arguments, passed to register_post_type()
		 */
		$payout_post_args = apply_filters( 'tribe_community_tickets_register_payout_post_type_args', $payout_post_args );

		register_post_type( $this::PAYOUT_OBJECT, $payout_post_args );
	}

	/**
	 * Build our custom post stati.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function build_post_stati() {
		$stati = [
			self::STATUS_PENDING_ORDER    => [
				'label'                     => __( 'Pending Order Completion', 'tribe-events-community' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of payouts */
				'label_count'               => _n_noop( 'Pending Order Completion <span class="count">(%s)</span>', 'Pending Order Completion <span class="count">(%s)</span>', 'tribe-events-community' ),
			],
			self::STATUS_PENDING          => [
				'label'                     => __( 'Pending Payout', 'tribe-events-community' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of payouts */
				'label_count'               => _n_noop( 'Pending Payout <span class="count">(%s)</span>', 'Pending Payout <span class="count">(%s)</span>', 'tribe-events-community' ),
			],
			self::STATUS_PAID             => [
				'label'                     => __( 'Paid', 'tribe-events-community' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of payouts */
				'label_count'               => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'tribe-events-community' ),
			],
			self::STATUS_FAILED           => [
				'label'                     => __( 'Failed', 'tribe-events-community' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of payouts */
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'tribe-events-community' ),
			],
		];

		/**
		 * Filter the arguments that craft the payout post stati.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @see register_post_type
		 *
		 * @param array $stati List of post status arguments that will be looped and passed to register_post_status()
		 */
		$this->supported_stati = apply_filters( 'tribe_community_tickets_register_payout_post_stati', $stati );
	}

	/**
	 * Register our custom post stati.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register_post_stati() {
		$this->build_post_stati();

		// Don't register post status until we're doing / have done action init.
		if ( ! doing_action( 'init' ) && ! did_action( 'init' ) ) {
			return;
		}

		foreach ( $this->supported_stati as $status => $args ) {
			register_post_status( $status, $args );
		}
	}

	/**
	 * Get status label from payout status.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string Payout status.
	 *
	 * @return string|null Status label for payout status, or null if not found.
	 */
	public function get_status_label( $status ) {
		// Maybe register post stati.
		if ( empty( $this->supported_stati ) ) {
			$this->build_post_stati();
		}

		if ( isset( $this->supported_stati[ $status ] ) ) {
			return $this->supported_stati[ $status ]['label'];
		}

		return null;
	}

	/**
	 * Get list of supported stati.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array List of supported stati.
	 */
	public function get_supported_stati() {
		// Maybe register post stati.
		if ( empty( $this->supported_stati ) ) {
			$this->build_post_stati();
		}

		return $this->supported_stati;
	}

	/**
	 * Orders report object accessor method.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return Report
	 */
	public function payouts_report() {
		static $report;

		if ( ! $report instanceof Report ) {
			$report = new Report;
		}

		return $report;
	}

	/**
	 * Sets the DI container the class should use to build views.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Container $container The DI container instance to use.
	 */
	public static function set_container( Container $container ) {
		self::$container = $container;
	}

	/**
	 * Get the method in a backwards-compatible way.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string|boolean The method name or false if none set.
	 */
	public function split_payment_method() {
		$enabled = $this->main->get_option( 'enable_split_payments' );

		// If the "enable" checkbox isn't set, bail.
		if ( empty( $enabled ) ) {
			return false;
		}

		$method = $this->main->get_option( 'split_payment_method' );

		if ( ! empty( $method ) ) {
			return $method;
		}

		// If the method isn't set, we may have a legacy user, so check for a required field.
		$field = $this->main->get_option( 'paypal_api_username' );

		if ( empty( $field ) ) {
			return false;
		}

		// A field is set, so we update the meta to reflect a legacy user and return that value.
		$method = Adaptive_Payments::GATEWAY;

		$this->main->set_option( 'split_payment_method', $method );

		return $method;
	}

	/**
	 * Just check if enabled - no matter the method.
	 * Really just boolean sugar for `split_payment_method`.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean
	 */
	public function is_split_payments_enabled() {
		$method = $this->split_payment_method();

		// If the "enable" checkbox isn't set, bail.
		if ( empty( $method ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Just check if organizer fee display override is enabled.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean Whether organizer fee display override is enabled.
	 */
	public function is_organizer_fee_display_override_enabled() {
		/**
		 * Allow plugins to filter whether organizer fee display override is enabled.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param boolean $is_enabled Whether organizer fee display override is enabled.
		 */
		return apply_filters( 'tribe_community_tickets_payouts_organizer_fee_display_override', true );
	}

	/**
	 * Returns whether or not payouts has enough data to be functional.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean
	 */
	public function is_split_payment_functional() {
		if ( ! $this->is_split_payments_enabled() ) {
			return false;
		}

		$options = $this->main->get_options();

		foreach ( $this->main->paypal_required_options as $option ) {
			if ( empty( $options[ $option ] ) ) {
				return false;
			}
		}

		$method = $this->split_payment_method();

		$required_option_gateways = [
			Adaptive_Payments::GATEWAY => Adaptive_Payments::REQUIRED_OPTIONS,
			Payouts_API::GATEWAY       => Payouts_API::REQUIRED_OPTIONS,
		];

		if ( ! isset( $required_option_gateways[ $method ] ) ) {
			return true;
		}

		$required_options = $required_option_gateways[ $method ];

		foreach ( $required_options as $option ) {
			if ( empty( $options[ $option ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns whether or not split payments are enabled.
	 * Fails if split payments aren't enabled
	 * OR the method is not set to adaptive payments.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean
	 */
	public function is_adaptive_payments_enabled() {
		$method = $this->split_payment_method();

		return Adaptive_Payments::GATEWAY === $method;
	}

	/**
	 * Returns whether or not payouts is enabled.
	 * Fails if split payments aren't enabled,
	 * or the method is not set to payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean
	 */
	public function is_payouts_enabled() {
		//  get and check the method we're using
		$method = $this->split_payment_method();

		return Payouts_API::GATEWAY === $method;
	}

	/**
	 * Determine whether payouts actions should be shown. Mainly for row actions and admin pages, also used on the front end for reports.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int|WP_Post $post
	 *
	 * @return boolean Whether payouts actions should be shown.
	 */
	public function should_show_payouts_actions( $post ) {
		// Get post object if post ID.
		if ( is_numeric( $post ) ) {
			$post = WP_Post::get_instance( $post );
		}

		// Only if split payments is enabled.
		if ( ! $this->is_split_payments_enabled() ) {
			return false;
		}

		// Only if tickets are active on this post type.
		if ( ! in_array( $post->post_type, Tickets_Main::instance()->post_types(), true ) ) {
			return false;
		}

		// Only if the post actually has tickets.
		if ( ! tribe_events_has_tickets( $post->ID ) ) {
			return false;
		}

		// Only if the post was created via Community.
		if ( 'community-events' !== get_post_meta( $post->ID, '_EventOrigin', true ) ) {
			return false;
		}

		/**
		 * Filters hiding the payouts tab/row actions on the front end.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array  $show_payouts_on_front_end defaults to value of `is_admin()` (false on front end),
		 *               whether to show the tab/links on the front end.
		 */
		$show_payouts_on_front_end = apply_filters( 'event_community_tickets_show_payouts_on_front_end', is_admin() );

		// Only (conditionally) in the admin.
		if ( ! $show_payouts_on_front_end ) {
			return false;
		}

		return true;
	}

	/**
	 * Save payout info to order metadata.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function save_meta( $order_id ) {
		$payment_method = $this->split_payment_method();

		update_post_meta( $order_id, '_tribe_split_payment_method', $payment_method );
	}

	/**
	 * Generate all payouts for an order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int|WC_Order $order WooCommerce Order object or ID.
	 */
	public function generate_payouts_for_order( $order ) {
		if ( ! $this->is_split_payments_enabled() ) {
			return;
		}

		if ( empty( $order ) ) {
			return;
		}

		$order_id = is_numeric( $order ) ? $order : $order->get_id();

		if ( empty( $order_id ) ) {
			return;
		}

		$this->save_meta( $order_id );

		try {
			// Attempt to NOT create duplicates
			$repository = tribe_payouts();
			$repository->by( 'order', $order_id );
			$repository->set_found_rows( true );

			$found = $repository->found();
		} catch ( \Tribe__Repository__Usage_Error $exception ) {
			// There was an error, skip processing.
			return;
		}

		if ( 0 < $found ) {
			return;
		}

		/** @var Order $payout_order */
		$payout_order = tribe( 'community-tickets.payouts.order' );

		// Setup order from ID.
		$payout_order->hydrate_from_order( $order_id );

		// Get receivers.
		$receivers = $payout_order->get_receivers();

		// Loop through receivers to save initial payouts.
		foreach ( $receivers as $receiver ) {
			// Skip receiver if they do not need a payout.
			if ( $receiver->get_total() <= 0 ) {
				continue;
			}

			/** @var Payout $payout */
			$payout = tribe( 'community-tickets.payouts.payout' );

			// Setup payout from receiver.
			$payout->hydrate_from_receiver( $receiver );

			$payout->save();
		}
	}

	/**
	 * Updates payout status on order status change.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int       $order_id    Order ID.
	 * @param string    $status_from Status being changed from.
	 * @param string    $status_to   Status being changed to.
	 * @param \WC_Order $order       Order object.
	 */
	public function order_status_changed( $order_id, $status_from, $status_to, $order ) {

		$repository = tribe_payouts();

		try {
			$repository->by( 'order', $order_id );
			$repository->by( 'status', [ Payouts::STATUS_PENDING_ORDER, Payouts::STATUS_PENDING ] );

			$payouts = $repository->all();
		} catch ( \Tribe__Repository__Usage_Error $exception ) {
			// There was an error, skip processing.
			return;
		}

		if ( empty( $payouts ) ) {
			return;
		}

		$new_status        = null;
		$new_date_paid     = null;
		$new_error_message = null;

		$gateway = get_post_meta( $order_id, '_tribe_split_payment_method', true );

		if ( empty( $gateway ) ) {
			return;
		}

		// No custom handling for Adaptive Payments unless we are marking an order pending payment as paid.
		if ( Payouts\PayPal\Adaptive_Payments::GATEWAY === $gateway && 'pending' === $status_from && 'processing' !== $status_to ) {
			return;
		}

		switch ( $status_to ) {
			case 'pending':
			case 'processing':
				// Waiting for order to be paid/complete before queueing to be paid.
				$new_status = Payouts::STATUS_PENDING_ORDER;

				// Handle Adaptive Payments, they are marked as paid as soon as they receive payment.
				if ( Payouts\PayPal\Adaptive_Payments::GATEWAY === $gateway ) {
					$new_status    = Payouts::STATUS_PAID;
					$new_date_paid = current_time( 'mysql' );
				}

				break;
			case 'completed':
				// Order is complete so let's queue the payouts to be paid.
				$new_status = Payouts::STATUS_PENDING;

				break;
			case 'cancelled':
			case 'refunded':
			case 'failed':
				// Order is no longer valid, cancel the payouts as failed.
				$new_status        = Payouts::STATUS_FAILED;
				$new_error_message = sprintf(
					__( 'Order status changed from %1$s to %2$s', 'tribe-events-community' ),
					$status_from,
					$status_to
				);

				break;
		}

		$changes = [
			'new_status'        => $new_status,
			'new_date_paid'     => $new_date_paid,
			'new_error_message' => $new_error_message,
		];

		/**
		 * Filter the changes to make for payouts on an order.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array     $changes     List of changes to make.
		 * @param int       $order_id    Order ID.
		 * @param string    $status_from Status being changed from.
		 * @param string    $status_to   Status being changed to.
		 * @param \WC_Order $order       Order object.
		 */
		$changes = apply_filters( 'tribe_community_tickets_payouts_record_changes_on_status_change', $changes, $order_id, $status_from, $status_to, $order );

		if ( empty( $changes['new_status'] ) ) {
			return;
		}

		try {
			// Set the status for all payouts.
			$repository->set( 'post_status', $changes['new_status'] );

			if ( $changes['new_date_paid'] ) {
				$repository->set( '_tribe_date_paid', esc_html( $changes['new_date_paid'] ) );
			}

			if ( $changes['new_error_message'] ) {
				$repository->set( '_tribe_error_message', esc_html( $changes['new_error_message'] ) );
			}

			// Save all payouts.
			$repository->save();
		} catch ( \Tribe__Repository__Usage_Error $exception ) {
			// There was an error, skip processing.
			return;
		}
	}
}
