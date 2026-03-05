<?php

/**
 * Class Tribe__Events__Community__Tickets__Reports__Sales
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 */
class Tribe__Events__Community__Tickets__Reports__Sales {

	/**
	 * The Event ID for the Report.
	 *
	 * @var string
	 */
	public $event_id;

	/**
	 * Add a hook for fee messages to the CE Ticket Form.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function hooks() {
		add_action( 'parse_query', array( $this, 'parse_query' ), 10 );

		add_filter( 'tribe_tickets_plus_woocommerce_orders_columns', array( $this, 'add_columns' ), 10, 2 );

		add_filter( 'tribe_events_tickets_orders_table_column', array( $this, 'display' ), 10, 3 );

		add_filter( 'tribe_tickets_plus_woocommerce_filter_column_total', array( $this, 'maybe_change_total' ), 10, 3 );

		add_action( 'tec_tickets_commerce_order_report_after_sales_breakdown', [ $this, 'fee_total' ], 10, 3 );
		// Keep this for backwards compatibility.
		add_action( 'tribe_tickets_plus_woocommerce_sales_report_after_order_breakdown', array( $this, 'fee_total' ), 10, 3 );
	}

	/**
	 * Parse Query to get Event ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 */
	public function parse_query() {
		// Set event id for the sales report.
		$this->event_id = Tribe__Utils__Array::get( $_GET, 'event_id', Tribe__Utils__Array::get( $_GET, 'post_id', 0 ) );
	}
	/**
	 * Add Community Tickets Sub Total and Fees Columns to Sales report.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $columns List of columns to show.
	 * @param int   $post_id Post / Event ID.
	 *
	 * @return array $columns An array of columns.
	 */
	public function add_columns( $columns, $post_id ) {
		if ( is_admin() ) {
			return $columns;
		}

		if ( tribe( 'community-tickets.fees' )->has_event_fees( $post_id ) ) {
			$columns['fees_subtotal'] = __( 'Subtotal', 'event-tickets-plus' );
			$columns['site_fees'] = __( 'Site Fee', 'event-tickets-plus' );
		}

		return $columns;
	}

	/**
	 * Display the Column Data.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $value  The value for the column.
	 * @param array  $item   An array of WooCommerce Order data.
	 * @param string $column The column name.
	 *
	 * @return string $value The value to be shown for the column.
	 */
	public function display( $value, $item, $column ) {
		switch ( $column ) {
			case 'fees_subtotal':
				$value = $this->column_subtotal( $item );

				break;
			case 'site_fees':
				$value = $this->column_site_fees( $item );

				break;
		}

		return $value;
	}

	/**
	 * Handler for the subtotal column.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $item An array of WooCommerce Order data.
	 *
	 * @return string The sub total of an order.
	 */
	public function column_subtotal( $item ) {
		$subtotal = $this->get_fee_data( $item, 'subtotal', 'per_event', $this->event_id );

		return esc_html( tribe_format_currency( number_format_i18n( $subtotal, 2 ) ) );
	}

	/**
	 * Handler for the site fees column.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $item An array of WooCommerce Order data.
	 *
	 * @return string The total fees for an order.
	 */
	public function column_site_fees( $item ) {
		$fee_total = $this->get_fee_data( $item, 'fees', 'per_event', $this->event_id );

		return esc_html( tribe_format_currency( number_format_i18n( $fee_total, 2 ) ) );
	}

	/**
	 * Maybe Change the Total with the Fee if using Pass Through Fee.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $total    The total for the order for the event.
	 * @param array  $item     An array of WooCommerce Order data.
	 * @param int    $event_id The event ID.
	 *
	 * @return string The total for the order for the event.
	 */
	public function maybe_change_total( $total, $item, $event_id ) {
		$operation = $this->get_fee_data( $item, 'operation' );
		$type      = $this->get_fee_data( $item, 'type' );

		if ( 'pass' === $operation && 'none' !== $type ) {
			return $this->get_fee_data( $item, 'total', 'per_event', $event_id );
		}

		return $total;
	}

	/**
	 * Get the Fee Data for an Order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array  $item     An array of WooCommerce Order data.
	 * @param string $field    The name of the key to get from the order fees array.
	 * @param string $parent   The optional parent name of the key.
	 * @param int    $event_id The optional event ID to use with the parent field.
	 *
	 * @return string The total for the field.
	 */
	public function get_fee_data( $item, $field, $parent = '', $event_id = 0 ) {
		$default = 0;

		if ( empty( $item['id'] ) ) {
			return $default;
		}

		$order_fees = get_post_meta( $item['id'], tribe( 'community-tickets.fees' )->ticket_fee_order_meta_key, true );

		if ( empty( $order_fees ) ) {
			$order_fees = tribe( 'community-tickets.fees' )->get_fee_data_for_an_order( $item['id'], true, false );
		}

		if ( ! empty( $parent ) && ! empty( $event_id ) ) {
			$fields_to_check = [
				$field,
			];

			$subtotal_fields = [
				'subtotal',
				'sub_total',
			];

			$fee_fields = [
				'fees',
				'fee_total',
			];

			if ( in_array( $field, $subtotal_fields, true ) ) {
				$fields_to_check = $subtotal_fields;
			} elseif ( in_array( $field, $fee_fields, true ) ) {
				$fields_to_check = $fee_fields;
			}

			foreach ( $fields_to_check as $field_to_check ) {
				if ( isset( $order_fees[ $parent ][ $event_id ][ $field_to_check ] ) ) {
					return $order_fees[ $parent ][ $event_id ][ $field_to_check ];
				}
			}
		}

		if ( ! empty( $order_fees[ $field ] ) ) {
			return $order_fees[ $field ];
		}

		return $default;
	}

	/**
	 * Display the Fee Total for the Sales Report.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The event ID.
	 */
	public function fee_total( $event_id ) {
		// Bail out if not using WooCommerce.
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $event_id );
		if ( 'woo' !== $provider->orm_provider ) {
			return;
		}

		$orders            = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::get_orders( $event_id );
		$event_sales       = 0;
		$event_fees        = 0;
		$complete_statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'count_completed', 'woo' );

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $order_id => $order ) {
			if ( ! in_array( $order['status'], $complete_statuses, true ) ) {
				continue;
			}

			$event_sales += $this->get_fee_data( $order, 'subtotal', 'per_event', $this->event_id );
			$event_fees  += $this->get_fee_data( $order, 'fees', 'per_event', $this->event_id );
		}

		if ( $event_fees ) {
			?>
			<div class="tribe-event-meta tribe-event-meta-total-ticket-sales">
				<strong><?php esc_html_e( 'Total Completed Sales:', 'tribe-events-community' ); ?></strong>
				<?php echo esc_html( tribe_format_currency( number_format_i18n( $event_sales, 2 ), $event_id ) ); ?>
			</div>
			<div class="tribe-event-meta tribe-event-meta-total-site-fees">
				<strong><?php esc_html_e( 'Total Site Fees:', 'tribe-events-community' ); ?></strong>
				<?php echo esc_html( tribe_format_currency( number_format_i18n( $event_fees, 2 ), $event_id ) ); ?>
				<ul class="tribe-event-meta-note">
					<?php
					/**
					 * Fires to add notes to Order Sales Report.
					 *
					 * @since 5.0.0 Migrated to Community from Community Tickets.
					 *
					 * @param int $event_id The event ID.
					 */
					do_action( 'tribe_community_tickets_orders_report_site_fees_note', $event_id );
					?>
				</ul>
			</div>
			<?php
		}
	}
}
