<?php
/**
 * Adaptive Payments API functionality.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts\PayPal
 */

namespace Tribe\Community\Tickets\Payouts\PayPal;

use Tribe\Community\Tickets\Payouts\Gateway;
use Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal as WC_PayPal_Adapter;
use WC_Order;

class Adaptive_Payments extends Gateway {

	/**
	 * Gateway key.
	 *
	 * @var string
	 */
	const GATEWAY = 'paypal_adaptive_payments';

	/**
	 * Iterable list of required Paypal options.
	 *
	 * @var array
	 */
	const REQUIRED_OPTIONS = [
		'paypal_api_password',
		'paypal_api_signature',
		'paypal_api_username',
		'paypal_application_id',
		'paypal_receiver_email',
	];

	/**
	 * Generates arguments for PayPal Adaptive Payments.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WC_PayPal_Adapter $paypal PayPal Gateway object.
	 * @param WC_Order          $order  Order object.
	 *
	 * @return array Arguments for PayPal Adaptive Payments.
	 */
	public function generate_payment_args( $paypal, $order ) {
		$gateway     = $paypal->gateway();
		$admin_email = $gateway->receiver_email;

		$args = [
			'actionType'         => 'PAY',
			'currencyCode'       => get_woocommerce_currency(),
			'trackingId'         => $gateway->invoice_prefix . $order->get_id(),
			'returnUrl'          => str_replace( '&amp;', '&', $paypal->get_return_url( $order ) ),
			'cancelUrl'          => str_replace( '&amp;', '&', $order->get_cancel_order_url() ),
			'ipnNotificationUrl' => $paypal->get_notify_url(),
			'requestEnvelope'    => [
				'errorLanguage' => 'en_US',
				'detailLevel'   => 'ReturnAll',
			],
			'receiverList'       => [
				'receiver' => [],
			],
		];

		// Parse WC_Order and setup receivers.
		$receivers = $this->get_receivers_from_order( $order );

		// Setup initial receiver to receive all funds.
		$receiver_data = [
			'email'   => $admin_email,
			'amount'  => number_format( $this->order->get_total() + $this->order->get_fees(), 2, '.', '' ),
			'primary' => 'true',
		];

		$args['receiverList']['receiver'][] = $receiver_data;

		// Split payments to multiple receivers.
		if ( tribe( 'community-tickets.payouts' )->is_split_payments_enabled() ) {
			foreach ( $receivers as $receiver ) {
				// Skip receiver processing if it's the same as the primary receiver e-mail.
				if ( $admin_email === $receiver['key'] ) {
					continue;
				}

				$receiver_data = [
					'email'   => $receiver['key'],
					'amount'  => number_format( $receiver['amount'], 2, '.', '' ),
					'primary' => 'false',
				];

				$args['receiverList']['receiver'][] = $receiver_data;
			}
		}

		/**
		 * Filters the arguments sent during a PayPal Adaptive Payment PayRequest.
		 * See: https://developer.paypal.com/docs/classic/api/adaptive-payments/Pay_API_Operation/
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array    $args  Array of arguments for a PayPal Adaptive Payment
		 * @param WC_Order $order WooCommerce Order object
		 */
		$args = apply_filters( 'tribe_community_tickets_paypal_payment_args', $args, $order );

		return $args;
	}
}
