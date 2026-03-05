<?php
/**
 * Queue to handle Fee migration for sales.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Migration;

use Tribe__Events__Community__Tickets__Fees as Fees;

class Queue {

	/**
	 * Which action will be triggered as an ongoing scheduled cron event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public $scheduled_key = 'tribe_community_tickets_add_fee_data';

	/**
	 * Batch offset key used to track migration progress.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public $batch_offset_key = 'tribe_community_tickets_fee_data_migration_offset';

	/**
	 * Number of items to be processed in a single batch.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $batch_size = 500;

	/**
	 * Queue Hooks.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function hooks() {
		// @todo Add custom CT deactivation code based on TEC.
		add_action( 'tribe_events_blog_deactivate', [ $this, 'clear_scheduled_task' ] );

		add_action( $this->scheduled_key, [ $this, 'process_queue' ], 20, 0 );
	}

	/**
	 * Handle things that need to happen during the init action.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function action_init() {
		$this->manage_scheduled_task();
	}

	/**
	 * Configures a scheduled task to handle "background processing" for the queue.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function manage_scheduled_task() {
		$this->register_scheduled_task();
	}

	/**
	 * Register scheduled task to be used for processing batches on plugin activation.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	protected function register_scheduled_task() {
		if ( wp_next_scheduled( $this->scheduled_key ) ) {
			return;
		}

		/**
		 * Filter the interval at which to process queue.
		 *
		 * By default the interval "hourly" is specified, however other intervals such as "daily"
		 * and "twicedaily" can normally be substituted.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @see   wp_schedule_event()
		 * @see   'cron_schedules'
		 */
		$interval = apply_filters( 'tribe_community_tickets_fee_migration_interval', 'hourly' );

		wp_schedule_event( time(), $interval, $this->scheduled_key );
	}

	/**
	 * Clear the scheduled task on plugin deactivation.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function clear_scheduled_task() {
		wp_clear_scheduled_hook( $this->scheduled_key );
	}

	/**
	 * Processes the next waiting batch of orders to migrate, if there are any.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param null|int $batch_size Batch processing size override.
	 */
	public function process_queue( $batch_size = null ) {
		if ( null === $batch_size ) {
			/**
			 * Controls the size of each batch processed by default (ie, during cron updates of record inserts/updates).
			 *
			 * @since 5.0.0 Migrated to Community from Community Tickets.
			 *
			 * @param int $default_batch_size
			 */
			$batch_size = apply_filters( 'tribe_community_tickets_fee_migration_batch_size', $this->batch_size );
		}

		$this->batch_size = (int) $batch_size;

		$processed = $this->add_fees_to_community_ticket_orders();

		// if no items are processed or not processed clear the task.
		if ( empty( $processed['not_processed'] ) && empty( $processed['processed'] ) ) {
			tribe_update_option( $this->batch_offset_key, 'complete' );

			$this->clear_scheduled_task();

			return;
		}

		$this->update_offset( $processed['not_processed'] );
	}

	/**
	 * Query WooCommerce Orders and If a Community Ticket Order, then Add Fee Data
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return array An array of processed and not processed
	 */
	protected function add_fees_to_community_ticket_orders() {
		$process = [
			'not_processed' => 0,
			'processed'     => 0,
		];

		$current_offset = $this->get_current_offset();

		if ( 'complete' === $current_offset ) {
			return $process;
		}

		$args = [
			'post_type'      => [ 'shop_order' ],
			'post_status'    => 'any',
			'posts_per_page' => $this->batch_size,
			'offset'         => $current_offset,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_community_tickets_order_fees',
					'value'   => '',
					'compare' => 'NOT EXISTS',
				],
			],
		];

		$orders = new \WP_Query( $args );

		/** @var Fees $fees */
		$fees = tribe( 'community-tickets.fees' );

		if ( $orders->have_posts() ) {
			while ( $orders->have_posts() ) {
				$orders->the_post();

				$order_id = get_the_id();
				$order    = wc_get_order( $order_id );

				if ( ! $fees->is_community_ticket_order( $order ) ) {
					$process['not_processed'] ++;

					continue;
				}

				$this->add_fee_data_to_order( $order_id );

				$process['processed'] ++;
			}
		}

		return $process;
	}

	/**
	 * Add The Fee Data to a Community Ticket Order
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $order_id an id for an order
	 */
	protected function add_fee_data_to_order( $order_id ) {
		/** @var Fees $fees */
		$fees = tribe( 'community-tickets.fees' );

		$fees->get_fee_data_for_an_order( $order_id, true, true );
	}

	/**
	 * Get the Current offset number.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string|int Current offset number.
	 */
	public function get_current_offset() {
		$current_offset = tribe_get_option( $this->batch_offset_key );

		// Set up default current offset.
		if ( false === $current_offset || '' === $current_offset ) {
			$current_offset = 0;
		}

		return $current_offset;
	}

	/**
	 * Update the Offset Number with the Current Batch.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $not_processed the number of orders not processed.
	 */
	protected function update_offset( $not_processed ) {
		$current_offset = $this->get_current_offset();

		// Only set if numeric.
		if ( ! is_numeric( $current_offset ) ) {
			return;
		}

		$current_offset = (int) $current_offset;
		$current_offset += $not_processed;

		tribe_update_option( $this->batch_offset_key, $current_offset );
	}
}
