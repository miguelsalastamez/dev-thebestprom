<?php
/**
 * Queue to handle payout processing.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts;

use Tribe\Community\Tickets\Payouts;
use Tribe__Events__Community__Tickets__Main as Main;

/**
 * Class Queue
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */
class Queue {

	/**
	 * Which action will be triggered as an ongoing scheduled cron event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public $scheduled_key = 'tribe_community_tickets_payouts_process';

	/**
	 * Which action will be triggered as a single cron event.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public $scheduled_single_key = 'tribe_community_tickets_payouts_single_process';

	/**
	 * Number of items to be processed in a single batch.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var int
	 */
	protected $batch_size = 1000;

	/**
	 * Payout repository.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var \Tribe\Community\Tickets\Repositories\Payout
	 */
	public $repository;

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
		// @todo Add custom CT deactivation code based on TEC.
		add_action( 'tribe_events_blog_deactivate', [ $this, 'clear_scheduled_task' ] );

		add_action( $this->scheduled_key, [ $this, 'process_queue' ], 20, 0 );
		add_action( $this->scheduled_single_key, [ $this, 'process_queue' ], 20, 0 );

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

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! $payouts->is_split_payments_enabled() ) {
			return;
		}

		/**
		 * Filter the interval at which to process queue.
		 *
		 * By default the interval "daily" is specified, however other intervals such as "hourly"
		 * and "twicedaily" can normally be substituted.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @see   wp_schedule_event()
		 * @see   'cron_schedules'
		 */
		$interval = apply_filters( 'tribe_community_tickets_payouts_record_processor_interval', 'daily' );

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
	 * Processes the next waiting batch of payouts to be processed, if there are any.
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
			$batch_size = apply_filters( 'tribe_community_tickets_payouts_batch_size', $this->batch_size );
		}

		$this->batch_size = (int) $batch_size;

		// If we have payouts that need to be processed.
		if ( ! $this->has_pending_payouts() ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::line( 'No pending payouts to process.' );
			}

			return;
		}

		$payout_list = $this->get_pending_payouts();

		if ( empty( $payout_list ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::line( 'No pending payouts to process.' );
			}

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::line( 'Processing payouts queue.' );
		}

		/**
		 * Process queue.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param Queue    $queue       Queue object.
		 * @param Payout[] $payout_list List of payout objects.
		 */
		do_action( 'tribe_community_tickets_payouts_process_queue', $this, $payout_list );

		// Continue processing the queue if there are still more left.
		if ( $this->batch_size <= count( $payout_list ) ) {
			wp_schedule_single_event( time() + 1, $this->scheduled_single_key );
		}
	}

	/**
	 * Setup repository with the filters needed.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return \Tribe\Community\Tickets\Repositories\Payout
	 */
	public function setup_repository() {
		$repository = tribe_payouts();

		// Fetch payouts that are pending.
		$repository->by( 'status', Payouts::STATUS_PENDING );

		// Limit max payout batch size.
		$repository->per_page( $this->batch_size );

		// Enable found calculations.
		$repository->set_found_rows( true );

		$this->repository = $repository;

		return $repository;
	}

	/**
	 * Check if there are any pending payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return bool Whether there are any pending payouts.
	 */
	public function has_pending_payouts() {
		$repository = $this->setup_repository();

		return 0 < $repository->found();
	}

	/**
	 * Get list of payout objects for payouts that are pending.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return Payout[] Payout objects for payouts that are pending.
	 */
	public function get_pending_payouts() {
		$repository = $this->setup_repository();

		$payouts = $repository->all();

		$payout_list = [];

		foreach ( $payouts as $post ) {
			/** @var Payout $payout */
			$payout = tribe( 'community-tickets.payouts.payout' );

			$payout->hydrate_from_post( $post );

			$payout_list[] = $payout;
		}

		return $payout_list;
	}
}
