<?php
/**
 * Payouts API functionality.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts\PayPal
 */

namespace Tribe\Community\Tickets\Payouts\PayPal;

use Exception;
use PayPal\Api\Currency as PayPal_Currency;
use PayPal\Api\Payout as PayPal_Payout;
use PayPal\Api\PayoutBatch;
use PayPal\Api\PayoutItem as PayPal_Payout_Item;
use PayPal\Api\PayoutSenderBatchHeader as PayPal_Payout_Sender_Batch_Header;
use PayPal\Auth\OAuthTokenCredential as PayPal_OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext as PayPal_ApiContext;
use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Gateway;
use Tribe\Community\Tickets\Payouts\Payout;
use Tribe\Community\Tickets\Payouts\Queue;
use Tribe\Community\Tickets\Payouts\Receiver;
use Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal as WC_PayPal_Adapter;
use Tribe__Events__Community__Tickets__Gateway__PayPal as PayPal_Gateway;
use Tribe__Events__Community__Tickets__Main;
use WC_Order;

class Payouts_API extends Gateway {

	/**
	 * Gateway key.
	 *
	 * @var string
	 */
	const GATEWAY = 'paypal_payouts_api';

	/**
	 * Iterable list of required Paypal options.
	 *
	 * @var array
	 */
	const REQUIRED_OPTIONS = [
		'paypal_api_client_id',
		'paypal_api_client_secret',
	];

	/**
	 * PayPal API Context object.
	 *
	 * @var PayPal_ApiContext
	 */
	public $api_context;

	/**
	 * Process queue for gateway.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param Queue    $queue       Queue object.
	 * @param Payout[] $payout_list List of payout objects.
	 */
	public function process_queue( Queue $queue, array $payout_list ) {
		$payouts = [];

		// Remove payouts that this gateway is not handling.
		foreach ( $payout_list as $k => $payout ) {
			if ( self::GATEWAY === $payout->get_order_gateway() ) {
				$payouts[] = $payout;
			}
		}

		if ( empty( $payouts ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::line( 'No payouts to process for the Payouts API.' );
			}

			return;
		}

		$payout_ids = [];

		foreach ( $payouts as $payout ) {
			$payout_ids[] = $payout->get_id();
		}

		/** @var Tribe__Events__Community__Tickets__Main $main */
		$main       = tribe( 'community-tickets.main' );
		$gateway    = $main->gateway( 'PayPal' );
		$repository = tribe_payouts();

		$sandbox       = (int) $main->get_option( 'paypal_sandbox' );
		$client_id     = $main->get_option( 'paypal_api_client_id' );
		$client_secret = $main->get_option( 'paypal_api_client_secret' );

		$args = [];

		if ( 1 === $sandbox ) {
			$args['mode'] = 'sandbox';
		}

		$this->set_credentials( $client_id, $client_secret, $args );

		try {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::line( sprintf( 'Sending payouts batch to Payouts API: %s', implode( ', ', $payout_ids ) ) );
			}

			$batch_response = $this->send_batch_for_payouts( $gateway, $payouts );

			// No batch sent.
			if ( ! $batch_response ) {
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					\WP_CLI::line( 'No payouts batch sent' );
				}

				return;
			}

			$batch_id = $batch_response->getBatchHeader()->getPayoutBatchId();

			$repository->in( $payout_ids );
			$repository->set( '_tribe_transaction_id', $batch_id );
			$repository->set( '_tribe_date_paid', current_time( 'mysql' ) );
			$repository->set( 'post_status', Payouts::STATUS_PAID );
			$repository->save();

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::line( sprintf( 'Payouts saved as paid: %s', implode( ', ', $payout_ids ) ) );
			}
		} catch ( Exception $exception ) {
			// Log queue processing failure.
			try {
				$message = $exception->getMessage();

				if ( $exception instanceof PayPalConnectionException ) {
					$data = $exception->getData();

					if ( is_string( $data ) ) {
						// Decode data from response.
						$data = json_decode( $data, true );
					} elseif ( is_object( $data ) ) {
						// Future proof in case this gets transformed into an object by PayPal.
						$data = get_object_vars( $data );
					}

					if ( ! empty( $data['message'] ) ) {
						$message = $data['message'];

						if ( ! empty( $data['name'] ) ) {
							$message = sprintf( '%1$s (%2$s)', $message, $data['name'] );
						}
					}
				}

				$error_message = sprintf( 'Payout API request failed: %s', $message );

				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					\WP_CLI::warning( $error_message );
				}

				$repository->in( $payout_ids );
				$repository->set( '_tribe_error_message', $error_message );
				$repository->set( 'post_status', Payouts::STATUS_FAILED );
				$repository->save();
			} catch ( \Tribe__Repository__Usage_Error $error ) {
				// Repository saving failure.
			}
		}
	}

	/**
	 * Set credentials for PayPal API.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param array  $args List of additional arguments for credentials / API config setup.
	 */
	public function set_credentials( $client_id, $client_secret, array $args = [] ) {
		$oauth_token_credential = new PayPal_OAuthTokenCredential( $client_id, $client_secret );

		$this->api_context = new PayPal_ApiContext( $oauth_token_credential );

		$sandbox = ! empty( $args['mode'] ) && 'sandbox' == $args['mode'];

		$config = [
			'log.LogEnabled' => true,
			'log.FileName'   => ABSPATH . '/paypal.log',
			'log.LogLevel'   => true,
		];

		$config['mode'] = $sandbox ? 'sandbox' : 'live';

		if ( ! empty( $args['config'] ) ) {
			$config = array_merge( $config, $args['config'] );
		}

		/**
		 * Filters the list of configuration options for PayPal API Context.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $config List of configuration options for PayPal API Context.
		 * @param array $args   List of arguments for credentials.
		 */
		$config = apply_filters( 'tribe_community_tickets_payouts_paypal_api_context_config', $config, $args );

		if ( ! empty( $config ) ) {
			$this->api_context->setConfig( $config );
		}
	}

	/**
	 * Send batch payouts to receivers in specific payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_PayPal_Adapter $paypal  PayPal Gateway object.
	 * @param Payout[]          $payouts List of payout objects.
	 *
	 * @return PayoutBatch
	 *
	 * @throws Exception
	 */
	public function send_batch_for_payouts( $paypal, $payouts ) {
		$receivers = $this->get_receivers_from_payouts( $payouts );

		// No receivers set for batch with an amount.
		if ( empty( $receivers ) ) {
			throw new Exception( 'No payouts to send' );
		}

		return $this->send_batch( $paypal, $receivers );
	}

	/**
	 * Send batch payouts to receivers in order.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_PayPal_Adapter $paypal PayPal Gateway object.
	 * @param WC_Order          $order  Order object.
	 *
	 * @return PayoutBatch
	 *
	 * @throws Exception
	 */
	public function send_batch_for_order( $paypal, $order ) {
		$receivers = $this->get_receivers_from_order( $order );

		// No receivers set for batch with an amount.
		if ( empty( $receivers ) ) {
			return;
		}

		return $this->send_batch( $paypal, $receivers );
	}

	/**
	 * Send batch payouts to receivers.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_PayPal_Adapter $paypal    PayPal Gateway object.
	 * @param Receiver[]        $receivers List of receivers.
	 *
	 * @return PayoutBatch
	 *
	 * @throws Exception
	 */
	public function send_batch( $paypal, $receivers ) {
		if ( ! $this->api_context ) {
			throw new Exception( 'API Context not set up correctly' );
		}

		// No receivers set for batch with an amount.
		if ( empty( $receivers ) ) {
			return;
		}

		$gateway = $paypal;

		if ( ! $gateway instanceof PayPal_Gateway ) {
			$gateway = $paypal->gateway();
		}

		$admin_email = $gateway->receiver_email;
		$payouts     = new PayPal_Payout();

		// Setup batch header.
		$sender_batch_header = new PayPal_Payout_Sender_Batch_Header();

		/**
		 * Filters the e-mail subject for the PayPal Payout.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param string $sender_subject The e-mail subject for the PayPal Payout.
		 */
		$sender_subject = apply_filters( 'tribe_community_tickets_payouts_paypal_api_sender_subject', __( 'You have a Payout!', 'tribe-events-community' ) );

		$sender_batch_header->setEmailSubject( $sender_subject );

		// Save batch header.
		$payouts->setSenderBatchHeader( $sender_batch_header );

		$total_receivers = 0;

		// Add payout receivers.
		foreach ( $receivers as $receiver ) {
			// Skip receiver processing if it's the same as the primary receiver e-mail.
			if ( $admin_email === $receiver['key'] ) {
				continue;
			}

			$total_receivers ++;

			// Setup payout item.
			$sender_item = new PayPal_Payout_Item();

			$sender_item->setRecipientType( 'Email' );
			$sender_item->setReceiver( $receiver['key'] );

			// Setup payout currency amount.
			$currency_args = [
				'value'    => number_format( $receiver['amount'], 2, '.', '' ),
				'currency' => get_woocommerce_currency(),
			];

			$currency = new PayPal_Currency( $currency_args );

			$sender_item->setAmount( $currency );

			// Save payout item.
			$payouts->addItem( $sender_item );
		}

		if ( 0 === $total_receivers ) {
			return;
		}

		// Create payout.
		return $payouts->create( [], $this->api_context );
	}
}
