<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( class_exists( 'Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal' ) ) {
	return;
}

class Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal extends WC_Payment_Gateway {
	public $id = 'tribe-paypal-adaptive-payments';
	public $icon;
	protected $debug = false;
	protected $gateway;

	// API URLs.
	protected $notify_url;

	/**
	 * constructor!
	 */
	public function __construct() {
		global $woocommerce;

		$community_tickets = tribe( 'community-tickets.main' );

		$this->icon = apply_filters( 'tribe_community_tickets_paypal_icon', $community_tickets->plugin_url . 'src/resources/images/paypal.png' );

		$this->notify_url = WC()->api_request_url( 'Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal' );
		$this->method_title = __( 'PayPal Split Payments', 'tribe-events-community' );
		$settings_url = admin_url( 'edit.php?page=tribe-common&tab=community&post_type=tribe_events#tribe-field-enable_split_payments' );
		$this->method_description = sprintf(
			__( 'Split the distribution of money between the site owner and event organizer at the time of ticket purchase with Community Tickets. Settings are available in %1$sEvents > Settings > Community%2$s', 'tribe-events-community' ),
			'<a href="' . esc_url( $settings_url ) . '">',
			'</a>' );
		$this->order_button_text = __( 'Proceed to PayPal', 'tribe-events-community' );

		add_action( 'woocommere_paypal_adaptive_payments_ipn', [ $this, 'process_ipn' ] );
		add_action( 'woocommerce_api_tribe__events__community__tickets__adapter__woocommerce_paypal', [ $this, 'check_ipn_response' ] );

		/**
		 * Filters the current debug setting for WooCommerce. Defaults debugging to false.
		 *
		 * @param boolean $debug Current debug state for the WooCommerce_PayPal adapter
		 * @return boolean
		 */
		$this->debug = apply_filters( 'tribe_community_tickets_woocommerce_paypal_debug', $this->debug );

		// Active logs.
		if ( $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}
	}//end __construct

	/**
	 * Returns a title to show on the WooCommerce settings page
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->method_title;
	}

	/**
	 * Returns notify URL for gateway.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return string
	 */
	public function get_notify_url() {
		return $this->notify_url;
	}

	/**
	 * Get the gateway used by this commerce adapter.
	 *
	 * @return Tribe__Events__Community__Tickets__Gateway__PayPal|WP_Error Gateway object or error object if class
	 *                                                                       does not exist.
	 */
	public function gateway() {
		return tribe( 'community-tickets.main' )->gateway( 'PayPal' );
	}//end gateway

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		$supported_currencies = [
			'AUD',
			'BRL',
			'CAD',
			'CHF',
			'CZK',
			'DKK',
			'EUR',
			'GBP',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MXN',
			'MYR',
			'NOK',
			'NZD',
			'PHP',
			'PLN',
			'SEK',
			'SGD',
			'THB',
			'TRY',
			'TWD',
			'USD',
		];

		/**
		 * Allow plugins to filter the list of supported currencies.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param array $supported_currencies List of supported currencies.
		 */
		$supported_currencies = apply_filters( 'tribe_community_tickets_supported_currencies', $supported_currencies );

		if ( ! in_array( get_woocommerce_currency(), $supported_currencies, true ) ) {
			return false;
		}

		return true;
	}//end using_supported_currency

	/**
	 * Returns whether or not all of the required fields have been entered
	 */
	public function is_available() {

		if (
			! $this->gateway()->is_available()
			|| ! $this->using_supported_currency()
		) {
			return false;
		}

		if ( $this->checking_out_with_no_tickets() ) {
			return false;
		}

		return true;
	}//end is_available

	/**
	 * Check if the cart currently being checked out contains any tickets.
	 *
	 * Split Payments only works with Products of the "tickets" type, so this
	 * method checks if that type of Product exists in the cart. If it doesn't,
	 * the Split Payments gateway can then be hidden from the user.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return bool
	 */
	public function checking_out_with_no_tickets() {

		if ( ! is_checkout() ) {
			return false;
		}

		$cart_items  = WC()->cart->get_cart();
		$has_tickets = false;

		if ( empty( $cart_items ) || ! is_array( $cart_items ) ) {
			return false;
		}

		// Thus far we've confirmed the cart is not empty. Now look for tickets.
		foreach ( $cart_items as $product ) {

			$is_ticket = tribe_events_product_is_ticket( $product['product_id'] );

			if ( $is_ticket ) {
				$has_tickets = true;
			}
		}

		return ! $has_tickets ? true : false;
	}

	/**
	 * Get the payment key.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string PayPal payment key.
	 */
	protected function get_payment_key( $order ) {
		/** @var \Tribe\Community\Tickets\Payouts\PayPal\Adaptive_Payments $adaptive_payments */
		$adaptive_payments = tribe( 'community-tickets.payouts.paypal.adaptive-payments' );

		$data = $adaptive_payments->generate_payment_args( $this, $order );

		// Sets the post params.
		$params = [
			'body'        => json_encode( $data ),
			'sslverify'   => false,
			'timeout'     => 60,
			'headers'     => $this->gateway()->get_headers(),
			'httpversion' => '1.1',
		];

		$url = $this->gateway()->get_api_url();

		if ( $this->debug ) {
			$this->log->add( $this->id, 'Requesting payment key for order ' . $order->get_order_number() . ' with the following data: ' . print_r( $data, true ) );
		}

		$response = wp_remote_post( $url . 'Pay', $params );

		if ( is_wp_error( $response ) ) {
			if ( $this->debug ) {
				$this->log->add( $this->id, 'WP_Error in generate payment key: ' . $response->get_error_message() );
			}
		} elseif ( 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
			$body = json_decode( $response['body'], true );

			if ( isset( $body['payKey'] ) ) {
				$pay_key = esc_attr( $body['payKey'] );

				if ( $this->debug ) {
					$this->log->add( $this->id, 'Payment key successfully created! The key is: ' . $pay_key );
				}

				// Just set the payment options.
				$this->set_payment_options( $pay_key );

				return esc_attr( $pay_key );
			}

			if ( isset( $body['error'] ) ) {
				if ( $this->debug ) {
					$this->log->add( $this->id, 'Failed to generate the payment key: ' . print_r( $body, true ) );
				}
			}
		} else {
			if ( $this->debug ) {
				$this->log->add( $this->id, 'Error in generate payment key: ' . print_r( $response, true ) );
			}
		}

		return '';
	}

	/**
	 * Set PayPal payment options.
	 *
	 * @param string $pay_key
	 */
	protected function set_payment_options( $pay_key ) {

		$data = [
			'payKey'          => $pay_key,
			'requestEnvelope' => [
				'errorLanguage' => 'en_US',
				'detailLevel'   => 'ReturnAll',
			],
			'displayOptions'  => [
				'businessName' => trim( substr( get_option( 'blogname' ), 0, 128 ) ),
			],
			'senderOptions'   => [
				'referrerCode' => 'WooThemes_Cart',
			],
		];

		// Sets the post params.
		$params = [
			'body'      => json_encode( $data ),
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => $this->gateway()->get_headers(),
		];

		$url = $this->gateway()->get_api_url();

		if ( $this->debug ) {
			$this->log->add( $this->id, 'Setting payment options with the following data: ' . print_r( $data, true ) );
		}

		$response = wp_remote_post( $url . 'SetPaymentOptions', $params );
		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
			if ( $this->debug ) {
				$this->log->add( $this->id, 'Payment options configured successfully!' );
			}
		} else {
			if ( $this->debug ) {
				$this->log->add( $this->id, 'Failed to configure payment options: ' . print_r( $response, true ) );
			}
		}
	}//end set_payment_options

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order       = new WC_Order( $order_id );
		$payment_key = $this->get_payment_key( $order );

		if ( $payment_key ) {
			$url = $this->gateway()->get_payment_url();

			return [
				'result'   => 'success',
				'redirect' => add_query_arg( [ 'cmd' => '_ap-payment', 'paykey' => $payment_key ], $url ),
			];
		} else {
			wc_add_notice( __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'tribe-events-community' ), 'error' );

			return [
				'result'   => 'fail',
				'redirect' => '',
			];
		}
	}//end process_payment

	/**
	 * Check for PayPal IPN Response
	 *
	 * @return void
	 */
	public function check_ipn_response() {
		@ob_clean();

		$ipn_response = ! empty( $_POST ) ? $_POST : false;

		if ( $ipn_response ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'woocommere_paypal_adaptive_payments_ipn', $ipn_response );
		} else {
			wp_die( 'PayPal IPN Request Failure', 'PayPal IPN', [ 'response' => 200 ] );
		}
	}

	/**
	 * Process the IPN.
	 *
	 * @param  array $posted PayPal IPN POST data.
	 *
	 * @return void
	 */
	public function process_ipn( $posted ) {
		$posted = stripslashes_deep( $posted );

		if ( ! isset( $posted['tracking_id'] ) ) {
			exit;
		}

		// Extract the order ID.
		$order_id = intval( str_replace( $this->gateway()->invoice_prefix, '', $posted['tracking_id'] ) );

		if ( $this->debug ) {
			$this->log->add( $this->id, 'Checking IPN response for order #' . $order_id . '...' );
		}

		// Get the order data.
		$order = new WC_Order( $order_id );

		// Checks whether the invoice number matches the order.
		// If true processes the payment.
		if ( $order->id === $order_id ) {
			$status = esc_attr( $posted['status'] );

			if ( $this->debug ) {
				$this->log->add( $this->id, 'Payment status: ' . $status );
			}

			switch ( $status ) {
				case 'CANCELED' :
					$order->update_status( 'cancelled', __( 'Payment canceled via IPN.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'CREATED' :
					$order->update_status( 'on-hold', __( 'The payment request was received. Funds will be transferred once the payment is approved.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'COMPLETED' :
					// Check order not already completed.
					if ( $order->status == 'completed' ) {
						if ( $this->debug ) {
							$this->log->add( $this->id, 'Aborting, Order #' . $order->id . ' is already complete.' );
						}
						exit;
					}

					if ( ! empty( $posted['sender_email'] ) ) {
						update_post_meta( $order->id, 'Payer PayPal address', sanitize_text_field( $posted['sender_email'] ) );
					}

					$order->add_order_note( __( 'The payment was successful.', 'woocommerce-gateway-paypal-adaptive-payments' ) );
					$order->payment_complete();

					break;
				case 'INCOMPLETE' :
					$order->update_status( 'on-hold', __( 'Some transfers succeeded and some failed for a parallel payment or, for a delayed chained payment, secondary receivers have not been paid.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'ERROR' :
					$order->update_status( 'failed', __( 'The payment failed and all attempted transfers failed or all completed transfers were successfully reversed.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'REVERSALERROR' :
					$order->update_status( 'failed', __( 'One or more transfers failed when attempting to reverse a payment.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'PROCESSING' :
					$order->update_status( 'on-hold', __( 'The payment is in progress.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;
				case 'PENDING' :
					$order->update_status( 'pending', __( 'The payment is awaiting processing.', 'woocommerce-gateway-paypal-adaptive-payments' ) );

					break;

				default :
					// No action.
					break;
			}
		} else {
			if ( $this->debug ) {
				$this->log->add( $this->id, 'Invalid IPN response for order #' . $order_id . '!' );
			}
		}
	}

}//end class
