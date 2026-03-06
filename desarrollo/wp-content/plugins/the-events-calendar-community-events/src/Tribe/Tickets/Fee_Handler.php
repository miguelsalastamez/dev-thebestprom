<?php

/**
 * Class Tribe__Events__Community__Tickets__Fee_Handler
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 */
class Tribe__Events__Community__Tickets__Fee_Handler {

	/**
	 * An Array of the Current Fee on the Site
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var array
	 */
	protected $current_fee = [];

	/**
	 * Fees object.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Tribe__Events__Community__Tickets__Fees
	 */
	protected $fees;

	/**
	 * List of product prices and fees.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var array
	 */
	protected $products = [];

	/**
	 * Add a hook for fee messages to the CE Ticket Form
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function __construct() {
		$this->setup_site_fee_data();

		$this->fees = tribe( 'community-tickets.fees' );

		$this->hooks();
	}

	/**
	 * Initialize data for object.
	 */
	public function init() {
		$this->current_fee = [
			'type'              => 'none',
			'is-per-event-fee'  => false,
			'is-per-ticket-fee' => false,
			'is-on-free'        => false,
			'operation'         => '',
			'flat-fee'          => 0,
			'is-flat-fee'       => false,
			'percentage-fee'    => 0,
			'is-percentage-fee' => false,
			'message'           => '',
		];

		$this->products = [];
	}

	/**
	 * Run hooks necessary for fee handling.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function hooks() {
		add_action( 'tribe_tickets_price_input_description', [ $this, 'add_fee_message_to_form' ], 10, 2 );
		add_action( 'tribe_community_tickets_orders_report_site_fees_note', [ $this, 'add_fee_message_to_report' ] );

		add_filter( 'woocommerce_product_get_price', [ $this, 'possibly_add_per_ticket_fee' ], 99, 2 );
		add_filter( 'woocommerce_product_get_regular_price', [ $this, 'possibly_add_per_ticket_fee' ], 99, 2 );

		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'set_fee_info_in_order' ] );
	}

	/**
	 * Set the Site Fee Data.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function setup_site_fee_data() {
		$this->init();

		/** @var Tribe__Events__Community__Tickets__Main $main */
		$main = tribe( 'community-tickets.main' );

		$options       = get_option( $main::OPTIONNAME, $main->option_defaults );
		$site_fee_type = isset( $options['site_fee_type'] ) ? $options['site_fee_type'] : 'none';

		/** @var Tribe__Events__Community__Tickets__Gateway__PayPal $gateway */
		$gateway = tribe( 'community-tickets.main' )->gateway( 'PayPal' );

		/** @var Tribe__Events__Community__Tickets__Fees $fees */
		$fees = tribe( 'community-tickets.fees' );

		$is_percentage_fee = $fees->is_percentage_fee( $site_fee_type );
		$is_flat_fee       = $fees->is_flat_fee( $site_fee_type );

		$operation = isset( $options['payment_fee_setting'] ) ? $options['payment_fee_setting'] : 'pass';

		$is_per_event_fee = $fees->is_per_event_fee( $site_fee_type );

		// We do not support per event fees for 'add'.
		if ( $is_per_event_fee && 'add' === $operation ) {
			// Defaults to what was used by init().
			return;
		}

		$this->current_fee = [
			'type'              => $site_fee_type,
			'is-per-event-fee'  => $is_per_event_fee,
			'is-per-ticket-fee' => $fees->is_per_ticket_fee( $site_fee_type ),
			'is-on-free'        => $gateway->site_fee_on_free(),
			'operation'         => $operation,
			'flat-fee'          => $is_flat_fee ? $gateway->fee_flat() : 0,
			'is-flat-fee'       => $is_flat_fee,
			'percentage-fee'    => $is_percentage_fee ? $gateway->fee_percentage() : 0,
			'is-percentage-fee' => $is_percentage_fee,
		];
	}

	/**
	 * Get the Site Fee Data.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array The Site Fee Data.
	 */
	public function get_site_fee_data() {
		return $this->current_fee;
	}

	/**
	 * Add the Fee Message to the Community Tickets Ticket Form.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $ticket_id The Ticket ID.
	 * @param int $event_id  The Event ID.
	 */
	public function add_fee_message_to_form( $ticket_id, $event_id ) {
		if ( 0 !== $event_id && ! $this->fees->has_event_fees( $event_id ) ) {
			return;
		}

		?>
		<span class="tribe_soft_note ticket_form_right">
			<?php echo esc_html( $this->fees->get_fee_message() ); ?>
		</span>
		<?php
	}

	/**
	 * Add the Fee Message to the Community Tickets Ticket Form.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The Event ID.
	 */
	public function add_fee_message_to_report( $event_id ) {
		if ( ! $this->fees->has_event_fees( $event_id ) ) {
			return;
		}

		echo esc_html( $this->fees->get_fee_message() );
	}


	/**
	 * Add Ticket Fee to Price if Per Ticket Fee and Set to Add to Price.
	 * It only adds on the add to cart form and cart page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param float      $price   Product price.
	 * @param WC_Product $product Product object.
	 *
	 * @return float New price with fee added (if necessary).
	 */
	public function possibly_add_per_ticket_fee( $price, $product ) {
		if ( is_admin() || tribe_is_community_edit_event_page() ) {
			return $price;
		}

		$product_id = $product->get_id();

		// Return original price if the product is not a ticket.
		if ( ! tribe_events_product_is_ticket( $product_id ) ) {
			return $price;
		}

		if ( isset( $this->products[ $product_id ] ) ) {
			return $this->products[ $product_id ]['total'];
		}

		$this->products[ $product_id ] = [
			'product_id' => $product_id,
			'price'      => $price,
			'fee'        => 0,
			'total'      => $price,
		];

		if ( 'add' !== $this->current_fee['operation'] ) {
			return $price;
		}

		if ( ! $this->current_fee['is-per-ticket-fee'] ) {
			return $price;
		}

		if ( ! $this->fees->should_add_product_fee( $product_id, 'add' ) ) {
			return $price;
		}

		$is_free = $price <= 0;

		// If the ticket is free and "Add flat fees to free tickets" is disabled, skip free tickets.
		if ( $is_free && ! $this->current_fee['is-on-free'] ) {
			return $price;
		}

		$fee = 0;

		if ( ! $is_free && $this->current_fee['is-percentage-fee'] && 0 < $this->current_fee['percentage-fee'] ) {
			$fee += round( $price * ( $this->current_fee['percentage-fee'] / 100 ), 2 );
		}

		if ( ( ! $is_free || $this->current_fee['is-on-free'] ) && $this->current_fee['is-flat-fee'] && 0 < $this->current_fee['flat-fee'] ) {
			$fee += $this->current_fee['flat-fee'];
		}

		$this->products[ $product_id ]['fee']   = $fee;
		$this->products[ $product_id ]['total'] += $fee;

		return $this->products[ $product_id ]['total'];
	}

	/**
	 * Get ticket price for product.
	 *
	 * @param WC_Product|WC_Order_Item_Product|array|int $product Product object, WC_Order item, WC_Cart item, or Product ID.
	 *
	 * @return float Ticket price for product.
	 */
	public function get_ticket_price_from_product( $product ) {
		$product_id = 0;

		if ( $product instanceof WC_Order_Item_Product ) {
			// Get product from order item.
			$product = $product->get_product();
		} elseif ( is_array( $product ) && ! empty( $product['data'] ) ) {
			// Get product from cart item.
			$product = $product['data'];
		} elseif ( is_numeric( $product ) ) {
			$product_id = $product;
		}

		$price = 0;

		// Get price from product.
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
			$price      = $product->get_price();
		}

		// Get real price if payment fee option is set to 'add'.
		if ( isset( $this->products[ $product_id ] ) ) {
			$price = $this->products[ $product_id ]['price'];
		}

		return $price;
	}

	/**
	 * Set the Current Ticket Fee in Meta
	 * Add Fees Message to Order Note
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $order_id an id for an order
	 */
	public function set_fee_info_in_order( $order_id ) {
		/** @var Tribe__Events__Community__Tickets__Fees $fees */
		$fees = tribe( 'community-tickets.fees' );

		$fees->get_fee_data_for_an_order( $order_id, true, true );
	}
}
