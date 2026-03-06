<?php

class Tribe__Events__Community__Tickets__Payment_Options_Form {
	public static $meta_key = 'tribe_events_community_tickets';
	public static $defaults = [
		'paypal_account_email' => null,
		'payment_fee_setting' => null,
	];

	/**
	 * Sets a default settings value
	 *
	 * @param string $setting Payment Options form setting
	 * @param mixed $value Value for the Payment Options form setting
	 */
	public function set_default( $setting, $value ) {
		self::$defaults[ $setting ] = $value;
	}

	/**
	 * Fetches payment options meta for a specific user ID.
	 *
	 * @param null|int $user_id User ID.
	 *
	 * @return array List of options for user.
	 */
	public static function get_meta( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$data = get_user_meta( $user_id, self::$meta_key, true );
		$data = wp_parse_args( $data, self::$defaults );

		return $data;
	}//end get_meta

	/**
	 * Fetches payment options meta for a specific user ID.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param null|int $user_id User ID.
	 *
	 * @return array List of options for user.
	 */
	public function get_meta_options( $user_id = null ) {
		return self::get_meta( $user_id );
	}

	/**
	 * Handles the submission of the payment options form
	 *
	 * @param $user_id int User ID of the user to save data to
	 * @param $data array Array of posted data
	 */
	public function save( $user_id, $data ) {
		// Get existing data.
		$original_data = $this->get_meta_options( $user_id );

		// Ensure we don't remove things unintentionally (if not saved).
		$data = array_merge( $original_data, $data );

		// make sure we only have keys that we care about
		$data = array_intersect_key( $data, self::$defaults );

		// make sure we have ALL the keys we want
		$data = wp_parse_args( $data, self::$defaults );

		if ( $data['paypal_account_email'] ) {
			$data['paypal_account_email'] = sanitize_email( $data['paypal_account_email'] );
		}//end if

		if ( ! in_array( $data['payment_fee_setting'], [ 'pass', 'absorb' ], true ) ) {
			// Don't save the setting.
			unset( $data['payment_fee_setting'] );
		}

		return update_user_meta( $user_id, self::$meta_key, $data );
	}//end save

	/**
	 * Renders the payment options UI.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function render() {
		$community_main = Tribe__Events__Community__Main::instance();

		// Added @since 4.10.17 as the payments option page does not trigger isEditPage so styles are not enqueued
		$community_main->isEditPage = true;
		$community_main->maybeLoadAssets( true );

		tribe_asset_enqueue( $community_main->get_community_events_post_type() . '-community-styles' );
		tribe_asset_enqueue( 'events-community-tickets-css' );

		tribe( Tribe__Events__Community__Templates::class )->tribe_get_template_part( 'community-tickets/modules/payment-options', null, [
			'data' => self::get_meta( get_current_user_id() ),
		] );
	}//end render
}//end class
