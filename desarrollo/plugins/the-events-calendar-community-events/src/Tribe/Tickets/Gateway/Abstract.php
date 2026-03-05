<?php

abstract class Tribe__Events__Community__Tickets__Gateway__Abstract {
	public $id;
	public $fee_percentage;
	public $fee_flat;
	public $site_fee_type;
	public $site_fee_percentage;
	public $site_fee_flat;
	public $site_fee_on_free;

	/**
	 * Returns whether or not all of the required fields have been entered
	 */
	abstract public function is_available();

	/**
	 * singleton to instantiate the Tickets class
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * constructor!
	 */
	public function __construct() {
		$community_tickets = tribe( 'community-tickets.main' );
		$settings = get_option( Tribe__Events__Community__Tickets__Main::OPTIONNAME, $community_tickets->option_defaults );

		foreach ( $settings as $key => $value ) {
			if ( false === strpos( $key, 'site_fee' ) ) {
				continue;
			}

			$this->$key = $value;
		}
	}

	/**
	 * Computes the price of a ticket based on the gateway's percentage, site percentage, etc.
	 *
	 * @param int     $price                    Price of the actual ticket.
	 * @param boolean $calculate_percentage_fee Calculate percentage fee.
	 *
	 * @return float
	 */
	public function ticket_price( $price, $calculate_percentage_fee = false ) {
		$percentage = 0;

		if ( $calculate_percentage_fee ) {
			$percentage = $this->fee_percentage();
		}

		$total = round( $price / 100 * ( 100 - $percentage ), 2 );

		return $total;
	}

	/**
	 * returns whether or not the site has a flat fee enabled
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 * utilize tribe( 'community-tickets.fees' )
	 *
	 * @return boolean
	 */
	public function has_site_fee_flat() {
		return tribe( 'community-tickets.fees' )->is_flat_fee( $this->site_fee_type );
	}

	/**
	 * returns whether or not the site has a percentage fee enabled
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 * utilize tribe( 'community-tickets.fees' )
	 *
	 * @return boolean
	 */
	public function has_site_fee_percentage() {
		return tribe( 'community-tickets.fees' )->is_percentage_fee( $this->site_fee_type );
	}

	/**
	 * returns the flat fee for tickets. Combines the gateway flat fee with the site flat fee if
	 * configured with one.
	 *
	 * @return float
	 */
	public function fee_flat() {
		$flat_fee = (float) $this->fee_flat;

		if ( $this->has_site_fee_flat() ) {
			$flat_fee += (float) $this->site_fee_flat;
		}

		return $flat_fee;
	}

	/**
	 * Return the protected value
	 *
	 * @return boolean
	 */
	public function site_fee_on_free() {
		return $this->site_fee_on_free;
	}

	/**
	 * returns the fee percentage for tickets. Combines the gateway percentage with the site
	 * percentage if configured with one.
	 *
	 * @return float
	 */
	public function fee_percentage() {
		$percentage = $this->fee_percentage;

		if ( $this->has_site_fee_percentage() ) {
			$percentage += (float) $this->site_fee_percentage;
		}

		return $percentage;
	}
}
