<?php

/**
 * The Payouts report table.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.

 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts\Tabbed_View;

use Tribe\Community\Tickets\Payouts\Payout;
use Tribe__Date_Utils as Date_Utils;
use Tribe__Utils__Array as Utils_Array;

class Table extends \WP_List_Table {

	public $event_id;

	/**
	 * In-memory cache of payouts per event, where each key represents the event ID
	 * and the value is an array of payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var array
	 */
	protected static $payouts = [];

	/**
	 * @var string The user option that will be used to store the number of payouts per page to show.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public $per_page_option;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$args = [
			'singular' => 'payout',
			'plural'   => 'payouts',
			'ajax'     => true,
		];

		$this->per_page_option = Screen_Options::$per_page_user_option;

		$screen = get_current_screen();

		if ( $screen instanceof \WP_Screen ) {
			$screen->add_option(
				'per_page',
				[
					'label'  => __( 'Number of payouts per page:', 'tribe-events-community' ),
					'option' => $this->per_page_option,
				]
			);
		}

		parent::__construct( $args );
	}

	/**
	 * Don't display the search box.
	 * We don't want Core's search box, because we implemented our own jQuery based filter,
	 * so this function overrides the parent's one and returns empty.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $unused_text     The search button text
	 * @param string $unused_input_id The search input id
	 */
	public function search_box( $unused_text, $unused_input_id ) {
		return;
	}

	/**
	 * Checks the current user's permissions.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return boolean
	 */
	public function ajax_user_can() {
		$post_type = get_post_type_object( $this->screen->post_type );

		return ! empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Get a list of columns for the payouts report].
	 * The format is: ['internal-name' => 'Title']
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'tickets'    => __( 'Tickets', 'tribe-events-community' ),
			'receiver'   => __( 'Receiver', 'tribe-events-community' ),
			'order_date' => __( 'Order Date', 'tribe-events-community' ),
			'date_paid'  => __( 'Date Paid', 'tribe-events-community' ),
			'amount'     => __( 'Payout', 'tribe-events-community' ),
			'status'     => __( 'Status', 'tribe-events-community' ),
		];

		return $columns;
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 * @param string $column the column we're rendering
	 *
	 * @return string column content (HTML)
	 */
	public function column_default( $item, $column ) {
		$value = empty( $item->$column ) ? '' : $item->$column;

		return apply_filters( 'events_community_tickets_payouts_table_column', $value, $item, $column );
	}

	/**
	 * Handler for the tickets column.
	 * Contains: number of tickets, ticket name, order # + link
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_tickets( $item ) {
		$tickets  = $item->get_tickets();
		$event_id = $this->event_id;
		$woo      = tribe( 'tickets-plus.commerce.woo' );

		if ( $event_id ) {
			$events = $item->get_events();

			if ( ! empty( $events[ $event_id ] ) ) {
				$tickets = $events[ $event_id ]['tickets'];
			}
		}

		ob_start();

		echo '<ul>';

		foreach ( $tickets as $ticket ) {
			$ticket_obj = $woo->get_ticket( $ticket['event_id'], $ticket['ticket_id'] );

			echo sprintf(
				'<li>%1$dx - %2$s</li>',
				esc_html( $ticket['quantity'] ),
				esc_html( $ticket_obj->name )
			);
		}

		echo '</ul>';

		$order_id = $item->get_order_id();
		$order    = wc_get_order( $order_id );

		$order_text = sprintf( __( 'Order #%s', 'tribe-events-community' ), $order_id );

		if ( $order instanceof \WC_Order ) {
			$order_link = $order->get_view_order_url();

			if ( current_user_can( 'edit_post', $order_id ) ) {
				$order_link = $order->get_edit_order_url();
			}

			echo sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( $order_link ),
				esc_html( $order_text )
			);
		} else {
			echo sprintf(
				'<p>%1$s</p>',
				esc_html( $order_text )
			);
		}

		$render = ob_get_clean();

		return $render;
	}

	/**
	 * Handler for the receiver column.
	 * Contains: receiver user display name, receiver PayPal e-mail
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_receiver( $item ) {
		$receiver     = $item->get_receiver_user();
		$receiver_key = $item->get_receiver_key();

		if ( $receiver instanceof \WP_User ) {
			$receiver_name = $receiver->display_name;

			if ( current_user_can( 'edit_users' ) ) {
				return sprintf( '<p><a href="%3$s">%1$s</a><br>%2$s</p>', esc_html( $receiver_name ), esc_html( $receiver_key ), esc_url( get_edit_user_link( $receiver->ID ) ) );
			} else {
				return sprintf( '<p>%1$s<br>%2$s</p>', esc_html( $receiver_name ), esc_html( $receiver_key ) );
			}
		} elseif ( ! empty( $receiver_key ) ) {
			return sprintf( '<p>%1$s</p>', esc_html( $receiver_key ) );
		}

		return __( 'Unknown', 'tribe-events-community' );
	}

	/**
	 * Handler for the order column.
	 * Contains: order date + time
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_order_date( $item ) {
		$date = strtotime( $item->get_order_date() );

		return Date_Utils::reformat( $date, Date_Utils::DBDATEFORMAT . ' ' . Date_Utils::TIMEFORMAT );
	}

	/**
	 * Handler for the paid column.
	 * Contains: date + time paid ("-" if not yet paid)
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_date_paid( $item ) {
		$date_paid = $item->get_date_paid();

		if ( empty( $date_paid ) ) {
			return '-';
		}

		$time = strtotime( $date_paid );

		if ( $time <= 0 ) {
			return '-';
		}

		return Date_Utils::reformat( $time, Date_Utils::DBDATEFORMAT . ' ' . Date_Utils::TIMEFORMAT );
	}

	/**
	 * Handler for the fee column.
	 * Contains: Fee Amount
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_fees( $item ) {
		return esc_html( tribe_format_currency( number_format_i18n( $item->get_fees(), 2 ) ) );
	}

	/**
	 * Handler for the amount column.
	 * Contains: Payout Amount
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_amount( $item ) {
		$amount = $item->get_amount();

		if ( $this->event_id ) {
			$amounts = $item->get_amount_per_event();
			$amount  = 0;

			if ( ! empty( $amounts[ $this->event_id ] ) ) {
				$amount = $amounts[ $this->event_id ];
			}
		}

		return esc_html( tribe_format_currency( number_format_i18n( $amount, 2 ) ) );
	}

	/**
	 * Handler for the status column.
	 * Contains: status, PayPal transaction # + link (if possible to even do this)
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 *
	 * @return string column content (HTML)
	 */
	public function column_status( $item ) {
		$status_label   = $item->get_status_label();
		$error_message  = $item->get_error_message();
		$transaction_id = $item->get_transaction_id();

		if ( null === $status_label ) {
			$status_label = __( 'Unknown', 'tribe-events-community' );
		}

		$status_details = esc_html( $status_label );

		if ( ! empty( $error_message ) ) {
			$status_details .= sprintf(
				'
					<br><br>
					<em>%1$s: %2$s</em>
				',
				esc_html__( 'Note', 'tribe-events-community' ),
				esc_html( $error_message )
			);
		}

		if ( ! empty( $transaction_id ) ) {
			$status_details .= sprintf(
				'
					<br><br>
					<em>%1$s: %2$s</em>
				',
				esc_html__( 'Transaction ID', 'tribe-events-community' ),
				esc_html( $transaction_id )
			);
		}

		return $status_details;
	}

	/**
	 * Echoes content for a single row of the table.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Payout $item The payout item for the row.
	 */
	public function single_row( $item ) {
		// \WP_List single_row_columns echoes, so we buffer!
		ob_start();
		$this->single_row_columns( $item );
		$row = ob_get_clean();

		echo sprintf(
			'<tr class="%s" id="payout-%s">%s</tr>',
			esc_attr( $item->get_status() ),
			esc_attr( $item->get_id() ),
			$row
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function prepare_items() {
		$this->event_id = absint( Utils_Array::get( $_GET, 'event_id', Utils_Array::get( $_GET, 'post_id', 0 ) ) );

		$per_page = $this->get_items_per_page( $this->per_page_option );

		/**
		 * Allow plugins to modify the default number of payouts shown per page.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param int The number of payouts shown per page.
		 */
		$per_page = apply_filters( 'events_community_tickets_payouts_pagination', $per_page );

		$repository = tribe_payouts();
		$repository->order_by( 'date' );
		$repository->order( 'DESC' );
		$repository->by( 'event', $this->event_id );
		$repository->page( $this->get_pagenum() );
		$repository->per_page( $per_page );
		$repository->set_found_rows( true );

		$payout_posts = $repository->all();

		$this->items = [];

		foreach ( $payout_posts as $post ) {
			/** @var Payout $payout */
			$payout = tribe( 'community-tickets.payouts.payout' );

			$this->items[] = $payout->hydrate_from_post( $post );
		}

		$this->set_pagination_args(
			[
				'total_items' => $repository->found(),
				'per_page'    => $per_page,
			]
		);
	}
}
