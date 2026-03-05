<?php

/**
 * The Payouts repository.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Repositories;

use Tribe\Community\Tickets\Payouts;
use Tribe__Repository;

class Payout extends Tribe__Repository {

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @var string
	 */
	protected $filter_name = 'payouts';

	/**
	 * Payouts_Repository constructor.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function __construct() {
		parent::__construct();

		$this->default_args = [
			'post_type'   => Payouts::PAYOUT_OBJECT,
			'post_status' => 'any',
			'orderby'     => [
				'date',
				'ID',
			],
		];

		$this->create_args = [
			'post_type' => Payouts::PAYOUT_OBJECT,
		];

		$this->add_simple_meta_schema_entry( 'event', '_tribe_event_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'event__not_in', '_tribe_event_id', 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'ticket', '_tribe_ticket_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'ticket__not_in', '_tribe_ticket_id', 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'order', '_tribe_order_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'order__not_in', '_tribe_order_id', 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'user', '_tribe_user_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'user__not_in', '_tribe_user_id', 'meta_not_in' );

		$this->add_simple_meta_schema_entry( 'amount', '_tribe_amount' );
		$this->add_simple_meta_schema_entry( 'fees', '_tribe_fees' );
		$this->add_simple_meta_schema_entry( 'order_provider', '_tribe_order_provider' );
		$this->add_simple_meta_schema_entry( 'receiver_key', '_tribe_receiver_key' );
		$this->add_simple_meta_schema_entry( 'gateway', '_tribe_gateway' );
		$this->add_simple_meta_schema_entry( 'transaction', '_tribe_transaction_id' );
		$this->add_simple_meta_schema_entry( 'date_paid', '_tribe_date_paid' );
	}

	/**
	 * Get total ticket quantity for tickets/events in query.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Total ticket quantity for tickets/events in query.
	 */
	public function get_total_ticket_quantity() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$query = $this->get_query();

		$value_ids = [];

		if ( ! empty( $query->query_vars['meta_query']['_tribe_ticket_id_in']['value'] ) ) {
			$meta_key = '_tribe_ticket_qty_%d';

			$value_ids = (array) $query->query_vars['meta_query']['_tribe_ticket_id_in']['value'];
		} elseif ( ! empty( $query->query_vars['meta_query']['_tribe_event_id_in']['value'] ) ) {
			$meta_key = '_tribe_event_qty_%d';

			$value_ids = (array) $query->query_vars['meta_query']['_tribe_event_id_in']['value'];
		} else {
			return 0;
		}

		$meta_keys = [];

		foreach ( $value_ids as $value_id ) {
			$meta_keys[] = sprintf( $meta_key, $value_id );
		}

		return $this->get_total_for_meta_keys( $meta_keys );
	}

	/**
	 * Get total amount of payouts in query.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Total amount of payouts in query.
	 */
	public function get_total_amount() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$query = $this->get_query();

		if ( ! empty( $query->query_vars['meta_query']['_tribe_event_id_in']['value'] ) ) {
			$meta_key = '_tribe_event_amt_%d';

			$value_ids = (array) $query->query_vars['meta_query']['_tribe_event_id_in']['value'];

			$meta_keys = [];

			foreach ( $value_ids as $value_id ) {
				$meta_keys[] = sprintf( $meta_key, $value_id );
			}
		} else {
			$meta_keys = '_tribe_amount';
		}

		return $this->get_total_for_meta_keys( $meta_keys );
	}

	/**
	 * Get total fees for payouts in query.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int Total fees for payouts in query.
	 */
	public function get_total_fees() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$query = $this->get_query();

		if ( ! empty( $query->query_vars['meta_query']['_tribe_event_id_in']['value'] ) ) {
			$meta_key = '_tribe_event_fee_%d';

			$value_ids = (array) $query->query_vars['meta_query']['_tribe_event_id_in']['value'];

			$meta_keys = [];

			foreach ( $value_ids as $value_id ) {
				$meta_keys[] = sprintf( $meta_key, $value_id );
			}
		} else {
			$meta_keys = '_tribe_fees';
		}

		return $this->get_total_for_meta_keys( $meta_keys );
	}

	/**
	 * Get total of a specific meta query for payouts in query.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string|array $meta_keys Meta key(s) to use.
	 *
	 * @return int Total of a specific meta query for payouts in query.
	 */
	public function get_total_for_meta_keys( $meta_keys ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$query = clone $this->get_query();

		$query->set( 'fields', 'ids' );
		$query->set( 'posts_per_page', 1 );

		$meta_keys    = (array) $meta_keys;
		$meta_key_sql = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

		$post_query = $query->request;
		$post_query = preg_replace( '/^\s*SELECT\s*SQL_CALC_FOUND_ROWS\s*/', 'SELECT ', $post_query );
		$post_query = preg_replace( '/\s*LIMIT\s*\d+,\s*\d+$/', '', $post_query );
		$post_query = "
			SELECT SUM( meta_value )
			FROM {$wpdb->postmeta}
			WHERE
				meta_key IN ( {$meta_key_sql} )
				AND post_id IN (
					{$post_query}
				)
		";

		$post_query = $wpdb->prepare( $post_query, $meta_keys );

		return (int) $wpdb->get_var( $post_query );
	}
}
