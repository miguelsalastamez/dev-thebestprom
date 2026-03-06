<?php

class Tribe__Events__Community__Tickets__Gateway__PayPal extends Tribe__Events__Community__Tickets__Gateway__Abstract {
	public $id = 'paypal';
	public $fee_percentage = 0;
	public $fee_flat = 0;
	public $invoice_prefix;
	public $receiver_email;
	protected $sandbox;
	protected $api_username;
	protected $api_password;
	protected $api_signature;
	protected $application_id;

	protected $api_prod_url        = 'https://svcs.paypal.com/AdaptivePayments/';
	protected $api_sandbox_url     = 'https://svcs.sandbox.paypal.com/AdaptivePayments/';
	protected $payment_prod_url    = 'https://www.paypal.com/cgi-bin/webscr';
	protected $payment_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1';

	public function __construct() {
		parent::__construct();

		$community_tickets = tribe( 'community-tickets.main' );
		$settings = get_option( Tribe__Events__Community__Tickets__Main::OPTIONNAME, $community_tickets->option_defaults );

		foreach ( $settings as $key => $value ) {
			if ( false === strpos( $key, 'paypal' ) ) {
				continue;
			}

			$key = str_replace( 'paypal_', '', $key );

			$this->$key = $value;
		}//end foreach

		// if sandbox is enabled, use the static sandbox application id
		if ( $this->sandbox ) {
			$this->application_id = 'APP-80W284485P519543T';
		}
	}//end __construct

	/**
	 * Returns whether or not all of the required fields have been entered
	 */
	public function is_available() {
		$gateway = tribe( 'community-tickets.main' )->gateway( 'PayPal' );

		if (
			empty( $gateway->api_username )
			|| empty( $gateway->api_password )
			|| empty( $gateway->api_signature )
			|| empty( $gateway->application_id )
			|| empty( $gateway->invoice_prefix )
			|| empty( $gateway->receiver_email )
		) {
			return false;
		}

		return true;
	}//end is_available

	/**
	 * returns the request headers for JSON requests
	 */
	public function get_headers() {
		return [
			'X-PAYPAL-SECURITY-USERID'      => $this->api_username,
			'X-PAYPAL-SECURITY-PASSWORD'    => $this->api_password,
			'X-PAYPAL-SECURITY-SIGNATURE'   => $this->api_signature,
			'X-PAYPAL-REQUEST-DATA-FORMAT'  => 'JSON',
			'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'JSON',
			'X-PAYPAL-APPLICATION-ID'       => $this->application_id,
		];
	}//end get_headers

	/**
	 * returns the appropriate API url based on whether or not sandbox is enabled
	 */
	public function get_api_url() {
		if ( $this->sandbox ) {
			return $this->api_sandbox_url;
		}

		return $this->api_prod_url;
	}//end get_api_url

	/**
	 * returns the appropriate Payment url based on whether or not sandbox is enabled
	 */
	public function get_payment_url() {
		if ( $this->sandbox ) {
			return $this->payment_sandbox_url;
		}

		return $this->payment_prod_url;
	}//end get_payment_url
}//end class
